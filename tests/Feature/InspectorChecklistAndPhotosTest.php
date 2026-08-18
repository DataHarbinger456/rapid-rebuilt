<?php

namespace Tests\Feature;

use App\Models\Inspection;
use App\Models\User;
use App\Models\UserCase;
use App\Models\UserFormScheduling;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * The inspector's checklist and photos screens.
 *
 * Both views referenced API route names that were never registered:
 * route('api.inspections.update') and route('api.inspections.photos') threw
 * "Route not defined" from inside the Blade engine, so both screens 500ed
 * for the assigned inspector before rendering anything. The endpoints exist
 * in routes/api.php but unnamed, so the forms now target the real URIs.
 */
class InspectorChecklistAndPhotosTest extends TestCase
{
    use DatabaseTransactions;

    private function workflow(): array
    {
        $inspector = User::factory()->create(['user_type' => 'inspector']);
        $customer = User::factory()->create(['user_type' => 'user']);

        $case = UserCase::create([
            'user_id' => $customer->id,
            'case_number' => 'RR-TEST-' . uniqid(),
            'status' => 'paid',
            'service_type' => 'rebuilt_title',
            'inspection_fee' => 150.00,
            'service_fee' => 100.00,
            'total_amount' => 250.00,
        ]);

        Vehicle::create([
            'user_case_id' => $case->id,
            'vin' => '1HGCV1F30LA123456',
            'title_state' => 'GA',
            'year' => 2020,
            'make' => 'Honda',
            'model' => 'Accord',
            'body_type' => 'Sedan',
            'color' => 'Blue',
            'odometer_reading' => 128450,
            'odometer_date' => '2025-11-04',
            'odometer_status' => 'actual',
        ]);

        $appointment = UserFormScheduling::create([
            'user_case_id' => $case->id,
            'user_id' => $customer->id,
            'inspector_id' => $inspector->id,
            'start' => now()->setTime(10, 0),
            'end' => now()->setTime(11, 0),
            'subject' => 'Rebuilt inspection',
            'status' => 'in_progress',
        ]);

        // Mirrors InspectionService::startInspection(): a real inspection is
        // created from an appointment, carrying scheduling_id and an audit
        // number. The photos view links back to the checklist through
        // ->scheduling, which is null without it.
        $inspection = Inspection::create([
            'user_case_id' => $case->id,
            'scheduling_id' => $appointment->id,
            'inspector_id' => $inspector->id,
            'result' => 'in_progress',
            'started_at' => now(),
            'audit_number' => 'A-' . uniqid(),
            'odometer_digit_count' => 6,
        ]);

        return [$inspector, $inspection, $appointment];
    }

    /**
     * The route-name fix is what makes this render: the old view crashed
     * inside the Blade engine before any HTML was produced.
     */
    public function test_the_assigned_inspector_can_open_the_checklist_screen(): void
    {
        [$inspector, , $appointment] = $this->workflow();

        $response = $this->actingAs($inspector)
            ->get('/inspector/appointments/' . $appointment->id . '/checklist');

        $response->assertOk();
    }

    public function test_the_assigned_inspector_can_open_the_photos_screen(): void
    {
        [$inspector, $inspection] = $this->workflow();

        $response = $this->actingAs($inspector)
            ->get('/inspector/inspections/' . $inspection->id . '/photos');

        $response->assertOk();
    }

    /**
     * Both forms now point at real endpoints rather than names that do not
     * exist. Asserted in the rendered HTML so a regression to route() fails
     * here with a readable message rather than a 500.
     */
    public function test_both_forms_target_the_real_api_endpoints(): void
    {
        [$inspector, $inspection, $appointment] = $this->workflow();

        $checklist = $this->actingAs($inspector)
            ->get('/inspector/appointments/' . $appointment->id . '/checklist')
            ->getContent();

        $this->assertStringContainsString(
            // url() renders an absolute URL, so match the path plus the id
            // and the closing quote rather than a host-dependent string.
            '/api/inspections/' . $inspection->id . '"',
            $checklist,
            'The checklist form does not target the inspection-update endpoint.'
        );

        $photos = $this->actingAs($inspector)
            ->get('/inspector/inspections/' . $inspection->id . '/photos')
            ->getContent();

        $this->assertStringContainsString(
            '/api/inspections/' . $inspection->id . '/photos"',
            $photos,
            'The upload form does not target the photo-upload endpoint.'
        );
    }

    public function test_another_inspector_is_refused_on_both_screens(): void
    {
        [$owner, $inspection, $appointment] = $this->workflow();
        $stranger = User::factory()->create(['user_type' => 'inspector']);

        $this->actingAs($stranger)
            ->get('/inspector/appointments/' . $appointment->id . '/checklist')
            ->assertStatus(403);

        $this->actingAs($stranger)
            ->get('/inspector/inspections/' . $inspection->id . '/photos')
            ->assertStatus(403);
    }
}
