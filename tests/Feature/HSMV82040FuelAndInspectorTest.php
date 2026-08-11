<?php

namespace Tests\Feature;

use App\Models\Inspection;
use App\Models\User;
use App\Models\UserCase;
use App\Models\Vehicle;
use App\Services\PDFGeneratorService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Section 2 fuel boxes, the licence plate, and the Section 8 inspector block.
 *
 * The fuel group is the one that needed care. NHTSA reports a hybrid's primary
 * fuel as Gasoline, so vehicles.fuel_type can never identify one on its own,
 * and the audit's assumption that fuel_type alone lights up six boxes is wrong.
 * The decoder now also captures Fuel Type - Secondary and Electrification
 * Level into vin_decoded_data, and the fixtures below are the real values the
 * live API returns for the vehicles they name.
 */
class HSMV82040FuelAndInspectorTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Every fuel box in Sections 2 and 3, by exact PDF field name.
     */
    private const FUEL_BOXES = [
        'Natural Gas Compressed',
        'Natural Gas Liquid',
        'Hybrid GasElectric',
        'Hybrid DieselElectric',
        'Electric',
        'Electric_2',
    ];

    private function makeCase(array $vehicle = []): UserCase
    {
        $case = UserCase::create([
            'user_id' => User::factory()->create()->id,
            'case_number' => 'RR-TEST-' . uniqid(),
            'status' => 'pending_documents',
            'service_type' => 'rebuilt_title',
            'inspection_fee' => 150.00,
            'service_fee' => 100.00,
            'total_amount' => 250.00,
        ]);

        Vehicle::create(array_merge([
            'user_case_id' => $case->id,
            'vin' => '1HGCV1F30LA123456',
            'title_state' => 'FL',
            'year' => 2020,
            'make' => 'Honda',
            'model' => 'Accord',
        ], $vehicle));

        return $case->fresh();
    }

    private function stampedValues(UserCase $case): array
    {
        $service = app(PDFGeneratorService::class);

        $prepare = new ReflectionMethod($service, 'prepareFormData');
        $map = new ReflectionMethod($service, 'mapFields');
        $prepare->setAccessible(true);
        $map->setAccessible(true);

        return $map->invoke(
            $service,
            config('hsmv_forms.hsmv_82040.fields'),
            $prepare->invoke($service, $case)
        );
    }

    /**
     * @return array<int, string> the fuel boxes that came out ticked
     */
    private function tickedFuelBoxes(UserCase $case): array
    {
        $values = $this->stampedValues($case);

        return array_values(array_filter(
            self::FUEL_BOXES,
            fn (string $box): bool => ($values[$box] ?? 'Off') !== 'Off'
        ));
    }

    /**
     * Each row is what NHTSA actually returns for that vehicle, checked against
     * the live API on 11 Aug 2026.
     *
     * @return array<string, array{0: array<string, mixed>, 1: array<int, string>}>
     */
    public static function realVehicles(): array
    {
        return [
            // The case that breaks the naive mapping: primary fuel is Gasoline.
            '2018 Prius is a hybrid, not a petrol car' => [
                [
                    'fuel_type' => 'Gasoline',
                    'vin_decoded_data' => [
                        'fuel_type' => 'Gasoline',
                        'fuel_type_secondary' => 'Electric',
                        'electrification_level' => 'Strong HEV (Hybrid Electric Vehicle)',
                    ],
                ],
                ['Hybrid GasElectric'],
            ],
            '2018 Nissan Leaf is pure electric' => [
                [
                    'fuel_type' => 'Electric',
                    'vin_decoded_data' => [
                        'fuel_type' => 'Electric',
                        'fuel_type_secondary' => null,
                        'electrification_level' => 'BEV (Battery Electric Vehicle)',
                    ],
                ],
                // Both Electric boxes: the form asks the same question twice.
                ['Electric', 'Electric_2'],
            ],
            'an ordinary petrol car ticks nothing' => [
                [
                    'fuel_type' => 'Gasoline',
                    'vin_decoded_data' => [
                        'fuel_type' => 'Gasoline',
                        'fuel_type_secondary' => null,
                        'electrification_level' => null,
                    ],
                ],
                [],
            ],
            'a diesel ticks nothing' => [
                ['fuel_type' => 'Diesel'],
                [],
            ],
            'a plug-in hybrid is still a hybrid' => [
                [
                    'fuel_type' => 'Gasoline',
                    'vin_decoded_data' => [
                        'electrification_level' => 'PHEV (Plug-in Hybrid Electric Vehicle)',
                    ],
                ],
                ['Hybrid GasElectric'],
            ],
            'a diesel hybrid ticks the diesel box only' => [
                [
                    'fuel_type' => 'Diesel',
                    'vin_decoded_data' => [
                        'fuel_type_secondary' => 'Electric',
                        'electrification_level' => 'Strong HEV (Hybrid Electric Vehicle)',
                    ],
                ],
                ['Hybrid DieselElectric'],
            ],
            'compressed natural gas' => [
                ['fuel_type' => 'Compressed Natural Gas (CNG)'],
                ['Natural Gas Compressed'],
            ],
            'liquefied natural gas' => [
                ['fuel_type' => 'Liquefied Natural Gas (LNG)'],
                ['Natural Gas Liquid'],
            ],
            // Propane is a different fuel with no box of its own on this form.
            'propane has no box and ticks nothing' => [
                ['fuel_type' => 'Liquefied Petroleum Gas (propane or LPG)'],
                [],
            ],
            // A fuel cell is not a battery car and not a hybrid.
            'a fuel cell vehicle ticks nothing' => [
                [
                    'fuel_type' => 'Hydrogen',
                    'vin_decoded_data' => ['electrification_level' => 'FCEV (Fuel Cell Electric Vehicle)'],
                ],
                [],
            ],
            'an unknown fuel ticks nothing' => [
                ['fuel_type' => null],
                [],
            ],
        ];
    }

    #[DataProvider('realVehicles')]
    public function test_fuel_boxes_follow_the_decoded_vehicle(array $vehicle, array $expected): void
    {
        $this->assertSame(
            $expected,
            $this->tickedFuelBoxes($this->makeCase($vehicle)),
            'The wrong fuel boxes were ticked.'
        );
    }

    /**
     * A hybrid carries electricity but is not an electric vehicle. Ticking both
     * would put two contradictory declarations on one sworn form.
     */
    public function test_a_hybrid_never_also_ticks_the_electric_box(): void
    {
        $case = $this->makeCase([
            'fuel_type' => 'Gasoline',
            'vin_decoded_data' => [
                'fuel_type_secondary' => 'Electric',
                'electrification_level' => 'Strong HEV (Hybrid Electric Vehicle)',
            ],
        ]);

        $values = $this->stampedValues($case);

        $this->assertSame('Off', $values['Electric']);
        $this->assertSame('Off', $values['Electric_2']);
        $this->assertSame('On', $values['Hybrid GasElectric']);
    }

    /**
     * Older vehicles were decoded before the two extra variables were
     * captured, so their vin_decoded_data has neither key.
     */
    public function test_a_vehicle_decoded_before_the_new_keys_still_works(): void
    {
        $case = $this->makeCase([
            'fuel_type' => 'Electric',
            'vin_decoded_data' => ['make' => 'Nissan', 'model' => 'Leaf'],
        ]);

        $this->assertSame(['Electric', 'Electric_2'], $this->tickedFuelBoxes($case));
    }

    public function test_a_vehicle_with_no_decoded_data_at_all_still_works(): void
    {
        $case = $this->makeCase([
            'fuel_type' => 'Compressed Natural Gas (CNG)',
            'vin_decoded_data' => null,
        ]);

        $this->assertSame(['Natural Gas Compressed'], $this->tickedFuelBoxes($case));
    }

    /**
     * The column is what staff can correct by hand, so it has to win over a
     * stale decode.
     */
    public function test_a_hand_corrected_column_beats_the_decoded_payload(): void
    {
        $case = $this->makeCase([
            'fuel_type' => 'Electric',
            'vin_decoded_data' => ['fuel_type' => 'Gasoline'],
        ]);

        $this->assertSame(['Electric', 'Electric_2'], $this->tickedFuelBoxes($case));
    }

    /**
     * A case can reach the generator with no vehicle at all, and every fuel
     * question then has to answer "no" rather than fail.
     */
    public function test_a_case_with_no_vehicle_ticks_no_fuel_box(): void
    {
        $case = UserCase::create([
            'user_id' => User::factory()->create()->id,
            'case_number' => 'RR-TEST-' . uniqid(),
            'status' => 'draft',
            'service_type' => 'rebuilt_title',
            'inspection_fee' => 150.00,
            'service_fee' => 100.00,
            'total_amount' => 250.00,
        ]);

        $this->assertSame([], $this->tickedFuelBoxes($case));
    }

    public function test_license_plate_lands_in_its_box(): void
    {
        $case = $this->makeCase(['license_plate' => 'LMN4821']);

        $this->assertSame('LMN4821', $this->stampedValues($case)['License Plate Number']);
    }

    public function test_a_vehicle_with_no_plate_leaves_the_box_blank(): void
    {
        $case = $this->makeCase(['license_plate' => null]);

        $this->assertArrayNotHasKey('License Plate Number', $this->stampedValues($case));
    }

    public function test_section_8_carries_the_inspector_name_and_signing_date(): void
    {
        $case = $this->makeCase();

        $inspector = User::factory()->create([
            'first_name' => 'Marcus',
            'last_name' => 'Delgado',
            'user_type' => 'inspector',
        ]);

        Inspection::create([
            'user_case_id' => $case->id,
            'inspector_id' => $inspector->id,
            'status' => 'completed',
            'signed_at' => '2026-03-09 14:20:00',
            'completed_at' => '2026-03-09 15:00:00',
        ]);

        $values = $this->stampedValues($case->fresh());

        $this->assertSame('Marcus Delgado', $values['Name Certifying Inspector']);
        // The date the inspector signed, not the date the form was generated.
        $this->assertSame('03/09/2026', $values['Date of Inspector Signature']);
    }

    /**
     * signed_at postdates some inspections, so completed_at is the fallback.
     */
    public function test_an_inspection_without_a_signed_date_falls_back_to_completed(): void
    {
        $case = $this->makeCase();

        Inspection::create([
            'user_case_id' => $case->id,
            'inspector_id' => User::factory()->create()->id,
            'status' => 'completed',
            'signed_at' => null,
            'completed_at' => '2026-02-01 09:00:00',
        ]);

        $this->assertSame(
            '02/01/2026',
            $this->stampedValues($case->fresh())['Date of Inspector Signature']
        );
    }

    /**
     * A form generated before the inspection must not claim one happened.
     */
    public function test_a_case_with_no_inspection_leaves_section_8_blank(): void
    {
        $values = $this->stampedValues($this->makeCase());

        $this->assertArrayNotHasKey('Name Certifying Inspector', $values);
        $this->assertArrayNotHasKey('Date of Inspector Signature', $values);

        // The VIN is still restated there: it comes from the vehicle, not the
        // inspection.
        $this->assertSame('1HGCV1F30LA123456', $values['Vehicle Identification Number VIN_2']);
    }

    /**
     * An unsigned, uncompleted inspection has no date to print. Today's date
     * would be a false statement about when the VIN was verified.
     */
    public function test_an_unfinished_inspection_prints_no_date(): void
    {
        $case = $this->makeCase();

        Inspection::create([
            'user_case_id' => $case->id,
            'inspector_id' => User::factory()->create()->id,
            'status' => 'scheduled',
            'signed_at' => null,
            'completed_at' => null,
        ]);

        $this->assertArrayNotHasKey(
            'Date of Inspector Signature',
            $this->stampedValues($case->fresh())
        );
    }
}
