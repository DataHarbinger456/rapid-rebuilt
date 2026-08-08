<?php

namespace Tests\Feature;

use App\Models\DocumentExtraction;
use App\Models\DocumentExtractionField;
use App\Models\UploadedFile;
use App\Models\User;
use App\Models\UserCase;
use App\Services\DocumentExtractionService;
use App\Services\Extraction\SalvageTitleExtractor;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\StructuredResponseFake;
use Prism\Prism\ValueObjects\Usage;
use RuntimeException;
use Tests\TestCase;
use Throwable;

/**
 * Orchestration, not transcription: these tests care about what lands in the
 * database, including what happens when the provider fails.
 *
 * QUEUE_CONNECTION is sync in tests, so dispatching the job runs it inline.
 * Every provider call is faked; nothing here touches the network.
 */
class DocumentExtractionServiceTest extends TestCase
{
    use DatabaseTransactions;

    private const STORED_PATH_PREFIX = 'cases/extraction-test/';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');
    }

    private function makeCase(): UserCase
    {
        $customer = User::factory()->create();

        return UserCase::create([
            'user_id' => $customer->id,
            'case_number' => 'RR-TEST-' . uniqid(),
            'status' => 'pending_documents',
            'service_type' => 'rebuilt_title',
            'inspection_fee' => 150.00,
            'service_fee' => 100.00,
            'total_amount' => 250.00,
        ]);
    }

    private function makeDocument(UserCase $case, string $type = 'salvage_title'): UploadedFile
    {
        $path = self::STORED_PATH_PREFIX . uniqid() . '.png';

        Storage::disk('private')->put(
            $path,
            file_get_contents(base_path('tests/Fixtures/salvage-title.png'))
        );

        return UploadedFile::create([
            'user_case_id' => $case->id,
            'user_id' => $case->user_id,
            'uploaded_by' => $case->user_id,
            'document_type' => $type,
            'file_path' => $path,
            'path' => $path,
            'original_name' => 'title.png',
            'mime_type' => 'image/png',
            'file_size' => 8208,
            'size' => 8208,
            'status' => 'pending',
        ]);
    }

    private function fakeReading(array $overrides = []): array
    {
        return array_merge([
            'vin' => '1HGCV1F30LA123456',
            'title_number' => '0123456789',
            'title_state' => 'FL',
            'title_brand' => 'salvage',
            'year' => '2020',
            'make' => 'HONDA',
            'model' => 'ACCORD',
            'body_type' => '4D SEDAN',
            'color' => 'BLUE',
            'weight' => '3400',
            'odometer_reading' => '128450',
            'odometer_date' => '2025-11-04',
            'odometer_status' => 'actual',
            'previous_state' => null,
            'license_plate' => 'ABC1234',
            'owner_name_on_title' => 'SAMPLE TESTCASE',
        ], $overrides);
    }

    public function test_it_records_the_extraction_and_one_row_per_field(): void
    {
        Prism::fake([
            StructuredResponseFake::make()
                ->withStructured($this->fakeReading())
                ->withUsage(new Usage(1200, 180)),
        ]);

        $case = $this->makeCase();
        $document = $this->makeDocument($case);

        $extraction = app(DocumentExtractionService::class)->extract($document)->fresh();

        $this->assertSame(DocumentExtraction::STATUS_COMPLETED, $extraction->status);
        $this->assertSame($case->id, $extraction->user_case_id);
        $this->assertSame('salvage_title', $extraction->document_type);
        $this->assertSame(config('extraction.default'), $extraction->provider);
        $this->assertSame(config('extraction.model'), $extraction->model);
        $this->assertSame(1200, $extraction->prompt_tokens);
        $this->assertSame(180, $extraction->completion_tokens);
        $this->assertNotNull($extraction->completed_at);

        // Every schema field gets a row, including the ones the model could not
        // find: the reviewer must still decide on them.
        $this->assertCount(16, $extraction->fields);
        $this->assertSame(
            '1HGCV1F30LA123456',
            $extraction->fields->firstWhere('field_key', 'vin')->ai_value
        );
        $this->assertNull($extraction->fields->firstWhere('field_key', 'previous_state')->ai_value);
        $this->assertTrue(
            $extraction->fields->every(fn ($f): bool => $f->status === DocumentExtractionField::STATUS_PENDING),
            'Nothing may be pre-confirmed on the machine\'s behalf.'
        );

        $this->assertDatabaseHas('case_timeline', [
            'user_case_id' => $case->id,
            'event_type' => 'extraction_completed',
        ]);
    }

    public function test_the_raw_payload_is_not_readable_in_the_database(): void
    {
        Prism::fake([StructuredResponseFake::make()->withStructured($this->fakeReading())]);

        $case = $this->makeCase();
        $extraction = app(DocumentExtractionService::class)->extract($this->makeDocument($case));

        $stored = \Illuminate\Support\Facades\DB::table('document_extractions')
            ->where('id', $extraction->id)
            ->value('raw_payload');

        // A salvage title carries the owner's name and address.
        $this->assertStringNotContainsString('SAMPLE TESTCASE', $stored);
        $this->assertSame('SAMPLE TESTCASE', $extraction->fresh()->raw_payload['owner_name_on_title']);
    }

    public function test_rerunning_supersedes_the_previous_extraction(): void
    {
        Prism::fake([
            StructuredResponseFake::make()->withStructured($this->fakeReading()),
            StructuredResponseFake::make()->withStructured($this->fakeReading(['title_state' => 'GA'])),
        ]);

        $case = $this->makeCase();
        $document = $this->makeDocument($case);
        $service = app(DocumentExtractionService::class);

        $first = $service->extract($document);
        $second = $service->extract($document);

        $this->assertSame(DocumentExtraction::STATUS_SUPERSEDED, $first->fresh()->status);
        $this->assertSame(DocumentExtraction::STATUS_COMPLETED, $second->fresh()->status);

        // The superseded attempt keeps its field rows: what a model read is
        // history, not scratch space.
        $this->assertCount(16, $first->fresh()->fields);
        $this->assertSame(
            'GA',
            $second->fresh()->fields->firstWhere('field_key', 'title_state')->ai_value
        );
    }

    public function test_a_provider_failure_is_recorded_without_partial_fields(): void
    {
        // Any provider-side error takes this path: a refused key, a timeout,
        // a malformed response.
        $this->mock(SalvageTitleExtractor::class, function ($mock): void {
            $mock->shouldReceive('documentType')->andReturn('salvage_title');
            $mock->shouldReceive('extract')->andThrow(new RuntimeException('Provider is unavailable'));
        });

        $case = $this->makeCase();
        $document = $this->makeDocument($case);

        try {
            app(DocumentExtractionService::class)->extract($document);
        } catch (Throwable $e) {
            // The sync queue rethrows after the job records the failure.
            $this->assertSame('Provider is unavailable', $e->getMessage());
        }

        $extraction = DocumentExtraction::where('uploaded_file_id', $document->id)->firstOrFail();

        $this->assertSame(DocumentExtraction::STATUS_FAILED, $extraction->status);
        $this->assertSame('Provider is unavailable', $extraction->error_message);
        $this->assertCount(0, $extraction->fields, 'A failure must not look like an empty document.');

        $this->assertDatabaseHas('case_timeline', [
            'user_case_id' => $case->id,
            'event_type' => 'extraction_failed',
        ]);
    }

    public function test_it_refuses_a_document_type_with_no_extractor(): void
    {
        $case = $this->makeCase();
        $document = $this->makeDocument($case, 'insurance_card');

        $service = app(DocumentExtractionService::class);

        $this->assertFalse($service->supports('insurance_card'));

        $this->expectException(\InvalidArgumentException::class);
        $service->extract($document);
    }
}
