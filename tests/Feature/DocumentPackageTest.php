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

        // Unmapped government forms.
        $this->assertStringContainsString('HSMV 84490', $manifest);
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
