<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserCase;
use App\Models\Vehicle;
use App\Services\PDFGeneratorService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The 82040 is filled by stamping the official PDF with pdftk, so a broken
 * field name or a flatten() regression produces a valid-looking but blank
 * form. These tests read the text back out of the generated PDF to prove the
 * data actually landed on the page.
 */
class HSMV82040GenerationTest extends TestCase
{
    use DatabaseTransactions;

    private const VIN = '1HGCV1F30LA123456';

    /**
     * Every Section 3 TYPE box on the 82040, by its exact PDF field name.
     */
    private const TYPE_BOXES = [
        'Rebuilt',
        'Assembled from Parts',
        'Kit Car',
        'Custom',
        'Street Rod',
        'Replica',
    ];

    private function makeCaseWithVehicle(User $customer, array $vehicleOverrides = []): UserCase
    {
        $case = UserCase::create([
            'user_id' => $customer->id,
            'case_number' => 'RR-TEST-' . uniqid(),
            'status' => 'pending_documents',
            'service_type' => 'rebuilt_title',
            'inspection_fee' => 150.00,
            'service_fee' => 100.00,
            'total_amount' => 250.00,
        ]);

        Vehicle::create(array_merge([
            'user_case_id' => $case->id,
            'vin' => self::VIN,
            'title_state' => 'FL',
            'title_brand' => 'salvage',
            'year' => 2020,
            'make' => 'Honda',
            'model' => 'Accord',
            'body_type' => 'Sedan',
            'color' => 'Blue',
        ], $vehicleOverrides));

        return $case->fresh();
    }

    /**
     * The exact field => value set the generator hands to pdftk for a case.
     *
     * flatten() bakes checkbox marks in as page graphics, so a ticked box
     * leaves no trace in the PDF's text layer and cannot be asserted with
     * pdftotext. This reads the values at the last point they are still
     * inspectable, going through the real config file, the real
     * prepareFormData() and the real mapFields().
     */
    private function stampedValues(UserCase $case): array
    {
        $service = app(PDFGeneratorService::class);

        $prepare = new \ReflectionMethod($service, 'prepareFormData');
        $map = new \ReflectionMethod($service, 'mapFields');
        $prepare->setAccessible(true);
        $map->setAccessible(true);

        return $map->invoke(
            $service,
            config('hsmv_forms.hsmv_82040.fields'),
            $prepare->invoke($service, $case)
        );
    }

    /**
     * Which of the Section 3 TYPE boxes come out ticked.
     */
    private function tickedTypeBoxes(UserCase $case): array
    {
        $values = $this->stampedValues($case);

        return array_values(array_filter(
            self::TYPE_BOXES,
            fn (string $box) => ($values[$box] ?? 'Off') !== 'Off'
        ));
    }

    /**
     * Read the text layer back out of a generated PDF.
     */
    private function textOf(string $storagePath): string
    {
        $absolute = Storage::disk('private')->path($storagePath);

        return (string) shell_exec('pdftotext ' . escapeshellarg($absolute) . ' -');
    }

    public function test_generated_82040_contains_vin_and_owner_name(): void
    {
        Storage::fake('private');

        $customer = User::factory()->create([
            'first_name' => 'Kelvyn',
            'last_name' => 'Quiroz',
        ]);

        $path = app(PDFGeneratorService::class)
            ->generateHSMV82040($this->makeCaseWithVehicle($customer));

        Storage::disk('private')->assertExists($path);

        $text = $this->textOf($path);

        $this->assertStringContainsString(self::VIN, $text);
        $this->assertStringContainsString('Kelvyn Quiroz', $text);
        $this->assertStringContainsString('Honda', $text);
    }

    public function test_generated_82040_is_flattened(): void
    {
        Storage::fake('private');

        $customer = User::factory()->create();

        $path = app(PDFGeneratorService::class)
            ->generateHSMV82040($this->makeCaseWithVehicle($customer));

        $absolute = Storage::disk('private')->path($path);
        $fields = (string) shell_exec(
            'pdftk ' . escapeshellarg($absolute) . ' dump_data_fields 2>/dev/null'
        );

        // A flattened PDF has no form fields left; if any survive, the values
        // are still editable and may not print.
        $this->assertStringNotContainsString('FieldName:', $fields);
    }

