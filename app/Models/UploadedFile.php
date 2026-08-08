<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class UploadedFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_case_id',
        'uploaded_by',
        'document_type',
        'original_name',
        'filename',
        'path',
        'file_path',
        'file_size',
        'mime_type',
        'size',
        'disk',
        'collection',
        'metadata',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'rejection_reason',
    ];

    protected $casts = [
        'size' => 'integer',
        'file_size' => 'integer',
        'metadata' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public const STATUSES = [
        'pending' => 'Pending Review',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'replaced' => 'Replaced',
    ];

    public const DOCUMENT_TYPES = [
        'salvage_title' => 'Salvage Title',
        'bill_of_sale' => 'Bill of Sale',
        'owner_id_front' => "Driver's License - Front",
        'owner_id_back' => "Driver's License - Back",
        'insurance_card' => 'Insurance Card',
        'damage_photos' => 'Damage Photos',
        'repair_photos' => 'Repair Photos',
        'component_receipts' => 'Component Receipts',
        'insurance_estimate' => 'Insurance Estimate',
        'power_of_attorney' => 'Power of Attorney',
        'hsmv_82040' => 'HSMV 82040',
        'hsmv_84490' => 'HSMV 84490',
        'hsmv_84491' => 'HSMV 84491',
        'hsmv_82050' => 'HSMV 82050',
        'hsmv_82053' => 'HSMV 82053',
        'inspection_report' => 'Inspection Report',
        'document_checklist' => 'Document Checklist',
        'cover_letter' => 'Cover Letter',
        'other' => 'Other',
    ];

    public const COLLECTIONS = [
        'documents' => 'Documents',
        'photos' => 'Photos',
        'title' => 'Title Documents',
        'identification' => 'Identification',
        'inspection' => 'Inspection Photos',
        'receipts' => 'Receipts',
        'other' => 'Other',
    ];

    // =====================
    // RELATIONSHIPS
    // =====================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function userCase()
    {
        return $this->belongsTo(UserCase::class);
    }

    public function inspectionPhoto()
    {
        return $this->hasOne(InspectionPhoto::class);
    }

    public function extractions()
    {
        return $this->hasMany(DocumentExtraction::class)->latest('id');
    }

    /**
     * The attempt staff should be looking at: the newest one that has not been
     * superseded by a re-run.
     */
    public function currentExtraction(): ?DocumentExtraction
    {
        return $this->extractions
            ->firstWhere(fn (DocumentExtraction $e): bool => $e->status !== DocumentExtraction::STATUS_SUPERSEDED);
    }

    // =====================
    // ACCESSORS
    // =====================

    public function getUrlAttribute(): string
    {
        $disk = $this->disk ?? 'private';
        $path = $this->file_path ?? $this->path;

        return Storage::disk($disk)->url($path);
    }

    public function getTemporaryUrlAttribute(): string
    {
        $disk = $this->disk ?? 'private';
        $path = $this->file_path ?? $this->path;

        // temporaryUrl() is only supported by the s3 driver.
        if (config("filesystems.disks.{$disk}.driver") === 's3') {
            return Storage::disk($disk)->temporaryUrl($path, now()->addMinutes(30));
        }

        return url("/api/cases/{$this->user_case_id}/documents/{$this->id}/download");
    }

    public function getHumanSizeAttribute(): string
    {
        $bytes = $this->file_size ?? $this->size ?? 0;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getIsImageAttribute(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }

    public function getIsPdfAttribute(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    public function getExtensionAttribute(): string
    {
        return pathinfo($this->original_name, PATHINFO_EXTENSION);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status ?? 'Unknown';
    }

    public function getDocumentTypeLabelAttribute(): string
    {
        return self::DOCUMENT_TYPES[$this->document_type] ?? $this->document_type ?? 'Unknown';
    }

    public function getIsPendingAttribute(): bool
    {
        return $this->status === 'pending';
    }

    public function getIsApprovedAttribute(): bool
    {
        return $this->status === 'approved';
    }

    public function getIsRejectedAttribute(): bool
    {
        return $this->status === 'rejected';
    }

    // =====================
    // SCOPES
    // =====================

    public function scopeByCollection($query, string $collection)
    {
        return $query->where('collection', $collection);
    }

    public function scopeByDocumentType($query, string $type)
    {
        return $query->where('document_type', $type);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeImages($query)
    {
        return $query->where('mime_type', 'like', 'image/%');
    }

    public function scopePdfs($query)
    {
        return $query->where('mime_type', 'application/pdf');
    }

    public function scopeForCase($query, int $caseId)
    {
        return $query->where('user_case_id', $caseId);
    }

    // =====================
    // METHODS
    // =====================

    public function exists(): bool
    {
        $disk = $this->disk ?? 'private';
        $path = $this->file_path ?? $this->path;

        return Storage::disk($disk)->exists($path);
    }

    public function download()
    {
        $disk = $this->disk ?? 'private';
        $path = $this->file_path ?? $this->path;

        return Storage::disk($disk)->download($path, $this->original_name);
    }

    public function getContent(): string
    {
        $disk = $this->disk ?? 'private';
        $path = $this->file_path ?? $this->path;

        return Storage::disk($disk)->get($path);
    }

    public function delete(): bool
    {
        $disk = $this->disk ?? 'private';
        $path = $this->file_path ?? $this->path;

        // Delete from storage
        Storage::disk($disk)->delete($path);

        // Delete from database
        return parent::delete();
    }

    public function approve(int $reviewerId, ?string $notes = null): void
    {
        $this->update([
            'status' => 'approved',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);
    }

    public function reject(int $reviewerId, string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            'review_notes' => $reason,
        ]);
    }

    public static function storeFile(
        $file,
        int $userId,
        ?int $userCaseId = null,
        string $collection = 'documents',
        string $disk = 'public'
    ): self {
        $path = $file->store("uploads/{$collection}", $disk);

        return self::create([
            'user_id' => $userId,
            'uploaded_by' => $userId,
            'user_case_id' => $userCaseId,
            'original_name' => $file->getClientOriginalName(),
            'filename' => basename($path),
            'path' => $path,
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'file_size' => $file->getSize(),
            'disk' => $disk,
            'collection' => $collection,
            'status' => 'pending',
        ]);
    }

    public static function storeDocument(
        $file,
        int $caseId,
        string $documentType,
        int $uploadedBy,
        string $disk = 'private'
    ): self {
        $path = $file->store("cases/{$caseId}/documents/{$documentType}", $disk);

        return self::create([
            'user_case_id' => $caseId,
            'uploaded_by' => $uploadedBy,
            'document_type' => $documentType,
            'original_name' => $file->getClientOriginalName(),
            'filename' => basename($path),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'disk' => $disk,
            'status' => 'pending',
        ]);
    }
}
