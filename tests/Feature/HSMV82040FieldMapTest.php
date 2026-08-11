<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserCase;
use App\Models\Vehicle;
use App\Services\PDFGeneratorService;
use App\Support\PdftkBinary;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use mikehaertl\pdftk\Pdf;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Proves the 82040 field map lands each value in the box it names, by reading
 * the values back out of a real filled PDF.
 *
 * Why this exists alongside HSMV82040GenerationTest: that suite asserts the
 * values the generator hands to pdftk, which cannot catch a field NAME that
 * does not exist in the document. pdftk silently ignores an unknown name, so a
 * typo prints a blank box and every value-level assertion still passes. The
 * 82040's names are inconsistently punctuated — 'Owner s County of Residence'
 * has no apostrophe, 'Owner’s Phone Number (Voluntary)' has a curly one and
 * 'Owner's City' a plain ASCII one — which makes that failure mode likely
 * rather than theoretical.
 *
 * Production flattens the PDF, which bakes values into the page and removes the
 * form fields, so a flattened file has nothing to read back. These tests fill
 * the same template with the same values through the same pdftk binary and skip
 * only the flatten step, leaving the fields machine-readable.
 */
class HSMV82040FieldMapTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * A case with every column the map can draw on populated, so no assertion
     * passes merely because a value was blank at both ends.
     */
    private function fullyPopulatedCase(array $vehicleOverrides = []): UserCase
    {
        $customer = User::factory()->create([
            'first_name' => 'Kelvyn',
            'last_name' => 'Quiroz',
            'email' => 'kelvyn.quiroz@example.test',
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
            'weight' => '3131',
            'odometer_reading' => 128450,
            'odometer_date' => '2025-11-04',
            'odometer_status' => 'actual',
        ], $vehicleOverrides));

        return $case->fresh();
    }

    /**
     * The exact field => value set the generator hands to pdftk, through the
     * real config, prepareFormData() and mapFields().
     */
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
     * Fill the official template unflattened and read every field back.
     *
     * @return array<string, string> pdf field name => value inside the document
     */
    private function fieldValuesInFilledPdf(UserCase $case): array
    {
        $out = tempnam(sys_get_temp_dir(), '82040_') . '.pdf';

        $pdf = new Pdf(
            base_path(config('hsmv_forms.hsmv_82040.template')),
            ['command' => PdftkBinary::path()]
        );

        $filled = $pdf
            ->fillForm($this->stampedValues($case))
            ->needAppearances()
            ->saveAs($out);

        $this->assertNotFalse($filled, 'pdftk could not fill the 82040: ' . $pdf->getError());

        $dump = (string) shell_exec(
            escapeshellarg(PdftkBinary::path()) . ' ' . escapeshellarg($out)
            . ' dump_data_fields_utf8'
        );

        @unlink($out);

        // Each record is a blank-line-separated block of Key: value lines.
        $values = [];

        foreach (preg_split('/\n(?=---)/', $dump) as $block) {
            if (!preg_match('/^FieldName: (.*)$/m', $block, $name)) {
                continue;
            }

            preg_match('/^FieldValue: (.*)$/m', $block, $value);

            $values[trim($name[1])] = isset($value[1]) ? trim($value[1]) : '';
        }

        return $values;
    }

    /**
     * The whole point of the suite: a name in the map that is not in the
     * document is silently dropped by pdftk and prints a blank box.
     */
    public function test_every_mapped_field_name_exists_in_the_official_template(): void
    {
        $dump = (string) shell_exec(
            escapeshellarg(PdftkBinary::path()) . ' '
            . escapeshellarg(base_path(config('hsmv_forms.hsmv_82040.template')))
            . ' dump_data_fields_utf8'
        );

        preg_match_all('/^FieldName: (.*)$/m', $dump, $matches);
        $realNames = array_map('trim', $matches[1]);

        $this->assertNotEmpty($realNames, 'Could not read any field names from the 82040 template.');

        foreach (array_keys(config('hsmv_forms.hsmv_82040.fields')) as $mapped) {
            $this->assertContains(
                $mapped,
                $realNames,
                "The map names [{$mapped}], which does not exist in 82040.pdf. "
                . 'pdftk will ignore it and the box will print blank — check the apostrophes.'
            );
        }
    }

    /**
     * Each newly mapped box, read back out of the filled document.
     *
     * @return array<string, array{0: string, 1: string}> label => [pdf field, expected]
     */
    public static function newlyMappedFields(): array
    {
        return [
            // Section 1 — owner block.
            'county of residence' => ['Owner s County of Residence', 'Miami-Dade'],
            'email' => ['Owners Email Voluntary', 'kelvyn.quiroz@example.test'],
            'phone (curly apostrophe)' => ['Owner’s Phone Number (Voluntary)', '3055550142'],
            'city (plain apostrophe)' => ["Owner's City", 'Miami'],
            'state (plain apostrophe)' => ["Owner's State", 'FL'],
            'zip (plain apostrophe)' => ["Owner's Zip Code", '33142'],

            // Section 2 — the Florida title number of a Florida title.
            'florida title number' => ['Florida Title Number', '0123456789'],

            // Section 8 — the VIN restated for the inspector's certification.
            'vin restated in section 8' => ['Vehicle Identification Number VIN_2', '1HGCV1F30LA123456'],

            // Section 12 — printed applicant name.
            'applicant name' => ['Full Name of Applicant Owner', 'Kelvyn Quiroz'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('newlyMappedFields')]
    public function test_each_new_mapping_lands_in_its_own_box(string $field, string $expected): void
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
     * Guards against a value leaking into a neighbouring box: the owner blocks
     * for co-owners, lessees, lienholders and the mail-to party are all
     * deliberately unmapped, and every one of them has a near-identical name.
     */
    public function test_unmapped_owner_lookalike_boxes_stay_empty(): void
    {
        $values = $this->fieldValuesInFilledPdf($this->fullyPopulatedCase());

        $mustStayBlank = [
            // Awaiting the client's answer on mailing vs residential address.
            'Owners Mailing Address',
            'Owner’s Residential City',
            'Owner’s Residential State',
            'Owner’s Residential Zip Code',
            // Scenarios this business does not handle at all.
            'Mail To’s City',
            'Mail To’s Email (Voluntary)',
            'Co-Owner’s Phone Number (Voluntary)',
            'Co-Owner’s/Lessee’s Mailing City',
            'Lienholder’s City',
            'Full Name of Applicant CoOwner',
        ];

        foreach ($mustStayBlank as $field) {
            $this->assertSame(
                '',
                $values[$field] ?? '',
                "[{$field}] is not mapped and must print blank, but the PDF carries a value."
            );
        }
    }

    /**
     * The box is labelled "Florida Title Number". An out-of-state title has no
     * Florida number until this application is granted, so printing its
     * home-state number would be a false statement on a sworn form.
     */
    public function test_an_out_of_state_title_number_never_prints_in_the_florida_box(): void
    {
        $case = $this->fullyPopulatedCase([
            'title_state' => 'GA',
            'title_number' => 'GA987654321',
        ]);

        $values = $this->fieldValuesInFilledPdf($case);

        $this->assertSame('', $values['Florida Title Number'] ?? '');

        // The rest of the map still fills, so this is a blank box rather than a
        // broken document.
        $this->assertSame('Miami-Dade', $values['Owner s County of Residence']);
        $this->assertSame('GA', $values['Previous State of Issue']);
    }

    /**
     * A missing optional value leaves its box empty rather than printing
     * "null" or the string of a null cast.
     */
    public function test_absent_owner_details_leave_their_boxes_blank(): void
    {
        $case = $this->fullyPopulatedCase();
        $case->user->update(['county' => null, 'phone' => '']);

        $values = $this->fieldValuesInFilledPdf($case->fresh());

        $this->assertSame('', $values['Owner s County of Residence'] ?? '');
        $this->assertSame('', $values['Owner’s Phone Number (Voluntary)'] ?? '');

        // A neighbouring mapped box is unaffected.
        $this->assertSame('Miami', $values["Owner's City"]);
    }

    /**
     * The production path still works end to end and prints the new values.
     */
    public function test_the_generated_document_shows_the_new_values(): void
    {
        Storage::fake('private');

        $path = app(PDFGeneratorService::class)
            ->generateHSMV82040($this->fullyPopulatedCase());

        Storage::disk('private')->assertExists($path);

        $text = (string) shell_exec(
            'pdftotext ' . escapeshellarg(Storage::disk('private')->path($path)) . ' -'
        );

        foreach (['Miami-Dade', 'kelvyn.quiroz@example.test', '3055550142', '0123456789'] as $value) {
            $this->assertStringContainsString(
                $value,
                $text,
                "[{$value}] was mapped but does not appear in the flattened PDF."
            );
        }
    }
}
