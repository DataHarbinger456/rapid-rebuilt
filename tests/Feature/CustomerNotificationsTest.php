<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use App\Models\UserCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The customer notifications screen: listing, unread counts, the two
 * mark-read flows, and the header bell fed from the same source.
 */
class CustomerNotificationsTest extends TestCase
{
    use DatabaseTransactions;

    private User $customer;

    private UserCase $case;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create(['user_type' => 'user']);
        $this->case = $this->caseFor($this->customer);
    }

    private function caseFor(User $owner): UserCase
    {
        return UserCase::create([
            'user_id' => $owner->id,
            'status' => 'paid',
            'service_type' => 'rebuilt_title',
            'total_amount' => 250.00,
        ]);
    }

    /** notifications.user_case_id is NOT NULL, so every row binds a case. */
    private function notificationFor(User $owner, array $overrides = []): Notification
    {
        return Notification::create(array_merge([
            'user_id' => $owner->id,
            'user_case_id' => ($owner->is($this->customer) ? $this->case : $this->caseFor($owner))->id,
            'type' => 'info',
            'title' => 'Payment Received',
            'notification' => 'We received your payment.',
        ], $overrides));
    }

    // =====================
    // Listing
    // =====================

    public function test_the_index_lists_own_notifications_with_an_unread_count(): void
    {
        $this->notificationFor($this->customer);
        $this->notificationFor($this->customer, ['title' => 'Documents Pending', 'notification' => 'Two documents still needed.']);
        $this->notificationFor($this->customer, ['read_at' => now(), 'status' => 'read']);

        $this->actingAs($this->customer)
            ->get('/customer/notifications')
            ->assertOk()
            ->assertViewHas('unreadCount', 2)
            ->assertSee('Payment Received')
            ->assertSee('Documents Pending')
            ->assertSee('We received your payment.')
            ->assertSee($this->case->case_number);
    }

    public function test_the_index_hides_another_customers_notifications(): void
    {
        $stranger = User::factory()->create(['user_type' => 'user']);
        $this->notificationFor($stranger, ['title' => 'Not Yours', 'notification' => 'Belongs to somebody else.']);

        $this->actingAs($this->customer)
            ->get('/customer/notifications')
            ->assertOk()
            ->assertViewHas('unreadCount', 0)
            ->assertDontSee('Belongs to somebody else.');
    }

    public function test_the_empty_state_renders(): void
    {
        $this->actingAs($this->customer)
            ->get('/customer/notifications')
            ->assertOk()
            ->assertViewHas('unreadCount', 0)
            ->assertSee('No notifications yet');
    }

    public function test_an_unread_row_offers_mark_read_and_a_read_row_does_not(): void
    {
        $unread = $this->notificationFor($this->customer);

        $this->actingAs($this->customer)
            ->get('/customer/notifications')
            ->assertOk()
            ->assertSee(route('customer.notifications.read', $unread))
            ->assertSee('Mark all read');

        $unread->markAsRead();

        $this->actingAs($this->customer)
            ->get('/customer/notifications')
            ->assertOk()
            ->assertDontSee(route('customer.notifications.read', $unread))
            ->assertDontSee('Mark all read');
    }

    // =====================
    // Mark one read
    // =====================

    public function test_marking_own_notification_read_stamps_read_at_and_status(): void
    {
        $notification = $this->notificationFor($this->customer);
        $this->assertNull($notification->read_at);

        $this->actingAs($this->customer)
            ->from('/customer/notifications')
            ->post("/customer/notifications/{$notification->id}/read")
            ->assertRedirect('/customer/notifications')
            ->assertSessionHas('success');

        $fresh = $notification->fresh();
        $this->assertNotNull($fresh->read_at);
        $this->assertSame('read', $fresh->status);
    }

    public function test_marking_someone_elses_notification_is_refused_and_changes_nothing(): void
    {
        $stranger = User::factory()->create(['user_type' => 'user']);
        $notification = $this->notificationFor($stranger);

        $this->actingAs($this->customer)
            ->post("/customer/notifications/{$notification->id}/read")
            ->assertNotFound();

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_marking_read_is_idempotent(): void
    {
        $readAt = now()->subDay();
        $notification = $this->notificationFor($this->customer, ['read_at' => $readAt, 'status' => 'read']);

        $this->actingAs($this->customer)
            ->from('/customer/notifications')
            ->post("/customer/notifications/{$notification->id}/read")
            ->assertRedirect('/customer/notifications');

        $this->assertSame(
            $readAt->toDateTimeString(),
            $notification->fresh()->read_at->toDateTimeString(),
            'An already-read row keeps its original timestamp.'
        );
    }

    // =====================
    // Mark all read
    // =====================

    public function test_mark_all_read_clears_only_the_customers_own_rows(): void
    {
        $stranger = User::factory()->create(['user_type' => 'user']);

        $this->notificationFor($this->customer);
        $this->notificationFor($this->customer, ['title' => 'Second']);
        $strangersRow = $this->notificationFor($stranger);

        $this->actingAs($this->customer)
            ->from('/customer/notifications')
            ->post('/customer/notifications/read-all')
            ->assertRedirect('/customer/notifications')
            ->assertSessionHas('success');

        $this->assertSame(
            0,
            Notification::forUser($this->customer->id)->unread()->count(),
            "All of the customer's rows should be read."
        );

        $this->assertNull($strangersRow->fresh()->read_at, "Another user's row must be untouched.");
    }

    // =====================
    // Header bell, same source
    // =====================

    /**
     * The composer binds to the header partial, so its variables never appear
     * in the root view's data: assert on what actually rendered instead.
     */
    private function bellCount(string $html): ?string
    {
        preg_match('/badge badge-primary badge-pill">(\d+)</', $html, $matches);

        return $matches[1] ?? null;
    }

    public function test_the_header_bell_shows_the_unread_count_on_any_customer_screen(): void
    {
        $this->notificationFor($this->customer, ['title' => 'Bell Worthy']);
        $this->notificationFor($this->customer, ['title' => 'Also Unread']);

        // The dashboard renders the shared header partial.
        $response = $this->actingAs($this->customer)->get('/customer');

        $response->assertOk()->assertSee('Bell Worthy');

        $this->assertSame('2', $this->bellCount($response->getContent()));
    }

    public function test_the_header_bell_reflects_rows_being_marked_read(): void
    {
        $notification = $this->notificationFor($this->customer);

        $html = $this->actingAs($this->customer)->get('/customer')->getContent();
        $this->assertSame('1', $this->bellCount($html));

        $notification->markAsRead();

        $html = $this->actingAs($this->customer)->get('/customer')->getContent();
        $this->assertNull($this->bellCount($html), 'With nothing unread the badge should disappear.');
    }

    public function test_the_bell_lists_only_the_five_most_recent(): void
    {
        // Explicit timestamps: rows created in the same second would order
        // nondeterministically and make this assertion flaky. created_at is
        // not fillable, so it has to be set on the model rather than passed
        // to create() -- where it would be silently dropped.
        for ($i = 0; $i < 7; $i++) {
            $notification = $this->notificationFor($this->customer, ['title' => "Notice {$i}"]);
            $notification->created_at = now()->subMinutes(10 - $i);
            $notification->save();
        }

        $response = $this->actingAs($this->customer)->get('/customer');
        $response->assertOk();

        $this->assertSame('7', $this->bellCount($response->getContent()), 'The count covers every unread row.');

        // Newest five are listed; the two oldest are not. "Notice 0"/"Notice 1"
        // also appear in the dashboard's own list, so scope to the dropdown --
        // bounded by the messages dropdown that immediately follows it.
        $dropdown = Str::between($response->getContent(), 'notification-dropdown', 'chat-dropdown');

        foreach ([2, 3, 4, 5, 6] as $shown) {
            $this->assertStringContainsString("Notice {$shown}", $dropdown);
        }

        foreach ([0, 1] as $hidden) {
            $this->assertStringNotContainsString("Notice {$hidden}", $dropdown);
        }
    }

    // =====================
    // Icons/colours the theme can actually render
    // =====================

    public function test_every_type_maps_to_a_feather_icon_and_a_theme_colour(): void
    {
        // Both accessors are fed straight into data-feather / font-* classes,
        // so invalid values render as a blank icon or an untinted badge.
        $feather = ['info', 'check-circle', 'alert-triangle', 'x-circle', 'bell'];
        $themeColours = ['primary', 'success', 'warning', 'danger', 'secondary'];

        foreach (array_keys(Notification::TYPES) as $type) {
            $notification = $this->notificationFor($this->customer, ['type' => $type]);

            $this->assertContains($notification->icon, $feather, "Type [{$type}] has no feather icon.");
            $this->assertContains($notification->color, $themeColours, "Type [{$type}] has no theme colour.");
        }
    }
}
