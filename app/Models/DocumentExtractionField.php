<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single field the model was asked to read, plus the human decision on it.
 *
 * 'confirmed' (staff accepted the AI value) is deliberately distinct from
 * 'corrected' (staff typed something else) and 'rejected' (not present or
 * unreadable — leave the column alone). The distinction turns the per-field
 * correction rate into a straight query, which is what tells us which parts of
 * the prompt need work.
 */
class DocumentExtractionField extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CORRECTED = 'corrected';
    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_PENDING => 'Awaiting review',
        self::STATUS_CONFIRMED => 'Confirmed',
        self::STATUS_CORRECTED => 'Corrected',
        self::STATUS_REJECTED => 'Rejected',
    ];

    /**
     * Statuses whose value may be written to the vehicle.
     */
    public const ACCEPTED_STATUSES = [self::STATUS_CONFIRMED, self::STATUS_CORRECTED];

    protected $fillable = [
        'document_extraction_id',
        'field_key',
        'ai_value',
        'confirmed_value',
        'status',
        'confidence',
        'confirmed_by',
        'confirmed_at',
    ];

    protected $casts = [
        'confidence' => 'float',
        'confirmed_at' => 'datetime',
    ];

    // =====================
    // RELATIONSHIPS
    // =====================

    public function extraction(): BelongsTo
    {
        return $this->belongsTo(DocumentExtraction::class, 'document_extraction_id');
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    // =====================
    // ACCESSORS
    // =====================

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * The value that should reach the vehicle, or null if this field carries
     * no accepted human decision.
     */
    public function getAcceptedValueAttribute(): ?string
    {
        if (! in_array($this->status, self::ACCEPTED_STATUSES, true)) {
            return null;
        }

        $value = $this->confirmed_value ?? $this->ai_value;

        return ($value === null || trim($value) === '') ? null : trim($value);
    }
}
