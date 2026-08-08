<?php

namespace Tests\Feature;

use App\Models\UploadedFile;
use App\Services\Extraction\SalvageTitleExtractor;
use Illuminate\Support\Facades\Storage;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Structured\Request as StructuredRequest;
use Prism\Prism\Testing\StructuredResponseFake;
use Prism\Prism\ValueObjects\Media\Image;
use Prism\Prism\ValueObjects\Usage;
use Tests\TestCase;

/**
 * The extractor is the only class that talks to a provider, so these tests
 * pin down the contract in both directions: what is sent (schema, image,
 * temperature) and how a response becomes a DTO.
 *
 * Every test fakes the provider. None of them touch the network.
 */
class SalvageTitleExtractorTest extends TestCase
{
    private const STORED_PATH = 'cases/1/documents/salvage_title/title.png';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');
        Storage::disk('private')->put(
            self::STORED_PATH,
            file_get_contents(base_path('tests/Fixtures/salvage-title.png'))
        );
    }

    private function file(array $overrides = []): UploadedFile
    {
        // Unsaved on purpose: the extractor reads a file, it does not persist
        // anything, so this test needs no database.
        return new UploadedFile(array_merge([
            'id' => 1,
            'user_case_id' => 1,
            'document_type' => 'salvage_title',
            'file_path' => self::STORED_PATH,
            'original_name' => 'title.png',
            'mime_type' => 'image/png',
            'disk' => 'private',
        ], $overrides));
    }

    private function completeReading(): array
    {
        return [
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
        ];
    }

    public function test_it_maps_every_structured_field_into_the_result(): void
    {
        Prism::fake([
            StructuredResponseFake::make()
                ->withStructured($this->completeReading())
                ->withUsage(new Usage(1200, 180)),
        ]);

        $result = app(SalvageTitleExtractor::class)->extract($this->file());

        $this->assertSame('1HGCV1F30LA123456', $result->value('vin'));
        $this->assertSame('FL', $result->value('title_state'));
        $this->assertSame('128450', $result->value('odometer_reading'));
        $this->assertSame('SAMPLE TESTCASE', $result->value('owner_name_on_title'));
        $this->assertSame(1200, $result->promptTokens);
        $this->assertSame(180, $result->completionTokens);
        $this->assertSame(config('extraction.default'), $result->provider);
        $this->assertSame(config('extraction.model'), $result->model);
    }

    public function test_a_field_the_model_did_not_find_stays_null(): void
    {
        Prism::fake([
            StructuredResponseFake::make()->withStructured(
                array_merge($this->completeReading(), [
                    'weight' => null,
                    // A provider that answers with a blank string is making the
                    // same statement as one answering null.
                    'license_plate' => '   ',
                ])
            ),
        ]);

        $result = app(SalvageTitleExtractor::class)->extract($this->file());

        $this->assertNull($result->value('weight'));
        $this->assertNull($result->value('license_plate'));
        $this->assertNotNull($result->value('vin'));
    }

    public function test_a_field_missing_from_the_response_is_still_present_as_null(): void
    {
        $partial = $this->completeReading();
        unset($partial['odometer_date'], $partial['previous_state']);

        Prism::fake([StructuredResponseFake::make()->withStructured($partial)]);

        $result = app(SalvageTitleExtractor::class)->extract($this->file());

        $this->assertArrayHasKey('odometer_date', $result->fields);
        $this->assertNull($result->value('odometer_date'));
        $this->assertCount(16, $result->fields);
    }

    public function test_it_sends_the_document_image_and_a_deterministic_request(): void
    {
        $fake = Prism::fake([
            StructuredResponseFake::make()->withStructured($this->completeReading()),
        ]);

        app(SalvageTitleExtractor::class)->extract($this->file());

        $fake->assertCallCount(1);
        $fake->assertRequest(function (array $requests): void {
            /** @var StructuredRequest $request */
            $request = $requests[0];

            $this->assertSame(0, $request->temperature());
            $this->assertSame(config('extraction.max_tokens'), $request->maxTokens());

            $schema = $request->schema()->toArray();
            $this->assertArrayHasKey('vin', $schema['properties']);
            $this->assertSame('^[A-HJ-NPR-Z0-9]{17}$', $schema['properties']['vin']['pattern']);
            // Nullable so the model can say "not on this document" rather than
            // inventing a weight.
            $this->assertContains('null', (array) $schema['properties']['weight']['type']);

            $images = collect($request->messages())
                ->flatMap(fn ($message): array => $message->additionalContent ?? [])
                ->filter(fn ($content): bool => $content instanceof Image);

            $this->assertCount(1, $images, 'The document image must reach the provider.');
        });
    }

    public function test_a_pdf_upload_is_rasterised_to_an_image(): void
    {
        Storage::disk('private')->put(
            'cases/1/documents/salvage_title/title.pdf',
            $this->onePagePdf()
        );

        Prism::fake([StructuredResponseFake::make()->withStructured($this->completeReading())]);

        $result = app(SalvageTitleExtractor::class)->extract($this->file([
            'file_path' => 'cases/1/documents/salvage_title/title.pdf',
            'original_name' => 'title.pdf',
            'mime_type' => 'application/pdf',
        ]));

        $this->assertSame('1HGCV1F30LA123456', $result->value('vin'));
    }

    /**
     * A minimal one-page PDF, assembled here so the repo carries no binary
     * fixture it does not need. Small, but a real PDF: pdftoppm renders it.
     */
    private function onePagePdf(): string
    {
        $stream = 'BT /F1 24 Tf 72 700 Td (VIN 1HGCV1F30LA123456) Tj ET';

        $objects = [
            '1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj',
            '2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj',
            '3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 612 792]'
                . '/Resources<</Font<</F1 4 0 R>>>>/Contents 5 0 R>>endobj',
            '4 0 obj<</Type/Font/Subtype/Type1/BaseFont/Courier>>endobj',
            '5 0 obj<</Length ' . strlen($stream) . ">>stream\n" . $stream . "\nendstream endobj",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object . "\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";

        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf . 'trailer<</Size ' . (count($objects) + 1) . "/Root 1 0 R>>\nstartxref\n"
            . $xrefOffset . "\n%%EOF";
    }
}
