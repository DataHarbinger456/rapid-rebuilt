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

/**
 * The inspector's documents view lists the HSMV forms from PACKAGE_FORMS.
 *
 * This exists because the view used to carry its own hardcoded list, which
 * drifted until four of its five names were wrong and the 82042 was missing
 * entirely — while the packet's manifest, built from PACKAGE_FORMS, said
 * something different. Two lists naming the same forms cannot be allowed to
 * disagree, so the view now renders the one constant, and this test fails the
 * moment either side changes without the other.
 */
class InspectorDocumentsFormListTest extends TestCase
{
    use DatabaseTransactions;

    private function inspector(): User
    {
        return User::factory()->create(['user_type' => 'inspector']);
    }

    private function passedInspection(User $inspector): Inspection
    {
        $case = UserCase::create([
            'user_id' => User::factory()->create(['user_type' => 'user'])->id,
            'case_number' => 'RR-TEST-' . uniqid(),
            'status' => 'inspection_passed',
            'service_type' => 'rebuilt_title',
            'inspection_fee' => 150.00,
            'service_fee' => 100.00,
            'total_amount' => 250.00,
        ]);

        Vehicle::create([
            'user_case_id' => $case->id,
            'vin' => '1HGCV1F30LA123456',
            'title_state' => 'GA',
            'year' => 2020,
            'make' => 'Honda',
            'model' => 'Accord',
            'body_type' => 'Sedan',
            'color' => 'Blue',
            'odometer_reading' => 128450,
            'odometer_date' => '2025-11-04',
            'odometer_status' => 'actual',
        ]);

        return Inspection::create([
            'user_case_id' => $case->id,
            'inspector_id' => $inspector->id,
            'result' => 'passed',
            'odometer_digit_count' => 6,
            'completed_at' => now(),
        ]);
    }

    public function test_the_view_lists_exactly_the_forms_in_package_forms(): void
    {
        $inspector = $this->inspector();

        $response = $this->actingAs($inspector)
            ->get('/inspector/inspections/' . $this->passedInspection($inspector)->id . '/documents');

        $response->assertOk();

        $html = $response->getContent();

        foreach (PDFGeneratorService::PACKAGE_FORMS as $formId => $form) {
            $this->assertStringContainsString(
                $formId,
                $html,
                "[{$formId}] is in PACKAGE_FORMS but missing from the inspector documents view."
            );

            $this->assertStringContainsString(
                e($form['name']),
                $html,
                "[{$formId}] is listed as \"{$form['name']}\" in PACKAGE_FORMS but not under that name in the view."
            );
        }

        // Exactly those forms: one row badge per entry, and no stray HSMV id
        // that PACKAGE_FORMS does not know about.
        $this->assertSame(
            count(PDFGeneratorService::PACKAGE_FORMS),
            substr_count($html, 'badge badge-secondary'),
            'The view renders a different number of form rows than PACKAGE_FORMS holds.'
        );

        preg_match_all('/HSMV_\d{5}/', $html, $ids);
        $stray = array_diff(array_unique($ids[0]), array_keys(PDFGeneratorService::PACKAGE_FORMS));

        $this->assertSame(
            [],
            $stray,
            'The view names HSMV forms that are not in PACKAGE_FORMS.'
        );
    }

    /**
     * The Generate All button is a browser form POST, and this is the test
     * that proves it works the way a browser actually submits it: session
     * authenticated, through the web middleware stack, expecting a redirect
     * back — not postJson to the API twin.
     */
    public function test_generate_all_submits_as_a_browser_form_and_redirects_back(): void
    {
        Storage::fake('private');

        $inspector = $this->inspector();
        $inspection = $this->passedInspection($inspector);

        $response = $this->actingAs($inspector)
            ->from('/inspector/inspections/' . $inspection->id . '/documents')
            ->post('/inspector/inspections/' . $inspection->id . '/generate-documents');

        $response->assertRedirect('/inspector/inspections/' . $inspection->id . '/documents');
        $response->assertSessionHas('success');

        $this->assertStringContainsString(
            'document package generated',
            strtolower(session('success'))
        );

        // The package really was generated, not just flashed about.
        $this->assertNotEmpty(
            app(\App\Services\PDFGeneratorService::class)
                ->getGeneratedDocuments($inspection->userCase),
            'The flash says success but no documents were filed.'
        );

        Storage::disk('private')->assertExists(
            $inspection->userCase->fresh()->documents
                ->firstWhere('document_type', 'hsmv_82040')
                ->file_path
        );
    }

    /**
     * A failed inspection must not generate, and the failure must come back
     * as a validation error redirect rather than JSON or a 500.
     */
    public function test_a_failed_inspection_redirects_back_with_an_error(): void
    {
        $inspector = $this->inspector();
        $inspection = $this->passedInspection($inspector);
        $inspection->update(['result' => 'failed']);

        $response = $this->actingAs($inspector)
            ->from('/inspector/inspections/' . $inspection->id . '/documents')
            ->post('/inspector/inspections/' . $inspection->id . '/generate-documents');

        $response->assertRedirect('/inspector/inspections/' . $inspection->id . '/documents');
        $response->assertSessionHasErrors('error');
    }

    /**
     * Another inspector cannot generate for someone else's inspection. The
     * route sits inside role.inspector, so the guard here is the ownership
     * check in the controller.
     */
    public function test_another_inspector_is_refused(): void
    {
        $owner = $this->inspector();
        $inspection = $this->passedInspection($owner);

        $response = $this->actingAs($this->inspector())
            ->post('/inspector/inspections/' . $inspection->id . '/generate-documents');

        $response->assertStatus(403);
    }

    /**
     * The names themselves are correct: each matches the PDF template's
     * printed title, at least to the distinguishing part. This is the check
     * that would have caught the original drift, where 84491 was called
     * "Major Component Parts Form" and 82053 "Odometer Disclosure Statement".
     */
    public function test_the_names_match_the_forms_they_point_at(): void
    {
        $expected = [
            'HSMV_82040' => 'Application for Certificate of Title',
            'HSMV_82042' => 'VIN and Odometer Verification',
            'HSMV_84490' => 'Statement of Builder',
            'HSMV_84491' => 'Statement of Builder',
            'HSMV_82050' => 'Notice of Sale',
            'HSMV_82053' => 'Power of Attorney',
        ];

        $this->assertSame(
            array_keys($expected),
            array_keys(PDFGeneratorService::PACKAGE_FORMS),
            'PACKAGE_FORMS changed shape; update this test with the new set, deliberately.'
        );

        foreach (PDFGeneratorService::PACKAGE_FORMS as $formId => $form) {
            $this->assertStringContainsString(
                $expected[$formId],
                $form['name'],
                "[{$formId}]'s name no longer identifies the form it points at."
            );
        }
    }
}
