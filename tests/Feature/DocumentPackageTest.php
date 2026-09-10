<?php

namespace Tests\Feature;

use App\Models\Inspection;
use App\Models\User;
use App\Models\UserCase;
use App\Models\Vehicle;
use App\Services\PDFGeneratorService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

/**
 * The full document package.
 *
 * The behaviour under test is that one unfillable form no longer costs staff
 * the whole packet. Three of the five HSMV forms have no field map yet, and
 * before this the first of them threw all the way out of generateFullPackage():
 * a case with a passed inspection produced no 82040, no supporting documents
 * and no inspection photos, because a later form could not be filled.
 */
class DocumentPackageTest extends TestCase
{
    use DatabaseTransactions;

    private function passedCase(array $vehicleOverrides = []): UserCase
    {
        $customer = User::factory()->create([
            'first_name' => 'Kelvyn',
            'last_name' => 'Quiroz',
            'email' => 'kelvyn.quiroz+' . uniqid() . '@example.test',
            'address_1' => '1841 NW 21st Terrace',
            'city' => 'Miami',
            'states' => 'FL',
            'zip' => '33142',
        ]);

        $case = UserCase::create([
            'user_id' => $customer->id,
            'case_number' => 'RR-TEST-' . uniqid(),
            'status' => 'inspection_passed',
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
            'year' => 2020,
            'make' => 'Honda',
            'model' => 'Accord',
            'body_type' => 'Sedan',
            'color' => 'Blue',
            'odometer_reading' => 128450,
            'odometer_date' => '2025-11-04',
            'odometer_status' => 'actual',
        ], $vehicleOverrides));

        Inspection::create([
            'user_case_id' => $case->id,
            'inspector_id' => User::factory()->create()->id,
            'result' => 'passed',
            'odometer_digit_count' => 6,
            'completed_at' => now(),
        ]);

