<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserCase;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Customer settings screen: password change and the email-notification
 * opt-out, plus the guard in NotificationService::sendEmail() that honours it.
 *
 * Email assertions read the array transport (phpunit.xml) because this
 * framework's MailFake is blind to closure-based Mail::send.
 */
class CustomerSettingsTest extends TestCase
{
    use DatabaseTransactions;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create(['user_type' => 'user']);
    }

    public function test_settings_screen_renders_both_cards(): void
    {
        $this->actingAs($this->customer)
            ->get(route('customer.settings'))
            ->assertOk()
            ->assertSee('Change Password')
            ->assertSee('Email Notifications')
            ->assertSee(route('customer.settings.password'))
            ->assertSee(route('customer.settings.preferences'));
    }

    public function test_settings_screen_reflects_a_saved_opt_out(): void
    {
        $this->customer->setPreference('notifications.email', false);

        $html = $this->actingAs($this->customer)->get(route('customer.settings'))->getContent();

        $this->assertMatchesRegularExpression('/id="email_notifications"[^>]*value="1"[^>]*>/', $html);
        $this->assertDoesNotMatchRegularExpression('/id="email_notifications"[^>]*checked/', $html);
    }

    public function test_wrong_current_password_is_rejected_and_hash_untouched(): void
    {
        $this->actingAs($this->customer)
            ->from(route('customer.settings'))
            ->post(route('customer.settings.password'), [
                'current_password' => 'not-the-password',
                'password' => 'brand-new-secret-16',
                'password_confirmation' => 'brand-new-secret-16',
            ])
            ->assertRedirect(route('customer.settings'))
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('password', $this->customer->fresh()->password));
    }

    public function test_correct_current_password_changes_the_hash_and_keeps_login_working(): void
    {
        $this->actingAs($this->customer)
            ->post(route('customer.settings.password'), [
                'current_password' => 'password',
                'password' => 'brand-new-secret-16',
                'password_confirmation' => 'brand-new-secret-16',
            ])
            ->assertRedirect(route('customer.settings'))
            ->assertSessionHas('password_success');

        $this->assertTrue(Hash::check('brand-new-secret-16', $this->customer->fresh()->password));
        $this->assertTrue(Auth::validate([
            'email' => $this->customer->email,
            'password' => 'brand-new-secret-16',
        ]));
    }

    public function test_unconfirmed_or_short_new_password_is_rejected(): void
    {
        $this->actingAs($this->customer)
            ->post(route('customer.settings.password'), [
                'current_password' => 'password',
                'password' => 'brand-new-secret-16',
                'password_confirmation' => 'something-else-entirely',
            ])
            ->assertSessionHasErrors('password');

        $this->actingAs($this->customer)
            ->post(route('customer.settings.password'), [
                'current_password' => 'password',
                'password' => 'short',
                'password_confirmation' => 'short',
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('password', $this->customer->fresh()->password));
    }

    public function test_opting_out_stores_a_real_false_not_the_string_zero(): void
    {
        $this->actingAs($this->customer)
            ->post(route('customer.settings.preferences'), ['email_notifications' => '0'])
            ->assertRedirect(route('customer.settings'))
            ->assertSessionHas('preferences_success');

        $this->assertFalse($this->customer->fresh()->getPreference('notifications.email'));
    }

    public function test_opting_back_in_stores_true(): void
    {
        $this->customer->setPreference('notifications.email', false);

        $this->actingAs($this->customer)
            ->post(route('customer.settings.preferences'), ['email_notifications' => '1'])
            ->assertRedirect(route('customer.settings'));

        $this->assertTrue($this->customer->fresh()->getPreference('notifications.email'));
    }

    public function test_opted_out_customer_receives_no_email(): void
    {
        $this->customer->setPreference('notifications.email', false);

        app(NotificationService::class)->sendCaseSubmitted($this->caseFor($this->customer));

        $this->assertCount(0, $this->sentEmails());
    }

    public function test_customer_with_no_preference_still_receives_email(): void
    {
        app(NotificationService::class)->sendCaseSubmitted($this->caseFor($this->customer));

        $this->assertCount(1, $this->sentEmails());
    }

    public function test_profile_password_form_posts_to_the_settings_endpoint(): void
    {
        $html = $this->actingAs($this->customer)->get(route('customer.profile'))->getContent();

        $this->assertStringContainsString('action="' . route('customer.settings.password') . '"', $html);
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
            'case_number' => 'RR-CST-' . uniqid(),
            'status' => 'draft',
            'service_type' => 'rebuilt_title',
            'total_amount' => 250.00,
        ]);
    }
}
