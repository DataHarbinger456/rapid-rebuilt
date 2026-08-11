<?php

namespace Tests\Feature;

use App\Models\UploadedFile;
use App\Models\User;
use App\Models\UserCase;
use App\Services\DocumentService;
use App\Support\DocumentReadiness;
use App\Support\DocumentReadinessItem;
use App\Support\DocumentRequirements;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DocumentReadinessTest extends TestCase
{
    use DatabaseTransactions;

    private function makeCase(User $customer, string $serviceType = 'rebuilt_title', string $status = 'pending_documents'): UserCase
    {
        return UserCase::create([
            'user_id' => $customer->id,
            'case_number' => 'RR-TEST-' . uniqid(),
            'status' => $status,
            'service_type' => $serviceType,
            'inspection_fee' => 150.00,
            'service_fee' => 100.00,
            'total_amount' => 250.00,
        ]);
    }

    private function addDocument(UserCase $case, string $type, string $status, array $attributes = []): UploadedFile
    {
        return UploadedFile::create(array_merge([
            'user_case_id' => $case->id,
            'user_id' => $case->user_id,
            'uploaded_by' => $case->user_id,
            'document_type' => $type,
            'original_name' => "{$type}.pdf",
            'file_path' => "cases/{$case->id}/documents/{$type}/" . uniqid() . '.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'status' => $status,
        ], $attributes));
    }

    /**
     * Approve every required document except the ones named.
     */
    private function approveAllRequiredExcept(UserCase $case, array $except = []): void
    {
        foreach (DocumentRequirements::forCase($case) as $type => $requirement) {
            if (!$requirement->required || in_array($type, $except, true)) {
                continue;
            }

            $this->addDocument($case, $type, 'approved');
        }
    }

    private function readinessFor(UserCase $case): DocumentReadiness
    {
        return app(DocumentService::class)->readinessFor($case->fresh());
    }

    public function test_untouched_case_is_not_started(): void
    {
        $case = $this->makeCase(User::factory()->create());

        $readiness = $this->readinessFor($case);

        $this->assertSame(DocumentReadiness::STATE_NOT_STARTED, $readiness->state);
        $this->assertSame(0, $readiness->approvedCount);
        $this->assertSame(8, $readiness->requiredTotal);
        $this->assertSame(0, $readiness->percent);
        $this->assertSame('0 of 8 documents approved', $readiness->headline());
    }

    public function test_each_document_state_is_derived_from_its_uploads(): void
    {
        $case = $this->makeCase(User::factory()->create());

        $this->addDocument($case, 'salvage_title', 'approved');
        $this->addDocument($case, 'bill_of_sale', 'pending');
        $this->addDocument($case, 'insurance_card', 'rejected', ['rejection_reason' => 'Expired policy']);

        $items = $this->readinessFor($case)->items;

        $this->assertSame(DocumentReadinessItem::STATE_APPROVED, $items['salvage_title']->state);
        $this->assertSame(DocumentReadinessItem::STATE_PENDING, $items['bill_of_sale']->state);
        $this->assertSame(DocumentReadinessItem::STATE_REJECTED, $items['insurance_card']->state);
        $this->assertSame('Expired policy', $items['insurance_card']->rejectionReason);
        $this->assertSame(DocumentReadinessItem::STATE_MISSING, $items['owner_id_front']->state);
    }

    public function test_replaced_documents_are_ignored(): void
    {
        $case = $this->makeCase(User::factory()->create());

        $this->addDocument($case, 'salvage_title', 'replaced');

        $this->assertSame(
            DocumentReadinessItem::STATE_MISSING,
            $this->readinessFor($case)->items['salvage_title']->state
        );
    }

    public function test_multiple_type_stays_approved_when_a_later_file_is_unreviewed(): void
    {
        $case = $this->makeCase(User::factory()->create());

        $this->addDocument($case, 'damage_photos', 'approved', ['created_at' => now()->subDay()]);
        $this->addDocument($case, 'damage_photos', 'pending', ['created_at' => now()]);

        $item = $this->readinessFor($case)->items['damage_photos'];

        $this->assertSame(DocumentReadinessItem::STATE_APPROVED, $item->state);
        $this->assertSame(2, $item->documentCount);
    }

    public function test_rejection_flips_the_case_to_action_required(): void
    {
        $case = $this->makeCase(User::factory()->create());

        $this->approveAllRequiredExcept($case, ['salvage_title']);
        $this->addDocument($case, 'salvage_title', 'rejected', ['rejection_reason' => 'Blurry scan']);

        $readiness = $this->readinessFor($case);

        $this->assertSame(DocumentReadiness::STATE_ACTION_REQUIRED, $readiness->state);
        $this->assertSame(7, $readiness->approvedCount);
        $this->assertArrayHasKey('salvage_title', $readiness->missingLabels());
    }

    public function test_all_uploaded_but_unreviewed_is_in_review(): void
    {
        $case = $this->makeCase(User::factory()->create());

        foreach (DocumentRequirements::forCase($case) as $type => $requirement) {
            if ($requirement->required) {
                $this->addDocument($case, $type, 'pending');
            }
        }

        $this->assertSame(DocumentReadiness::STATE_IN_REVIEW, $this->readinessFor($case)->state);
    }

    public function test_optional_documents_do_not_drag_the_meter_down(): void
    {
        $case = $this->makeCase(User::factory()->create());

        $this->approveAllRequiredExcept($case);

        $readiness = $this->readinessFor($case);

        $this->assertSame(100, $readiness->percent);
        $this->assertTrue($readiness->isReady());
        $this->assertSame('All 8 documents approved — ready for inspection', $readiness->headline());
        $this->assertNotEmpty($readiness->optionalItems());
        $this->assertSame(
            DocumentReadinessItem::STATE_MISSING,
            $readiness->items['power_of_attorney']->state
        );
    }

    public function test_reinspection_requires_a_shorter_list(): void
    {
        $case = $this->makeCase(User::factory()->create(), 'reinspection');

        $readiness = $this->readinessFor($case);

        $this->assertSame(2, $readiness->requiredTotal);
        $this->assertArrayHasKey('repair_photos', $readiness->requiredItems());
        $this->assertArrayNotHasKey('salvage_title', $readiness->items);
    }

    public function test_unknown_or_missing_service_type_falls_back_to_the_default_list(): void
    {
        // user_cases.service_type is a NOT NULL enum defaulting to
        // rebuilt_title, so this fallback is exercised through the resolver:
        // a service type added to UserCase but not yet to config/documents.php
        // must still produce the full default list rather than an empty meter.
        $this->assertCount(10, DocumentRequirements::forServiceType(null));
        $this->assertCount(10, DocumentRequirements::forServiceType('service_type_we_have_not_configured'));
    }

    public function test_approving_the_last_document_marks_the_case_documents_complete(): void
    {
        $customer = User::factory()->create();
        $staff = User::factory()->staff()->create();
        $case = $this->makeCase($customer);

        $this->approveAllRequiredExcept($case, ['component_receipts']);
        $last = $this->addDocument($case, 'component_receipts', 'pending');

        app(DocumentService::class)->approveDocument($last->fresh(), $staff);

        $this->assertSame('documents_complete', $case->fresh()->status);
        $this->assertDatabaseHas('case_timeline', [
            'user_case_id' => $case->id,
            'event_type' => 'status_changed',
            'title' => 'Status changed to documents_complete',
        ]);
    }

    public function test_approving_a_document_does_not_move_a_scheduled_case_backwards(): void
    {
        $customer = User::factory()->create();
        $staff = User::factory()->staff()->create();
        $case = $this->makeCase($customer, 'rebuilt_title', 'scheduled');

        $this->approveAllRequiredExcept($case, ['component_receipts']);
        $last = $this->addDocument($case, 'component_receipts', 'pending');

        app(DocumentService::class)->approveDocument($last->fresh(), $staff);

        $this->assertSame('scheduled', $case->fresh()->status);
    }

    public function test_customer_case_page_renders_the_meter(): void
    {
        $customer = User::factory()->create();
        $case = $this->makeCase($customer);

        $this->addDocument($case, 'salvage_title', 'approved');
        $this->addDocument($case, 'bill_of_sale', 'rejected', ['rejection_reason' => 'Unreadable']);

        $response = $this->actingAs($customer)->get(route('customer.cases.show', $case));

        $response->assertOk();
        $response->assertSee('1 of 8 documents approved');
        $response->assertSee('Action required');
        $response->assertSee('Unreadable');
    }

    public function test_upload_page_renders_the_meter_and_a_type_for_every_requirement(): void
    {
        $customer = User::factory()->create();
        $case = $this->makeCase($customer);

        $this->addDocument($case, 'salvage_title', 'approved');

        $response = $this->actingAs($customer)->get(route('customer.cases.documents', $case));

        $response->assertOk();
        $response->assertSee('1 of 8 documents approved');

        foreach (array_keys(DocumentRequirements::forCase($case)) as $type) {
            $response->assertSee('value="' . $type . '"', false);
        }
    }

    public function test_case_creation_wizard_renders_the_default_requirements(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('customer.cases.create'));

        $response->assertOk();
        $response->assertSee('documents[salvage_title]', false);
        $response->assertSee('documents[damage_photos][]', false);
    }

    public function test_admin_case_page_renders_the_meter(): void
    {
        $customer = User::factory()->create();
        $staff = User::factory()->staff()->create();
        $case = $this->makeCase($customer);

        $this->addDocument($case, 'salvage_title', 'approved');

        $response = $this->actingAs($staff)->get(route('admin.cases.show', $case));

        $response->assertOk();
        $response->assertSee('1 of 8 documents approved');
    }
}
