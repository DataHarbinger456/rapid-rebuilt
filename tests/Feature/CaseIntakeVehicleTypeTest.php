<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserCase;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\ReadsPdfFormFields;
use Tests\TestCase;

/**
 * Vehicle type is collected at intake and decides which TYPE box is checked in
 * Section 3 of the HSMV 82040. These tests follow it from the submitted form
 * through to the values stamped onto the PDF.
 */
class CaseIntakeVehicleTypeTest extends TestCase
{
    use DatabaseTransactions;
    use ReadsPdfFormFields;

    protected function formKey(): string
    {
        return 'hsmv_82040';
    }

    /**
     * A complete, valid case submission.
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'vin' => '1HGCV1F30LA123456',
            'year' => 2020,
            'make' => 'Honda',
            'model' => 'Accord',
            'body_style' => 'Sedan',
            'color' => 'blue',
            'odometer' => 48200,
            'title_state' => 'FL',
            'vehicle_type' => 'rebuilt',
            'components' => [
                ['type' => 'hood', 'source' => 'used', 'source_vin' => '2HGFB2F59DH512345'],
            ],
        ], $overrides);
    }

    /**
     * Which Section 3 TYPE boxes the generator would stamp for a case.
     */
    private function tickedTypeBoxes(UserCase $case): array
    {
        $values = $this->stampedValues($case);

        return array_values(array_filter(
            ['Rebuilt', 'Assembled from Parts', 'Kit Car', 'Custom', 'Street Rod', 'Replica'],
            fn (string $box) => ($values[$box] ?? 'Off') !== 'Off'
        ));
    }

    #[DataProvider('submittedTypes')]
    public function test_submitted_vehicle_type_reaches_the_82040(
        string $submitted,
        ?string $expectedBox
    ): void {
        Storage::fake('private');

        $customer = User::factory()->create();

        $response = $this->actingAs($customer)->post(
            route('customer.cases.store'),
            $this->payload(['vehicle_type' => $submitted])
        );

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $vehicle = Vehicle::whereRelation('userCase', 'user_id', $customer->id)->firstOrFail();

        $this->assertSame($submitted, $vehicle->vehicle_type);

        $this->assertSame(
            $expectedBox === null ? [] : [$expectedBox],
            $this->tickedTypeBoxes($vehicle->userCase),
            "Submitting '{$submitted}' checked the wrong Section 3 box"
        );
    }

    public static function submittedTypes(): array
    {
        return [
            'rebuilt' => ['rebuilt', 'Rebuilt'],
            'aspt' => ['aspt', 'Assembled from Parts'],
            'kit car' => ['kit_car', 'Kit Car'],
            'custom vehicle' => ['custom_vehicle', 'Custom'],
            'street rod' => ['street_rod', 'Street Rod'],
            'replica' => ['replica', 'Replica'],
            // Neither has a box of its own in Section 3.
            'mobile home' => ['mobile_home', null],
            'other' => ['other', null],
        ];
    }

    public function test_vehicle_type_outside_the_enum_is_rejected(): void
    {
        Storage::fake('private');

        $customer = User::factory()->create();

        $response = $this->actingAs($customer)->post(
            route('customer.cases.store'),
            $this->payload(['vehicle_type' => 'spaceship'])
        );

        $response->assertSessionHasErrors('vehicle_type');
        $this->assertDatabaseMissing('user_cases', ['user_id' => $customer->id]);
    }

    public function test_vehicle_type_is_required(): void
    {
        Storage::fake('private');

        $customer = User::factory()->create();
        $payload = $this->payload();
        unset($payload['vehicle_type']);

        $response = $this->actingAs($customer)->post(
            route('customer.cases.store'),
            $payload
        );

        $response->assertSessionHasErrors('vehicle_type');
        $this->assertDatabaseMissing('user_cases', ['user_id' => $customer->id]);
    }