    #[DataProvider('vehicleTypeBoxes')]
    public function test_vehicle_type_ticks_exactly_one_section_3_box(
        string $vehicleType,
        ?string $expectedBox
    ): void {
        Storage::fake('private');

        $case = $this->makeCaseWithVehicle(
            User::factory()->create(),
            ['vehicle_type' => $vehicleType]
        );

        $this->assertSame(
            $expectedBox === null ? [] : [$expectedBox],
            $this->tickedTypeBoxes($case),
            "vehicle_type '{$vehicleType}' ticked the wrong Section 3 boxes"
        );

        // The values above are what gets stamped, so also prove the document
        // itself still builds with each type.
        Storage::disk('private')->assertExists(
            app(PDFGeneratorService::class)->generateHSMV82040($case)
        );
    }

    public static function vehicleTypeBoxes(): array
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

    public function test_missing_vehicle_type_falls_back_to_rebuilt(): void
    {
        $case = $this->makeCaseWithVehicle(
            User::factory()->create(),
            ['vehicle_type' => null]
        );

        $this->assertSame(['Rebuilt'], $this->tickedTypeBoxes($case));
    }

    public function test_flood_brand_is_independent_of_vehicle_type(): void
    {
        $case = $this->makeCaseWithVehicle(
            User::factory()->create(),
            ['vehicle_type' => 'aspt', 'title_brand' => 'flood']
        );

        $values = $this->stampedValues($case);

        // The brand box comes from title_brand, the type box from
        // vehicle_type, so a flood-branded ASPT build ticks both.
        $this->assertSame('On', $values['Flood']);
        $this->assertSame(['Assembled from Parts'], $this->tickedTypeBoxes($case));
    }

    public function test_salvage_brand_does_not_tick_the_flood_box(): void
    {
        $case = $this->makeCaseWithVehicle(
            User::factory()->create(),
            ['title_brand' => 'salvage']
        );

        $this->assertSame('Off', $this->stampedValues($case)['Flood']);
    }

    /**
     * Application Type, confirmed by the client 5 Aug 2026: a Florida title is
     * an Original application, an out-of-state title is a Transfer. The title
     * number plays no part.
     *
     * @param  array<string, mixed>  $vehicle
     */
    #[DataProvider('applicationTypes')]
    public function test_title_state_decides_application_type(
        array $vehicle,
        string $original,
        string $transfer
    ): void {
        $case = $this->makeCaseWithVehicle(User::factory()->create(), $vehicle);

        $values = $this->stampedValues($case);

        $this->assertSame($original, $values['Original']);
        $this->assertSame($transfer, $values['Transfer']);
    }

    public static function applicationTypes(): array
    {
        return [
            'florida title is original' => [
                ['title_state' => 'FL'],
                'On',
                'Off',
            ],
            // vehicles.title_state is varchar(2), so an out-of-state title is
            // always a two-letter code.
            'out of state title is transfer' => [
                ['title_state' => 'GA'],
                'Off',
                'On',
            ],
            'another out of state title is transfer' => [
                ['title_state' => 'NY'],
                'Off',
                'On',
            ],
            // The title number is not part of the rule.
            'florida title with a title number is still original' => [
                ['title_state' => 'FL', 'title_number' => '0123456789'],
                'On',
                'Off',
            ],
            'out of state title with a title number is still transfer' => [
                ['title_state' => 'GA', 'title_number' => '0123456789'],
                'Off',
                'On',
            ],
        ];
    }

    /**
     * vehicles.title_state is NOT NULL, so the only way to reach the generator
     * without a title state is a case that has no vehicle attached at all.
     * Neither box should be guessed at in that situation.
     */
    public function test_case_without_a_vehicle_ticks_neither_application_type(): void
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

        $values = $this->stampedValues($case);

        $this->assertSame('Off', $values['Original']);
        $this->assertSame('Off', $values['Transfer']);
    }

    public function test_unmapped_form_throws_instead_of_producing_a_blank_pdf(): void
    {
        Storage::fake('private');

        $customer = User::factory()->create();
        $case = $this->makeCaseWithVehicle($customer);

        $this->expectException(\RuntimeException::class);

        app(PDFGeneratorService::class)->generateHSMV82050($case);
    }
}
