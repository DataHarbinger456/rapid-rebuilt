<?php

namespace Tests\Feature;

use App\Models\DocumentExtraction;
use App\Models\DocumentExtractionField;
use App\Models\UploadedFile;
use App\Models\User;
use App\Models\UserCase;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\StructuredResponseFake;
use Tests\TestCase;

/**
 * The confirmation screen is the gate between a model's reading and a state
 * form. These tests cover who may open it and what it refuses to apply.
 */
class ExtractionReviewControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');
    }

    private function makeCase(): UserCase
    {
        $customer = User::factory()->create(['user_type' => 'user']);

        $case = UserCase::create([
            'user_id' => $customer->id,
            'case_number' => 'RR-TEST-' . uniqid(),
            'status' => 'pending_documents',
            'service_type' => 'rebuilt_title',
            'inspection_fee' => 150.00,
            'service_fee' => 100.00,
            'total_amount' => 250.00,
        ]);

        Vehicle::create([
            'user_case_id' => $case->id,
            'vin' => '1HGCV1F30LA123456',
            'title_state' => 'FL',
            'year' => 2020,
            'make' => 'HONDA',
            'model' => 'ACCORD',
        ]);

        return $case;
    }

    private function makeDocument(UserCase $case): UploadedFile
    {
        $path = 'cases/' . $case->id . '/documents/salvage_title/title.png';

        Storage::disk('private')->put(
            $path,
            file_get_contents(base_path('tests/Fixtures/salvage-title.png'))
        );

        return UploadedFile::create([
            'user_case_id' => $case->id,
            'user_id' => $case->user_id,
            'uploaded_by' => $case->user_id,
            'document_type' => 'salvage_title',
            'file_path' => $path,
            'path' => $path,
            'original_name' => 'title.png',
            'mime_type' => 'image/png',
            'file_size' => 8208,
            'size' => 8208,
            'status' => 'pending',
        ]);
    }

    private function makeExtraction(UserCase $case, array $aiValues): DocumentExtraction
    {
        $extraction = DocumentExtraction::create([
            'uploaded_file_id' => $this->makeDocument($case)->id,
            'user_case_id' => $case->id,
            'document_type' => 'salvage_title',
            'status' => DocumentExtraction::STATUS_COMPLETED,
            'provider' => 'anthropic',
            'model' => 'claude-sonnet-4-5',
        ]);

        foreach ($aiValues as $key => $value) {
            DocumentExtractionField::create([
                'document_extraction_id' => $extraction->id,
                'field_key' => $key,
                'ai_value' => $value,
                'status' => DocumentExtractionField::STATUS_PENDING,
            ]);
        }

        return $extraction->load('fields');
    }

    private function staff(): User
    {
        return User::factory()->create(['user_type' => 'staff']);
    }

    public function test_staff_can_open_the_review_screen(): void
    {
        $case = $this->makeCase();
        $extraction = $this->makeExtraction($case, [
            'vin' => '1HGCV1F30LA123456',
            'title_state' => 'GA',
        ]);

        $response = $this->actingAs($this->staff())
            ->get(route('admin.extractions.show', [$case->id, $extraction->id]));

        $response->assertOk();
        $response->assertViewIs('admin.cases.extraction-review');
        $response->assertSee('Vehicle Identification Number');
        // The intake said FL and the title says GA: that decides Original vs
        // Transfer on the 82040, so the screen must surface it.
        $response->assertSee('Customer entered: FL');
        $response->assertViewHas('conflicts', fn (array $c): bool => ($c['title_state'] ?? null) === 'FL');
    }

    public function test_a_customer_cannot_open_the_review_screen(): void
    {
        $case = $this->makeCase();
        $extraction = $this->makeExtraction($case, ['vin' => '1HGCV1F30LA123456']);

        $response = $this->actingAs(User::factory()->create(['user_type' => 'user']))
            ->get(route('admin.extractions.show', [$case->id, $extraction->id]));

        $response->assertForbidden();
    }

    public function test_an_extraction_from_another_case_is_not_found(): void
    {
        $case = $this->makeCase();
        $other = $this->makeCase();
        $extraction = $this->makeExtraction($other, ['vin' => '1HGCV1F30LA123456']);

        $response = $this->actingAs($this->staff())
            ->get(route('admin.extractions.show', [$case->id, $extraction->id]));

        $response->assertNotFound();
    }

    public function test_confirming_every_field_writes_the_values_to_the_vehicle(): void
    {
        $case = $this->makeCase();
        $extraction = $this->makeExtraction($case, [
            'vin' => '1HGCV1F30LA123456',
            'title_state' => 'GA',
            'color' => 'BLUF',
        ]);

        $response = $this->actingAs($this->staff())
            ->post(route('admin.extractions.confirm', [$case->id, $extraction->id]), [
                'fields' => [
                    'vin' => ['status' => 'confirmed', 'value' => '1HGCV1F30LA123456'],
                    'title_state' => ['status' => 'confirmed', 'value' => 'GA'],
                    'color' => ['status' => 'rejected', 'value' => 'BLUF'],
                ],
            ]);

        $response->assertRedirect(route('admin.cases.show', $case->id));
        $response->assertSessionHas('success');

        $vehicle = $case->vehicle()->first();
        $this->assertSame('GA', $vehicle->title_state);
        $this->assertNull($vehicle->color);

        $this->assertDatabaseHas('document_extraction_fields', [
            'document_extraction_id' => $extraction->id,
            'field_key' => 'color',
            'status' => 'rejected',
            'confirmed_value' => null,
        ]);
    }

    public function test_an_edited_value_is_recorded_as_a_correction(): void
    {
        $case = $this->makeCase();
        $extraction = $this->makeExtraction($case, ['title_state' => 'FI']);

        $this->actingAs($this->staff())
            ->post(route('admin.extractions.confirm', [$case->id, $extraction->id]), [
                'fields' => [
                    // Reviewer typed a new value but left "confirm" selected.
                    'title_state' => ['status' => 'confirmed', 'value' => 'FL'],
                ],
            ]);

        $this->assertDatabaseHas('document_extraction_fields', [
            'document_extraction_id' => $extraction->id,
            'field_key' => 'title_state',
            'ai_value' => 'FI',
            'confirmed_value' => 'FL',
            'status' => 'corrected',
        ]);
    }

    public function test_leaving_a_field_pending_blocks_the_apply(): void
    {
        $case = $this->makeCase();
        $extraction = $this->makeExtraction($case, [
            'vin' => '1HGCV1F30LA123456',
            'title_state' => 'GA',
        ]);

        $response = $this->actingAs($this->staff())
            ->post(route('admin.extractions.confirm', [$case->id, $extraction->id]), [
                'fields' => [
                    'vin' => ['status' => 'confirmed', 'value' => '1HGCV1F30LA123456'],
                ],
            ]);

        $response->assertSessionHas('error');
        $this->assertSame('FL', $case->vehicle()->first()->title_state);
    }

    public function test_a_superseded_extraction_can_no_longer_be_confirmed(): void
    {
        $case = $this->makeCase();
        $extraction = $this->makeExtraction($case, ['title_state' => 'GA']);

        // The document was read again; this attempt is now history. Confirming
        // it from a stale tab would push the older reading onto the vehicle.
        $extraction->update(['status' => DocumentExtraction::STATUS_SUPERSEDED]);

        $response = $this->actingAs($this->staff())
            ->post(route('admin.extractions.confirm', [$case->id, $extraction->id]), [
                'fields' => [
                    'title_state' => ['status' => 'confirmed', 'value' => 'GA'],
                ],
            ]);

        $response->assertSessionHas('error');
        $this->assertSame('FL', $case->vehicle()->first()->title_state);

        // Not even the decision was recorded against the stale attempt.
        $this->assertDatabaseHas('document_extraction_fields', [
            'document_extraction_id' => $extraction->id,
            'field_key' => 'title_state',
            'status' => 'pending',
        ]);
    }

    public function test_an_unknown_field_status_is_rejected(): void
    {
        $case = $this->makeCase();
        $extraction = $this->makeExtraction($case, ['vin' => '1HGCV1F30LA123456']);

        $response = $this->actingAs($this->staff())
            ->post(route('admin.extractions.confirm', [$case->id, $extraction->id]), [
                'fields' => [
                    'vin' => ['status' => 'pending', 'value' => '1HGCV1F30LA123456'],
                ],
            ]);

        $response->assertSessionHasErrors('fields.vin.status');
    }

    public function test_staff_can_start_an_extraction_from_the_case_page(): void
    {
        Prism::fake([
            StructuredResponseFake::make()->withStructured(['vin' => '1HGCV1F30LA123456']),
        ]);

        $case = $this->makeCase();
        $document = $this->makeDocument($case);

        $response = $this->actingAs($this->staff())
            ->post(route('admin.extractions.store', [$case->id, $document->id]));

        $extraction = DocumentExtraction::where('uploaded_file_id', $document->id)->firstOrFail();

        $response->assertRedirect(route('admin.extractions.show', [$case->id, $extraction->id]));
        $this->assertSame(DocumentExtraction::STATUS_COMPLETED, $extraction->status);
    }

    public function test_an_unsupported_document_type_is_refused(): void
    {
        $case = $this->makeCase();
        $document = $this->makeDocument($case);
        $document->update(['document_type' => 'insurance_card']);

        $response = $this->actingAs($this->staff())
            ->post(route('admin.extractions.store', [$case->id, $document->id]));

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('document_extractions', ['uploaded_file_id' => $document->id]);
    }
}
