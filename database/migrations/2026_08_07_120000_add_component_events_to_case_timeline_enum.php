<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * ComponentController logs 'component_added' and 'component_removed', but
 * neither value existed in the case_timeline.event_type enum. MySQL truncates
 * the unknown value and raises a warning, which surfaces as a PDOException, so
 * adding or deleting a component through the API returned a 500 and rolled the
 * request back.
 *
 * The enum is rewritten in full because MySQL has no "add value to enum"
 * operation; the list below is the original set plus the two component events.
 */
return new class extends Migration
{
    private const ORIGINAL = [
        'case_created', 'case_submitted', 'case_assigned', 'status_changed',
        'document_uploaded', 'document_approved', 'document_rejected',
        'document_requested', 'payment_initiated', 'payment_received',
        'payment_failed', 'appointment_scheduled', 'appointment_confirmed',
        'appointment_rescheduled', 'appointment_cancelled', 'appointment_no_show',
        'inspection_started', 'inspection_paused', 'inspection_completed',
        'inspection_passed', 'inspection_failed', 'documents_generated',
        'documents_printed', 'sent_to_dmv', 'dmv_received', 'title_issued',
        'case_completed', 'case_cancelled', 'note_added', 'message_sent',
        'reminder_sent', 'other',
    ];

    private const ADDED = ['component_added', 'component_removed'];

    public function up(): void
    {
        $this->setEnum(array_merge(self::ORIGINAL, self::ADDED));
    }

    public function down(): void
    {
        // Rows using the new values would otherwise be truncated to an empty
        // string by the column change, so they are relabelled first.
        DB::table('case_timeline')
            ->whereIn('event_type', self::ADDED)
            ->update(['event_type' => 'other']);

        $this->setEnum(self::ORIGINAL);
    }

    private function setEnum(array $values): void
    {
        $list = implode(',', array_map(fn (string $v) => "'{$v}'", $values));

        DB::statement("ALTER TABLE case_timeline MODIFY COLUMN event_type ENUM({$list}) NOT NULL");
    }
};
