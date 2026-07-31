<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class MagicLinkTest extends TestCase
{
    use DatabaseTransactions;

    public function test_signed_link_logs_customer_in_and_redirects_to_customer_dashboard(): void
    {
        $user = User::factory()->create(['user_type' => 'user']);

        $url = URL::temporarySignedRoute('magic.login', now()->addMinutes(30), ['user' => $user->id]);

        $response = $this->get($url);

        $response->assertRedirect(route('customer.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_signed_link_redirects_staff_to_staff_dashboard(): void
    {
        $user = User::factory()->staff()->create();

        $url = URL::temporarySignedRoute('magic.login', now()->addMinutes(30), ['user' => $user->id]);

        $response = $this->get($url);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_expired_link_is_rejected(): void
    {
        $user = User::factory()->create();

        $url = URL::temporarySignedRoute('magic.login', now()->subMinute(), ['user' => $user->id]);

        $this->get($url)->assertForbidden();
        $this->assertGuest();
    }

    public function test_unsigned_link_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->get("/magic-login/{$user->id}")->assertForbidden();
        $this->assertGuest();
    }

    public function test_send_form_never_reveals_whether_account_exists(): void
    {
        $known = User::factory()->create();

        $this->post(route('magic.send'), ['email' => $known->email])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->post(route('magic.send'), ['email' => 'nobody@example.com'])
            ->assertRedirect()
            ->assertSessionHas('success');
    }
}
