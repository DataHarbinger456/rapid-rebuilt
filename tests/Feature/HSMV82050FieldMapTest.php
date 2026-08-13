<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserCase;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\ReadsPdfFormFields;
use Tests\TestCase;

/**
 * The 82050 field map, read back out of real filled documents.
 *
 * This form needs more defending than the others because its field names are
 * auto-generated and most of them lie: the six odometer digit boxes are called
 * Text1, 'Federal and State law requires...', undefined_2, 'Failure to',
 * undefined_3 and 'xx', in that page order. Nothing in those names says which
 * decimal place each one is.
 *
 * The mapping was derived from widget positions, and the digit test below is
 * the regression guard for that reasoning: it is the only thing standing
 * between a correct form and one that swears a vehicle has done 654,321 miles.
 */
class HSMV82050FieldMapTest extends TestCase
{
    use DatabaseTransactions;
    use ReadsPdfFormFields;

    protected function formKey(): string
    {
        return 'hsmv_82050';
    }

    /**
     * The six odometer digit boxes in page order, left to right.
     *
     * Deliberately spelled out rather than read from the config: if someone
     * reorders the mapping, this list is what disagrees with them.
     */
    private const DIGIT_BOXES_LEFT_TO_RIGHT = [
        'Text1',
        'Federal and State law requires that you state the mileage in connection with the transfer of ownership',
        'undefined_2',
        'Failure to',
        'undefined_3',
        'xx',
    ];

    /**
     * The three date boxes in page order: month, day, year.
     */
    private const DATE_BOXES_LEFT_TO_RIGHT = [
        'undefined_4',
        'undefined_5',
        'AND WE HEREBY CERTIFY THAT TO THE BEST OF OUR KNOWLEDGE',
    ];

    /**
     * The eight fields held back until the client says who signs this form.
     *
     * The first two carry a double space inside the name, which is in the PDF
     * and must survive copy and paste.
     */
    private const SELLER_PURCHASER_FIELDS = [
        'Print Names of Purchasers',
        'Address City State Zip Code',
        'Sellers Printed Name',
        'Date',
        'Sellers Address City State Zip Code',
        'Purchasers Printed Name',
    ];

    private const MODE_BUTTONS = [
        'Notice of Sale Seller must complete sections 1  3 The purchasers signature in section 3 is optional',
        'Bill of Sale Seller and purchaser must complete sections 1 2 when applicable  3',
    ];

