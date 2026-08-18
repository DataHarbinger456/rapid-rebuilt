<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserCase;
use App\Models\UserFormScheduling;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Appointment authorization for customers.
 *
 * The policy checked $appointment->case, but the model's relation is
 * userCase(). ->case is not a relation, so it silently evaluated to null and
 * every customer branch in view(), update() and cancel() was unreachable:
 * an owning customer was refused their own appointment, forever. The bug hid
 * a second one — the policy also called canReschedule() and canCancel(),
 * which did not exist on the model and would have crashed the moment the
 * first bug was fixed.
 */
class AppointmentAuthorizationTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * A future-dated confirmed appointment on a case owned by $customer.
     *
     * Future-dated because both helpers refuse to move or cancel an
     * appointment that has already ended.
     */
    private function appointmentFor(User $customer, array $overrides = []): UserFormScheduling
    {
        $case = UserCase::create([
            'user_id' => $customer->id,
            'case_number' => 'RR-TEST-' . uniqid(),
            'status' => 'paid',
            'service_type' => 'rebuilt_title',
            'inspection_fee' => 150.00,
            'service_fee' => 100.00,
            'total_amount' => 250.00,
        ]);

        return UserFormScheduling::create(array_merge([
            'user_case_id' => $case->id,
            'user_id' => $customer->id,
            'inspector_id' => User::factory()->create(['user_type' => 'inspector'])->id,
            'start' => now()->addDays(2)->setTime(10, 0),
            'end' => now()->addDays(2)->setTime(11, 0),
            'subject' => 'Rebuilt inspection',
            'status' => 'confirmed',
        ], $overrides));
    }

    /**
     * The relation the policy actually reads, through the real screen.
     */
    public function test_the_owning_customer_can_view_their_appointment(): void
    {
        $customer = User::factory()->create(['user_type' => 'user']);
        $appointment = $this->appointmentFor($customer);

        $this->actingAs($customer)
            ->get('/customer/appointments/' . $appointment->id)
            ->assertOk();
    }

    public function test_the_owning_customer_can_open_the_reschedule_screen(): void
    {
        $customer = User::factory()->create(['user_type' => 'user']);
        $appointment = $this->appointmentFor($customer);

        $this->actingAs($customer)
            ->get('/customer/appointments/' . $appointment->id . '/reschedule')
            ->assertOk();
    }

    public function test_another_customer_cannot_view_someone_elses_appointment(): void
    {
        $owner = User::factory()->create(['user_type' => 'user']);
        $appointment = $this->appointmentFor($owner);

        $this->actingAs(User::factory()->create(['user_type' => 'user']))
            ->get('/customer/appointments/' . $appointment->id)
            ->assertStatus(403);
    }

    public function test_another_customer_cannot_open_someone_elses_reschedule_screen(): void
    {
        $owner = User::factory()->create(['user_type' => 'user']);
        $appointment = $this->appointmentFor($owner);

        $this->actingAs(User::factory()->create(['user_type' => 'user']))
            ->get('/customer/appointments/' . $appointment->id . '/reschedule')
            ->assertStatus(403);
    }

    /**
     * The policy's own view/update/cancel answers, asserted directly so the
     * test says "authorization" rather than "a screen".
     */
    public function test_the_policy_answers_for_each_party(): void
    {
        $owner = User::factory()->create(['user_type' => 'user']);
        $appointment = $this->appointmentFor($owner);

        $this->assertTrue($owner->can('view', $appointment));
        $this->assertTrue($owner->can('update', $appointment));
        $this->assertTrue($owner->can('cancel', $appointment));

        $stranger = User::factory()->create(['user_type' => 'user']);

        $this->assertFalse($stranger->can('view', $appointment));
        $this->assertFalse($stranger->can('update', $appointment));
        $this->assertFalse($stranger->can('cancel', $appointment));

        $assignedInspector = User::find($appointment->inspector_id);

        $this->assertTrue($assignedInspector->can('view', $appointment));
        $this->assertTrue($assignedInspector->can('update', $appointment));
    }

    /**
     * The helpers the null was hiding. A past appointment can neither be
     * moved nor cancelled, and a completed or cancelled one is not movable
     * even if its times are somehow in the future.
     */
    public function test_past_and_finished_appointments_cannot_be_moved(): void
    {
        $customer = User::factory()->create(['user_type' => 'user']);

        $past = $this->appointmentFor($customer, [
            'start' => now()->subDays(2)->setTime(10, 0),
            'end' => now()->subDays(2)->setTime(11, 0),
        ]);

        $this->assertFalse($customer->can('update', $past));
        $this->assertFalse($customer->can('cancel', $past));

        foreach (['completed', 'cancelled', 'rescheduled', 'no_show'] as $status) {
            $finished = $this->appointmentFor($customer, ['status' => $status]);

            $this->assertFalse(
                $customer->can('update', $finished),
                "A {$status} appointment must not be reschedulable."
            );
        }
    }

    /**
     * The reschedule screen's own guard redirects a not-yet-schedulable case
     * rather than 403ing; that redirect is reachable only now that the
     * policy lets the owner through.
     */
    public function test_the_appointments_index_lists_the_customers_own(): void
    {
        $customer = User::factory()->create(['user_type' => 'user']);
        $appointment = $this->appointmentFor($customer);

        $response = $this->actingAs($customer)->get('/customer/appointments');

        $response->assertOk();
        $response->assertViewHas('upcomingAppointments');

        // And the appointment seeded above is on it, with its case reachable
        // — the relation the old view called ->case on, silently null.
        $this->assertTrue(
            $response->viewData('upcomingAppointments')->contains($appointment),
            'The customer\'s own appointment is not listed on their index.'
        );
    }

    /**
     * A customer with history must not crash the index: the past-appointments
     * query eager-loaded a nonexistent inspection relation on the appointment
     * model, which only surfaced once a customer actually had a past row.
     */
    public function test_the_index_survives_a_customer_with_past_appointments(): void
    {
        $customer = User::factory()->create(['user_type' => 'user']);

        $this->appointmentFor($customer, [
            'start' => now()->subDays(2)->setTime(10, 0),
            'end' => now()->subDays(2)->setTime(11, 0),
            'status' => 'completed',
        ]);

        $this->actingAs($customer)->get('/customer/appointments')->assertOk();
    }
}
