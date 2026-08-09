<?php

namespace Tests\Feature;

use App\Models\DocumentExtraction;
use App\Models\DocumentExtractionField;
use App\Models\UploadedFile;
use App\Models\User;
use App\Models\UserCase;
use App\Models\Vehicle;
use App\Services\Extraction\VinDecodeCheck;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
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

        // VINDecoderService caches decodes for 24h, so without this a result
        // faked in one test would leak into the next.
        Cache::flush();
    }

    /**
     * Shape of a real NHTSA decodevin response, trimmed to the variables the
     * decoder reads.
     *
     * Http::fake() merges stubs and the first match wins, so this must be the
     * only decoder stub a test registers — a default in setUp would silently
     * shadow it.
     */
    private function fakeDecode(?int $year, ?string $make, ?string $model): void
    {
        Http::fake([
            'vpic.nhtsa.dot.gov/*' => Http::response([
                'Results' => [
                    ['Variable' => 'Model Year', 'Value' => $year === null ? null : (string) $year],
                    ['Variable' => 'Make', 'Value' => $make],
                    ['Variable' => 'Model', 'Value' => $model],
                    ['Variable' => 'Error Code', 'Value' => '0'],
                ],
            ]),
        ]);
    }

    private function decoderUnreachable(): void
    {
        Http::fake(['vpic.nhtsa.dot.gov/*' => Http::response(null, 503)]);
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

        // This test is not about VIN decoding; keep the decoder out of it.
        $this->decoderUnreachable();

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

    public function test_an_invalid_vin_is_flagged_as_failing_its_checksum(): void
    {
        $case = $this->makeCase();
        // One character misread: a real failure from the live sample run.
        $extraction = $this->makeExtraction($case, ['vin' => '1FMSK8DH1LGB37874']);

        // This test is not about VIN decoding; keep the decoder out of it.
        $this->decoderUnreachable();

        $response = $this->actingAs($this->staff())
            ->get(route('admin.extractions.show', [$case->id, $extraction->id]));

        $response->assertOk();
        $response->assertSee('VIN failed checksum validation');
        $response->assertSee('character by character');
        $response->assertViewHas('vinWarning', fn (?string $w): bool => $w !== null);
    }

    public function test_a_valid_vin_is_not_flagged(): void
    {
        $case = $this->makeCase();
        // The same vehicle's real VIN, which passes its check digit.
        $extraction = $this->makeExtraction($case, ['vin' => '1FMSK8DH1LGB73874']);

        // This test is not about VIN decoding; keep the decoder out of it.
        $this->decoderUnreachable();

        $response = $this->actingAs($this->staff())
            ->get(route('admin.extractions.show', [$case->id, $extraction->id]));

        $response->assertOk();
        $response->assertDontSee('VIN failed checksum validation');
        // A closure, not a bare null: assertViewHas treats null as "key
        // exists" and would pass even if the VIN were flagged.
        $response->assertViewHas('vinWarning', fn (?string $w): bool => $w === null);
    }

    public function test_a_corrected_vin_clears_the_warning(): void
    {
        $case = $this->makeCase();
        $extraction = $this->makeExtraction($case, ['vin' => '1FMSK8DH1LGB37874']);

        // Staff checked the document and fixed the transposition. The warning
        // describes what is on screen, so it must not persist against the
        // superseded AI value.
        $extraction->fields()->where('field_key', 'vin')->update([
            'confirmed_value' => '1FMSK8DH1LGB73874',
            'status' => DocumentExtractionField::STATUS_CORRECTED,
        ]);

        // This test is not about VIN decoding; keep the decoder out of it.
        $this->decoderUnreachable();

        $this->actingAs($this->staff())
            ->get(route('admin.extractions.show', [$case->id, $extraction->id]))
            ->assertOk()
            ->assertDontSee('VIN failed checksum validation')
            ->assertViewHas('vinWarning', fn (?string $w): bool => $w === null);
    }

    public function test_a_vin_the_model_could_not_read_is_not_flagged(): void
    {
        $case = $this->makeCase();

        // Nothing found is already shown as "Not found on the document";
        // a checksum warning on top of that would be noise.
        $extraction = $this->makeExtraction($case, ['vin' => null]);

        $this->actingAs($this->staff())
            ->get(route('admin.extractions.show', [$case->id, $extraction->id]))
            ->assertOk()
            ->assertViewHas('vinWarning', fn (?string $w): bool => $w === null);
    }

    public function test_a_failing_checksum_does_not_block_confirmation(): void
    {
        $case = $this->makeCase();
        $extraction = $this->makeExtraction($case, ['vin' => '1FMSK8DH1LGB37874']);

        // Pre-1981 vehicles have no valid check digit, so staff must be able to
        // confirm a failing VIN once they have checked it against the document.
        $response = $this->actingAs($this->staff())
            ->post(route('admin.extractions.confirm', [$case->id, $extraction->id]), [
                'fields' => [
                    'vin' => ['status' => 'confirmed', 'value' => '1FMSK8DH1LGB37874'],
                ],
            ]);

        $response->assertRedirect(route('admin.cases.show', $case->id));
        $response->assertSessionHas('success');
        $this->assertSame('1FMSK8DH1LGB37874', $case->vehicle()->first()->vin);
    }

    public function test_a_vin_decoding_to_a_different_vehicle_is_flagged(): void
    {
        $case = $this->makeCase();
        // A real Ford Explorer VIN, but the model read the vehicle details off
        // an auction sticker for something else.
        $extraction = $this->makeExtraction($case, [
            'vin' => '1FMSK8DH1LGB73874',
            'year' => '2020',
            'make' => 'TOYT',
            'model' => 'CAMRY',
        ]);

        $this->fakeDecode(2020, 'FORD', 'Explorer');

        $response = $this->actingAs($this->staff())
            ->get(route('admin.extractions.show', [$case->id, $extraction->id]));

        $response->assertOk();
        $response->assertSee('The VIN decodes to a different vehicle.');
        $response->assertSee('2020 FORD Explorer');
        $response->assertViewHas(
            'vinDecode',
            fn (?array $d): bool => $d['status'] === VinDecodeCheck::MISMATCHED
                && array_keys($d['mismatches']) === ['make', 'model']
        );
    }

    public function test_an_agreeing_decode_is_not_flagged(): void
    {
        $case = $this->makeCase();
        // Titles abbreviate the make and pad the model; the decoder spells
        // both out. That is agreement, not a mismatch.
        $extraction = $this->makeExtraction($case, [
            'vin' => '1FMSK8DH1LGB73874',
            'year' => '2020',
            'make' => 'FORD',
            'model' => 'EXPLORER XLT',
        ]);

        $this->fakeDecode(2020, 'FORD', 'Explorer');

        $response = $this->actingAs($this->staff())
            ->get(route('admin.extractions.show', [$case->id, $extraction->id]));

        $response->assertOk();
        $response->assertDontSee('The VIN decodes to a different vehicle.');
        $response->assertViewHas('vinDecode', fn (?array $d): bool => $d['status'] === VinDecodeCheck::MATCHED);
    }

    public function test_an_abbreviated_make_still_counts_as_a_match(): void
    {
        $case = $this->makeCase();
        // "TOYT" on the title against "TOYOTA" from the decoder must not flag:
        // a cross-check that fires on every row would be ignored on every row.
        $extraction = $this->makeExtraction($case, [
            'vin' => '4T1C11AK7LU387042',
            'year' => '2020',
            'make' => 'TOYT',
            'model' => '4D',
        ]);

        $this->fakeDecode(2020, 'TOYOTA', 'Camry');

        $this->actingAs($this->staff())
            ->get(route('admin.extractions.show', [$case->id, $extraction->id]))
            ->assertOk()
            ->assertViewHas('vinDecode', fn (?array $d): bool => $d['status'] === VinDecodeCheck::MATCHED);
    }

    public function test_a_decoder_failure_degrades_to_a_soft_notice(): void
    {
        $case = $this->makeCase();
        $extraction = $this->makeExtraction($case, [
            'vin' => '1FMSK8DH1LGB73874',
            'make' => 'FORD',
        ]);

        // The decoder is unreachable. That says nothing about the document, so
        // it must not look like a finding and must not block anything.
        $this->decoderUnreachable();

        $response = $this->actingAs($this->staff())
            ->get(route('admin.extractions.show', [$case->id, $extraction->id]));

        $response->assertOk();
        $response->assertSee('could not confirm this VIN');
        $response->assertDontSee('The VIN decodes to a different vehicle.');
        $response->assertViewHas('vinDecode', fn (?array $d): bool => $d['status'] === VinDecodeCheck::UNAVAILABLE);
    }

    public function test_a_decoder_exception_does_not_break_the_screen(): void
    {
        $case = $this->makeCase();
        $extraction = $this->makeExtraction($case, ['vin' => '1FMSK8DH1LGB73874']);

        Http::fake(fn () => throw new \RuntimeException('connection reset'));

        $this->actingAs($this->staff())
            ->get(route('admin.extractions.show', [$case->id, $extraction->id]))
            ->assertOk()
            ->assertViewHas('vinDecode', fn (?array $d): bool => $d['status'] === VinDecodeCheck::UNAVAILABLE);
    }

    public function test_an_unknown_vin_is_not_reported_as_a_mismatch(): void
    {
        $case = $this->makeCase();
        $extraction = $this->makeExtraction($case, [
            'vin' => '1FMSK8DH1LGB73874',
            'make' => 'FORD',
        ]);

        // NHTSA answers, but recognises nothing about this VIN.
        $this->fakeDecode(null, null, null);

        $this->actingAs($this->staff())
            ->get(route('admin.extractions.show', [$case->id, $extraction->id]))
            ->assertOk()
            ->assertViewHas('vinDecode', fn (?array $d): bool => $d['status'] === VinDecodeCheck::UNAVAILABLE);
    }

    public function test_a_malformed_vin_is_not_sent_to_the_decoder(): void
    {
        $case = $this->makeCase();
        $extraction = $this->makeExtraction($case, ['vin' => 'NOT A VIN']);

        $this->decoderUnreachable();

        $this->actingAs($this->staff())
            ->get(route('admin.extractions.show', [$case->id, $extraction->id]))
            ->assertOk()
            ->assertViewHas('vinDecode', null);

        Http::assertNothingSent();
    }

    public function test_a_decode_mismatch_does_not_block_confirmation(): void
    {
        $case = $this->makeCase();
        $extraction = $this->makeExtraction($case, [
            'vin' => '1FMSK8DH1LGB73874',
            'make' => 'TOYT',
        ]);

        $this->fakeDecode(2020, 'FORD', 'Explorer');

        // The decoder can be wrong about rebodied or re-VINed vehicles, so
        // staff must be able to confirm past it.
        $response = $this->actingAs($this->staff())
            ->post(route('admin.extractions.confirm', [$case->id, $extraction->id]), [
                'fields' => [
                    'vin' => ['status' => 'confirmed', 'value' => '1FMSK8DH1LGB73874'],
                    'make' => ['status' => 'confirmed', 'value' => 'TOYT'],
                ],
            ]);

        $response->assertRedirect(route('admin.cases.show', $case->id));
        $response->assertSessionHas('success');
        $this->assertSame('TOYT', $case->vehicle()->first()->make);
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
