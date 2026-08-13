<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Registration collecting the dealer's sales tax registration number.
 *
 * Section 7 of the HSMV 82040 prints it, and it belongs to the dealer rather
 * than to Rapid Rebuilt, so signup is where it has to be captured.
 */
class DealerRegistrationTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @return array<string, string> a complete, valid dealer registration
     */
    private function dealerPayload(array $overrides = []): array
    {
        $unique = uniqid();

        return array_merge([
            'user_type' => 'dealer',
            'first_name' => 'Genesis',
            'last_name' => 'Nicholson',
            'email' => "dealer+{$unique}@example.test",
            'username' => "dealer{$unique}",
            'password' => 'a-long-enough-password',
            'password_confirmation' => 'a-long-enough-password',
            'address_1' => '1841 NW 21st Terrace',
            'city' => 'Miami',
            'states' => 'FL',
            'zip' => '33142',
            'phone' => substr('305' . $unique, 0, 15),
            // Dealer-only fields.
            'county' => 'Miami-Dade',
            'dealer_name' => 'Rapid Rebuilt Motors',
            'license_type' => 'VI',
            'license_number' => '1043277',
            'region' => '5',
            'zones' => 'A',
            'sales_tax_registration_number' => '78-8012345678-4',
        ], $overrides);
    }

    public function test_a_dealer_registration_stores_the_sales_tax_number(): void
    {
        $payload = $this->dealerPayload();

        $this->post('/register', $payload)->assertRedirect();

        $dealer = User::where('email', $payload['email'])->first();

        $this->assertNotNull($dealer, 'The dealer was not created.');
        $this->assertSame('dealer', $dealer->user_type);
        $this->assertSame('78-8012345678-4', $dealer->sales_tax_registration_number);
        $this->assertSame('VI', $dealer->license_type);
        $this->assertSame('1043277', $dealer->license_number);
    }

    /**
     * A dealer may not have the number to hand at signup, and it is not needed
     * until a form is generated, so it must not block registering.
     */
    public function test_the_sales_tax_number_is_optional(): void
    {
        $payload = $this->dealerPayload(['sales_tax_registration_number' => null]);

        $this->post('/register', $payload)->assertRedirect();

        $dealer = User::where('email', $payload['email'])->first();

        $this->assertNotNull($dealer, 'A dealer without a sales tax number should still register.');
        $this->assertNull($dealer->sales_tax_registration_number);
    }

    /**
     * The field is dealer-only. A private customer never sees it, and a value
     * posted alongside user_type=user is not collected.
     */
    public function test_a_private_customer_does_not_get_one(): void
    {
        $payload = $this->dealerPayload([
            'user_type' => 'user',
            'sales_tax_registration_number' => '78-8012345678-4',
        ]);

        $this->post('/register', $payload)->assertRedirect();

        $customer = User::where('email', $payload['email'])->first();

        $this->assertNotNull($customer);
        $this->assertSame('user', $customer->user_type);
        $this->assertNull(
            $customer->sales_tax_registration_number,
            'A private customer must not carry a dealer sales tax number.'
        );
    }

    /**
     * The signup form has to offer the field, or nothing can ever fill it.
     */
    public function test_the_signup_form_offers_the_field(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSee('name="sales_tax_registration_number"', false);
    }
}
