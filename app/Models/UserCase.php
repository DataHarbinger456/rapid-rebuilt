<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserCase extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'assigned_inspector_id',
        'case_status',
        'status',
        'service_type',
        'inspection_fee',
        'service_fee',
        'reinspection_fee',
        'total_amount',
        'is_paid',
        'payment_id',
        'priority',
        'inspection_location',
        'customer_notes',
        'internal_notes',
        'submitted_at',
        'paid_at',
        'scheduled_at',
        'inspected_at',
        'completed_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'inspection_fee' => 'decimal:2',
        'service_fee' => 'decimal:2',
        'reinspection_fee' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'is_paid' => 'boolean',
        'submitted_at' => 'datetime',
        'paid_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'inspected_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public const STATUSES = [
        'draft' => 'Draft',
        'submitted' => 'Submitted',
        'pending_payment' => 'Pending Payment',
        'paid' => 'Paid',
        'pending_documents' => 'Pending Documents',
        'documents_complete' => 'Documents Complete',
        'pending_scheduling' => 'Pending Scheduling',
        'scheduled' => 'Scheduled',
        'inspection_in_progress' => 'Inspection In Progress',
        'pending_review' => 'Pending Review',
        'passed' => 'Passed',
        'failed' => 'Failed',
        'generating_documents' => 'Generating Documents',
        'ready_for_dmv' => 'Ready for DMV',
        'submitted_to_dmv' => 'Submitted to DMV',
        'title_issued' => 'Title Issued',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

    public const SERVICE_TYPES = [
        'rebuilt_title' => 'Rebuilt Title',
        'salvage_inspection' => 'Salvage Inspection',
        'dealer_package' => 'Dealer Package',
        'expedited' => 'Expedited Service',
        'reinspection' => 'Re-inspection',
    ];

    public const PRIORITIES = [
        'low' => 'Low',
        'normal' => 'Normal',
        'high' => 'High',
        'urgent' => 'Urgent',
    ];

    // =====================
    // RELATIONSHIPS
    // =====================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedInspector()
    {
        return $this->belongsTo(User::class, 'assigned_inspector_id');
    }

    public function vehicle()
    {
        return $this->hasOne(Vehicle::class);
    }

    public function inspection()
    {
        return $this->hasOne(Inspection::class);
    }

    public function inspections()
    {
        return $this->hasMany(Inspection::class);
    }

    public function components()
    {
        return $this->hasMany(Component::class);
    }

    public function timeline()
    {
        return $this->hasMany(CaseTimeline::class)->orderBy('created_at', 'desc');
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function scheduling()
    {
        return $this->hasOne(UserFormScheduling::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function documents()
    {
        return $this->hasMany(UploadedFile::class);
    }

    // =====================
    // ACCESSORS
    // =====================

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        $colors = [
            'draft' => 'secondary',
            'submitted' => 'info',
            'pending_payment' => 'warning',
            'paid' => 'primary',
            'pending_documents' => 'warning',
            'documents_complete' => 'info',
            'pending_scheduling' => 'warning',
            'scheduled' => 'primary',
            'inspection_in_progress' => 'info',
            'pending_review' => 'warning',
            'passed' => 'success',
            'failed' => 'danger',
            'generating_documents' => 'info',
            'ready_for_dmv' => 'primary',
            'submitted_to_dmv' => 'primary',
            'title_issued' => 'success',
            'completed' => 'success',
            'cancelled' => 'danger',
        ];

        return $colors[$this->status] ?? 'secondary';
    }

    public function getServiceTypeLabelAttribute(): string
    {
        return self::SERVICE_TYPES[$this->service_type] ?? $this->service_type;
    }

    public function getPriorityLabelAttribute(): string
    {
        return self::PRIORITIES[$this->priority] ?? $this->priority;
    }

    public function getCaseNumberAttribute(): string
    {
        return 'RR-' . str_pad($this->id, 6, '0', STR_PAD_LEFT);
    }

    public function getProgressPercentageAttribute(): int
    {
        $statusOrder = array_keys(self::STATUSES);
        $currentIndex = array_search($this->status, $statusOrder);
        $total = count($statusOrder) - 2; // Exclude cancelled

        if ($currentIndex === false || $this->status === 'cancelled') {
            return 0;
        }

        return min(100, round(($currentIndex / $total) * 100));
    }

    public function getIsCompleteAttribute(): bool
    {
        return in_array($this->status, ['completed', 'title_issued']);
    }

    public function getIsCancelledAttribute(): bool
    {
        return $this->status === 'cancelled';
    }

    public function getIsActiveAttribute(): bool
    {
        return !$this->is_complete && !$this->is_cancelled;
    }

    public function getNeedsPaymentAttribute(): bool
    {
        return !$this->is_paid && in_array($this->status, ['submitted', 'pending_payment']);
    }

    public function getNeedsDocumentsAttribute(): bool
    {
        return in_array($this->status, ['pending_documents']);
    }

    public function getNeedsSchedulingAttribute(): bool
    {
        return in_array($this->status, ['pending_scheduling', 'documents_complete']);
    }

    // =====================
    // SCOPES
    // =====================

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['completed', 'cancelled', 'title_issued']);
    }

    public function scopeCompleted($query)
    {
        return $query->whereIn('status', ['completed', 'title_issued']);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopePaid($query)
    {
        return $query->where('is_paid', true);
    }

    public function scopeUnpaid($query)
    {
        return $query->where('is_paid', false);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByServiceType($query, string $type)
    {
        return $query->where('service_type', $type);
    }

    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeAssignedTo($query, int $inspectorId)
    {
        return $query->where('assigned_inspector_id', $inspectorId);
    }

    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_inspector_id');
    }

    public function scopeReadyForInspection($query)
    {
        return $query->where('is_paid', true)
                     ->whereIn('status', ['scheduled', 'documents_complete']);
    }

    public function scopeNeedsAttention($query)
    {
        return $query->where(function ($q) {
            $q->where('priority', 'urgent')
              ->orWhere('priority', 'high')
              ->orWhere(function ($q2) {
                  $q2->where('status', 'pending_documents')
                     ->where('created_at', '<', now()->subDays(3));
              });
        });
    }

    // =====================
    // STATUS WORKFLOW
    // =====================

    public function updateStatus(string $newStatus, ?int $userId = null): void
    {
        $oldStatus = $this->status;

        $this->update(['status' => $newStatus]);

        CaseTimeline::logStatusChange($this->id, $oldStatus, $newStatus, $userId);

        // Update relevant timestamps
        $this->updateStatusTimestamp($newStatus);
    }

    protected function updateStatusTimestamp(string $status): void
    {
        $timestampMap = [
            'submitted' => 'submitted_at',
            'paid' => 'paid_at',
            'scheduled' => 'scheduled_at',
            'passed' => 'inspected_at',
            'failed' => 'inspected_at',
            'completed' => 'completed_at',
            'cancelled' => 'cancelled_at',
        ];

        if (isset($timestampMap[$status])) {
            $this->update([$timestampMap[$status] => now()]);
        }
    }

    public function submit(): void
    {
        if ($this->status !== 'draft') {
            throw new \Exception('Case can only be submitted from draft status');
        }

        $this->updateStatus('submitted');
    }

    public function markAsPaid(int $paymentId): void
    {
        $this->update([
            'is_paid' => true,
            'payment_id' => $paymentId,
            'paid_at' => now(),
        ]);

        $this->updateStatus('paid');
    }

    public function assignInspector(int $inspectorId): void
    {
        $this->update(['assigned_inspector_id' => $inspectorId]);

        CaseTimeline::log(
            $this->id,
            'case_assigned',
            'Inspector assigned',
            null,
            ['inspector_id' => $inspectorId]
        );
    }

    public function schedule(\DateTime $scheduledAt): void
    {
        $this->update(['scheduled_at' => $scheduledAt]);
        $this->updateStatus('scheduled');
    }

    public function cancel(string $reason): void
    {
        $this->update([
            'cancellation_reason' => $reason,
            'cancelled_at' => now(),
        ]);

        $this->updateStatus('cancelled');
    }

    // =====================
    // PRICING
    // =====================

    public function calculateTotal(): float
    {
        $total = $this->inspection_fee + $this->service_fee + $this->reinspection_fee;
        $this->update(['total_amount' => $total]);
        return $total;
    }

    public function applyServiceFee(string $type = 'standard'): void
    {
        $fee = Setting::serviceFee($type);
        $this->update(['service_fee' => $fee]);
        $this->calculateTotal();
    }

    // =====================
    // HELPERS
    // =====================

    public function logEvent(string $type, string $title, ?string $description = null, ?array $metadata = null): CaseTimeline
    {
        return CaseTimeline::log($this->id, $type, $title, $description, $metadata);
    }

    public function addNote(string $note, bool $internal = false): void
    {
        $field = $internal ? 'internal_notes' : 'customer_notes';
        $existingNotes = $this->{$field} ?? '';
        $timestamp = now()->format('Y-m-d H:i');

        $newNote = "[{$timestamp}] {$note}";
        $updatedNotes = $existingNotes ? "{$existingNotes}\n{$newNote}" : $newNote;

        $this->update([$field => $updatedNotes]);

        CaseTimeline::log($this->id, 'note_added', 'Note added', $note, ['internal' => $internal]);
    }

    public static function getStatusWorkflow(): array
    {
        return [
            'draft' => ['submitted', 'cancelled'],
            'submitted' => ['pending_payment', 'cancelled'],
            'pending_payment' => ['paid', 'cancelled'],
            'paid' => ['pending_documents', 'pending_scheduling'],
            'pending_documents' => ['documents_complete', 'cancelled'],
            'documents_complete' => ['pending_scheduling'],
            'pending_scheduling' => ['scheduled'],
            'scheduled' => ['inspection_in_progress', 'cancelled'],
            'inspection_in_progress' => ['pending_review'],
            'pending_review' => ['passed', 'failed'],
            'passed' => ['generating_documents'],
            'failed' => ['pending_scheduling'],
            'generating_documents' => ['ready_for_dmv'],
            'ready_for_dmv' => ['submitted_to_dmv'],
            'submitted_to_dmv' => ['title_issued'],
            'title_issued' => ['completed'],
            'completed' => [],
            'cancelled' => [],
        ];
    }

    public function canTransitionTo(string $newStatus): bool
    {
        $workflow = self::getStatusWorkflow();
        $allowedTransitions = $workflow[$this->status] ?? [];

        return in_array($newStatus, $allowedTransitions);
    }
}
