<?php

namespace Tests\Feature;

use App\Models\Inspection;
use App\Models\User;
use App\Models\UserCase;
use App\Models\UserFormScheduling;
use App\Models\Vehicle;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * The three notification emails whose templates exist on disk but whose call
 * sites pointed at nonexistent view paths (and omitted most of the variables
 * those templates need), so every one of them died inside sendEmail()'s catch:
 *
 *   emails.appointment-confirmation -> emails.appointments.confirmation
 *   emails.appointment-reminder     -> emails.appointments.reminder
 *   emails.inspection-result        -> emails.inspections.completed
 *
 * Each test asserts content that only renders when a specific variable was
 * passed, so a regression to the old payload fails here rather than silently
 * mailing a broken page.
 *
 * Note: this framework version's MailFake records only Mailable objects and is
 * blind to closure-based Mail::send, so these read the array transport that
 * phpunit.xml already configures.
 */
class NotificationEmailTemplatesTest extends TestCase
{
    use DatabaseTransactions;

    private NotificationService $service;

    private User $customer;

    private UserCase $case;

    private Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(NotificationService::class);
        $this->customer = User::factory()->create(['user_type' => 'user']);

        $this->case = UserCase::create([
            'user_id' => $this->customer->id,
            'case_number' => 'RR-MAIL-' . uniqid(),
            'status' => 'paid',
            'service_type' => 'rebuilt_title',
            'total_amount' => 250.00,
        ]);