    /**
     * Isolate the vehicle_type <select> from the rest of the page.
     */
    private function vehicleTypeSelect(string $html): string
    {
        preg_match('/<select name="vehicle_type".*?<\/select>/s', $html, $matches);

        return $matches[0] ?? '';
    }

    public function test_create_form_offers_every_enum_value_and_defaults_to_rebuilt(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get(route('customer.cases.create'));

        $response->assertOk();

        foreach (Vehicle::VEHICLE_TYPES as $value => $label) {
            $response->assertSee('value="' . $value . '"', false);
            $response->assertSee($label, false);
        }

        // Only the rebuilt option carries the selected attribute. Matched
        // loosely so reformatting the Blade template does not break the test.
        $this->assertMatchesRegularExpression(
            '/value="rebuilt"\s+selected/',
            $response->getContent()
        );
        $this->assertSame(
            1,
            preg_match_all('/\bselected\b/', $this->vehicleTypeSelect($response->getContent()))
        );
    }

    /**
     * Out-of-state titles were previously a dead end: the form offered a single
     * "Other State" option worth 5 characters, which failed a size:2 rule and
     * would not have fitted the varchar(2) column either. Those customers are
     * exactly the ones whose 82040 needs Transfer ticked.
     */
    public function test_out_of_state_case_submits_and_ticks_transfer(): void
    {
        Storage::fake('private');

        $customer = User::factory()->create();

        $response = $this->actingAs($customer)->post(
            route('customer.cases.store'),
            $this->payload(['title_state' => 'GA'])
        );

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $vehicle = Vehicle::whereRelation('userCase', 'user_id', $customer->id)->firstOrFail();
        $this->assertSame('GA', $vehicle->title_state);

        $values = $this->stampedValues($vehicle->userCase);
        $this->assertSame('On', $values['Transfer']);
        $this->assertSame('Off', $values['Original']);
    }

    public function test_florida_case_submits_and_ticks_original(): void
    {
        Storage::fake('private');

        $customer = User::factory()->create();

        $response = $this->actingAs($customer)->post(
            route('customer.cases.store'),
            $this->payload(['title_state' => 'FL'])
        );

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $vehicle = Vehicle::whereRelation('userCase', 'user_id', $customer->id)->firstOrFail();
        $this->assertSame('FL', $vehicle->title_state);

        $values = $this->stampedValues($vehicle->userCase);
        $this->assertSame('On', $values['Original']);
        $this->assertSame('Off', $values['Transfer']);
    }

    public function test_title_state_outside_the_list_is_rejected(): void
    {
        Storage::fake('private');

        $customer = User::factory()->create();

        $response = $this->actingAs($customer)->post(
            route('customer.cases.store'),
            $this->payload(['title_state' => 'ZZ'])
        );

        $response->assertSessionHasErrors('title_state');
        $this->assertDatabaseMissing('user_cases', ['user_id' => $customer->id]);
    }

    /**
     * The old "other" sentinel is no longer a valid state code. Guarding
     * against it stops the dead end being reintroduced.
     */
    public function test_legacy_other_state_value_is_rejected(): void
    {
        Storage::fake('private');

        $customer = User::factory()->create();

        $response = $this->actingAs($customer)->post(
            route('customer.cases.store'),
            $this->payload(['title_state' => 'other'])
        );

        $response->assertSessionHasErrors('title_state');
        $this->assertDatabaseMissing('user_cases', ['user_id' => $customer->id]);
    }

    public function test_create_form_lists_every_state_with_florida_first(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get(route('customer.cases.create'));

        $response->assertOk();

        preg_match('/<select name="title_state".*?<\/select>/s', $response->getContent(), $matches);
        $select = $matches[0] ?? '';

        preg_match_all('/value="([A-Z]{2})"/', $select, $codes);

        $this->assertSame(array_keys(Vehicle::US_STATES), $codes[1]);
        $this->assertSame('FL', $codes[1][0]);
        $this->assertCount(51, $codes[1]);
        $this->assertStringNotContainsString('value="other"', $select);
    }
}
