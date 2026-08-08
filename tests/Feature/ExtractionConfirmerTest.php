<?php

namespace Tests\Feature;

use App\Models\DocumentExtraction;
use App\Models\DocumentExtractionField;
use App\Models\UploadedFile;
use App\Models\User;
use App\Models\UserCase;
use App\Models\Vehicle;
use App\Services\Extraction\ExtractionConfirmer;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use RuntimeException;
use Tests\TestCase;

/**
 * ExtractionConfirmer is the only writer between a vision model and the
 * vehicle record that feeds the HSMV 82040, so these tests are about what is
 * allowed through and what is not.
 */
class ExtractionConfirmerTest extends TestCase
{
    use DatabaseTransactions;

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

    /**
     * The vehicle a case already carries from intake. vehicles.vin, year, make
     * and model are NOT NULL, so a case always has these before a title is
     * ever read.
     */
    private function makeVehicle(UserCase $case, array $overrides = []): Vehicle
    {
        return Vehicle::create(array_merge([
            'user_case_id' => $case->id,
            'vin' => '1HGCV1F30LA123456',
            'year' => 2020,
            'make' => 'HONDA',
            'model' => 'ACCORD',
        ], $overrides));
    }

    private function makeDocument(UserCase $case): UploadedFile
    {
        return UploadedFile::create([
            'user_case_id' => $case->id,
            'user_id' => $case->user_id,
            'uploaded_by' => $case->user_id,
            'document_type' => 'salvage_title',
            'file_path' => 'cases/' . $case->id . '/documents/title.png',
            'path' => 'cases/' . $case->id . '/documents/title.png',
            'original_name' => 'title.png',
            'mime_type' => 'image/png',
            'file_size' => 8208,
            'size' => 8208,
            'status' => 'pending',
        ]);
    }

    /**
     * @param  array<string, array{0: ?string, 1: string, 2?: ?string}>  $fields
     *   field_key => [ai_value, status, confirmed_value]
     */
    private function makeExtraction(UserCase $case, array $fields): DocumentExtraction
    {
        $extraction = DocumentExtraction::create([
            'uploaded_file_id' => $this->makeDocument($case)->id,
            'user_case_id' => $case->id,
            'document_type' => 'salvage_title',
            'status' => DocumentExtraction::STATUS_COMPLETED,
            'provider' => 'anthropic',
            'model' => 'claude-sonnet-4-5',
        ]);

        foreach ($fields as $key => $spec) {
            DocumentExtractionField::create([
                'document_extraction_id' => $extraction->id,
                'field_key' => $key,
                'ai_value' => $spec[0],
                'status' => $spec[1],
                'confirmed_value' => $spec[2] ?? null,
            ]);
        }

        return $extraction->load('fields');
    }

    public function test_confirmed_and_corrected_values_reach_the_vehicle(): void
    {
        $case = $this->makeCase();
        $staff = User::factory()->create();
        $this->makeVehicle($case, ['title_state' => 'GA', 'make' => 'HONDA']);

        $extraction = $this->makeExtraction($case, [
            'vin' => ['1HGCV1F30LA123456', DocumentExtractionField::STATUS_CONFIRMED],
            // Staff read the title differently from the model.
            'title_state' => ['FI', DocumentExtractionField::STATUS_CORRECTED, 'FL'],
            'make' => ['HONDA', DocumentExtractionField::STATUS_CONFIRMED],
        ]);

        $vehicle = app(ExtractionConfirmer::class)->apply($extraction, $staff);

        $this->assertSame('1HGCV1F30LA123456', $vehicle->vin);
        $this->assertSame('FL', $vehicle->title_state);
        $this->assertSame('HONDA', $vehicle->make);

        $extraction->refresh();
        $this->assertSame($staff->id, $extraction->reviewed_by);
        $this->assertNotNull($extraction->reviewed_at);

        $this->assertDatabaseHas('case_timeline', [
            'user_case_id' => $case->id,
            'event_type' => 'extraction_confirmed',
        ]);
    }

