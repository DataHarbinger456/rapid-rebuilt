<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserCase;
use App\Models\Vehicle;
use App\Services\PDFGeneratorService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\ReadsPdfFormFields;
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
    use ReadsPdfFormFields;

    protected function formKey(): string
    {
        return 'hsmv_82040';
    }

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
     *
     * flatten() bakes checkbox marks in as page graphics, so a ticked box
     * leaves no trace in the text layer and cannot be asserted this way. Use
     * stampedValues() from ReadsPdfFormFields for those.
     */
    private function textOf(string $storagePath): string
    {
        return $this->textOfStoredPdf($storagePath);
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

    /**
     * Section 6 mileage boxes, most significant first. A box the generator
     * leaves untouched prints blank.
     */
    private const MILEAGE_BOXES = [
        '100, 000 Place',
        '10,000 Place',
        '1,000 Place',
        '100 Place',
        '10 Place',
        'Ones Place',
    ];

    private const DECLARATION_BOXES = [
        '1  REFLECTS ACTUAL MILEAGE',
        '2 IS NOT THE ACTUAL MILEAGE',
        '3 IS IN EXCESS OF ITS MECHANICAL LIMITS',
    ];

    /**
     * The six mileage boxes as one string, a space per blank box, so a
     * misalignment by one place shows up in the failure message.
     */
    private function mileageBoxes(UserCase $case): string
    {
        $values = $this->stampedValues($case);

        return implode('', array_map(
            fn (string $box): string => $values[$box] ?? ' ',
            self::MILEAGE_BOXES
        ));
    }

    #[DataProvider('odometerReadings')]
    public function test_odometer_reading_fills_the_digit_boxes_right_aligned(
        ?int $reading,
        string $expected
    ): void {
        $case = $this->makeCaseWithVehicle(
            User::factory()->create(),
            ['odometer_reading' => $reading]
        );

        $this->assertSame($expected, $this->mileageBoxes($case));
    }

    public static function odometerReadings(): array
    {
        return [
            // The reading from the Alabama title in docs/samples.
            'four digits' => [4527, '  4527'],
            'six digits fills every box' => [128450, '128450'],
            'internal zero is kept' => [4027, '  4027'],
            'one digit' => [7, '     7'],
            // A genuine zero still needs a printed digit, or the box looks
            // unanswered rather than answered with nought.
            'zero prints a single digit' => [0, '     0'],
            'missing reading leaves every box blank' => [null, '      '],
            // Six boxes cannot hold this. A truncation on a declaration sworn
            // under penalty of perjury is worse than a blank.
            'more than six digits leaves every box blank' => [1234567, '      '],
        ];
    }

    public function test_odometer_date_fills_the_three_date_read_boxes(): void
    {
        $case = $this->makeCaseWithVehicle(
            User::factory()->create(),
            ['odometer_date' => '2025-11-04']
        );

        $values = $this->stampedValues($case);

        // Zero padded, because each box is a fixed two- or four-character space
        // on the form.
        $this->assertSame('11', $values['Date Read MM']);
        $this->assertSame('04', $values['Date Read DD']);
        $this->assertSame('2025', $values['Date Read YYYY']);
    }

    public function test_a_title_with_no_odometer_date_leaves_the_date_read_boxes_blank(): void
    {
        // Alabama titles carry no odometer date. Staff reject the field rather
        // than let a model supply one, so it arrives here null.
        $case = $this->makeCaseWithVehicle(
            User::factory()->create(),
            ['odometer_date' => null]
        );

        $values = $this->stampedValues($case);

        $this->assertArrayNotHasKey('Date Read MM', $values);
        $this->assertArrayNotHasKey('Date Read DD', $values);
        $this->assertArrayNotHasKey('Date Read YYYY', $values);
    }

    #[DataProvider('odometerStatuses')]
    public function test_odometer_status_ticks_exactly_one_declaration_box(
        ?string $status,
        ?string $expectedBox
    ): void {
        $case = $this->makeCaseWithVehicle(
            User::factory()->create(),
            ['odometer_status' => $status]
        );

        $values = $this->stampedValues($case);

        $ticked = array_values(array_filter(
            self::DECLARATION_BOXES,
            fn (string $box): bool => ($values[$box] ?? 'Off') !== 'Off'
        ));

        $this->assertSame(
            $expectedBox === null ? [] : [$expectedBox],
            $ticked,
            "odometer_status '" . ($status ?? 'null') . "' ticked the wrong declaration box"
        );
    }

    public static function odometerStatuses(): array
    {
        return [
            'actual' => ['actual', '1  REFLECTS ACTUAL MILEAGE'],
            'not actual' => ['not_actual', '2 IS NOT THE ACTUAL MILEAGE'],
            'exceeds limits' => ['exceeds_limits', '3 IS IN EXCESS OF ITS MECHANICAL LIMITS'],
            // Sworn under penalty of perjury, so an unknown status ticks
            // nothing and staff complete it by hand.
            'unknown' => [null, null],
        ];
    }

    public function test_model_lands_in_its_own_box(): void
    {
        $case = $this->makeCaseWithVehicle(
            User::factory()->create(),
            ['model' => 'ACCORD SEDAN TOURING']
        );

        $this->assertSame('ACCORD SEDAN TOURING', $this->stampedValues($case)['Model']);
    }

    public function test_previous_state_of_issue_falls_back_to_the_issuing_state(): void
    {
        // The usual case: the title names no earlier state, so the state that
        // issued the surrendered title is the answer.
        $case = $this->makeCaseWithVehicle(
            User::factory()->create(),
            ['title_state' => 'AL', 'previous_state' => null]
        );

        $this->assertSame('AL', $this->stampedValues($case)['Previous State of Issue']);
    }

    public function test_an_explicit_previous_state_wins_over_the_issuing_state(): void
    {
        $case = $this->makeCaseWithVehicle(
            User::factory()->create(),
            ['title_state' => 'GA', 'previous_state' => 'TX']
        );

        $this->assertSame('TX', $this->stampedValues($case)['Previous State of Issue']);
    }

    /**
     * Pinned deliberately, because it is the arguable case: a Florida title is
     * an Original application with no earlier jurisdiction, yet this still
     * prints FL. Awaiting the client's answer on whether the box should be
     * blank for an Original — if it should, this test is the one to change.
     */
    public function test_a_florida_title_still_prints_fl_as_previous_state(): void
    {
        $case = $this->makeCaseWithVehicle(
            User::factory()->create(),
            ['title_state' => 'FL', 'previous_state' => null]
        );

        $values = $this->stampedValues($case);

        $this->assertSame('On', $values['Original']);
        $this->assertSame('FL', $values['Previous State of Issue']);
    }

    /**
     * The digit-count boxes are not answered by the title, but they are
     * answered by the model year: five-digit odometers were gone from US
     * production well before the mid 1990s.
     *
     * @param  int  $year  vehicle model year
     * @param  bool  $expectSixDigit  whether the 6-digit box should be ticked
     */
    #[DataProvider('odometerDigitCountYears')]
    public function test_the_digit_count_box_follows_the_model_year(
        int $year,
        bool $expectSixDigit
    ): void {
        $case = $this->makeCaseWithVehicle(User::factory()->create(), ['year' => $year]);

        $values = $this->stampedValues($case);

        if ($expectSixDigit) {
            $this->assertSame('On', $values['6-digit odometer now reads']);
        } else {
            $this->assertNotSame('On', $values['6-digit odometer now reads'] ?? 'Off');
        }

        // Never ticked automatically. It is only right for genuinely old
        // vehicles, where the year alone cannot justify a sworn statement.
        $this->assertArrayNotHasKey('5-digit odometer now reads', $values);
    }

    public static function odometerDigitCountYears(): array
    {
        return [
            'a 2020 vehicle is six digit' => [2020, true],
            'the 1995 cutoff is inclusive' => [1995, true],
            'a 1994 vehicle is left blank' => [1994, false],
            'a 1990 vehicle is left blank' => [1990, false],
        ];
    }

    /**
     * vehicles.year is NOT NULL, so an unknown year in practice means a case
     * with no vehicle record at all. A blank is a question for staff; a wrong
     * tick is a false statement on a declaration sworn under penalty of
     * perjury.
     */
    public function test_a_case_with_no_vehicle_ticks_neither_digit_box(): void
    {
        $customer = User::factory()->create();

        $case = UserCase::create([
            'user_id' => $customer->id,
            'case_number' => 'RR-TEST-' . uniqid(),
            'status' => 'pending_documents',
            'service_type' => 'rebuilt_title',
            'inspection_fee' => 150.00,
            'service_fee' => 100.00,
            'total_amount' => 250.00,
        ]);

        $values = $this->stampedValues($case);

        $this->assertNotSame('On', $values['6-digit odometer now reads'] ?? 'Off');
        $this->assertArrayNotHasKey('5-digit odometer now reads', $values);
    }

    public function test_section_6_survives_into_the_generated_pdf(): void
    {
        Storage::fake('private');

        $case = $this->makeCaseWithVehicle(User::factory()->create(), [
            'model' => 'ACCORD SEDAN TOURING',
            'title_state' => 'AL',
            'odometer_reading' => 128450,
            'odometer_date' => '2025-11-04',
            'odometer_status' => 'actual',
        ]);

        $path = app(PDFGeneratorService::class)->generateHSMV82040($case);
        $text = $this->textOf($path);

        // Checkbox marks flatten into page graphics and leave no text, so only
        // the typed values are assertable here.
        $this->assertStringContainsString('ACCORD SEDAN TOURING', $text);

        $layout = $this->textOfStoredPdf($path, layout: true);

        // The six mileage boxes sit at slightly different heights, so pdftotext
        // emits them out of order and across lines. Scope to the Section 6
        // sentence and compare digits as a multiset: that still proves all six
        // landed in the odometer area rather than anywhere else on the page.
        //
        // Anchored just past the form's own "5 or 6-digit" label. Anchoring
        // earlier drags that label's literal 5 and 6 into the comparison;
        // anchoring on "odometer now reads" misses the first mileage digit,
        // because a ticked digit-count box is emitted before that phrase and
        // pushes the digit onto the line above.
        $this->assertSame(
            $this->sortedDigits('128450'),
            $this->sortedDigitsIn('/5 or .{0,4}6-digit(.*?)\.xx miles/s', $layout),
            'The mileage digits did not land in Section 6.'
        );

        // The three date boxes stagger across lines for the same reason, so
        // scope from the Date Read label to the end of Section 6 rather than to
        // the end of its line.
        $this->assertSame(
            $this->sortedDigits('11' . '04' . '2025'),
            $this->sortedDigitsIn('/Date Read:(.*?)I\/we hereby certify/s', $layout),
            'The odometer date did not land next to Date Read.'
        );
    }

    /**
     * @return array<int, string>
     */
    private function sortedDigits(string $value): array
    {
        $digits = str_split(preg_replace('/[^0-9]/', '', $value));
        sort($digits);

        return $digits;
    }

    /**
     * The digits inside the first capture group of $pattern, sorted.
     *
     * @return array<int, string>
     */
    private function sortedDigitsIn(string $pattern, string $subject): array
    {
        $this->assertMatchesRegularExpression($pattern, $subject);
        preg_match($pattern, $subject, $matches);

        return $this->sortedDigits($matches[1]);
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
