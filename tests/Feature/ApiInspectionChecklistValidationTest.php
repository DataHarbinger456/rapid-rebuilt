<?php

namespace Tests\Feature;

use App\Models\Inspection;
use App\Models\User;
use App\Models\UserCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * InspectionService::updateChecklist() filters unknown keys but does not check
 * types, so the controller has to. These fields end up on the HSMV 84490
 * Statement of Builder, where a garbage value would be printed onto a state
 * document.
 */
class ApiInspectionChecklistValidationTest extends TestCase
{
    use DatabaseTransactions;

    private function inspectionFor(User $inspector): Inspection
    {
        $case = UserCase::create([
            'user_id' => User::factory()->create()->id,
            'case_number' => 'RR-TEST-' . uniqid(),
            'status' => 'inspection_scheduled',
            'service_type' => 'rebuilt_title',
            'inspection_fee' => 150.00,
            'service_fee' => 100.00,
            'total_amount' => 250.00,
        ]);

        return Inspection::create([
            'user_case_id' => $case->id,
            'inspector_id' => $inspector->id,
        ]);
    }

    private function inspector(): User
    {
        return User::factory()->create(['user_type' => 'inspector']);
    }

    public function test_non_boolean_flag_is_rejected(): void
    {
        $inspector = $this->inspector();
        $inspection = $this->inspectionFor($inspector);

        $response = $this->actingAs($inspector, 'sanctum')->putJson(
            "/api/inspections/{$inspection->id}",
            ['brakes_functional' => 'banana']
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('brakes_functional');
    }

    public function test_non_integer_odometer_reading_is_rejected(): void
    {
        $inspector = $this->inspector();
        $inspection = $this->inspectionFor($inspector);

        $response = $this->actingAs($inspector, 'sanctum')->putJson(
            "/api/inspections/{$inspection->id}",
            ['odometer_reading_verified' => 'not a number']
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('odometer_reading_verified');
    }

    public function test_overlong_inspector_notes_are_rejected(): void
    {
        $inspector = $this->inspector();
        $inspection = $this->inspectionFor($inspector);

        $response = $this->actingAs($inspector, 'sanctum')->putJson(
            "/api/inspections/{$inspection->id}",
            ['inspector_notes' => str_repeat('a', 5001)]
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('inspector_notes');
    }

    public function test_valid_checklist_update_is_accepted(): void
    {
        $inspector = $this->inspector();
        $inspection = $this->inspectionFor($inspector);

        $response = $this->actingAs($inspector, 'sanctum')->putJson(
            "/api/inspections/{$inspection->id}",
            [
                'brakes_functional' => true,
                'lights_functional' => false,
                'vin_location_1' => '1HGCV1F30LA123456',
                'odometer_reading_verified' => 48200,
                'inspector_notes' => 'Front bumper receipt matches.',
            ]
        );

        $response->assertOk();

        $inspection->refresh();

        $this->assertTrue($inspection->brakes_functional);
        $this->assertFalse($inspection->lights_functional);
        $this->assertSame('1HGCV1F30LA123456', $inspection->vin_location_1);
        $this->assertSame(48200, $inspection->odometer_reading_verified);
        $this->assertSame('Front bumper receipt matches.', $inspection->inspector_notes);
    }

    /**
     * The service whitelists keys; validation must not quietly widen that.
     */
    public function test_fields_outside_the_checklist_are_not_persisted(): void
    {
        $inspector = $this->inspector();
        $inspection = $this->inspectionFor($inspector);

        $this->actingAs($inspector, 'sanctum')->putJson(
            "/api/inspections/{$inspection->id}",
            [
                'brakes_functional' => true,
                'is_completed' => true,
                'inspector_id' => User::factory()->create()->id,
            ]
        )->assertOk();

        $inspection->refresh();

        $this->assertFalse((bool) $inspection->is_completed);
        $this->assertSame($inspector->id, $inspection->inspector_id);
    }
}
