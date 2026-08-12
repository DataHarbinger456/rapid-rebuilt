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
 * The 82042 field map, read back out of real filled documents.
 *
 * Part A of this form asks the same questions as the 82040 and fills from the
 * same prepareFormData() keys, which is what makes it cheap and also what makes
 * it dangerous: the two forms number their odometer declarations differently,
 * so a map copied across by position would swear the wrong statement.
 *
 * Part B is unmapped pending the client's answer on certifier type, so the
 * tests here pin it as blank rather than filled.
 */
class HSMV82042FieldMapTest extends TestCase
{
    use DatabaseTransactions;
    use ReadsPdfFormFields;

    protected function formKey(): string
    {
        return 'hsmv_82042';
    }

    /**
     * Every Part B field, which must all stay blank until the client answers
     * which authority certifies their inspections.
     */
    private const PART_B_FIELDS = [
        'Date - Part B',
        'Name of Signer',
        'Commissioned Name of Florida Notary',
        'Florida Compliance Examiner/Inspector Name',
        'Badge or ID Number',
        'Law Enforcement Agency Name',
        'LEO Badge Number',
        'Florida Dealer Name',
        'Florida Dealer Number',
    ];

    /**
     * The three certifier-type buttons, which export 'Yes' rather than 'On'.
     */
    private const CERTIFIER_BUTTONS = [
        'Military officer or Law Enforcement Officer',
        'Florida Licensed Dealer',
        'Florida Compliance Examiner or Inspector DMS or Tax Collector employee',
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
            'previous_state' => 'GA',
            'title_number' => '0123456789',
            'title_brand' => 'salvage',
            'vehicle_type' => 'rebuilt',
            'year' => 2020,
            'make' => 'Honda',
            'model' => 'Accord',
            'body_type' => 'Sedan',
            'color' => 'Blue',
            'odometer_reading' => 128450,
            'odometer_date' => '2025-11-04',
            'odometer_status' => 'actual',
        ], $vehicleOverrides));

        return $case->fresh();
    }

    /**
     * A name in the map that is not in the document is silently dropped by
     * pdftk, printing a blank box while every value assertion still passes.
     */
    public function test_every_mapped_field_name_exists_in_the_official_template(): void
    {
        $realNames = $this->fieldNamesInTemplate();

        $this->assertNotEmpty($realNames, 'Could not read any field names from the 82042 template.');

        foreach (array_keys($this->mappedFields()) as $mapped) {
            $this->assertContains(
                $mapped,
                $realNames,
                "The map names [{$mapped}], which does not exist in 82042.pdf. "
                . 'pdftk will ignore it and the box will print blank.'
            );
        }
    }

    /**
     * @return array<string, array{0: string, 1: string}> label => [pdf field, expected]
     */
    public static function partAFields(): array
    {
        return [
            'vin' => ['Vehicle Identification Number', '1HGCV1F30LA123456'],
            'year' => ['Year', '2020'],
            'make' => ['Make', 'Honda'],
            'color' => ['Color', 'Blue'],
            'body type' => ['Body Type', 'Sedan'],
            'previous state' => ['Previous State Vehicle Titled In', 'GA'],

            // Odometer 128450, one box per place, most significant first.
            'hundred thousands digit' => ['Hundred thousands place that the odometer number now reads', '1'],
            'ten thousands digit' => ['Ten thousands place that the odometer number now reads', '2'],
            'thousands digit' => ['Thousands place that the odometer number now reads', '8'],
            'hundreds digit' => ['Hundreds place that the odometer number now reads', '4'],
            'tens digit' => ['Tens place that the odometer number now reads', '5'],
            'ones digit' => ['Ones place that the odometer number now reads', '0'],

            // Read 4 November 2025.
            'odometer month' => ['Two-digit month of odometer reading', '11'],
            'odometer day' => ['Two-digit day of odometer reading', '04'],
            'odometer year' => ['Four-digit year of odometer reading', '2025'],

            'owner printed name' => ['Owner/Purchaser Name', 'Kelvyn Quiroz'],
        ];
    }

    #[DataProvider('partAFields')]
    public function test_each_part_a_field_lands_in_its_own_box(string $field, string $expected): void
    {
        $values = $this->fieldValuesInFilledPdf($this->fullyPopulatedCase());

        $this->assertArrayHasKey($field, $values, "The filled PDF has no field named [{$field}].");
        $this->assertSame(
            $expected,
            $values[$field],
            "[{$field}] did not carry the expected value inside the generated PDF."
        );
    }

    /**
     * The trap this form sets.
     *
     * Each odometer status must tick the box whose SENTENCE matches, and the
     * sentence sits behind a different leading digit on each form. Asserting
     * the field name in full is the point: a map copied across by number would
     * pass a test that only checked "some box is ticked".
     *
     * @return array<string, array{0: string, 1: string}> status => 82042 field
     */
    public static function odometerDeclarations(): array
    {
        return [
            'actual mileage' => ['actual', '1. reflects ACTUAL MILEAGE'],
            'exceeds mechanical limits' => ['exceeds_limits', '2. is IN EXCESS OF ITS MECHANICAL LIMITS'],
            'not the actual mileage' => ['not_actual', '3. is NOT THE ACTUAL MILEAGE'],
        ];
    }

    #[DataProvider('odometerDeclarations')]
    public function test_the_odometer_declaration_matches_its_sentence(string $status, string $expectedField): void
    {
        $values = $this->fieldValuesInFilledPdf(
            $this->fullyPopulatedCase(['odometer_status' => $status])
        );

        $allThree = array_column(self::odometerDeclarations(), 1);

        foreach ($allThree as $field) {
            $this->assertSame(
                $field === $expectedField ? 'On' : 'Off',
                $values[$field],
                "Odometer status [{$status}] ticked the wrong declaration on the 82042."
            );
        }
    }

    /**
     * The same status, on both forms at once.
     *
     * This is the assertion that actually catches the swap: "in excess of its
     * mechanical limits" is option 3 on the 82040 and option 2 on the 82042, so
     * one status must tick two differently numbered boxes. A map that copied
     * the 82040's numbering would fail here and nowhere else.
     */
    public function test_the_two_forms_agree_on_meaning_while_disagreeing_on_number(): void
    {
        $case = $this->fullyPopulatedCase(['odometer_status' => 'exceeds_limits']);

        $on42 = $this->stampedValues($case);

        $on40 = $this->stampedValuesForForm($case, 'hsmv_82040');

        // Same meaning.
        $this->assertSame('On', $on42['2. is IN EXCESS OF ITS MECHANICAL LIMITS']);
        $this->assertSame('On', $on40['3 IS IN EXCESS OF ITS MECHANICAL LIMITS']);

        // Different number, and neither form ticks the other's box.
        $this->assertSame('Off', $on42['3. is NOT THE ACTUAL MILEAGE']);
        $this->assertSame('Off', $on40['2 IS NOT THE ACTUAL MILEAGE']);
    }

    /**
     * The 82040's stamped values, for the cross-form comparison above.
     */
    private function stampedValuesForForm(UserCase $case, string $formKey): array
    {
        $service = app(PDFGeneratorService::class);

        $prepare = new \ReflectionMethod($service, 'prepareFormData');
        $map = new \ReflectionMethod($service, 'mapFields');
        $prepare->setAccessible(true);
        $map->setAccessible(true);

        return $map->invoke(
            $service,
            config("hsmv_forms.{$formKey}.fields"),
            $prepare->invoke($service, $case)
        );
    }

    /**
     * The five-digit box is never ticked automatically, on either form: only a
     * human can swear to what an old odometer physically displays.
     */
    public function test_the_digit_count_boxes_follow_the_same_rule_as_the_82040(): void
    {
        $modern = $this->fieldValuesInFilledPdf($this->fullyPopulatedCase(['year' => 2020]));

        $this->assertSame('On', $modern['Select if 6 digit odometer reading']);

        // The 5-digit box is unmapped, so it is never written and pdftk reports
        // no value rather than 'Off'. Both print as an empty box.
        $this->assertContains($modern['Select if 5 digit odometer reading'] ?? '', ['', 'Off']);

        $old = $this->fieldValuesInFilledPdf($this->fullyPopulatedCase(['year' => 1980]));

        $this->assertSame('Off', $old['Select if 6 digit odometer reading']);
        $this->assertContains($old['Select if 5 digit odometer reading'] ?? '', ['', 'Off']);
    }

    /**
     * Part B is a sworn certification by a named authority. Until the client
     * says which authority their inspectors are, every box in it stays blank
     * for a wet signature rather than being filled with a guess.
     */
    public function test_part_b_stays_blank_pending_the_certifier_answer(): void
    {
        $values = $this->fieldValuesInFilledPdf($this->fullyPopulatedCase());

        foreach (self::PART_B_FIELDS as $field) {
            $this->assertSame(
                '',
                $values[$field] ?? '',
                "[{$field}] is in Part B and must stay blank until the certifier type is confirmed."
            );
        }

        foreach (self::CERTIFIER_BUTTONS as $button) {
            // An unmapped button is never written at all, so pdftk reports no
            // value rather than 'Off'. Both mean the same thing on paper: an
            // empty box.
            $this->assertContains(
                $values[$button] ?? '',
                ['', 'Off'],
                "[{$button}] names a certifier type and must not be ticked by guesswork."
            );
        }
    }

    /**
     * The second trap, pinned before it can be sprung.
     *
     * Every button on the 82040 exports 'On', and checkboxValue() defaults to
     * 'On' when a mapping omits the key, so the three certifier buttons here
     * would silently never tick. These are unmapped today, so this asserts the
     * export values in the template itself: whoever maps them after the client
     * answers needs 'on' => 'Yes', and this test says so by name.
     */
    public function test_the_certifier_buttons_export_yes_not_on(): void
    {
        $dump = $this->dumpFormFields($this->formTemplatePath());

        foreach (self::CERTIFIER_BUTTONS as $button) {
            $options = $this->exportValuesFor($dump, $button);

            $this->assertContains(
                'Yes',
                $options,
                "[{$button}] is expected to export 'Yes'. Map it with ['on' => 'Yes']; "
                . "the default of 'On' would leave it silently unticked."
            );

            $this->assertNotContains(
                'On',
                $options,
                "[{$button}] unexpectedly exports 'On' as well, so the mapping guidance needs revisiting."
            );
        }
    }

    /**
     * The odometer and digit-count buttons do export 'On', which is why the
     * mapped ones above are correct.
     */
    public function test_the_part_a_buttons_export_on(): void
    {
        $dump = $this->dumpFormFields($this->formTemplatePath());

        $partAButtons = [
            'Select if 5 digit odometer reading',
            'Select if 6 digit odometer reading',
            '1. reflects ACTUAL MILEAGE',
            '2. is IN EXCESS OF ITS MECHANICAL LIMITS',
            '3. is NOT THE ACTUAL MILEAGE',
        ];

        foreach ($partAButtons as $button) {
            $this->assertContains(
                'On',
                $this->exportValuesFor($dump, $button),
                "[{$button}] does not export 'On', so its mapping needs an explicit export value."
            );
        }
    }

    /**
     * Every non-Off export value pdftk lists for one button.
     *
     * @return array<int, string>
     */
    private function exportValuesFor(string $dump, string $fieldName): array
    {
        foreach (preg_split('/\n(?=---)/', $dump) as $block) {
            if (!preg_match('/^FieldName: (.*)$/m', $block, $name) || trim($name[1]) !== $fieldName) {
                continue;
            }

            preg_match_all('/^FieldStateOption: (.*)$/m', $block, $options);

            return array_values(array_diff(array_map('trim', $options[1]), ['Off']));
        }

        $this->fail("No field named [{$fieldName}] in the 82042 template.");
    }

    /**
     * A missing optional value leaves its box blank rather than printing a cast
     * null. Colour and body type are the nullable ones here: title_state is NOT
     * NULL in the schema, so previous_state_of_issue always resolves to
     * something and its box is never empty in practice.
     */
    public function test_absent_values_leave_their_boxes_blank(): void
    {
        $case = $this->fullyPopulatedCase(['color' => null, 'body_type' => null]);

        $values = $this->fieldValuesInFilledPdf($case);

        $this->assertSame('', $values['Color'] ?? '');
        $this->assertSame('', $values['Body Type'] ?? '');

        // A neighbouring mapped box is unaffected.
        $this->assertSame('Honda', $values['Make']);
    }

    /**
     * previous_state is the more precise answer when the title names an earlier
     * state, and the issuing state is the fallback.
     */
    public function test_previous_state_prefers_the_earlier_state_over_the_issuer(): void
    {
        $named = $this->fieldValuesInFilledPdf(
            $this->fullyPopulatedCase(['previous_state' => 'GA', 'title_state' => 'FL'])
        );

        $this->assertSame('GA', $named['Previous State Vehicle Titled In']);

        $fallback = $this->fieldValuesInFilledPdf(
            $this->fullyPopulatedCase(['previous_state' => null, 'title_state' => 'FL'])
        );

        $this->assertSame('FL', $fallback['Previous State Vehicle Titled In']);
    }

    /**
     * The production path works end to end and puts the data on the page.
     */
    public function test_the_generated_document_carries_part_a(): void
    {
        Storage::fake('private');

        $path = app(PDFGeneratorService::class)
            ->generateHSMV82042($this->fullyPopulatedCase());

        Storage::disk('private')->assertExists($path);

        $text = $this->textOfStoredPdf($path);

        foreach (['1HGCV1F30LA123456', 'Honda', 'Kelvyn Quiroz'] as $value) {
            $this->assertStringContainsString(
                $value,
                $text,
                "[{$value}] was mapped but does not appear in the flattened 82042."
            );
        }
    }
}
