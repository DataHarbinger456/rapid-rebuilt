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
            'license_number' => '1142356-1',
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
        $this->assertSame('VI', $dealer->license_type);
        $this->assertSame('1142356-1', $dealer->license_number);

        // Stored as digits: the dashes are presentation and go back on when
        // the 82040 is printed.
        $this->assertSame('7880123456784', $dealer->sales_tax_registration_number);
    }

    /**
     * A Florida number is 13 digits off a DR-11 certificate, however the dealer
     * chose to punctuate it.
     */
    public function test_a_florida_number_is_accepted_dashed_or_bare(): void
    {
        foreach (['78-8012345678-4', '7880123456784', '78 8012345678 4'] as $typed) {
            $payload = $this->dealerPayload([
                'states' => 'FL',
                'sales_tax_registration_number' => $typed,
            ]);

            $this->post('/register', $payload)->assertRedirect()->assertSessionHasNoErrors();

            $this->assertSame(
                '7880123456784',
                User::where('email', $payload['email'])->value('sales_tax_registration_number'),
                "[{$typed}] is the same Florida number and should store identically."
            );
        }
    }

    /**
     * The typo this validation exists to catch: one digit short. Without it the
     * number reaches a filed form and the state bounces the application.
     */
    public function test_a_florida_number_with_the_wrong_digit_count_is_rejected(): void
    {
        foreach (['78-801234567-4', '780123456784', '78-80123456789-4'] as $typo) {
            $payload = $this->dealerPayload([
                'states' => 'FL',
                'sales_tax_registration_number' => $typo,
            ]);

            $this->post('/register', $payload)
                ->assertRedirect()
                ->assertSessionHasErrors('sales_tax_registration_number');

            $this->assertNull(
                User::where('email', $payload['email'])->first(),
                "[{$typo}] is not a valid Florida number and must not register."
            );
        }
    }

    /**
     * Every other state prints its own format, which we cannot verify, so an
     * out-of-state dealer's number is kept as free text exactly as typed.
     */
    public function test_an_out_of_state_dealer_keeps_their_own_format(): void
    {
        foreach ([['GA', 'R-12345-6'], ['AL', 'SLS-9988776'], ['TX', '1-75-0123456-7']] as [$state, $number]) {
            $payload = $this->dealerPayload([
                'states' => $state,
                'sales_tax_registration_number' => $number,
            ]);

            $this->post('/register', $payload)->assertRedirect()->assertSessionHasNoErrors();

            $this->assertSame(
                $number,
                User::where('email', $payload['email'])->value('sales_tax_registration_number'),
                "A {$state} dealer's number must be stored exactly as typed."
            );
        }
    }

    /**
     * The Florida rule keys off the dealer's state, not off the shape of what
     * they typed, so a number that would fail in Florida is fine elsewhere.
     */
    public function test_the_florida_rule_only_applies_to_florida_dealers(): void
    {
        $payload = $this->dealerPayload([
            'states' => 'GA',
            // Twelve digits: rejected for a Florida dealer, accepted here.
            'sales_tax_registration_number' => '780123456784',
        ]);

        $this->post('/register', $payload)->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(
            '780123456784',
            User::where('email', $payload['email'])->value('sales_tax_registration_number')
        );
    }

    /**
     * A Florida dealer licence number carries a suffix, as in 1142356-1. The
     * rule was 'numeric', which rejected every real one, so no dealer could
     * register at all.
     */
    public function test_a_real_licence_number_with_a_suffix_is_accepted(): void
    {
        foreach (['1142356-1', '1101136-1', '1129986-1'] as $licence) {
            $payload = $this->dealerPayload(['license_number' => $licence]);

            $this->post('/register', $payload)->assertRedirect();

            $this->assertSame(
                $licence,
                User::where('email', $payload['email'])->value('license_number'),
                "[{$licence}] is a real Florida dealer licence number and must be accepted."
            );
        }
    }

    /**
     * Still a licence number, not free text.
     */
    public function test_a_licence_number_that_is_not_one_is_rejected(): void
    {
        foreach (['not a licence', 'VI-1142356-1', '<script>x</script>'] as $bad) {
            $payload = $this->dealerPayload(['license_number' => $bad]);

            $this->post('/register', $payload)
                ->assertRedirect()
                ->assertSessionHasErrors('license_number');

            $this->assertNull(User::where('email', $payload['email'])->first());
        }
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
