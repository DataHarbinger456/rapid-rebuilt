<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserCase;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * title_state feeds the Original/Transfer decision on the HSMV 82040, so the
 * API has to reject codes that are not real states rather than accepting any
 * two characters.
 */
class ApiVehicleTitleStateTest extends TestCase
{
    use DatabaseTransactions;

    private function draftCase(User $owner): UserCase
    {
        $case = UserCase::create([
            'user_id' => $owner->id,
            'case_number' => 'RR-TEST-' . uniqid(),
            'status' => 'draft',
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
            'make' => 'Honda',
            'model' => 'Accord',
        ]);

        return $case->fresh();
    }

    public function test_api_accepts_a_real_out_of_state_code(): void
    {
        $owner = User::factory()->create();
        $case = $this->draftCase($owner);

        $response = $this->actingAs($owner, 'sanctum')
            ->putJson("/api/cases/{$case->id}/vehicle", ['title_state' => 'GA']);

        $response->assertOk();
        $this->assertSame('GA', $case->vehicle->fresh()->title_state);
    }

    public function test_api_rejects_a_code_that_is_not_a_state(): void
    {
        $owner = User::factory()->create();
        $case = $this->draftCase($owner);

        $response = $this->actingAs($owner, 'sanctum')
            ->putJson("/api/cases/{$case->id}/vehicle", ['title_state' => 'ZZ']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('title_state');

        // The stored value is untouched.
        $this->assertSame('FL', $case->vehicle->fresh()->title_state);
    }

    public function test_api_rejects_the_legacy_other_sentinel(): void
    {
        $owner = User::factory()->create();
        $case = $this->draftCase($owner);

        $response = $this->actingAs($owner, 'sanctum')
            ->putJson("/api/cases/{$case->id}/vehicle", ['title_state' => 'other']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('title_state');
    }

    public function test_api_still_allows_omitting_title_state(): void
    {
        $owner = User::factory()->create();
        $case = $this->draftCase($owner);

        $response = $this->actingAs($owner, 'sanctum')
            ->putJson("/api/cases/{$case->id}/vehicle", ['color' => 'Blue']);

        $response->assertOk();
        $this->assertSame('FL', $case->vehicle->fresh()->title_state);
    }

    /**
     * The endpoint persists only what it validated. vehicle_type and
     * vin_decoded_data are both fillable but carry no validation rule here, so
     * a caller must not be able to set them through this route.
     */
    public function test_api_ignores_fields_it_does_not_validate(): void
    {
        $owner = User::factory()->create();
        $case = $this->draftCase($owner);

        $response = $this->actingAs($owner, 'sanctum')->putJson(
            "/api/cases/{$case->id}/vehicle",
            [
                'color' => 'Blue',
                'vehicle_type' => 'street_rod',
                'vin_decoded_data' => ['tampered' => true],
            ]
        );

        $response->assertOk();

        $vehicle = $case->vehicle->fresh();

        $this->assertSame('Blue', $vehicle->color);
        $this->assertNull($vehicle->vehicle_type);
        $this->assertNull($vehicle->vin_decoded_data);
    }
}
