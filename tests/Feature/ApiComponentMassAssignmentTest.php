<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\User;
use App\Models\UserCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * The component endpoints persist only what they validate.
 *
 * Component::$fillable includes verified, verified_by, verified_at and
 * verification_notes, none of which these endpoints validate. Those belong to
 * an inspector calling Component::verify(); a case owner must not be able to
 * set them by adding the keys to a request body.
 */
class ApiComponentMassAssignmentTest extends TestCase
{
    use DatabaseTransactions;

    private function ownedCase(User $owner): UserCase
    {
        return UserCase::create([
            'user_id' => $owner->id,
            'case_number' => 'RR-TEST-' . uniqid(),
            'status' => 'draft',
            'service_type' => 'rebuilt_title',
            'inspection_fee' => 150.00,
            'service_fee' => 100.00,
            'total_amount' => 250.00,
        ]);
    }

    private function existingComponent(UserCase $case): Component
    {
        return Component::create([
            'user_case_id' => $case->id,
            'part_type' => 'hood',
            'condition' => 'used',
        ]);
    }

    public function test_store_ignores_unvalidated_fillable_fields(): void
    {
        $owner = User::factory()->create();
        $case = $this->ownedCase($owner);

        $response = $this->actingAs($owner, 'sanctum')->postJson(
            "/api/cases/{$case->id}/components",
            [
                'part_type' => 'hood',
                'condition' => 'used',
                'part_number' => 'PN-123',
                // None of these are validated by the endpoint.
                'verified' => true,
                'verification_notes' => 'self approved',
                'receipt_number' => 'FAKE-RECEIPT',
            ]
        );

        $response->assertCreated();

        $component = Component::where('user_case_id', $case->id)->firstOrFail();

        // The validated field lands.
        $this->assertSame('PN-123', $component->part_number);

        // The unvalidated ones do not.
        $this->assertFalse((bool) $component->verified);
        $this->assertNull($component->verification_notes);
        $this->assertNull($component->receipt_number);
    }

    public function test_update_ignores_unvalidated_fillable_fields(): void
    {
        $owner = User::factory()->create();
        $case = $this->ownedCase($owner);
        $component = $this->existingComponent($case);

        $response = $this->actingAs($owner, 'sanctum')->putJson(
            "/api/cases/{$case->id}/components/{$component->id}",
            [
                'part_number' => 'PN-456',
                'verified' => true,
                'verified_by' => $owner->id,
                'verification_notes' => 'self approved',
            ]
        );

        $response->assertOk();

        $component->refresh();

        $this->assertSame('PN-456', $component->part_number);
        $this->assertFalse((bool) $component->verified);
        $this->assertNull($component->verified_by);
        $this->assertNull($component->verification_notes);
    }

    /**
     * Self-verifying was not merely untidy: destroy() refuses to delete a
     * verified component, so a customer could previously have made their own
     * component permanently undeletable.
     */
    public function test_owner_cannot_self_verify_a_component_and_lock_it(): void
    {
        $owner = User::factory()->create();
        $case = $this->ownedCase($owner);
        $component = $this->existingComponent($case);

        $this->actingAs($owner, 'sanctum')->putJson(
            "/api/cases/{$case->id}/components/{$component->id}",
            ['verified' => true]
        )->assertOk();

        $this->assertFalse((bool) $component->fresh()->verified);

        // Still deletable, because it was never verified.
        $this->actingAs($owner, 'sanctum')->deleteJson(
            "/api/cases/{$case->id}/components/{$component->id}"
        )->assertOk();

        $this->assertNull(Component::find($component->id));
    }
}
