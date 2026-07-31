<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserCase;
use App\Models\UploadedFile;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile as TestFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DocumentUploadTest extends TestCase
{
    use DatabaseTransactions;

    private function makeCase(User $customer, string $status = 'pending_documents'): UserCase
    {
        return UserCase::create([
            'user_id' => $customer->id,
            'case_number' => 'RR-TEST-' . uniqid(),
            'status' => $status,
            'service_type' => 'rebuilt_title',
            'inspection_fee' => 150.00,
            'service_fee' => 100.00,
            'total_amount' => 250.00,
        ]);
    }

    public function test_customer_can_upload_document_to_own_case(): void
    {
        Storage::fake('private');

        $customer = User::factory()->create();
        $case = $this->makeCase($customer);

        $response = $this->actingAs($customer)->post(
            route('customer.documents.upload', $case),
            [
                'document_type' => 'salvage_title',
                'file' => TestFile::fake()->create('title.pdf', 500, 'application/pdf'),
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $document = UploadedFile::where('user_case_id', $case->id)->first();
        $this->assertNotNull($document);
        $this->assertSame('pending', $document->status);
        Storage::disk('private')->assertExists($document->file_path);

        $this->assertDatabaseHas('case_timeline', [
            'user_case_id' => $case->id,
            'event_type' => 'document_uploaded',
        ]);
    }

    public function test_customer_cannot_upload_to_another_customers_case(): void
    {
        Storage::fake('private');

        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $case = $this->makeCase($owner);

        $response = $this->actingAs($intruder)->post(
            route('customer.documents.upload', $case),
            [
                'document_type' => 'salvage_title',
                'file' => TestFile::fake()->create('title.pdf', 500, 'application/pdf'),
            ]
        );

        $response->assertForbidden();
        $this->assertDatabaseMissing('uploaded_files', ['user_case_id' => $case->id]);
    }

    public function test_staff_can_approve_document_via_api(): void
    {
        $customer = User::factory()->create();
        $staff = User::factory()->staff()->create();
        $case = $this->makeCase($customer);

        $document = UploadedFile::create([
            'user_case_id' => $case->id,
            'user_id' => $customer->id,
            'uploaded_by' => $customer->id,
            'document_type' => 'salvage_title',
            'original_name' => 'title.pdf',
            'file_path' => "cases/{$case->id}/documents/title.pdf",
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'status' => 'pending',
        ]);

        Sanctum::actingAs($staff);

        $response = $this->postJson(
            "/api/cases/{$case->id}/documents/{$document->id}/approve",
            ['notes' => 'Looks good']
        );

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertSame('approved', $document->fresh()->status);
        $this->assertDatabaseHas('case_timeline', [
            'user_case_id' => $case->id,
            'event_type' => 'document_approved',
        ]);
    }
}
