<?php

namespace App\Services;

use App\Models\UserCase;
use App\Models\User;
use App\Models\UploadedFile;
use App\Models\CaseTimeline;
use Illuminate\Http\UploadedFile as HttpUploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentService
{
    /**
     * Required documents for rebuilt title
     */
    public const REQUIRED_DOCUMENTS = [
        'salvage_title' => [
            'label' => 'Salvage Title',
            'description' => 'Original salvage title or certificate of destruction',
            'required' => true,
            'accept' => 'image/*,.pdf',
        ],
        'bill_of_sale' => [
            'label' => 'Bill of Sale',
            'description' => 'Bill of sale from when you purchased the vehicle',
            'required' => true,
            'accept' => 'image/*,.pdf',
        ],
        'owner_id' => [
            'label' => 'Owner ID',
            'description' => 'Valid government-issued photo ID of the owner',
            'required' => true,
            'accept' => 'image/*,.pdf',
        ],
        'insurance_card' => [
            'label' => 'Insurance Card',
            'description' => 'Current insurance card for the vehicle',
            'required' => true,
            'accept' => 'image/*,.pdf',
        ],
        'damage_photos' => [
            'label' => 'Damage Photos',
            'description' => 'Photos showing the vehicle before repair (damaged state)',
            'required' => true,
            'accept' => 'image/*',
            'multiple' => true,
        ],
        'repair_photos' => [
            'label' => 'Repair Photos',
            'description' => 'Photos showing the vehicle after repair',
            'required' => true,
            'accept' => 'image/*',
            'multiple' => true,
        ],
        'component_receipts' => [
            'label' => 'Component Receipts',
            'description' => 'Receipts for major components purchased for repair',
            'required' => true,
            'accept' => 'image/*,.pdf',
            'multiple' => true,
        ],
        'insurance_estimate' => [
            'label' => 'Insurance Estimate/Report',
            'description' => 'Insurance company estimate or damage report',
            'required' => false,
            'accept' => 'image/*,.pdf',
        ],
        'power_of_attorney' => [
            'label' => 'Power of Attorney',
            'description' => 'If someone else will be handling the title application',
            'required' => false,
            'accept' => 'image/*,.pdf',
        ],
    ];

    /**
     * Upload a document for a case
     */
    public function uploadDocument(
        UserCase $case,
        HttpUploadedFile $file,
        string $documentType,
        ?int $uploadedBy = null
    ): UploadedFile {
        // Validate document type
        if (!array_key_exists($documentType, self::REQUIRED_DOCUMENTS)) {
            throw new \Exception("Invalid document type: {$documentType}");
        }

        // Validate file type
        $allowedMimes = $this->getAllowedMimes($documentType);
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new \Exception('Invalid file type');
        }

        // Generate file path
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = "cases/{$case->id}/documents/{$documentType}/{$filename}";

        // Store file
        Storage::disk('private')->put($path, file_get_contents($file));

        // Create database record
        $document = UploadedFile::create([
            'user_case_id' => $case->id,
            'uploaded_by' => $uploadedBy ?? $case->user_id,
            'document_type' => $documentType,
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'status' => 'pending',
        ]);

        CaseTimeline::log(
            $case->id,
            'document_uploaded',
            'Document uploaded',
            self::REQUIRED_DOCUMENTS[$documentType]['label'],
            ['document_type' => $documentType, 'document_id' => $document->id]
        );

        return $document;
    }

    /**
     * Delete a document
     */
    public function deleteDocument(UploadedFile $document): void
    {
        // Delete file from storage
        Storage::disk('private')->delete($document->file_path);

        CaseTimeline::log(
            $document->user_case_id,
            'document_deleted',
            'Document deleted',
            self::REQUIRED_DOCUMENTS[$document->document_type]['label'] ?? $document->document_type,
            ['document_type' => $document->document_type]
        );

        // Delete record
        $document->delete();
    }

    /**
     * Approve a document
     */
    public function approveDocument(UploadedFile $document, User $reviewer, ?string $notes = null): void
    {
        $document->update([
            'status' => 'approved',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);

        CaseTimeline::log(
            $document->user_case_id,
            'document_approved',
            'Document approved',
            self::REQUIRED_DOCUMENTS[$document->document_type]['label'] ?? $document->document_type,
            ['document_type' => $document->document_type, 'reviewer_id' => $reviewer->id]
        );
    }

    /**
     * Reject a document
     */
    public function rejectDocument(UploadedFile $document, User $reviewer, string $reason): void
    {
        $document->update([
            'status' => 'rejected',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_notes' => $reason,
        ]);

        CaseTimeline::log(
            $document->user_case_id,
            'document_rejected',
            'Document rejected',
            "Reason: {$reason}",
            ['document_type' => $document->document_type, 'reviewer_id' => $reviewer->id]
        );
    }

    /**
     * Get required documents configuration
     */
    public function getRequiredDocuments(): array
    {
        return self::REQUIRED_DOCUMENTS;
    }

    /**
     * Get documents for a case
     */
    public function getCaseDocuments(UserCase $case): Collection
    {
        return $case->hasMany(UploadedFile::class, 'user_case_id')
            ->orderBy('document_type')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get missing required documents for a case
     */
    public function getMissingDocuments(UserCase $case): array
    {
        $requiredTypes = collect(self::REQUIRED_DOCUMENTS)
            ->filter(fn($config) => $config['required'])
            ->keys()
            ->toArray();

        $uploadedTypes = UploadedFile::where('user_case_id', $case->id)
            ->whereIn('status', ['pending', 'approved'])
            ->pluck('document_type')
            ->unique()
            ->toArray();

        $missing = [];
        foreach ($requiredTypes as $type) {
            if (!in_array($type, $uploadedTypes)) {
                $missing[$type] = self::REQUIRED_DOCUMENTS[$type]['label'];
            }
        }

        return $missing;
    }

    /**
     * Check if all required documents are uploaded
     */
    public function hasAllRequiredDocuments(UserCase $case): bool
    {
        return empty($this->getMissingDocuments($case));
    }

    /**
     * Check if all documents are approved
     */
    public function allDocumentsApproved(UserCase $case): bool
    {
        $requiredTypes = collect(self::REQUIRED_DOCUMENTS)
            ->filter(fn($config) => $config['required'])
            ->keys()
            ->toArray();

        foreach ($requiredTypes as $type) {
            $hasApproved = UploadedFile::where('user_case_id', $case->id)
                ->where('document_type', $type)
                ->where('status', 'approved')
                ->exists();

            if (!$hasApproved) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get documents pending review
     */
    public function getPendingReviewDocuments(?int $limit = null): Collection
    {
        $query = UploadedFile::where('status', 'pending')
            ->with(['userCase.user', 'userCase.vehicle'])
            ->orderBy('created_at', 'asc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Get document download URL (signed for private storage)
     */
    public function getDownloadUrl(UploadedFile $document): string
    {
        return Storage::disk('private')->temporaryUrl(
            $document->file_path,
            now()->addMinutes(30)
        );
    }

    /**
     * Get document content
     */
    public function getDocumentContent(UploadedFile $document): string
    {
        return Storage::disk('private')->get($document->file_path);
    }

    /**
     * Get allowed MIME types for a document type
     */
    protected function getAllowedMimes(string $documentType): array
    {
        $config = self::REQUIRED_DOCUMENTS[$documentType] ?? null;

        if (!$config) {
            return [];
        }

        $accept = $config['accept'] ?? 'image/*,.pdf';

        $mimes = [];

        if (str_contains($accept, 'image/*')) {
            $mimes = array_merge($mimes, [
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp',
                'image/heic',
                'image/heif',
            ]);
        }

        if (str_contains($accept, '.pdf')) {
            $mimes[] = 'application/pdf';
        }

        return $mimes;
    }

    /**
     * Get document statistics for dashboard
     */
    public function getDocumentStats(): array
    {
        return [
            'pending_review' => UploadedFile::where('status', 'pending')->count(),
            'approved_today' => UploadedFile::where('status', 'approved')
                ->whereDate('reviewed_at', today())
                ->count(),
            'rejected_today' => UploadedFile::where('status', 'rejected')
                ->whereDate('reviewed_at', today())
                ->count(),
        ];
    }

    /**
     * Get documents by type for a case
     */
    public function getDocumentsByType(UserCase $case, string $type): Collection
    {
        return UploadedFile::where('user_case_id', $case->id)
            ->where('document_type', $type)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Replace a rejected document
     */
    public function replaceDocument(
        UploadedFile $oldDocument,
        HttpUploadedFile $newFile,
        ?int $uploadedBy = null
    ): UploadedFile {
        $case = $oldDocument->userCase;
        $documentType = $oldDocument->document_type;

        // Upload new document
        $newDocument = $this->uploadDocument($case, $newFile, $documentType, $uploadedBy);

        // Mark old document as replaced
        $oldDocument->update(['status' => 'replaced']);

        CaseTimeline::log(
            $case->id,
            'document_replaced',
            'Document replaced',
            self::REQUIRED_DOCUMENTS[$documentType]['label'],
            [
                'old_document_id' => $oldDocument->id,
                'new_document_id' => $newDocument->id,
            ]
        );

        return $newDocument;
    }
}