    private function fullyPopulatedCase(array $vehicleOverrides = []): UserCase
    {
        // Several tests build two cases to compare them, so the email cannot be
        // a fixed string: users.email is unique.
        $customer = User::factory()->create([
            'first_name' => 'Kelvyn',
            'last_name' => 'Quiroz',
            'email' => 'kelvyn.quiroz+' . uniqid() . '@example.test',
            'phone' => '3055550142',
            'address_1' => '1841 NW 21st Terrace',
            'city' => 'Miami',
            'states' => 'FL',
            'zip' => '33142',
            'county' => 'Miami-Dade',
        ]);

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
            'vin' => '1HGCV1F30LA123456',
            'title_state' => 'FL',
            'title_number' => '0123456789',
            'title_brand' => 'salvage',
            'vehicle_type' => 'rebuilt',
            'year' => 2020,
            'make' => 'Honda',
            'model' => 'Accord',
            'body_type' => 'Sedan',
            'color' => 'Blue',
            'odometer_reading' => 123456,
            'odometer_date' => '2025-03-09',
            'odometer_status' => 'actual',
        ], $vehicleOverrides));

        return $case->fresh();
    }

    /**
     * A name in the map that is not in the document is silently dropped by
     * pdftk, printing a blank box while every value assertion still passes.
     *
     * Whitespace is the specific risk on this form: two of its field names
     * contain a double space, and a name retyped by hand rather than copied
     * would collapse it and miss.
     */
    public function test_every_mapped_field_name_exists_verbatim_in_the_template(): void
    {
        $realNames = $this->fieldNamesInTemplate();

        $this->assertNotEmpty($realNames, 'Could not read any field names from the 82050 template.');

        foreach (array_keys($this->mappedFields()) as $mapped) {
            $this->assertContains(
                $mapped,
                $realNames,
                "The map names [{$mapped}], which does not exist in 82050.pdf. "
                . 'pdftk will ignore it and the box will print blank. Check whitespace: '
                . 'these names are auto-generated and some contain a double space.'
            );
        }
    }

    /**
     * Whitespace inside a mapped name must match the template byte for byte.
     *
     * assertContains above would already catch this, but only with a message
     * about a missing field. This one says what actually went wrong.
     */
    public function test_mapped_names_preserve_their_exact_whitespace(): void
    {
        $realNames = $this->fieldNamesInTemplate();
        $collapsed = [];

        foreach ($realNames as $name) {
            $collapsed[preg_replace('/\s+/', ' ', $name)] = $name;
        }

        foreach (array_keys($this->mappedFields()) as $mapped) {
            $canonical = $collapsed[preg_replace('/\s+/', ' ', $mapped)] ?? null;

            if ($canonical === null) {
                continue; // Not a whitespace problem; the test above owns it.
            }

            $this->assertSame(
                $canonical,
                $mapped,
                "The map names [{$mapped}] but the template spells it [{$canonical}]. "
                . 'The difference is whitespace only, so copy the name from the dump.'
            );
        }
    }

    /**
     * THE REGRESSION GUARD FOR THE POSITION DECODING.
     *
     * Odometer 123456 puts a different digit in every box, so any reordering of
     * the six mappings shows up as a scrambled number rather than passing by
     * coincidence. Reading left to right across the page must spell the reading
     * exactly, most significant digit first.
     *
     * The mapping was derived by measuring widget rectangles: all six boxes sit
     * on one row, and the gap between the third and fourth is 11pt where every
     * other gap is 6pt, because that is where the form prints the thousands
     * comma. Nothing in the field names themselves says any of this.
     */
    public function test_the_six_digit_boxes_read_left_to_right_in_order(): void
    {
        $values = $this->fieldValuesInFilledPdf(
            $this->fullyPopulatedCase(['odometer_reading' => 123456])
        );

        $acrossThePage = '';

        foreach (self::DIGIT_BOXES_LEFT_TO_RIGHT as $box) {
            $this->assertArrayHasKey($box, $values, "The filled PDF has no digit box named [{$box}].");
            $acrossThePage .= $values[$box];
        }

        $this->assertSame(
            '123456',
            $acrossThePage,
            'The odometer digits do not read left to right across the page. '
            . 'The six boxes are mapped in the wrong order, which would print a '
            . 'different mileage than the vehicle has done.'
        );
    }

    /**
     * A shorter reading fills from the right, leaving the leading boxes blank
     * rather than zero-padded, which is how the form is filled by hand.
     */
    public function test_a_shorter_reading_right_aligns_and_leaves_leading_boxes_blank(): void
    {
        $values = $this->fieldValuesInFilledPdf(
            $this->fullyPopulatedCase(['odometer_reading' => 4527])
        );

        $perBox = array_map(
            fn (string $box): string => $values[$box] ?? '',
            self::DIGIT_BOXES_LEFT_TO_RIGHT
        );

        $this->assertSame(['', '', '4', '5', '2', '7'], $perBox);
    }

    /**
     * Month, day, year, in that order across the page.
     *
     * 9 March is the point of the fixture: a day and month that cannot be
     * confused, so a transposed mapping cannot pass.
     */
    public function test_the_date_boxes_read_month_day_year(): void
    {
        $values = $this->fieldValuesInFilledPdf(
            $this->fullyPopulatedCase(['odometer_date' => '2025-03-09'])
        );

        $perBox = array_map(
            fn (string $box): string => $values[$box] ?? '',
            self::DATE_BOXES_LEFT_TO_RIGHT
        );

        $this->assertSame(
            ['03', '09', '2025'],
            $perBox,
            'The date read boxes are not month/day/year across the page.'
        );
    }

    /**
     * @return array<string, array{0: string, 1: string}> status => field that must tick
     */
    public static function odometerDeclarations(): array
    {
        return [
            'actual mileage' => ['actual', '1 REFLECTS THE ACTUAL MILEAGE'],
            'exceeds mechanical limits' => ['exceeds_limits', '2 IS IN EXCESS OF ITS MECHANICAL LIMITS'],
            'not the actual mileage' => ['not_actual', '3 IS NOT THE ACTUAL MILEAGE'],
        ];
    }

    /**
     * Exactly one declaration ticks, and it is the one whose sentence matches.
     *
     * The 82050 and the 82042 order these the same way and the 82040 swaps the
     * last two, so the mapping is by sentence rather than by leading digit.
     */
    #[DataProvider('odometerDeclarations')]
    public function test_exactly_one_odometer_declaration_ticks(string $status, string $expectedField): void
    {
        $values = $this->fieldValuesInFilledPdf(
            $this->fullyPopulatedCase(['odometer_status' => $status])
        );

        $ticked = [];

        foreach (array_column(self::odometerDeclarations(), 1) as $field) {
            $this->assertArrayHasKey($field, $values, "The filled PDF has no button named [{$field}].");

            $this->assertSame(
                $field === $expectedField ? 'On' : 'Off',
                $values[$field],
                "Odometer status [{$status}] set the wrong declaration on the 82050."
            );

            if ($values[$field] === 'On') {
                $ticked[] = $field;
            }
        }

        $this->assertCount(1, $ticked, 'The three declarations must be mutually exclusive.');
    }

    /**
     * The same status must tick differently numbered boxes on the 82040 and the
     * 82050, because the two forms number their declarations differently. This
     * is the assertion a map copied between forms would fail.
     */
    public function test_the_82040_and_82050_disagree_on_number_while_agreeing_on_meaning(): void
    {
        $on = fn (array $map, string $key): array => array_keys(array_filter(
            $map,
            fn ($m): bool => is_array($m) && ($m['checkbox'] ?? null) === $key
        ));

        $fields40 = config('hsmv_forms.hsmv_82040.fields');
        $fields50 = config('hsmv_forms.hsmv_82050.fields');

        $this->assertSame(['3 IS IN EXCESS OF ITS MECHANICAL LIMITS'], $on($fields40, 'is_odometer_exceeds_limits'));
        $this->assertSame(['2 IS IN EXCESS OF ITS MECHANICAL LIMITS'], $on($fields50, 'is_odometer_exceeds_limits'));

        $this->assertSame(['2 IS NOT THE ACTUAL MILEAGE'], $on($fields40, 'is_odometer_not_actual'));
        $this->assertSame(['3 IS NOT THE ACTUAL MILEAGE'], $on($fields50, 'is_odometer_not_actual'));
    }

    /**
     * The 82050 is completed by the seller, and whether our customer is the
     * seller or the purchaser is still an open question with the client. Until
     * it is answered, every field that turns on the answer stays blank rather
     * than carrying a guess onto a document signed under penalty of perjury.
     */
    public function test_the_seller_and_purchaser_fields_stay_blank_pending_the_answer(): void
    {
        $values = $this->fieldValuesInFilledPdf($this->fullyPopulatedCase());

        foreach (self::SELLER_PURCHASER_FIELDS as $field) {
            $this->assertSame(
                '',
                $values[$field] ?? '',
                "[{$field}] turns on who signs the 82050 and must stay blank until the client answers."
            );
        }

        foreach (self::MODE_BUTTONS as $button) {
            // An unmapped button is never written, so pdftk reports no value
            // rather than 'Off'. Both print as an empty box.
            $this->assertContains(
                $values[$button] ?? '',
                ['', 'Off'],
                "[{$button}] selects Notice of Sale or Bill of Sale, which depends on who signs."
            );
        }
    }

    /**
     * The customer's name must not reach the form by any route while the
     * question is open, including the boxes we would fill under one reading.
     */
    public function test_the_customer_name_appears_nowhere_on_the_unanswered_form(): void
    {
        $values = $this->fieldValuesInFilledPdf($this->fullyPopulatedCase());

        foreach ($values as $field => $value) {
            $this->assertStringNotContainsStringIgnoringCase(
                'Quiroz',
                $value,
                "[{$field}] carries the customer's name, but who signs the 82050 is still unconfirmed."
            );
        }
    }

    /**
     * The vehicle description and title number, which do not depend on the
     * open question.
     *
     * @return array<string, array{0: string, 1: string}> label => [pdf field, expected]
     */
    public static function vehicleFields(): array
    {
        return [
            'year' => ['Year', '2020'],
            'make' => ['MakeManufacturer', 'Honda'],
            'body type' => ['Body Type', 'Sedan'],
            'model' => ['Model', 'Accord'],
            'color' => ['Color', 'Blue'],
            'vin' => ['VehicleVessel Identification Number', '1HGCV1F30LA123456'],

            // Decoded by position: leftmost box of the three-box row whose
            // printed labels are title number, issue date and VIN.
            'certificate of title number' => [
                'Iwe do hereby sell or have sold and delivered the above described motor vehicle mobile home',
                '0123456789',
            ],
        ];
    }

    #[DataProvider('vehicleFields')]
    public function test_each_vehicle_field_lands_in_its_own_box(string $field, string $expected): void
    {
        $values = $this->fieldValuesInFilledPdf($this->fullyPopulatedCase());

        $this->assertArrayHasKey($field, $values, "The filled PDF has no field named [{$field}].");
        $this->assertSame($expected, $values[$field]);
    }

    /**
     * Unlike the 82040's Florida Title Number box, this one is not
     * state-qualified, so an out-of-state title number belongs in it.
     */
    public function test_an_out_of_state_title_number_still_fills_this_box(): void
    {
        $values = $this->fieldValuesInFilledPdf($this->fullyPopulatedCase([
            'title_state' => 'GA',
            'title_number' => 'GA987654321',
        ]));

        $this->assertSame(
            'GA987654321',
            $values['Iwe do hereby sell or have sold and delivered the above described motor vehicle mobile home']
        );
    }

    /**
     * The digit-count boxes follow the same rule as the other two forms: the
     * 6-digit box is ticked from 1995, and the 5-digit box is never ticked
     * automatically because only a human can swear to what an old odometer
     * physically displays.
     */
    public function test_the_digit_count_boxes_follow_the_same_rule_as_the_other_forms(): void
    {
        $modern = $this->fieldValuesInFilledPdf($this->fullyPopulatedCase(['year' => 2020]));

        $this->assertSame('On', $modern['6 DIGIT ODOMETER NOW READS']);
        $this->assertContains($modern['5 DIGIT OR'] ?? '', ['', 'Off']);

        $old = $this->fieldValuesInFilledPdf($this->fullyPopulatedCase(['year' => 1980]));

        $this->assertSame('Off', $old['6 DIGIT ODOMETER NOW READS']);
        $this->assertContains($old['5 DIGIT OR'] ?? '', ['', 'Off']);
    }

    /**
     * Every mapped button on this form exports 'On', unlike the 82042's
     * certifier buttons which export 'Yes'. checkboxValue() defaults to 'On',
     * so this pins the assumption the mappings rely on.
     */
    public function test_every_mapped_button_exports_on(): void
    {
        $dump = $this->dumpFormFields($this->formTemplatePath());

        foreach ($this->mappedFields() as $field => $mapping) {
            if (!is_array($mapping) || !isset($mapping['checkbox'])) {
                continue;
            }

            $options = [];

            foreach (preg_split('/\n(?=---)/', $dump) as $block) {
                if (!preg_match('/^FieldName: (.*)$/m', $block, $name) || trim($name[1]) !== $field) {
                    continue;
                }

                preg_match_all('/^FieldStateOption: (.*)$/m', $block, $matches);
                $options = array_values(array_diff(array_map('trim', $matches[1]), ['Off']));
            }

            $this->assertContains(
                $mapping['on'],
                $options,
                "[{$field}] is mapped with ['on' => '{$mapping['on']}'], which is not an export "
                . 'value the template offers. The box would silently never tick.'
            );
        }
    }
}
