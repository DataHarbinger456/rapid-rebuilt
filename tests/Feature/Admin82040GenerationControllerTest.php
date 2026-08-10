<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserCase;
use App\Models\Vehicle;
use App\Services\PDFGeneratorService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

/**
 * Staff can produce the 82040 straight from a case, without waiting on an
 * inspection: the form is filled from the case and vehicle record alone.
 *
 * The generator shells out to pdftk and throws for a missing template or an
 * unmapped form. None of that is the operator's fault, so these tests pin that
 * it surfaces as a flash message rather than a 500.
 */
class Admin82040GenerationControllerTest extends TestCase
{
    use DatabaseTransactions;

    private function makeCase(array $vehicleOverrides = []): UserCase
    {
        $customer = User::factory()->create(['user_type' => 'user']);

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
            'vin' => '1HGCY2F82SA070581',
            'title_state' => 'AL',
            'title_brand' => 'salvage',
            'year' => 2025,
            'make' => 'HONDA',
            'model' => 'ACCORD SEDAN TOURING',
            'body_type' => '4-DOOR',
            'color' => 'BLACK',
            'odometer_reading' => 4527,
            'odometer_status' => 'actual',
        ], $vehicleOverrides));

        return $case->fresh();
    }

    private function staff(): User
    {
        return User::factory()->create(['user_type' => 'staff']);
    }

    public function test_staff_can_generate_the_82040_from_the_case_page(): void
    {
        Storage::fake('private');

        $case = $this->makeCase();

        $response = $this->actingAs($this->staff())
            ->post(route('admin.cases.generate82040', $case));

        $response->assertRedirect(route('admin.cases.show', $case));
        $response->assertSessionHas('success');

        // Stored like any other case document, so it lists on the case page and
        // the existing view route serves it.
        $document = $case->documents()->where('document_type', 'hsmv_82040')->first();

        $this->assertNotNull($document, 'The generated form must be attached to the case.');
        $this->assertSame('application/pdf', $document->mime_type);
        Storage::disk('private')->assertExists($document->file_path);

        $this->assertDatabaseHas('case_timeline', [
            'user_case_id' => $case->id,
            'event_type' => 'documents_generated',
            'title' => 'HSMV 82040 generated',
        ]);
    }

    public function test_no_inspection_is_required(): void
    {
        Storage::fake('private');

        // A case with no inspection at all. The 84490 needs one; the 82040 does
        // not, and blocking here would leave staff unable to file.
        $case = $this->makeCase();

        $this->assertNull($case->inspection);

        $this->actingAs($this->staff())
            ->post(route('admin.cases.generate82040', $case))
            ->assertSessionHas('success');
    }

    public function test_regenerating_replaces_rather_than_duplicates_the_document(): void
    {
        Storage::fake('private');

        $case = $this->makeCase();
        $staff = $this->staff();

        $this->actingAs($staff)->post(route('admin.cases.generate82040', $case));
        $this->actingAs($staff)->post(route('admin.cases.generate82040', $case));

        // Staff correct a field and regenerate; the case must not accumulate a
        // pile of near-identical forms with no way to tell which is current.
        $this->assertSame(
            1,
            $case->documents()->where('document_type', 'hsmv_82040')->count()
        );
    }

    public function test_a_customer_cannot_generate_the_form(): void
    {
        Storage::fake('private');

        $case = $this->makeCase();

        $this->actingAs(User::factory()->create(['user_type' => 'user']))
            ->post(route('admin.cases.generate82040', $case))
            ->assertForbidden();

        $this->assertSame(0, $case->documents()->where('document_type', 'hsmv_82040')->count());
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        $case = $this->makeCase();

        $this->post(route('admin.cases.generate82040', $case))
            ->assertRedirect(route('login'));
    }

    public function test_a_generation_failure_flashes_an_error_instead_of_500ing(): void
    {
        Storage::fake('private');

        $case = $this->makeCase();

        // Stands in for a missing template, an unmapped form or pdftk failing.
        $this->mock(PDFGeneratorService::class, function ($mock): void {
            $mock->shouldReceive('generateHSMV82040')
                ->andThrow(new RuntimeException('pdftk exited with an error'));
        });

        $response = $this->actingAs($this->staff())
            ->post(route('admin.cases.generate82040', $case));

        $response->assertRedirect(route('admin.cases.show', $case));
        $response->assertSessionHas('error');
        $this->assertStringContainsString(
            'pdftk exited with an error',
            session('error'),
            'Staff need the real reason, not a generic failure.'
        );

        // A failed run must leave no document row and no success event behind.
        $this->assertSame(0, $case->documents()->where('document_type', 'hsmv_82040')->count());
        $this->assertDatabaseMissing('case_timeline', [
            'user_case_id' => $case->id,
            'event_type' => 'documents_generated',
        ]);
    }

    public function test_the_case_page_offers_the_button_and_shows_inspection_status(): void
    {
        $case = $this->makeCase();

        $response = $this->actingAs($this->staff())
            ->get(route('admin.cases.show', $case->id));

        $response->assertOk();
        $response->assertSee('Generate HSMV 82040');
        $response->assertSee(route('admin.cases.generate82040', $case), false);
        // Staff must be able to see the case's inspection state from the same
        // panel, since the button deliberately does not depend on it.
        $response->assertSee('No inspection yet');
    }
}
