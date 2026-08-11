<?php

namespace App\Services;

use App\Models\UserCase;
use App\Models\User;
use App\Models\UploadedFile;
use App\Models\CaseTimeline;
use App\Support\DocumentReadiness;
use App\Support\DocumentRequirement;
use App\Support\DocumentRequirements;
use Illuminate\Http\UploadedFile as HttpUploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentService
{
    /**
     * Upload a document for a case
     */
    public function uploadDocument(
        UserCase $case,
        HttpUploadedFile $file,
        string $documentType,
        ?int $uploadedBy = null
    ): UploadedFile {
        // Validated against the catalogue of known types, not against this
        // case's requirement list: trimming a service type's list must not
        // start rejecting uploads that used to be accepted.
        if (!DocumentRequirements::isKnownType($documentType)) {
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
            DocumentRequirements::label($documentType),
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
            DocumentRequirements::label($document->document_type),
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
            DocumentRequirements::label($document->document_type),
            ['document_type' => $document->document_type, 'reviewer_id' => $reviewer->id]
        );

        $this->markDocumentsCompleteIfReady($document->userCase);
    }

    /**
     * Flip a case to documents_complete the moment its last required document
     * is approved, so staff see the green light without a manual status change.
     *
     * canTransitionTo() only allows this from pending_documents, so a case that
     * is already scheduled or inspected is never dragged backwards.
     */
    protected function markDocumentsCompleteIfReady(?UserCase $case): void
    {
        if ($case === null || !$case->canTransitionTo('documents_complete')) {
            return;
        }

        // The approval that triggered this was written after the relation was
        // loaded, so re-read it rather than trusting a cached collection.
        $case->load('documents');

        if (!$this->readinessFor($case)->isReady()) {
            return;
        }

        // updateStatus() writes the status_changed timeline entry itself; the
        // case_timeline.event_type enum has no 'documents_complete' value, and
        // a second row would say the same thing twice.
        $case->updateStatus('documents_complete');
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
            // Customer-facing views read rejection_reason.
            'rejection_reason' => $reason,
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
     * Required documents configuration, in the array shape the views and the
     * API have always consumed. Case-scoped when a case is given; the creation
     * wizard has none yet, so it gets the default list.
     *
     * @return array<string, array{label: string, description: string, required: bool, accept: string, multiple: bool}>
     */
    public function getRequiredDocuments(?UserCase $case = null): array
    {
        $requirements = $case
            ? DocumentRequirements::forCase($case)
            : DocumentRequirements::forServiceType(null);

        return array_map(
            fn (DocumentRequirement $requirement): array => $requirement->toArray(),
            $requirements
        );
    }

    /**
     * The document readiness meter for a case: per-type state, approved count
     * and the overall ready-for-inspection verdict.
     */
    public function readinessFor(UserCase $case): DocumentReadiness
    {
        if (!$case->relationLoaded('documents')) {
            $case->load('documents');
        }

        return DocumentReadiness::for($case);
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
     * Missing required documents for a case, keyed type => label.
     *
     * A rejected document counts as missing: the customer still owes a usable
     * copy of it.
     *
     * @return array<string, string>
     */
    public function getMissingDocuments(UserCase $case): array
    {
        return $this->readinessFor($case)->missingLabels();
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
        return $this->readinessFor($case)->isReady();
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
        // temporaryUrl() is only supported by the s3 driver; local disks
        // fall back to the authenticated API download route.
        if (config('filesystems.disks.private.driver') === 's3') {
            return Storage::disk('private')->temporaryUrl(
                $document->file_path,
                now()->addMinutes(30)
            );
        }

        return url("/api/cases/{$document->user_case_id}/documents/{$document->id}/download");
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
        $config = DocumentRequirements::catalogue()[$documentType] ?? null;

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
            DocumentRequirements::label($documentType),
            [
                'old_document_id' => $oldDocument->id,
                'new_document_id' => $newDocument->id,
            ]
        );

        return $newDocument;
    }
}