        return $case->fresh();
    }

    /**
     * @return array<int, string> every entry name inside the zip
     */
    private function entriesIn(string $zipStoragePath): array
    {
        $temp = tempnam(sys_get_temp_dir(), 'pkg_') . '.zip';
        file_put_contents($temp, Storage::disk('private')->get($zipStoragePath));

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($temp) === true, 'The generated package is not a readable zip.');

        $entries = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entries[] = $zip->getNameIndex($i);
        }

        $zip->close();
        @unlink($temp);

        return $entries;
    }

    private function manifestIn(string $zipStoragePath): string
    {
        $temp = tempnam(sys_get_temp_dir(), 'pkg_') . '.zip';
        file_put_contents($temp, Storage::disk('private')->get($zipStoragePath));

        $zip = new ZipArchive();
        $zip->open($temp);
        $manifest = $zip->getFromName('MANIFEST.txt');
        $zip->close();
        @unlink($temp);

        $this->assertNotFalse($manifest, 'The package has no MANIFEST.txt.');

        return $manifest;
    }

    /**
     * The regression this exists for: an unmapped form must not cost the packet.
     */
    public function test_the_package_is_produced_despite_unfillable_forms(): void
    {
        Storage::fake('private');

        $path = app(PDFGeneratorService::class)->generateFullPackage($this->passedCase());

        Storage::disk('private')->assertExists($path);

        $entries = $this->entriesIn($path);

        // The two forms with a field map today.
        $this->assertContains('Generated_Forms/HSMV_82040_' . $this->caseNumberOf($path) . '.pdf', $entries);

        $this->assertTrue(
            (bool) array_filter($entries, fn (string $e): bool => str_starts_with($e, 'Generated_Forms/HSMV_82050_')),
            'The 82050 is mapped and should have been generated.'
        );
    }

    private function caseNumberOf(string $zipPath): string
    {
        preg_match('/RapidRebuilt_(.+)_Package\.zip$/', $zipPath, $m);

        return $m[1];
    }

    public function test_the_manifest_names_every_form_that_needs_completing_by_hand(): void
    {
        Storage::fake('private');

        $path = app(PDFGeneratorService::class)->generateFullPackage($this->passedCase());
        $manifest = $this->manifestIn($path);

        // Unmapped government forms. The 84490 is no longer among them: it
        // has a partial map and is generated, with a caveat of its own — see
        // test_a_partially_filled_form_says_so_in_the_manifest().
        $this->assertStringContainsString('HSMV 84491', $manifest);
        $this->assertStringContainsString('HSMV 82053', $manifest);

        // Each carries the reason it could not be filled.
        $this->assertStringContainsString('still', $manifest);
        $this->assertStringContainsString('COMPLETE THESE BY HAND', $manifest);
    }

    /**
     * The composed documents are reported rather than attempted.
     *
     * They have no template and no field map, so routing them through
     * fillForm() only ever produced the "in development" error, which is what
     * used to abort the whole packet on its very first line.
     */
    public function test_the_manifest_explains_the_composed_documents(): void
    {
        Storage::fake('private');

        $path = app(PDFGeneratorService::class)->generateFullPackage($this->passedCase());
        $manifest = $this->manifestIn($path);

        $this->assertStringContainsString('Cover Letter', $manifest);
        $this->assertStringContainsString('Document Checklist', $manifest);
        $this->assertStringContainsString('compose', $manifest);

        // And no attempt was made to put them in the zip.
        foreach ($this->entriesIn($path) as $entry) {
            $this->assertStringNotContainsString('Cover_Letter', $entry);
            $this->assertStringNotContainsString('Document_Checklist', $entry);
        }
    }

    public function test_the_manifest_lists_what_was_generated(): void
    {
        Storage::fake('private');

        $path = app(PDFGeneratorService::class)->generateFullPackage($this->passedCase());
        $manifest = $this->manifestIn($path);

        $this->assertStringContainsString('GENERATED AND ENCLOSED', $manifest);
        $this->assertStringContainsString('HSMV 82040', $manifest);
        $this->assertStringContainsString('Application for Certificate of Title', $manifest);
        $this->assertStringContainsString($this->caseNumberOf($path), $manifest);
    }

    /**
     * The manifest is the first thing seen when the package is opened, so it
     * sits at the root rather than inside a folder.
     */
    public function test_the_manifest_is_at_the_root_of_the_zip(): void
    {
        Storage::fake('private');

        $path = app(PDFGeneratorService::class)->generateFullPackage($this->passedCase());

        $this->assertContains('MANIFEST.txt', $this->entriesIn($path));
    }

    /**
     * Unchanged behaviour: the packet asserts an inspection passed.
     */
    public function test_a_case_without_a_passed_inspection_still_refuses(): void
    {
        Storage::fake('private');

        $case = $this->passedCase();
        $case->inspection->update(['result' => 'failed']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('passed inspection');

        app(PDFGeneratorService::class)->generateFullPackage($case->fresh());
    }

    public function test_a_case_with_no_inspection_at_all_still_refuses(): void
    {
        Storage::fake('private');

        $case = $this->passedCase();
        $case->inspection->delete();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('passed inspection');

        app(PDFGeneratorService::class)->generateFullPackage($case->fresh());
    }

    /**
     * End to end through the endpoint staff actually call.
     *
     * This is the whole change in one test. Before it, POST
     * .../generate-documents answered 422 with "Cover Letter cannot be
     * generated yet" for every case ever submitted, because the composed
     * documents were attempted first and threw. A passed inspection produced
     * no package at all.
     */
    public function test_the_endpoint_returns_a_package_instead_of_a_422(): void
    {
        Storage::fake('private');

        // Titled in Georgia, so all three mapped forms apply. A Florida title
        // gates the 82042 out, which the branch tests below cover.
        $case = $this->passedCase(['title_state' => 'GA']);
        $inspection = $case->inspection;

        $response = $this->actingAs($inspection->inspector)
            ->postJson('/api/inspections/' . $inspection->id . '/generate-documents');

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $path = $response->json('data.package_path');
        $this->assertNotEmpty($path, 'The response carries no package path.');
        Storage::disk('private')->assertExists($path);

        // The four forms with a field map are in the folder.
        $entries = $this->entriesIn($path);

        foreach (['HSMV_82040', 'HSMV_82042', 'HSMV_82050', 'HSMV_84490'] as $form) {
            $this->assertTrue(
                (bool) array_filter(
                    $entries,
                    fn (string $e): bool => str_starts_with($e, "Generated_Forms/{$form}_")
                ),
                "{$form} is mapped and should be in Generated_Forms/."
            );
        }

        // The two without one are named in the manifest as manual.
        $manifest = $this->manifestIn($path);

        foreach (['HSMV 84491', 'HSMV 82053'] as $form) {
            $this->assertStringContainsString($form, $manifest, "{$form} should be listed as manual.");
        }

        // And they are under the manual heading, not the generated one.
        [$manualSection, $generatedSection] = explode('GENERATED AND ENCLOSED', $manifest, 2);

        foreach (['HSMV 84491', 'HSMV 82053'] as $form) {
            $this->assertStringContainsString($form, $manualSection);
            $this->assertStringNotContainsString($form, $generatedSection);
        }

        foreach (['HSMV 82040', 'HSMV 82042', 'HSMV 82050', 'HSMV 84490'] as $form) {
            $this->assertStringContainsString($form, $generatedSection);
        }
    }

    /**
     * The 82042's own instructions limit it to used vehicles not currently
     * titled in Florida, and the client said the same on 13 Aug. A Florida car
     * does not need one, so the folder must not contain it.
     */
    public function test_a_florida_titled_vehicle_skips_the_82042(): void
    {
        Storage::fake('private');

        $case = $this->passedCase(['title_state' => 'FL']);
        $inspection = $case->inspection;

        $response = $this->actingAs($inspection->inspector)
            ->postJson('/api/inspections/' . $inspection->id . '/generate-documents');

        $response->assertOk();

        $path = $response->json('data.package_path');

        foreach ($this->entriesIn($path) as $entry) {
            $this->assertStringNotContainsString(
                'HSMV_82042',
                $entry,
                'A Florida-titled vehicle does not need an 82042.'
            );
        }

        // The other two mapped forms are unaffected.
        $entries = $this->entriesIn($path);

        foreach (['HSMV_82040', 'HSMV_82050'] as $form) {
            $this->assertTrue(
                (bool) array_filter(
                    $entries,
                    fn (string $e): bool => str_starts_with($e, "Generated_Forms/{$form}_")
                ),
                "{$form} applies to every case and should still be generated."
            );
        }
    }

    /**
     * Skipping it silently would look like a bug at the counter, so the reason
     * is on the manifest.
     */
    public function test_the_manifest_explains_why_the_82042_was_skipped(): void
    {
        Storage::fake('private');

        $path = app(PDFGeneratorService::class)
            ->generateFullPackage($this->passedCase(['title_state' => 'FL']));

        $manifest = $this->manifestIn($path);

        $this->assertStringContainsString('NOT REQUIRED FOR THIS CASE', $manifest);
        $this->assertStringContainsString('HSMV 82042', $manifest);
        $this->assertStringContainsString('not currently titled in Florida', $manifest);
    }

    /**
     * The distinction that matters at the counter: a skipped form must not be
     * listed as one to complete by hand, or staff will go and fill in a form
     * the state does not want for this vehicle.
     */
    public function test_a_skipped_form_is_not_listed_as_one_to_complete_by_hand(): void
    {
        Storage::fake('private');

        $path = app(PDFGeneratorService::class)
            ->generateFullPackage($this->passedCase(['title_state' => 'FL']));

        $manifest = $this->manifestIn($path);

        [$byHand, $rest] = explode('NOT REQUIRED FOR THIS CASE', $manifest, 2);
        [$notRequired, $enclosed] = explode('GENERATED AND ENCLOSED', $rest, 2);

        $this->assertStringNotContainsString('HSMV 82042', $byHand);
        $this->assertStringContainsString('HSMV 82042', $notRequired);
        $this->assertStringNotContainsString('HSMV 82042', $enclosed);

        // The genuinely unfillable forms stay where they were.
        foreach (['HSMV 84491', 'HSMV 82053'] as $form) {
            $this->assertStringContainsString($form, $byHand);
        }
    }

    /**
     * The 84490 is the first form the packet encloses while it is still mostly
     * blank: 20 of its 134 boxes fill, and the rest wait on questions the
     * client has not answered.
     *
     * Listing it under GENERATED AND ENCLOSED with no caveat would be worse
     * than not generating it at all. Staff read that heading as "done", and
     * the enclosed copy is flattened, so the boxes they still have to complete
     * can no longer be typed into — they would discover both at the counter,
     * holding a declaration sworn under penalty of perjury with its
     * attestations empty.
     */
    public function test_a_partially_filled_form_says_so_in_the_manifest(): void
    {
        Storage::fake('private');

        $path = app(PDFGeneratorService::class)->generateFullPackage($this->passedCase());
        $manifest = $this->manifestIn($path);

        [, $enclosed] = explode('GENERATED AND ENCLOSED', $manifest, 2);

        $lines = explode("\n", $enclosed);

        // Attribution is the whole point of the warning, and proximity in
        // characters does not establish it: the forms are listed one per line,
        // so the caveat belongs on the line IMMEDIATELY after its own form.
        $at = null;

        foreach ($lines as $i => $line) {
            if (str_contains($line, 'HSMV 84490')) {
                $at = $i;
                break;
            }
        }

        $this->assertNotNull($at, 'The 84490 is not listed as enclosed.');

        $this->assertStringContainsString(
            'PARTLY FILLED',
            $lines[$at + 1] ?? '',
            'The 84490 is enclosed mostly blank and the line under it does not say so.'
        );

        // And it says what is still missing, in words staff can act on.
        foreach (['sign', 'parts'] as $word) {
            $this->assertStringContainsString($word, strtolower($enclosed));
        }

        // No other enclosed form is tarred with it.
        $this->assertSame(
            1,
            substr_count($enclosed, 'PARTLY FILLED'),
            'Exactly one enclosed form is partly filled, so the warning appears once.'
        );

        $this->assertStringNotContainsString('PARTLY FILLED', $lines[$at] ?? '');
    }

    /**
     * An out-of-state vehicle gets the form, and no "not required" heading is
     * printed when nothing was skipped.
     */
    public function test_an_out_of_state_vehicle_gets_the_82042(): void
    {
        Storage::fake('private');

        $path = app(PDFGeneratorService::class)
            ->generateFullPackage($this->passedCase(['title_state' => 'GA']));

        $this->assertTrue(
            (bool) array_filter(
                $this->entriesIn($path),
                fn (string $e): bool => str_starts_with($e, 'Generated_Forms/HSMV_82042_')
            ),
            'A vehicle titled outside Florida needs an 82042.'
        );

        $this->assertStringNotContainsString(
            'NOT REQUIRED FOR THIS CASE',
            $this->manifestIn($path),
            'Nothing was skipped, so that heading should not appear at all.'
        );
    }

    /**
     * An unknown title state is a data gap, not an out-of-state vehicle. The
     * form is held back, and the manifest says to fix the case rather than
     * implying the vehicle is Floridian.
     *
     * vehicles.title_state is varchar(2) NOT NULL DEFAULT 'FL', so it is only
     * unknown when the column is blank or the case has no vehicle row at all.
     */
    public function test_an_unknown_title_state_is_reported_as_a_data_gap(): void
    {
        Storage::fake('private');

        $path = app(PDFGeneratorService::class)
            ->generateFullPackage($this->passedCase(['title_state' => '']));

        $manifest = $this->manifestIn($path);

        $this->assertStringContainsString('no title state is recorded', $manifest);
        $this->assertStringNotContainsString('holds a Florida title', $manifest);
    }

    public function test_a_case_with_no_vehicle_holds_the_82042_back(): void
    {
        Storage::fake('private');

        $case = $this->passedCase();
        $case->vehicle->delete();

        $path = app(PDFGeneratorService::class)->generateFullPackage($case->fresh());

        $this->assertStringContainsString('no title state is recorded', $this->manifestIn($path));

        foreach ($this->entriesIn($path) as $entry) {
            $this->assertStringNotContainsString('HSMV_82042', $entry);
        }
    }

    /**
     * The 82040's Transfer checkbox and the 82042's presence answer the same
     * question, so they must never disagree: a packet claiming a transfer with
     * no 82042 in the folder would contradict itself.
     */
    public function test_the_82042_agrees_with_the_82040_transfer_checkbox(): void
    {
        Storage::fake('private');

        $service = app(PDFGeneratorService::class);

        $prepare = new \ReflectionMethod($service, 'prepareFormData');
        $prepare->setAccessible(true);

        foreach (['GA' => true, 'FL' => false] as $state => $expectTransfer) {
            $case = $this->passedCase(['title_state' => $state]);

            $this->assertSame(
                $expectTransfer,
                $prepare->invoke($service, $case)['is_application_transfer'],
                "The 82040 Transfer checkbox is wrong for a {$state} title."
            );

            $hasForm = (bool) array_filter(
                $this->entriesIn($service->generateFullPackage($case)),
                fn (string $e): bool => str_starts_with($e, 'Generated_Forms/HSMV_82042_')
            );

            $this->assertSame(
                $expectTransfer,
                $hasForm,
                "The 82042's presence disagrees with the Transfer checkbox for a {$state} title."
            );
        }
    }

    /**
     * A generated form is filed as an approved document, so without an
     * exclusion it would also be swept into Supporting_Documents. The list was
     * hardcoded and had already drifted: it never learned about the 82042.
     */
    public function test_a_generated_form_is_not_also_filed_as_a_supporting_document(): void
    {
        Storage::fake('private');

        $path = app(PDFGeneratorService::class)->generateFullPackage($this->passedCase());

        foreach ($this->entriesIn($path) as $entry) {
            if (!str_starts_with($entry, 'Supporting_Documents/')) {
                continue;
            }

            $this->assertStringNotContainsStringIgnoringCase(
                'hsmv',
                $entry,
                "[{$entry}] duplicates a generated form into Supporting_Documents."
            );
        }
    }

    /**
     * The case still advances, because the packet is genuinely ready for staff
     * to finish and send.
     */
    public function test_the_case_advances_and_the_timeline_records_the_split(): void
    {
        Storage::fake('private');

        $case = $this->passedCase();

        app(PDFGeneratorService::class)->generateFullPackage($case);

        $this->assertSame('ready_for_dmv', $case->fresh()->status);

        $this->assertDatabaseHas('case_timeline', [
            'user_case_id' => $case->id,
            'event_type' => 'documents_generated',
        ]);
    }
}