    public function test_a_pending_field_blocks_the_whole_apply(): void
    {
        $case = $this->makeCase();
        $this->makeVehicle($case, ['vin' => '1HGCV1F30LA999999']);

        $extraction = $this->makeExtraction($case, [
            'vin' => ['1HGCV1F30LA123456', DocumentExtractionField::STATUS_CONFIRMED],
            'title_state' => ['FL', DocumentExtractionField::STATUS_PENDING],
        ]);

        try {
            app(ExtractionConfirmer::class)->apply($extraction, User::factory()->create());
            $this->fail('A pending field must stop the apply.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('title_state', $e->getMessage());
        }

        // Nothing partial: the confirmed VIN must not have landed either.
        $this->assertSame(
            '1HGCV1F30LA999999',
            Vehicle::where('user_case_id', $case->id)->value('vin')
        );
    }

    public function test_a_rejected_field_leaves_the_existing_column_untouched(): void
    {
        $case = $this->makeCase();

        $this->makeVehicle($case, ['color' => 'BLUE', 'make' => 'ACURA']);

        $extraction = $this->makeExtraction($case, [
            // The model misread a smudge; staff say the title does not show it.
            'color' => ['BLUF', DocumentExtractionField::STATUS_REJECTED],
            'make' => ['HONDA', DocumentExtractionField::STATUS_CONFIRMED],
        ]);

        $vehicle = app(ExtractionConfirmer::class)->apply($extraction, User::factory()->create());

        $this->assertSame('BLUE', $vehicle->color);
        $this->assertSame('HONDA', $vehicle->make);
    }

    public function test_numeric_and_date_fields_are_cast_before_they_are_stored(): void
    {
        $case = $this->makeCase();
        $this->makeVehicle($case, ['year' => 1999]);

        $extraction = $this->makeExtraction($case, [
            'year' => ['2020', DocumentExtractionField::STATUS_CONFIRMED],
            // Staff typed the reading straight off the title, separators and all.
            'odometer_reading' => ['128450', DocumentExtractionField::STATUS_CORRECTED, '128,450'],
            'weight' => ['3400 lbs', DocumentExtractionField::STATUS_CONFIRMED],
            'odometer_date' => ['11/04/2025', DocumentExtractionField::STATUS_CONFIRMED],
        ]);

        $vehicle = app(ExtractionConfirmer::class)->apply($extraction, User::factory()->create());

        $this->assertSame(2020, $vehicle->year);
        $this->assertSame(128450, $vehicle->odometer_reading);
        $this->assertSame(3400, $vehicle->weight);
        $this->assertInstanceOf(Carbon::class, $vehicle->odometer_date);
        $this->assertSame('2025-11-04', $vehicle->odometer_date->format('Y-m-d'));
    }

    public function test_the_owner_name_is_read_for_cross_checking_but_never_stored(): void
    {
        $case = $this->makeCase();
        $this->makeVehicle($case);

        $extraction = $this->makeExtraction($case, [
            'vin' => ['1HGCV1F30LA123456', DocumentExtractionField::STATUS_CONFIRMED],
            'owner_name_on_title' => ['SAMPLE TESTCASE', DocumentExtractionField::STATUS_CONFIRMED],
        ]);

        $vehicle = app(ExtractionConfirmer::class)->apply($extraction, User::factory()->create());

        $this->assertNull(ExtractionConfirmer::vehicleColumnFor('owner_name_on_title'));
        $this->assertArrayNotHasKey('owner_name_on_title', $vehicle->getAttributes());
    }

    public function test_an_accepted_but_empty_value_does_not_blank_the_column(): void
    {
        $case = $this->makeCase();

        $this->makeVehicle($case, ['license_plate' => 'ABC1234']);

        $extraction = $this->makeExtraction($case, [
            'license_plate' => [null, DocumentExtractionField::STATUS_CONFIRMED],
        ]);

        $vehicle = app(ExtractionConfirmer::class)->apply($extraction, User::factory()->create());

        $this->assertSame('ABC1234', $vehicle->license_plate);
    }
}
