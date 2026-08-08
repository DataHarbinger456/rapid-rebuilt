<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One attempt at reading an uploaded document with a vision model.
 *
 * The provider and model that ran are recorded on the row itself; config may
 * change, but what read a given document must not change with it.
 */
class DocumentExtraction extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SUPERSEDED = 'superseded';

    public const STATUSES = [
        self::STATUS_PENDING => 'Queued',
        self::STATUS_PROCESSING => 'Reading',
        self::STATUS_COMPLETED => 'Read',
        self::STATUS_FAILED => 'Failed',
        self::STATUS_SUPERSEDED => 'Superseded',
    ];

    /**
     * Statuses a new extraction must not collide with: an extraction already
     * in flight for the same file.
     */
    public const ACTIVE_STATUSES = [self::STATUS_PENDING, self::STATUS_PROCESSING];

    protected $fillable = [
        'uploaded_file_id',
        'user_case_id',
        'document_type',
        'status',
        'provider',
        'model',
        'raw_payload',
        'prompt_tokens',
        'completion_tokens',
        'error_message',
        'started_at',
        'completed_at',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        // A salvage title carries the owner's name and address, so the
        // provider's verbatim response is encrypted at rest.
        'raw_payload' => 'encrypted:array',
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    // =====================
    // RELATIONSHIPS
    // =====================

    public function uploadedFile(): BelongsTo
    {
        return $this->belongsTo(UploadedFile::class);
    }

    public function userCase(): BelongsTo
    {
        return $this->belongsTo(UserCase::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(DocumentExtractionField::class);
    }

    // =====================
    // ACCESSORS
    // =====================

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    // =====================
    // SCOPES
    // =====================

    public function scopeActive($query)
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES);
    }
}