        $this->vehicle = Vehicle::create([
            'user_case_id' => $this->case->id,
            'vin' => '1HGCV1F30LA123456',
            'title_state' => 'FL',
            'year' => 2020,
            'make' => 'Honda',
            'model' => 'Accord',
            'color' => 'blue',
            'odometer_reading' => 128450,
            'vehicle_type' => 'rebuilt',
        ]);
    }

    // =====================
    // emails.appointments.confirmation
    // =====================

    public function test_appointment_confirmation_renders_the_real_template(): void
    {
        $appointment = $this->appointment();

        $this->service->sendAppointmentConfirmation($appointment);

        $sent = $this->sentEmails();
        $this->assertCount(1, $sent, 'The confirmation email should ship.');

        $message = $sent[0]->getOriginalMessage();
        $body = $message->getHtmlBody();

        $this->assertSame($this->customer->email, $message->getTo()[0]->getAddress());

        // Template-specific heading proves the right view rendered.
        $this->assertStringContainsString('Inspection Scheduled', $body);

        // Each assertion below fails if its variable is missing.
        $this->assertStringContainsString($this->customer->full_name, $body, '$case->user missing.');
        $this->assertStringContainsString($this->case->case_number, $body, '$case missing.');
        $this->assertStringContainsString($this->vehicle->vin, $body, '$vehicle missing.');
        $this->assertStringContainsString(
            route('customer.appointments.show', $appointment),
            $body,
            '$detailsUrl missing.'
        );
        $this->assertStringContainsString($appointment->start->format('g:i A'), $body, '$appointment missing.');
    }

    public function test_a_mobile_inspection_shows_the_customer_address_not_the_office(): void
    {
        // A mobile booking is stored as location_name 'Mobile Inspection' with
        // the customer's address filled in (AppointmentController::store).
        $appointment = $this->appointment([
            'location_name' => 'Mobile Inspection',
            'location_address' => '4820 Palm Grove Way',
            'location_city' => 'Hialeah',
            'location_state' => 'FL',
            'location_zip' => '33012',
        ]);

        $this->service->sendAppointmentConfirmation($appointment);

        $body = $this->sentEmails()[0]->getOriginalMessage()->getHtmlBody();

        $this->assertStringContainsString('4820 Palm Grove Way', $body, 'The mobile address is missing.');
        $this->assertStringContainsString('Hialeah', $body);
        $this->assertStringContainsString('33012', $body);
        $this->assertStringContainsString('Mobile Inspection (We come to you)', $body, 'Wrong inspection type.');

        // The bug: the office fallback used to render for every appointment.
        $this->assertStringNotContainsString('123 Main Street', $body, 'The office address leaked into a mobile booking.');
        $this->assertStringNotContainsString('Rapid Rebuilt Office', $body);
    }

    public function test_an_office_inspection_still_shows_the_office_address(): void
    {
        // Office bookings store no address, so the fallback is correct here.
        $this->service->sendAppointmentConfirmation($this->appointment());

        $body = $this->sentEmails()[0]->getOriginalMessage()->getHtmlBody();

        $this->assertStringContainsString('Rapid Rebuilt Office', $body);
        $this->assertStringContainsString('123 Main Street', $body);
        $this->assertStringContainsString('Office Inspection', $body);
        $this->assertStringNotContainsString('We come to you', $body);
    }

    public function test_a_mobile_reminder_also_shows_the_customer_address(): void
    {
        $appointment = $this->appointment([
            'location_name' => 'Mobile Inspection',
            'location_address' => '77 Sunset Ridge Rd',
            'location_city' => 'Coral Gables',
            'location_state' => 'FL',
            'location_zip' => '33134',
        ]);

        $this->service->sendAppointmentReminder($appointment, '24h');

        $body = $this->sentEmails()[0]->getOriginalMessage()->getHtmlBody();

        $this->assertStringContainsString('77 Sunset Ridge Rd', $body, 'The mobile address is missing.');
        $this->assertStringContainsString('Coral Gables', $body);
        $this->assertStringNotContainsString('Rapid Rebuilt Office', $body, 'The office fallback leaked.');
    }

    public function test_appointment_confirmation_renders_html_not_raw_markdown(): void
    {
        $this->service->sendAppointmentConfirmation($this->appointment());

        $body = $this->sentEmails()[0]->getOriginalMessage()->getHtmlBody();

        $this->assertStringContainsString('<table', $body, 'The mail layout chrome is missing.');
        $this->assertStringNotContainsString('# Inspection Scheduled', $body, 'Raw markdown heading leaked.');
        $this->assertStringNotContainsString('**Date:**', $body, 'Raw markdown bold leaked.');
    }

    // =====================
    // emails.appointments.reminder
    // =====================

    public function test_appointment_reminder_renders_the_real_template(): void
    {
        $appointment = $this->appointment();

        $this->service->sendAppointmentReminder($appointment, '24h');

        $sent = $this->sentEmails();
        $this->assertCount(1, $sent, 'The reminder email should ship.');

        $body = $sent[0]->getOriginalMessage()->getHtmlBody();

        $this->assertStringContainsString('Inspection Reminder', $body);
        $this->assertStringContainsString($this->case->case_number, $body, '$case missing.');
        $this->assertStringContainsString($this->vehicle->make, $body, '$vehicle missing.');
        $this->assertStringContainsString(
            route('customer.appointments.show', $appointment),
            $body,
            '$detailsUrl missing.'
        );
    }

    public function test_the_one_hour_reminder_also_supplies_hours_until(): void
    {
        // The service speaks '24h'/'1h' but the template speaks a number, so
        // the call site maps between them. Without $hoursUntil the template
        // raises "Undefined variable", sendEmail() swallows it, and no mail
        // ships -- which is what these assertions actually detect.
        //
        // Ceiling: the template renders 'tomorrow' for anything <= 24, so 1
        // and 24 are indistinguishable in the output; this pins that the
        // variable is supplied for both branches, not its exact value.
        $this->service->sendAppointmentReminder($this->appointment(), '1h');

        $sent = $this->sentEmails();
        $this->assertCount(1, $sent, 'A 1h reminder must render and ship.');
        $this->assertStringContainsString(
            'tomorrow',
            $sent[0]->getOriginalMessage()->getHtmlBody(),
            '$hoursUntil missing.'
        );
    }

    // =====================
    // emails.inspections.completed
    // =====================

    public function test_passed_inspection_renders_the_real_template(): void
    {
        $inspection = $this->inspection('passed');

        $this->service->sendInspectionResult($inspection);

        $sent = $this->sentEmails();
        $this->assertCount(1, $sent, 'The inspection result email should ship.');

        $body = $sent[0]->getOriginalMessage()->getHtmlBody();

        // The $passed branch of the template.
        $this->assertStringContainsString('Inspection Passed', $body, '$passed missing or false.');
        $this->assertStringContainsString('Congratulations', $body);
        $this->assertStringContainsString($this->vehicle->vin, $body, '$vehicle missing.');
        $this->assertStringContainsString($this->case->case_number, $body, '$case missing.');
        $this->assertStringContainsString(
            route('customer.cases.show', $this->case),
            $body,
            '$caseUrl missing.'
        );
    }

    public function test_failed_inspection_renders_the_other_branch(): void
    {
        $this->service->sendInspectionResult($this->inspection('failed'));

        $body = $this->sentEmails()[0]->getOriginalMessage()->getHtmlBody();

        $this->assertStringContainsString('Inspection Results', $body);
        $this->assertStringContainsString('did not pass', $body);
        $this->assertStringNotContainsString('Congratulations', $body);
    }

    public function test_a_failed_inspection_shows_the_real_inspector_notes(): void
    {
        // The template read $inspection->notes, which is not a column; the
        // real one is inspector_notes, so the generic fallback always won.
        $inspection = $this->inspection('failed');
        $inspection->update(['inspector_notes' => 'Frame rail weld does not meet spec.']);

        $this->service->sendInspectionResult($inspection->fresh());

        $body = $this->sentEmails()[0]->getOriginalMessage()->getHtmlBody();

        $this->assertStringContainsString('Frame rail weld does not meet spec.', $body);
        $this->assertStringNotContainsString(
            'Please contact us for detailed information',
            $body,
            'The fallback rendered even though notes exist.'
        );
    }

    // =====================
    // The catch that hid all of this
    // =====================

    public function test_none_of_the_three_logs_a_swallowed_failure(): void
    {
        Log::spy();

        $this->service->sendAppointmentConfirmation($this->appointment());
        $this->service->sendAppointmentReminder($this->appointment(), '24h');
        $this->service->sendInspectionResult($this->inspection('passed'));

        Log::shouldNotHaveReceived('error');
        $this->assertCount(3, $this->sentEmails(), 'All three emails should ship.');
    }

    // =====================
    // Helpers
    // =====================

    /**
     * @return \Symfony\Component\Mailer\SentMessage[]
     */
    private function sentEmails(): array
    {
        return app('mailer')->getSymfonyTransport()->messages()->all();
    }

    /**
     * An office appointment by default (no address stored, matching how
     * AppointmentController::store writes office bookings); pass location
     * overrides to make it a mobile one.
     */
    private function appointment(array $overrides = []): UserFormScheduling
    {
        return UserFormScheduling::create(array_merge([
            'user_id' => $this->customer->id,
            'user_case_id' => $this->case->id,
            'inspector_id' => User::factory()->create(['user_type' => 'inspector'])->id,
            'start' => now()->addDay()->setTime(10, 0),
            'end' => now()->addDay()->setTime(11, 0),
            'status' => 'confirmed',
            'location_name' => 'Office Inspection',
            'location_state' => 'FL',
        ], $overrides));
    }

    private function inspection(string $result): Inspection
    {
        return Inspection::create([
            'user_case_id' => $this->case->id,
            'inspector_id' => User::factory()->create(['user_type' => 'inspector'])->id,
            'result' => $result,
            'completed_at' => now(),
        ]);
    }
}
