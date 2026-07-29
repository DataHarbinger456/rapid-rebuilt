<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaseTimeline extends Model
{
    use HasFactory;

    protected $table = 'case_timeline';

    protected $fillable = [
        'user_case_id',
        'user_id',
        'event_type',
        'title',
        'description',
        'metadata',
        'is_system_event',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_system_event' => 'boolean',
    ];

    public const EVENT_TYPES = [
        // Case lifecycle
        'case_created' => 'Case Created',
        'case_submitted' => 'Case Submitted',
        'case_assigned' => 'Case Assigned',
        'status_changed' => 'Status Changed',
        'case_completed' => 'Case Completed',
        'case_cancelled' => 'Case Cancelled',
        // Documents
        'document_uploaded' => 'Document Uploaded',
        'document_approved' => 'Document Approved',
        'document_rejected' => 'Document Rejected',
        'document_requested' => 'Document Requested',
        // Payments
        'payment_initiated' => 'Payment Initiated',
        'payment_received' => 'Payment Received',
        'payment_failed' => 'Payment Failed',
        // Appointments
        'appointment_scheduled' => 'Appointment Scheduled',
        'appointment_confirmed' => 'Appointment Confirmed',
        'appointment_rescheduled' => 'Appointment Rescheduled',
        'appointment_cancelled' => 'Appointment Cancelled',
        'appointment_no_show' => 'No Show',
        // Inspection
        'inspection_started' => 'Inspection Started',
        'inspection_paused' => 'Inspection Paused',
        'inspection_completed' => 'Inspection Completed',
        'inspection_passed' => 'Inspection Passed',
        'inspection_failed' => 'Inspection Failed',
        // DMV Process
        'documents_generated' => 'Documents Generated',
        'documents_printed' => 'Documents Printed',
        'sent_to_dmv' => 'Sent to DMV',
        'dmv_received' => 'DMV Received',
        'title_issued' => 'Title Issued',
        // Communication
        'note_added' => 'Note Added',
        'message_sent' => 'Message Sent',
        'reminder_sent' => 'Reminder Sent',
        'other' => 'Other',
    ];

    // =====================
    // RELATIONSHIPS
    // =====================

    public function userCase()
    {
        return $this->belongsTo(UserCase::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // =====================
    // ACCESSORS
    // =====================

    public function getEventTypeLabelAttribute(): string
    {
        return self::EVENT_TYPES[$this->event_type] ?? $this->event_type;
    }

    public function getIconAttribute(): string
    {
        $icons = [
            'case_created' => 'file-plus',
            'case_submitted' => 'send',
            'case_assigned' => 'user-check',
            'status_changed' => 'refresh-cw',
            'document_uploaded' => 'upload',
            'document_approved' => 'check-circle',
            'document_rejected' => 'x-circle',
            'payment_received' => 'dollar-sign',
            'appointment_scheduled' => 'calendar',
            'inspection_started' => 'play',
            'inspection_passed' => 'check',
            'inspection_failed' => 'x',
            'title_issued' => 'award',
            'case_completed' => 'flag',
            'case_cancelled' => 'slash',
        ];

        return $icons[$this->event_type] ?? 'activity';
    }

    public function getColorAttribute(): string
    {
        $colors = [
            'case_created' => 'blue',
            'case_submitted' => 'blue',
            'case_completed' => 'green',
            'case_cancelled' => 'red',
            'document_approved' => 'green',
            'document_rejected' => 'red',
            'payment_received' => 'green',
            'payment_failed' => 'red',
            'inspection_passed' => 'green',
            'inspection_failed' => 'red',
            'title_issued' => 'green',
        ];

        return $colors[$this->event_type] ?? 'gray';
    }

    // =====================
    // SCOPES
    // =====================

    public function scopeSystemEvents($query)
    {
        return $query->where('is_system_event', true);
    }

    public function scopeUserEvents($query)
    {
        return $query->where('is_system_event', false);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('event_type', $type);
    }

    // =====================
    // STATIC METHODS
    // =====================

    public static function log(
        int $userCaseId,
        string $eventType,
        string $title,
        ?string $description = null,
        ?array $metadata = null,
        ?int $userId = null,
        bool $isSystem = false
    ): self {
        return self::create([
            'user_case_id' => $userCaseId,
            'user_id' => $userId ?? auth()->id(),
            'event_type' => $eventType,
            'title' => $title,
            'description' => $description,
            'metadata' => $metadata,
            'is_system_event' => $isSystem,
        ]);
    }

    public static function logStatusChange(int $userCaseId, string $oldStatus, string $newStatus, ?int $userId = null): self
    {
        return self::log(
            $userCaseId,
            'status_changed',
            "Status changed to {$newStatus}",
            "Previous status: {$oldStatus}",
            ['old_status' => $oldStatus, 'new_status' => $newStatus],
            $userId,
            true
        );
    }
}
