<?php

namespace App\Services;

use App\Models\UserCase;
use App\Models\User;
use App\Models\Inspection;
use App\Models\UserFormScheduling;
use App\Models\Notification;
use App\Models\Setting;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Send notification when case is submitted
     */
    public function sendCaseSubmitted(UserCase $case): void
    {
        $this->createNotification(
            $case->user_id,
            'case_submitted',
            'Case Submitted',
            "Your case {$case->case_number} has been submitted for review.",
            $case->id
        );

        $this->sendEmail(
            $case->user,
            'Case Submitted - ' . $case->case_number,
            'emails.cases.submitted',
            [
                'case' => $case,
                'user' => $case->user,
                'vehicle' => $case->vehicle,
                'dashboardUrl' => route('customer.dashboard'),
            ]
        );

        // Notify staff. The case id is required: notifications.user_case_id
        // is NOT NULL, and this notice is about that case.
        $this->notifyStaff(
            'New Case Submitted',
            "New case {$case->case_number} from {$case->user->full_name} needs review.",
            $case->id
        );
    }

    /**
     * Send notification when case status changes
     */
    public function sendStatusChanged(UserCase $case, string $newStatus): void
    {
        $statusLabels = UserCase::STATUSES;
        $statusLabel = $statusLabels[$newStatus] ?? $newStatus;

        $this->createNotification(
            $case->user_id,
            'status_changed',
            'Case Status Updated',
            "Your case {$case->case_number} status changed to: {$statusLabel}",
            $case->id
        );

        if (Setting::get('send_email_on_status_change', true)) {
            $this->sendEmail(
                $case->user,
                "Case Update - {$case->case_number}",
                'emails.status-changed',
                ['case' => $case, 'newStatus' => $statusLabel]
            );
        }
    }

    /**
     * Send notification when inspector is assigned
     */
    public function sendInspectorAssigned(UserCase $case, User $inspector): void
    {
        // Notify customer
        $this->createNotification(
            $case->user_id,
            'inspector_assigned',
            'Inspector Assigned',
            "Inspector {$inspector->full_name} has been assigned to your case.",
            $case->id
        );

        // Notify inspector
        $this->createNotification(
            $inspector->id,
            'case_assigned',
            'New Case Assigned',
            "You have been assigned to case {$case->case_number}.",
            $case->id
        );

        $this->sendEmail(
            $inspector,
            'New Case Assignment - ' . $case->case_number,
            'emails.inspector-assigned',
            ['case' => $case, 'inspector' => $inspector]
        );
    }

    /**
     * Send payment confirmation
     */
    public function sendPaymentConfirmation(UserCase $case): void
    {
        $this->createNotification(
            $case->user_id,
            'payment_received',
            'Payment Received',
            "Payment of \${$case->total_amount} received for case {$case->case_number}.",
            $case->id
        );

        $this->sendEmail(
            $case->user,
            'Payment Confirmation - ' . $case->case_number,
            'emails.payment-confirmation',
            ['case' => $case]
        );
    }

    /**
     * Send case cancelled notification
     */
    public function sendCaseCancelled(UserCase $case): void
    {
        $this->createNotification(
            $case->user_id,
            'case_cancelled',
            'Case Cancelled',
            "Your case {$case->case_number} has been cancelled.",
            $case->id
        );

        $this->sendEmail(
            $case->user,
            'Case Cancelled - ' . $case->case_number,
            'emails.case-cancelled',
            ['case' => $case]
        );
    }

    /**
     * Send appointment confirmation
     */
    public function sendAppointmentConfirmation(UserFormScheduling $scheduling): void
    {
        $case = $scheduling->userCase;
        $dateFormatted = $scheduling->start->format('l, F j, Y');
        $timeFormatted = $scheduling->start->format('g:i A');

        $this->createNotification(
            $case->user_id,
            'appointment_scheduled',
            'Appointment Scheduled',
            "Your inspection is scheduled for {$dateFormatted} at {$timeFormatted}.",
            $case->id
        );

        $this->sendEmail(
            $case->user,
            'Appointment Confirmation - ' . $case->case_number,
            'emails.appointments.confirmation',
            [
                'appointment' => $scheduling,
                'case' => $case,
                'vehicle' => $case->vehicle,
                'detailsUrl' => route('customer.appointments.show', $scheduling),
            ]
        );

        // Notify inspector
        if ($scheduling->inspector) {
            $this->createNotification(
                $scheduling->inspector_id,
                'appointment_scheduled',
                'New Appointment',
                "New inspection scheduled for {$dateFormatted} at {$timeFormatted}.",
                $case->id
            );
        }
    }

    /**
     * Send appointment rescheduled notification
     */
    public function sendAppointmentRescheduled(UserFormScheduling $scheduling): void
    {
        $case = $scheduling->userCase;
        $dateFormatted = $scheduling->start->format('l, F j, Y');
        $timeFormatted = $scheduling->start->format('g:i A');

        $this->createNotification(
            $case->user_id,
            'appointment_rescheduled',
            'Appointment Rescheduled',
            "Your inspection has been rescheduled to {$dateFormatted} at {$timeFormatted}.",
            $case->id
        );

        $this->sendEmail(
            $case->user,
            'Appointment Rescheduled - ' . $case->case_number,
            'emails.appointment-rescheduled',
            ['scheduling' => $scheduling, 'case' => $case]
        );
    }

    /**
     * Send appointment cancelled notification
     */
    public function sendAppointmentCancelled(UserFormScheduling $scheduling, string $reason): void
    {
        $case = $scheduling->userCase;

        $this->createNotification(
            $case->user_id,
            'appointment_cancelled',
            'Appointment Cancelled',
            "Your scheduled inspection has been cancelled. Reason: {$reason}",
            $case->id
        );

        $this->sendEmail(
            $case->user,
            'Appointment Cancelled - ' . $case->case_number,
            'emails.appointment-cancelled',
            ['scheduling' => $scheduling, 'case' => $case, 'reason' => $reason]
        );
    }

    /**
     * Send appointment reminder
     */
    public function sendAppointmentReminder(UserFormScheduling $scheduling, string $type = '24h'): void
    {
        $case = $scheduling->userCase;
        $dateFormatted = $scheduling->start->format('l, F j, Y');
        $timeFormatted = $scheduling->start->format('g:i A');

        $reminderText = $type === '24h'
            ? "Reminder: Your inspection is tomorrow at {$timeFormatted}."
            : "Reminder: Your inspection is in 1 hour at {$timeFormatted}.";

        $this->createNotification(
            $case->user_id,
            'appointment_reminder',
            'Appointment Reminder',
            $reminderText,
            $case->id
        );

        $this->sendEmail(
            $case->user,
            'Appointment Reminder - ' . $case->case_number,
            'emails.appointments.reminder',
            [
                'appointment' => $scheduling,
                'case' => $case,
                'vehicle' => $case->vehicle,
                'hoursUntil' => $type === '1h' ? 1 : 24,
                'detailsUrl' => route('customer.appointments.show', $scheduling),
            ]
        );

        // SMS reminder if enabled
        if (Setting::get('send_sms_reminders', false)) {
            $this->sendSMS($case->user->phone, $reminderText);
        }
    }

    /**
     * Send no-show notification
     */
    public function sendNoShowNotification(UserFormScheduling $scheduling): void
    {
        $case = $scheduling->userCase;

        $this->createNotification(
            $case->user_id,
            'no_show',
            'Missed Appointment',
            "You missed your scheduled inspection. Please reschedule at your earliest convenience.",
            $case->id
        );

        $this->sendEmail(
            $case->user,
            'Missed Appointment - ' . $case->case_number,
            'emails.no-show',
            ['scheduling' => $scheduling, 'case' => $case]
        );
    }

    /**
     * Send inspection result notification
     */
    public function sendInspectionResult(Inspection $inspection): void
    {
        $case = $inspection->userCase;
        $passed = $inspection->result === 'passed';

        $title = $passed ? 'Inspection Passed!' : 'Inspection Failed';
        $message = $passed
            ? "Congratulations! Your vehicle passed the inspection. Documents are being prepared."
            : "Unfortunately, your vehicle did not pass inspection. Reason: {$inspection->failure_reasons}";

        $this->createNotification(
            $case->user_id,
            $passed ? 'inspection_passed' : 'inspection_failed',
            $title,
            $message,
            $case->id
        );

        $this->sendEmail(
            $case->user,
            "{$title} - " . $case->case_number,
            'emails.inspections.completed',
            [
                'inspection' => $inspection,
                'case' => $case,
                'vehicle' => $case->vehicle,
                'passed' => $passed,
                'caseUrl' => route('customer.cases.show', $case),
            ]
        );
    }

    /**
     * Send documents ready notification
     */
    public function sendDocumentsReady(UserCase $case): void
    {
        $this->createNotification(
            $case->user_id,
            'documents_ready',
            'Documents Ready',
            "Your rebuilt title documents are ready for pickup/download.",
            $case->id
        );

        $this->sendEmail(
            $case->user,
            'Documents Ready - ' . $case->case_number,
            'emails.documents-ready',
            ['case' => $case]
        );
    }

    /**
     * Send case completed notification
     */
    public function sendCaseCompleted(UserCase $case): void
    {
        $this->createNotification(
            $case->user_id,
            'case_completed',
            'Case Completed!',
            "Congratulations! Your rebuilt title case {$case->case_number} has been completed.",
            $case->id
        );

        $this->sendEmail(
            $case->user,
            'Congratulations! Case Completed - ' . $case->case_number,
            'emails.case-completed',
            ['case' => $case]
        );
    }

    /**
     * Create in-app notification
     */
    protected function createNotification(
        int $userId,
        string $type,
        string $title,
        string $message,
        int $caseId
    ): Notification {
        return Notification::create([
            'user_id' => $userId,
            'user_case_id' => $caseId,
            'type' => $type,
            'title' => $title,
            'notification' => $message,
            'data' => ['case_id' => $caseId],
        ]);
    }

    /**
     * Send email notification
     */
    protected function sendEmail(User $user, string $subject, string $template, array $data): void
    {
        if (!$user->getPreference('notifications.email', true)) {
            return;
        }

        try {
            Mail::send($template, $data, function ($message) use ($user, $subject) {
                $message->to($user->email, $user->full_name)
                    ->subject($subject)
                    ->from(Setting::companyEmail(), Setting::companyName());
            });
        } catch (\Exception $e) {
            Log::error('Failed to send email', [
                'user_id' => $user->id,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send SMS notification (placeholder - integrate with Twilio)
     */
    protected function sendSMS(?string $phone, string $message): void
    {
        if (!$phone || !Setting::get('twilio_enabled', false)) {
            return;
        }

        try {
            // TODO: Implement Twilio integration
            Log::info('SMS would be sent', ['phone' => $phone, 'message' => $message]);
        } catch (\Exception $e) {
            Log::error('Failed to send SMS', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify all staff members
     */
    protected function notifyStaff(string $title, string $message, int $caseId): void
    {
        $staffMembers = User::staff()->get();

        foreach ($staffMembers as $staff) {
            $this->createNotification(
                $staff->id,
                'staff_notification',
                $title,
                $message,
                $caseId
            );
        }
    }

    /**
     * Mark notification as read.
     *
     * There is no is_read column: read_at (nullable timestamp) and status
     * are the real ones, and the model already stamps both, skipping rows
     * that are already read.
     */
    public function markAsRead(Notification $notification): void
    {
        $notification->markAsRead();
    }

    /**
     * Mark all notifications as read for user. Returns the number of rows
     * that were still unread.
     */
    public function markAllAsRead(User $user): int
    {
        return Notification::markAllAsReadForUser($user->id);
    }

    /**
     * Get unread notifications count
     */
    public function getUnreadCount(User $user): int
    {
        return Notification::forUser($user->id)->unread()->count();
    }

    /**
     * Get user notifications
     */
    public function getUserNotifications(User $user, int $limit = 20)
    {
        return Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
