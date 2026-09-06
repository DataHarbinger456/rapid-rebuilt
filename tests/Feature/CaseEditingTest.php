<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\User;
use App\Models\UserCase;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Editing a draft case: the prefilled edit wizard and the PUT endpoint
 * behind it, which mirrors store()'s contract minus document uploads.
 */
class CaseEditingTest extends TestCase
{
    use DatabaseTransactions;

    private User $customer;

    /** A complete, valid edit payload. */
    private array $payload = [
        'vin' => '1HGCV1F30LA123456',
        'year' => 2020,
        'make' => 'Honda',
        'model' => 'Accord',
        'body_style' => 'Sedan',
        'engine' => '2.4L I4',
        'color' => 'blue',
        'odometer' => 128450,
        'title_state' => 'GA',
        'vehicle_type' => 'rebuilt',
        'components' => [
            [
                'type' => 'hood',
                'source' => 'new',
                'source_vin' => 'INV-8899',
                'description' => 'Aftermarket hood',
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create(['user_type' => 'user']);
    }

    private function draftCase(User $owner, array $overrides = []): UserCase
    {
        $case = UserCase::create(array_merge([
            'user_id' => $owner->id,
            'status' => 'draft',
            'service_type' => 'rebuilt_title',
            'total_amount' => 150.00,
        ], $overrides));

        Vehicle::create([
            'user_case_id' => $case->id,
            'vin' => '2HGFC2F59LH500000',
            'title_state' => 'FL',
            'year' => 2019,
            'make' => 'Honda',
            'model' => 'Civic',
            'color' => 'white',
            'odometer_reading' => 90000,
            'vehicle_type' => 'rebuilt',
        ]);

        Component::create([
            'user_case_id' => $case->id,
            'part_type' => 'bumper_front',
            'condition' => 'used',
            'serial_number' => '2HGFC2F59LH500000',
            'other_description' => 'Salvage yard bumper',
        ]);

        return $case;
    }

    // =====================
    // The screen
    // =====================

    public function test_the_owner_sees_the_draft_prefilled(): void
    {
        $case = $this->draftCase($this->customer);

        $this->actingAs($this->customer)
            ->get("/customer/cases/{$case->id}/edit")
            ->assertOk()
            ->assertSee($case->case_number)
            ->assertSee('2HGFC2F59LH500000')      // saved VIN, in the input value
            ->assertSee('Salvage yard bumper')    // saved component description
            ->assertSee('Manage Documents');      // documents stay on their own screen
    }

    public function test_another_customer_cannot_open_the_edit_screen(): void
    {
        $case = $this->draftCase($this->customer);

        $this->actingAs(User::factory()->create(['user_type' => 'user']))
            ->get("/customer/cases/{$case->id}/edit")
            ->assertStatus(403);
    }

    // =====================
    // Saving a draft
    // =====================

    public function test_saving_a_draft_updates_the_vehicle_and_replaces_components(): void
    {
        $case = $this->draftCase($this->customer);

        $this->actingAs($this->customer)
            ->put("/customer/cases/{$case->id}", $this->payload + ['save_draft' => '1'])
            ->assertRedirect(route('customer.cases.edit', $case));

        $this->assertDatabaseHas('vehicles', [
            'user_case_id' => $case->id,
            'vin' => '1HGCV1F30LA123456',
            'year' => 2020,
            'model' => 'Accord',
            'body_type' => 'Sedan',
            'engine_type' => '2.4L I4',
            'color' => 'blue',
            'odometer_reading' => 128450,
            'title_state' => 'GA',
        ]);

        // Replaced, not appended.
        $this->assertSame(1, $case->components()->count());

        // A "new" part is traced by receipt, not by a donor VIN.
        $this->assertDatabaseHas('components', [
            'user_case_id' => $case->id,
            'part_type' => 'hood',
            'condition' => 'new',
            'receipt_number' => 'INV-8899',
            'serial_number' => null,
            'other_description' => 'Aftermarket hood',
        ]);

        $this->assertDatabaseMissing('components', [
            'user_case_id' => $case->id,
            'part_type' => 'bumper_front',
        ]);

        $this->assertSame('draft', $case->fresh()->status);
    }

    public function test_a_used_component_keeps_its_donor_vin(): void
    {
        $case = $this->draftCase($this->customer);

        $payload = $this->payload;
        $payload['components'][0] = [
            'type' => 'engine',
            'source' => 'used',
            'source_vin' => '3FADP4EJ4EM123456',
            'description' => 'Donor engine',
        ];

        $this->actingAs($this->customer)
            ->put("/customer/cases/{$case->id}", $payload + ['save_draft' => '1'])
            ->assertRedirect(route('customer.cases.edit', $case));

        $this->assertDatabaseHas('components', [
            'user_case_id' => $case->id,
            'part_type' => 'engine',
            'condition' => 'used',
            'serial_number' => '3FADP4EJ4EM123456',
            'receipt_number' => null,
        ]);
    }

    public function test_the_estimate_is_recalculated_from_the_component_count(): void
    {
        $case = $this->draftCase($this->customer);

        $payload = $this->payload;
        $payload['components'][] = [
            'type' => 'engine',
            'source' => 'used',
            'source_vin' => '3FADP4EJ4EM123456',
            'description' => 'Donor engine',
        ];

        $this->actingAs($this->customer)
            ->put("/customer/cases/{$case->id}", $payload + ['save_draft' => '1']);

        // Base 150 + 2 components x 10 (the controller's default pricing).
        $this->assertEqualsWithDelta(170.00, (float) $case->fresh()->total_amount, 0.001);
    }

    // =====================
    // Submitting
    // =====================

    public function test_submitting_moves_the_case_out_of_draft_and_logs_the_timeline(): void
    {
        $case = $this->draftCase($this->customer);

        $this->actingAs($this->customer)
            ->put("/customer/cases/{$case->id}", $this->payload)
            ->assertRedirect(route('customer.cases.show', $case));

        $this->assertSame('submitted', $case->fresh()->status);

        $this->assertDatabaseHas('case_timeline', [
            'user_case_id' => $case->id,
            'event_type' => 'status_changed',
            'title' => 'Case Submitted',
        ]);
    }

    // =====================
    // Guards
    // =====================

    public function test_another_customer_cannot_update_the_case(): void
    {
        $case = $this->draftCase($this->customer);

        $this->actingAs(User::factory()->create(['user_type' => 'user']))
            ->put("/customer/cases/{$case->id}", $this->payload)
            ->assertStatus(403);

        $this->assertDatabaseHas('vehicles', [
            'user_case_id' => $case->id,
            'vin' => '2HGFC2F59LH500000',
        ]);
    }

    public function test_a_non_draft_case_is_bounced_to_show_unchanged(): void
    {
        // NB: the plan expected 403 here, but UserCasePolicy::update permits the
        // owner well past draft (paid, pending_scheduling, ...). The draft-only
        // rule is the controller's, matching edit(), so this redirects.
        $case = $this->draftCase($this->customer, ['status' => 'paid']);

        $this->actingAs($this->customer)
            ->put("/customer/cases/{$case->id}", $this->payload)
            ->assertRedirect(route('customer.cases.show', $case));

        $this->assertSame('paid', $case->fresh()->status);

        $this->assertDatabaseHas('vehicles', [
            'user_case_id' => $case->id,
            'vin' => '2HGFC2F59LH500000',
        ]);
    }

    public function test_an_invalid_component_type_is_rejected_and_nothing_changes(): void
    {
        $case = $this->draftCase($this->customer);

        $payload = $this->payload;
        $payload['components'][0]['type'] = 'flux_capacitor';

        $this->actingAs($this->customer)
            ->from("/customer/cases/{$case->id}/edit")
            ->put("/customer/cases/{$case->id}", $payload)
            ->assertRedirect("/customer/cases/{$case->id}/edit")
            ->assertSessionHasErrors('components.0.type');

        $this->assertDatabaseHas('vehicles', [
            'user_case_id' => $case->id,
            'vin' => '2HGFC2F59LH500000',
        ]);

        $this->assertDatabaseHas('components', [
            'user_case_id' => $case->id,
            'part_type' => 'bumper_front',
        ]);

        $this->assertSame('draft', $case->fresh()->status);
    }

    public function test_a_missing_vin_is_rejected(): void
    {
        $case = $this->draftCase($this->customer);

        $payload = $this->payload;
        unset($payload['vin']);

        $this->actingAs($this->customer)
            ->from("/customer/cases/{$case->id}/edit")
            ->put("/customer/cases/{$case->id}", $payload)
            ->assertSessionHasErrors('vin');

        $this->assertDatabaseHas('vehicles', [
            'user_case_id' => $case->id,
            'vin' => '2HGFC2F59LH500000',
        ]);
    }
}
