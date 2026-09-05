<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use App\Models\UserCase;
use App\Models\Vehicle;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * The notification/email backend, pinned against four bugs that made it
 * silently useless before anything consumed it:
 *
 *  1. the read/unread methods filtered on an is_read column that does not
 *     exist (real columns: read_at, status);
 *  2. notifyStaff() inserted user_case_id = null into a NOT NULL column,
 *     so every case submission blew up inside the notification path;
 *  3. sendCaseSubmitted() pointed at a nonexistent view ('emails.case-submitted'
 *     vs. the real 'emails.cases.submitted') and omitted the vars that
 *     template needs;
 *  4. the markdown mail theme was unpublished and the mail:: namespace
 *     unregistered, so closure-based Mail::send could not resolve the
 *     components and every email died inside sendEmail()'s catch.
 *
 * Note: this framework version's MailFake records only Mailable objects and
 * is blind to closure-based Mail::send, so the email assertions read the
 * array transport that phpunit.xml already configures.
 */
class NotificationServiceTest extends TestCase
{
    use DatabaseTransactions;

    private NotificationService $service;

    private User $customer;

    private UserCase $case;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(NotificationService::class);
        $this->customer = User::factory()->create(['user_type' => 'user']);
        $this->case = $this->caseFor($this->customer);
    }

    // =====================
    // Fix 1 — read_at / status, not is_read
    // =====================

    public function test_unread_count_uses_the_read_at_column(): void
    {
        $this->notificationFor($this->customer);
        $this->notificationFor($this->customer);
        $this->notificationFor($this->customer, ['read_at' => now(), 'status' => 'read']);

        // Previously this filtered on a nonexistent is_read column and threw
        // "Unknown column 'is_read'".
        $this->assertSame(2, $this->service->getUnreadCount($this->customer));
    }

    public function test_unread_count_is_scoped_to_the_given_user(): void
    {
        $this->notificationFor($this->customer);
        $this->notificationFor(User::factory()->create(['user_type' => 'user']));

        $this->assertSame(1, $this->service->getUnreadCount($this->customer));
    }

    public function test_mark_as_read_stamps_both_read_at_and_status(): void
    {
        $notification = $this->notificationFor($this->customer);

        $this->assertNull($notification->read_at);

        $this->service->markAsRead($notification);

        $fresh = $notification->fresh();
        $this->assertNotNull($fresh->read_at, 'read_at must be stamped.');
        $this->assertSame('read', $fresh->status, 'status must move off unseen.');
        $this->assertSame(0, $this->service->getUnreadCount($this->customer));
    }

    public function test_mark_as_read_leaves_an_already_read_row_alone(): void
    {
        $readAt = now()->subDay();
        $notification = $this->notificationFor($this->customer, [
            'read_at' => $readAt,
            'status' => 'read',
        ]);

        $this->service->markAsRead($notification);

        $this->assertSame(
            $readAt->toDateTimeString(),
            $notification->fresh()->read_at->toDateTimeString(),
            'An already-read row must keep its original timestamp.'
        );
    }

    public function test_mark_all_as_read_clears_only_that_users_rows(): void
    {
        $stranger = User::factory()->create(['user_type' => 'user']);

        $this->notificationFor($this->customer);
        $this->notificationFor($this->customer);
        $strangersRow = $this->notificationFor($stranger);

        $cleared = $this->service->markAllAsRead($this->customer);

        $this->assertSame(2, $cleared, 'It should report how many rows it cleared.');
        $this->assertSame(0, $this->service->getUnreadCount($this->customer));
        $this->assertNull($strangersRow->fresh()->read_at, "Another user's row must be untouched.");
    }

    // =====================
    // Fix 2 — notifyStaff binds the case (user_case_id is NOT NULL)
    // =====================

    public function test_case_submitted_notifies_staff_bound_to_the_case(): void
    {
        $staff = User::factory()->create(['user_type' => 'staff']);

        // Previously threw a NOT NULL violation on notifications.user_case_id.
        $this->service->sendCaseSubmitted($this->case);

        $staffNotification = Notification::where('user_id', $staff->id)
            ->where('type', 'staff_notification')
            ->latest('id')
            ->first();

        $this->assertNotNull($staffNotification, 'Staff should be notified of a new submission.');
        $this->assertSame($this->case->id, $staffNotification->user_case_id);
        $this->assertStringContainsString($this->case->case_number, $staffNotification->notification);
    }

    public function test_case_submitted_also_notifies_the_owner(): void
    {
        $this->service->sendCaseSubmitted($this->case);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->customer->id,
            'user_case_id' => $this->case->id,
            'type' => 'case_submitted',
        ]);
    }

    // =====================
    // Fix 3 + 4 — real template, real vars, resolvable mail:: components
    // =====================

    public function test_the_mail_component_namespace_is_registered(): void
    {
        $this->assertTrue(
            view()->exists('mail::message'),
            'Closure-based Mail::send needs the mail:: namespace registered globally.'
        );
    }

    public function test_case_submitted_emails_the_owner_from_the_real_template(): void
    {
        $vehicle = Vehicle::create([
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

        $this->service->sendCaseSubmitted($this->case->fresh());

        $sent = $this->sentEmails();
        $this->assertCount(1, $sent, 'Exactly one email should ship for a submission.');

        $message = $sent[0]->getOriginalMessage();
        $body = $message->getHtmlBody();

        $this->assertSame(
            $this->customer->email,
            $message->getTo()[0]->getAddress(),
            'The owner is the recipient.'
        );
        $this->assertStringContainsString($this->case->case_number, $body);

        // Each of these only renders if the corresponding var was passed --
        // the old call site supplied just ['case' => $case].
        $this->assertStringContainsString($this->customer->full_name, $body, '$user was not passed.');
        $this->assertStringContainsString($vehicle->vin, $body, '$vehicle was not passed.');
        $this->assertStringContainsString(route('customer.dashboard'), $body, '$dashboardUrl was not passed.');
    }

    public function test_the_submission_email_renders_real_html_not_raw_markdown(): void
    {
        $this->service->sendCaseSubmitted($this->case);

        $body = $this->sentEmails()[0]->getOriginalMessage()->getHtmlBody();

        // mail:: components resolved and the layout ran Markdown::parse.
        $this->assertStringContainsString('<table', $body, 'The mail layout chrome is missing.');
        $this->assertStringNotContainsString('# Case Submitted', $body, 'Raw markdown heading leaked.');
        $this->assertStringNotContainsString('**Case Number:**', $body, 'Raw markdown bold leaked.');
    }

    public function test_sending_the_submission_email_logs_no_swallowed_failure(): void
    {
        // sendEmail() catches everything and logs it, which is exactly how
        // these bugs stayed invisible. Assert the catch never fires.
        Log::spy();

        $this->service->sendCaseSubmitted($this->case);

        Log::shouldNotHaveReceived('error');
        $this->assertCount(1, $this->sentEmails());
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

    private function caseFor(User $owner): UserCase
    {
        return UserCase::create([
            'user_id' => $owner->id,
            'case_number' => 'RR-NSVC-' . uniqid(),
            'status' => 'draft',
            'service_type' => 'rebuilt_title',
            'total_amount' => 250.00,
        ]);
    }

    private function notificationFor(User $owner, array $overrides = []): Notification
    {
        return Notification::create(array_merge([
            'user_id' => $owner->id,
            'user_case_id' => $this->case->id,
            'type' => 'info',
            'title' => 'Case Update',
            'notification' => 'Something happened on your case.',
        ], $overrides));
    }
}
