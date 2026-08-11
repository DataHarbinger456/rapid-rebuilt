<?php

namespace App\Support;

use App\Models\UploadedFile;
use App\Models\UserCase;
use Illuminate\Support\Collection;

/**
 * "4 of 8 documents approved" — the per-case document readiness meter.
 *
 * Built in memory from an already-loaded documents collection: the whole point
 * is one pass over the relation instead of a query per required type, and one
 * shared answer behind the customer view, the upload page, the admin case page
 * and the API.
 */
final class DocumentReadiness
{
    public const STATE_READY = 'ready';
    public const STATE_ACTION_REQUIRED = 'action_required';
    public const STATE_AWAITING_UPLOAD = 'awaiting_upload';
    public const STATE_IN_REVIEW = 'in_review';
    public const STATE_NOT_STARTED = 'not_started';

    /**
     * @param array<string, DocumentReadinessItem> $items keyed by document type
     */
    private function __construct(
        public readonly array $items,
        public readonly int $approvedCount,
        public readonly int $requiredTotal,
        public readonly int $percent,
        public readonly string $state,
    ) {
    }

    public static function for(UserCase $case): self
    {
        $requirements = DocumentRequirements::forCase($case);

        // 'replaced' rows are superseded by a re-upload; counting them would
        // leave a stale badge on a document the customer already fixed.
        $byType = $case->documents
            ->reject(fn (UploadedFile $document): bool => $document->status === 'replaced')
            ->groupBy('document_type');

        $items = [];

        foreach ($requirements as $type => $requirement) {
            $items[$type] = self::buildItem($requirement, collect($byType->get($type, [])));
        }

        return new self(
            items: $items,
            approvedCount: self::countRequired($items, fn (DocumentReadinessItem $i): bool => $i->isApproved()),
            requiredTotal: self::countRequired($items, fn (): bool => true),
            percent: self::percentOf($items),
            state: self::deriveState($items),
        );
    }

    /**
     * @return array<string, DocumentReadinessItem>
     */
    public function requiredItems(): array
    {
        return array_filter($this->items, fn (DocumentReadinessItem $i): bool => $i->requirement->required);
    }

    /**
     * @return array<string, DocumentReadinessItem>
     */
    public function optionalItems(): array
    {
        return array_filter($this->items, fn (DocumentReadinessItem $i): bool => !$i->requirement->required);
    }

    /**
     * @return array<string, string> document type => label, for the callers
     *                               that only ever wanted the missing list
     */
    public function missingLabels(): array
    {
        $missing = [];

        foreach ($this->requiredItems() as $type => $item) {
            // A rejected document is missing as far as the case is concerned:
            // the customer still owes a usable copy.
            if ($item->isMissing() || $item->isRejected()) {
                $missing[$type] = $item->requirement->label;
            }
        }

        return $missing;
    }

    public function isReady(): bool
    {
        return $this->state === self::STATE_READY;
    }

    public function headline(): string
    {
        if ($this->requiredTotal === 0) {
            return 'No documents required for this service type';
        }

        if ($this->isReady()) {
            return "All {$this->requiredTotal} documents approved — ready for inspection";
        }

        return "{$this->approvedCount} of {$this->requiredTotal} documents approved";
    }

    public function stateLabel(): string
    {
        return [
            self::STATE_READY => 'Ready for inspection',
            self::STATE_ACTION_REQUIRED => 'Action required',
            self::STATE_AWAITING_UPLOAD => 'Documents outstanding',
            self::STATE_IN_REVIEW => 'In review',
            self::STATE_NOT_STARTED => 'Not started',
        ][$this->state] ?? ucfirst($this->state);
    }

    /**
     * Bootstrap contextual colour, shared by the badge and the progress bar.
     */
    public function stateColor(): string
    {
        return [
            self::STATE_READY => 'success',
            self::STATE_ACTION_REQUIRED => 'danger',
            self::STATE_AWAITING_UPLOAD => 'warning',
            self::STATE_IN_REVIEW => 'info',
            self::STATE_NOT_STARTED => 'secondary',
        ][$this->state] ?? 'secondary';
    }

    public function toArray(): array
    {
        return [
            'state' => $this->state,
            'state_label' => $this->stateLabel(),
            'headline' => $this->headline(),
            'approved_count' => $this->approvedCount,
            'required_total' => $this->requiredTotal,
            'percent' => $this->percent,
            'is_ready' => $this->isReady(),
            'items' => array_values(array_map(
                fn (DocumentReadinessItem $item): array => $item->toArray(),
                $this->items
            )),
        ];
    }

    /**
     * @param Collection<int, UploadedFile> $documents documents of one type
     */
    private static function buildItem(DocumentRequirement $requirement, Collection $documents): DocumentReadinessItem
    {
        $documents = $documents->sortByDesc('created_at')->values();
        $latest = $documents->first();

        if ($latest === null) {
            return new DocumentReadinessItem($requirement, DocumentReadinessItem::STATE_MISSING);
        }

        // "Any approved" rather than "the newest is approved": a multiple type
        // such as damage_photos stays satisfied when an approved batch is
        // followed by one extra shot that has not been reviewed yet.
        $state = match (true) {
            $documents->contains(fn (UploadedFile $d): bool => $d->status === 'approved')
                => DocumentReadinessItem::STATE_APPROVED,
            $latest->status === 'rejected' => DocumentReadinessItem::STATE_REJECTED,
            default => DocumentReadinessItem::STATE_PENDING,
        };

        return new DocumentReadinessItem(
            requirement: $requirement,
            state: $state,
            latestDocument: $latest,
            rejectionReason: $state === DocumentReadinessItem::STATE_REJECTED
                ? ($latest->rejection_reason ?? $latest->review_notes)
                : null,
            documentCount: $documents->count(),
        );
    }

    /**
     * @param array<string, DocumentReadinessItem> $items
     */
    private static function countRequired(array $items, callable $predicate): int
    {
        return count(array_filter(
            $items,
            fn (DocumentReadinessItem $i): bool => $i->requirement->required && $predicate($i)
        ));
    }

    /**
     * @param array<string, DocumentReadinessItem> $items
     */
    private static function percentOf(array $items): int
    {
        $total = self::countRequired($items, fn (): bool => true);

        if ($total === 0) {
            return 100;
        }

        $approved = self::countRequired($items, fn (DocumentReadinessItem $i): bool => $i->isApproved());

        return (int) round($approved / $total * 100);
    }

    /**
     * @param array<string, DocumentReadinessItem> $items
     */
    private static function deriveState(array $items): string
    {
        $required = array_filter($items, fn (DocumentReadinessItem $i): bool => $i->requirement->required);

        if ($required === []) {
            return self::STATE_READY;
        }

        $has = fn (string $state): bool => (bool) array_filter(
            $required,
            fn (DocumentReadinessItem $i): bool => $i->state === $state
        );

        return match (true) {
            !$has(DocumentReadinessItem::STATE_MISSING)
                && !$has(DocumentReadinessItem::STATE_REJECTED)
                && !$has(DocumentReadinessItem::STATE_PENDING) => self::STATE_READY,
            $has(DocumentReadinessItem::STATE_REJECTED) => self::STATE_ACTION_REQUIRED,
            !$has(DocumentReadinessItem::STATE_APPROVED)
                && !$has(DocumentReadinessItem::STATE_PENDING) => self::STATE_NOT_STARTED,
            $has(DocumentReadinessItem::STATE_MISSING) => self::STATE_AWAITING_UPLOAD,
            default => self::STATE_IN_REVIEW,
        };
    }
}
