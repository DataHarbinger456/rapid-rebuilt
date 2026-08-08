<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Document extraction logs three timeline events that the enum does not know
 * about. MySQL truncates an unknown enum value and raises a warning, which
 * surfaces as a PDOException and 500s the request — the exact bug the
 * component events hit in 2026_08_07_120000.
 *
 * MySQL has no "add value to enum" operation, so the list is rewritten in full:
 * the original set, plus the component events added since, plus the three here.
 */
return new class extends Migration
{
    private const EXISTING = [
        'case_created', 'case_submitted', 'case_assigned', 'status_changed',
        'document_uploaded', 'document_approved', 'document_rejected',
        'document_requested', 'payment_initiated', 'payment_received',
        'payment_failed', 'appointment_scheduled', 'appointment_confirmed',
        'appointment_rescheduled', 'appointment_cancelled', 'appointment_no_show',
        'inspection_started', 'inspection_paused', 'inspection_completed',
        'inspection_passed', 'inspection_failed', 'documents_generated',
        'documents_printed', 'sent_to_dmv', 'dmv_received', 'title_issued',
        'case_completed', 'case_cancelled', 'note_added', 'message_sent',
        'reminder_sent', 'other', 'component_added', 'component_removed',
    ];

    private const ADDED = ['extraction_completed', 'extraction_failed', 'extraction_confirmed'];

    public function up(): void
    {
        $this->setEnum(array_merge(self::EXISTING, self::ADDED));
    }

    public function down(): void
    {
        // Rows using the new values would otherwise be truncated to an empty
        // string by the column change, so they are relabelled first.
        DB::table('case_timeline')
            ->whereIn('event_type', self::ADDED)
            ->update(['event_type' => 'other']);

        $this->setEnum(self::EXISTING);
    }

    private function setEnum(array $values): void
    {
        $list = implode(',', array_map(fn (string $v) => "'{$v}'", $values));

        DB::statement("ALTER TABLE case_timeline MODIFY COLUMN event_type ENUM({$list}) NOT NULL");
    }
};
