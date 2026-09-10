<?php

namespace Tests\Feature;

use App\Models\Inspection;
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
 * The 84490 field map — step 1 only: the 20 descriptive fields.
 *
 * This form is not a data form with a signature at the bottom. It is a
 * builder's declaration sworn twice, and its back half belongs to the state.
 * Section IV's perjury clause covers the WHOLE document, so anything printed
 * above the applicant's signature becomes their sworn statement.
 *
 * That makes the blank assertions here as important as the filled ones. What
 * this map fills is descriptive fact already on file: the vehicle, the title,
 * and the inspector's own recorded name and date. Everything else stays blank
 * on purpose, and the tests below pin each reason so a later pass cannot
 * quietly fill an attestation box:
 *
 *   - Section III swears to USAGE INTENT and EQUIPMENT, not vehicle type.
 *     Ticking those from is_custom/is_street_rod would be a false attestation.
 *   - The Section I initials boxes are a personal attestation.
 *   - Section IV's applicant block waits on the builder-identity question
 *     (docs/84490-coverage.md ambiguity 1): owner_* keys are only correct if
 *     the customer, not Rapid Rebuilt, signs as builder.
 *   - The parts table waits on ambiguity 6: components.condition stores
 *     new/used/repaired/rebuilt/original, the form offers
 *     New/Used/Repaired/Aftermarket/Homemade, and two values on each side have
 *     no counterpart.
 *   - The page-4 Yes/No matrix waits on ambiguity 3: who may swear to it.
 */
class HSMV84490FieldMapTest extends TestCase
{
    use DatabaseTransactions;
    use ReadsPdfFormFields;

    protected function formKey(): string
    {
        return 'hsmv_84490';
    }

    /**
     * Sworn attestations that no vehicle_type may ever tick.
     *
     * Both export 'On', and both names are truncated mid-sentence in the PDF
     * itself — verbatim from docs/forms/84490-fields.txt.
     */
    private const ATTESTATION_BUTTONS = [
        'The vehicle will not be used for general daily transportation but will be maintained for occasional',
        'The vehicle meets state equipment and safety requirements for motor vehicles that were in effect in this',
    ];

    /**
     * Boxes a person signs or initials in their own hand.
     *
     * Note 'Motorcycly' — a typo in the PDF's own field name, copied verbatim.
     */
    private const PERSONAL_ATTESTATION_FIELDS = [
        'Motor Vehicle/Motorcycly is complete and in road operable condition Initials',
        'Mobile Home is habitable for residential or commercial purposes Initials',
    ];

    /**
     * Section IV, blocked on the builder-identity question.
     */
    private const APPLICANT_FIELDS = [
        'Printed Name of ApplicantBusiness',
        'Printed Name of ApplicantBusiness_2',
        'Street Address',
        'Street Address_2',
        'City',
        'State',
        'Zip',
        'Telephone NumberS',
        'Date_2',
    ];

    /**
     * The parts table: five name boxes, five source boxes, 25 condition
     * buttons. Blocked on the condition-column mismatch.
     *
     * The button names cannot be generated in a loop: numbers 19-23 do not
     * exist, and 'Check Box9', 'Check Box10', 'Check Box11', 'Check Box28' and
     * 'Check Box29' have no space after 'Box' while every other number does.
     * 'Check Box 28' (row 4) and 'Check Box28' (row 5) are two different
     * fields whose names differ by that space alone.
     */
    private const PARTS_TABLE_FIELDS = [
        'Part', 'Part #1', 'Part #2', 'Part #3', 'Part #4',
        'Source/VIN', 'Source/VIN #1', 'Source/VIN 2', 'Source/VIN #3', 'Source/VIN #4',
        'Check Box 4', 'Check Box 5', 'Check Box 6', 'Check Box 7', 'Check Box 8',
        'Check Box9', 'Check Box10', 'Check Box11', 'Check Box 12', 'Check Box 13',
        'Check Box 14', 'Check Box 15', 'Check Box 16', 'Check Box 17', 'Check Box 18',
        'Check Box 24', 'Check Box 25', 'Check Box 26', 'Check Box 27', 'Check Box 28',
        'Check Box28', 'Check Box29', 'Check Box 30', 'Check Box 31', 'Check Box 32',
    ];

