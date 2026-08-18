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
 * The inspector's print screen.
 *
 * print() declares View|JsonResponse but its no-documents path returns
 * back()->withErrors() — a RedirectResponse — and PHP enforces declared
 * return types at runtime. An inspector who visited Print before
 * generating the package therefore got a TypeError instead of the flash
 * redirect the code intended. The union now includes RedirectResponse;
 * both paths are proven here.
 */
class InspectionPrintTest extends TestCase
{
    use DatabaseTransactions;

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

    /**
     * The TypeError path, before the fix: no documents generated yet.
     *
     * The intended response is a redirect back with the error bag, which is
     * what the smoke test saw crash.
     */
    public function test_print_with_no_documents_redirects_back_with_an_error(): void
    {
        $inspector = User::factory()->create(['user_type' => 'inspector']);
        $inspection = $this->passedInspection($inspector);

        $response = $this->actingAs($inspector)
            ->from('/inspector/inspections/' . $inspection->id . '/summary')
            ->get('/inspector/inspections/' . $inspection->id . '/print');

        $response->assertRedirect('/inspector/inspections/' . $inspection->id . '/summary');
        $response->assertSessionHasErrors('error');
    }

    /**
     * The intended success path: with a package generated, the print view
     * renders with the documents.
     */
    public function test_print_with_documents_renders_the_print_view(): void
    {
        Storage::fake('private');

        $inspector = User::factory()->create(['user_type' => 'inspector']);
        $inspection = $this->passedInspection($inspector);

        app(PDFGeneratorService::class)->generateFullPackage($inspection->userCase);

        $response = $this->actingAs($inspector)
            ->get('/inspector/inspections/' . $inspection->id . '/print');

        $response->assertOk();
        $response->assertViewIs('inspector.inspection.print');
        $response->assertViewHas('documents');

        $this->assertNotEmpty(
            $response->viewData('documents'),
            'The print view rendered but carries no documents.'
        );
    }

    public function test_another_inspector_is_refused(): void
    {
        $owner = User::factory()->create(['user_type' => 'inspector']);
        $inspection = $this->passedInspection($owner);

        $this->actingAs(User::factory()->create(['user_type' => 'inspector']))
            ->get('/inspector/inspections/' . $inspection->id . '/print')
            ->assertStatus(403);
    }
}
