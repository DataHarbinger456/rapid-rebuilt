<?php

namespace App\Support;

use App\Models\UploadedFile;

/**
 * The state of one required (or optional) document on a case.
 */
final class DocumentReadinessItem
{
    public const STATE_APPROVED = 'approved';
    public const STATE_REJECTED = 'rejected';
    public const STATE_PENDING = 'pending';
    public const STATE_MISSING = 'missing';

    public function __construct(
        public readonly DocumentRequirement $requirement,
        public readonly string $state,
        public readonly ?UploadedFile $latestDocument = null,
        public readonly ?string $rejectionReason = null,
        public readonly int $documentCount = 0,
    ) {
    }

    public function isApproved(): bool
    {
        return $this->state === self::STATE_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->state === self::STATE_REJECTED;
    }

    public function isMissing(): bool
    {
        return $this->state === self::STATE_MISSING;
    }

    public function stateLabel(): string
    {
        return [
            self::STATE_APPROVED => 'Approved',
            self::STATE_REJECTED => 'Rejected',
            self::STATE_PENDING => 'In review',
            self::STATE_MISSING => 'Not uploaded',
        ][$this->state] ?? ucfirst($this->state);
    }

    /**
     * Bootstrap contextual colour for the badge.
     */
    public function stateColor(): string
    {
        return [
            self::STATE_APPROVED => 'success',
            self::STATE_REJECTED => 'danger',
            self::STATE_PENDING => 'info',
            self::STATE_MISSING => 'secondary',
        ][$this->state] ?? 'secondary';
    }

    public function toArray(): array
    {
        return [
            'type' => $this->requirement->type,
            'label' => $this->requirement->label,
            'required' => $this->requirement->required,
            'state' => $this->state,
            'state_label' => $this->stateLabel(),
            'rejection_reason' => $this->rejectionReason,
            'document_count' => $this->documentCount,
            'document_id' => $this->latestDocument?->id,
        ];
    }
}