    /**
     * Every case here needs an inspection, because two of the 20 fields are
     * the inspector's own name and signing date.
     */
    private function fullyPopulatedCase(
        array $vehicleOverrides = [],
        array $inspectionOverrides = []
    ): UserCase
    {
        $customer = User::factory()->create([
            'first_name' => 'Kelvyn',
            'last_name' => 'Quiroz',
            'email' => 'kelvyn.quiroz+' . uniqid() . '@example.test',
            'phone' => '3055550142',
            'address_1' => '1841 NW 21st Terrace',
            'city' => 'Miami',
            'states' => 'FL',
            'zip' => '33142',
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
            'title_state' => 'GA',
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

        Inspection::create(array_merge([
            'user_case_id' => $case->id,
            'inspector_id' => User::factory()->create([
                'first_name' => 'Marcus',
                'last_name' => 'Delgado',
                'email' => 'marcus.delgado+' . uniqid() . '@example.test',
                'user_type' => 'inspector',
            ])->id,
            'status' => 'completed',
            'result' => 'passed',
            'signed_at' => '2026-03-09 14:20:00',
            'completed_at' => '2026-03-09 15:00:00',

            // Both are set by InspectionService when the inspection starts, so
            // a real passed inspection always carries them.
            'audit_number' => 'RR-260309-A1B2',
            'region_number' => '05',
        ], $inspectionOverrides));

        return $case->fresh();
    }

    /**
     * A name in the map that is not in the document is silently dropped by
     * pdftk, printing a blank box while every value assertion still passes.
     */
    public function test_every_mapped_field_name_exists_in_the_official_template(): void
    {
        $realNames = $this->fieldNamesInTemplate();

        $this->assertNotEmpty($realNames, 'Could not read any field names from the 84490 template.');

        foreach (array_keys($this->mappedFields()) as $mapped) {
            $this->assertContains(
                $mapped,
                $realNames,
                "The map names [{$mapped}], which does not exist in 84490.pdf. "
                . 'pdftk will ignore it and the box will print blank.'
            );
        }
    }

    /**
     * The 20 descriptive fields, by PDF name and expected value.
     *
     * The form asks for the same vehicle twice — Section I on page 1 and the
     * Section V block on page 3 — under different field names. Both are
     * asserted, because a map that filled only one leaves half the form blank
     * with no error anywhere.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function descriptiveFields(): array
    {
        return [
            // Section I.
            'section I year' => ['Year', '2020'],
            'section I make' => ['Make', 'Honda'],
            'section I vin' => ['Identification Number', '1HGCV1F30LA123456'],
            'section I color' => ['Color', 'Blue'],
            'section I body' => ['Body', 'Sedan'],

            // Unlike the 82040's Section 2, this box is NOT state-qualified —
            // it asks for the title number full stop, so a Georgia title
            // number belongs here. The case above is deliberately titled in GA
            // to pin that difference.
            'section I title number' => ['Title Number', '0123456789'],
            'section I title state' => ['Title State', 'GA'],

            // Section V descriptive block, page 3.
            'section V vin' => ['VIN', '1HGCV1F30LA123456'],
            'section V title number' => ['Title #1', '0123456789'],
            'section V title state' => ['Title State #1', 'GA'],
            'section V year' => ['Year #1', '2020'],
            'section V make' => ['Make #1', 'Honda'],
            'section V body' => ['Body #1', 'Sedan'],
            'section V color' => ['Color #1', 'Blue'],

            // One plain box, unlike the per-digit rows on the 82040/82042/82050.
            'section V odometer' => ['Odometer', '128450'],

            // The PRVIP inspector's own recorded facts, not an attestation.
            'prvip printed name' => ['Print Name of PRVIP Inspector', 'Marcus Delgado'],
            'prvip date' => ['Date_4', '03/09/2026'],

            // Section V office-use pair. Both are our own generated data,
            // recorded on the inspection when it started.
            'audit number' => ['Audit #', 'RR-260309-A1B2'],
            'region number' => ['Region #', '05'],
        ];
    }

    #[DataProvider('descriptiveFields')]
    public function test_each_descriptive_field_lands_in_its_own_box(string $field, string $expected): void
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
     * The header build-type buttons, one per vehicle_type.
     *
     * @return array<string, array{0: string, 1: ?string}>
     */
    public static function buildTypes(): array
    {
        return [
            'rebuilt' => ['rebuilt', 'REBUILT'],
            'assembled from parts' => ['aspt', 'ASPT'],
            'kit car' => ['kit_car', 'KIT CAR'],

            // OTHER is unmapped: no key is derived for it, and the free-text
            // description beside it has no source at all. A ticked OTHER with
            // no description says less than a blank pair.
            'other ticks nothing' => ['other', null],

            // These have boxes on the 82040 but not here, so they must not
            // bleed into a neighbouring build type.
            'custom ticks nothing' => ['custom_vehicle', null],
            'street rod ticks nothing' => ['street_rod', null],
        ];
    }

    #[DataProvider('buildTypes')]
    public function test_the_build_type_ticks_exactly_its_own_box(string $vehicleType, ?string $expected): void
    {
        $values = $this->stampedValues($this->fullyPopulatedCase(['vehicle_type' => $vehicleType]));

        foreach (['REBUILT', 'ASPT', 'KIT CAR'] as $button) {
            $this->assertSame(
                $button === $expected ? 'On' : 'Off',
                $values[$button] ?? 'Off',
                "Vehicle type [{$vehicleType}] ticked the wrong build-type box on the 84490."
            );
        }

        // OTHER is never written at all, ticked or unticked.
        $this->assertArrayNotHasKey('OTHER', $values);
    }

    /**
     * The two Section III boxes swear to usage intent and equipment under
     * s.320.0863 — things only the owner knows. is_custom and is_street_rod
     * describe a vehicle TYPE and are the obvious wrong mapping, so this pins
     * that neither one, nor anything else, ever ticks them.
     */
    public function test_the_section_iii_attestations_are_never_ticked(): void
    {
        foreach (['custom_vehicle', 'street_rod', 'rebuilt'] as $vehicleType) {
            $values = $this->fieldValuesInFilledPdf(
                $this->fullyPopulatedCase(['vehicle_type' => $vehicleType])
            );

            foreach (self::ATTESTATION_BUTTONS as $button) {
                $this->assertContains(
                    $values[$button] ?? '',
                    ['', 'Off'],
                    "A [{$vehicleType}] vehicle ticked [{$button}]. That box swears to how the "
                    . 'owner will USE the vehicle, which no vehicle_type can answer.'
                );
            }
        }
    }

    /**
     * Initials are a personal attestation and cannot be typed truthfully by us.
     */
    public function test_the_initials_boxes_stay_blank(): void
    {
        $values = $this->fieldValuesInFilledPdf($this->fullyPopulatedCase());

        foreach (self::PERSONAL_ATTESTATION_FIELDS as $field) {
            $this->assertSame(
                '',
                $values[$field] ?? '',
                "[{$field}] is an initials box and must be filled in a person's own hand."
            );
        }
    }

    /**
     * Section IV holds the perjury clause covering the whole document, and who
     * signs as builder is unanswered. Filling owner_* here would name the
     * customer as builder on a case the shop may have rebuilt.
     */
    public function test_the_applicant_block_stays_blank_pending_the_builder_answer(): void
    {
        $values = $this->fieldValuesInFilledPdf($this->fullyPopulatedCase());

        foreach (self::APPLICANT_FIELDS as $field) {
            $this->assertSame(
                '',
                $values[$field] ?? '',
                "[{$field}] is in Section IV and must stay blank until the client says who "
                . 'signs as builder.'
            );
        }
    }

    /**
     * The parts table is the biggest single win on this form and the easiest
     * to get wrong: two enum values have no column and two columns have no
     * enum value.
     */
    public function test_the_parts_table_stays_blank_pending_the_condition_answer(): void
    {
        $values = $this->fieldValuesInFilledPdf($this->fullyPopulatedCase());

        foreach (self::PARTS_TABLE_FIELDS as $field) {
            $this->assertContains(
                $values[$field] ?? '',
                ['', 'Off'],
                "[{$field}] is in the parts table, which sits under the perjury clause and "
                . 'cannot be filled until the condition columns are settled.'
            );
        }
    }

    /**
     * All 22 matrix buttons sit under their own perjury statement: whoever
     * completes them swears to them "based on that inspection". Recording a
     * value in our database is not the same as being entitled to swear to it.
     */
    public function test_the_yes_no_matrix_stays_blank(): void
    {
        $values = $this->fieldValuesInFilledPdf($this->fullyPopulatedCase());

        foreach (range(33, 54) as $n) {
            $field = "Check Box {$n}";

            $this->assertContains(
                $values[$field] ?? '',
                ['', 'Off'],
                "[{$field}] is in the page-4 sworn matrix and must not be pre-filled."
            );
        }
    }

    /**
     * No signature widget is ever written. All six are wet by law.
     */
    public function test_no_signature_widget_is_filled(): void
    {
        $values = $this->fieldValuesInFilledPdf($this->fullyPopulatedCase());

        $signatures = [
            'Signature',
            'Signature Of ApplicantBusiness',
            'Signature Of ApplicantBusiness_2',
            'Signature of HSMV',
            'Signature of PRVIP Inspector',
        ];

        foreach ($signatures as $field) {
            $this->assertSame(
                '',
                $values[$field] ?? '',
                "[{$field}] is a signature and must be signed by hand."
            );
        }
    }

    /**
     * This form mixes export values on the same page, and checkboxValue()
     * defaults to 'On'. The three buttons we map export 'On', so the default
     * happens to be right — but that is a fact about the template, not a
     * guarantee, so it is pinned here rather than assumed.
     */
    public function test_the_mapped_buttons_export_on(): void
    {
        $dump = $this->dumpFormFields($this->formTemplatePath());

        foreach (['REBUILT', 'ASPT', 'KIT CAR'] as $button) {
            $this->assertSame(
                ['On'],
                $this->exportValuesFor($dump, $button),
                "[{$button}] does not export 'On' alone, so its mapping needs an explicit export value."
            );
        }
    }

    /**
     * The trap the next pass will hit.
     *
     * The parts-table buttons export 'Yes', and so do most of the matrix — but
     * five matrix buttons export 'On' instead, for no visible reason. A
     * mapping that omits the 'on' key silently leaves those unticked, which is
     * the same failure mode as the 82042's certifier buttons. Pinned by name
     * now so whoever maps them after the client answers cannot guess.
     */
    public function test_the_unmapped_buttons_export_values_are_pinned(): void
    {
        $dump = $this->dumpFormFields($this->formTemplatePath());

        // Every parts-table condition button.
        foreach (array_slice(self::PARTS_TABLE_FIELDS, 10) as $button) {
            $this->assertSame(
                ['Yes'],
                $this->exportValuesFor($dump, $button),
                "[{$button}] is a parts-table condition button and is expected to export 'Yes'. "
                . "Map it with ['on' => 'Yes']; the default of 'On' would leave it unticked."
            );
        }

        // The five matrix buttons that break the pattern.
        foreach ([45, 47, 49, 51, 53] as $n) {
            $this->assertSame(
                ['On'],
                $this->exportValuesFor($dump, "Check Box {$n}"),
                "[Check Box {$n}] is expected to export 'On' where its neighbours export 'Yes'."
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

        $this->fail("No field named [{$fieldName}] in the 84490 template.");
    }

    /**
     * A missing optional value leaves its box blank on both pages rather than
     * printing a cast null.
     */
    public function test_absent_values_leave_their_boxes_blank(): void
    {
        $values = $this->fieldValuesInFilledPdf(
            $this->fullyPopulatedCase(['color' => null, 'body_type' => null])
        );

        foreach (['Color', 'Body', 'Color #1', 'Body #1'] as $field) {
            $this->assertSame('', $values[$field] ?? '', "[{$field}] printed something for a null value.");
        }

        // A neighbouring mapped box is unaffected, on both pages.
        $this->assertSame('Honda', $values['Make']);
        $this->assertSame('Honda', $values['Make #1']);
    }

    /**
     * A form generated before the inspection must not claim one happened.
     */
    public function test_a_case_with_no_inspection_leaves_the_prvip_line_blank(): void
    {
        $case = $this->fullyPopulatedCase();
        $case->inspection->delete();

        $values = $this->fieldValuesInFilledPdf($case->fresh());

        $this->assertSame('', $values['Print Name of PRVIP Inspector'] ?? '');
        $this->assertSame('', $values['Date_4'] ?? '');

        // The office-use pair comes off the same inspection and goes with it.
        $this->assertSame('', $values['Audit #'] ?? '');
        $this->assertSame('', $values['Region #'] ?? '');

        // The vehicle description still fills: it does not depend on an inspection.
        $this->assertSame('1HGCV1F30LA123456', $values['Identification Number']);
    }

    /**
     * The 'FL' fallback is a state, not a region number, and this box asks for
     * a region number. InspectionService writes `$inspector->region ?? 'FL'`,
     * so every inspection whose inspector has no region on file carries 'FL'
     * in a column the form expects a number in.
     *
     * The call: print nothing. A blank box on a sworn document is a question
     * for whoever completes it; 'FL' in a box labelled Region # is an answer,
     * and a wrong one that looks deliberate. Staff can write the real region
     * in by hand, which they cannot do once a wrong value is flattened onto
     * the page.
     */
    public function test_the_fl_fallback_is_not_a_region_number_and_prints_blank(): void
    {
        $values = $this->fieldValuesInFilledPdf(
            $this->fullyPopulatedCase([], ['region_number' => 'FL'])
        );

        $this->assertSame(
            '',
            $values['Region #'] ?? '',
            "'FL' is InspectionService's fallback for an inspector with no region, "
            . 'not a region number, and must not be printed in a box that asks for one.'
        );

        // The audit number beside it is unaffected: it has no such fallback.
        $this->assertSame('RR-260309-A1B2', $values['Audit #']);
    }

    /**
     * A real region number is not held back by the rule above.
     */
    public function test_a_real_region_number_still_prints(): void
    {
        foreach (['05', '5', 'R05'] as $region) {
            $values = $this->fieldValuesInFilledPdf(
                $this->fullyPopulatedCase([], ['region_number' => $region])
            );

            $this->assertSame($region, $values['Region #'] ?? '');
        }
    }

    /**
     * There are TWO region boxes on this form, and only one of them is ours.
     *
     * 'Region #' is the Section V office-use box on page 3, mapped above.
     * 'Region' is a separate class D field in the page-4 Compliance Examiner
     * block, which a CE completes alongside their own printed name and date.
     * The names differ by one character, so a map that reached for the wrong
     * one would fill a state officer's block with our number and pass every
     * value assertion.
     */
    public function test_the_compliance_examiner_region_box_stays_blank(): void
    {
        $values = $this->fieldValuesInFilledPdf($this->fullyPopulatedCase());

        $this->assertSame(
            '',
            $values['Region'] ?? '',
            "[Region] is the Compliance Examiner's own box on page 4, not ours."
        );

        // The rest of the CE block stays blank with it.
        foreach (['Print Name of HSMV', 'Date_3'] as $field) {
            $this->assertSame('', $values[$field] ?? '', "[{$field}] is CE-only.");
        }

        // And our box on page 3 did fill, so this is not passing by accident.
        $this->assertSame('05', $values['Region #']);
    }

    /**
     * The two names are distinct fields in the template, which is the fact the
     * test above depends on.
     */
    public function test_region_and_region_hash_are_different_fields(): void
    {
        $names = $this->fieldNamesInTemplate();

        $this->assertContains('Region', $names);
        $this->assertContains('Region #', $names);
    }

    /**
     * The production path works end to end and puts the data on the page.
     */
    public function test_the_generated_document_carries_the_description(): void
    {
        Storage::fake('private');

        $case = $this->fullyPopulatedCase();

        $path = app(PDFGeneratorService::class)
            ->generateHSMV84490($case, $case->inspection);

        Storage::disk('private')->assertExists($path);

        $text = $this->textOfStoredPdf($path);

        foreach (['1HGCV1F30LA123456', 'Honda', 'Marcus Delgado', '128450'] as $value) {
            $this->assertStringContainsString(
                $value,
                $text,
                "[{$value}] was mapped but does not appear in the flattened 84490."
            );
        }
    }
}
