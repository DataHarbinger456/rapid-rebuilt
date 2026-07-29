<?php

namespace App\Services;

use App\Models\UserCase;
use App\Models\User;
use App\Models\UserFormScheduling;
use App\Models\Setting;
use App\Models\CaseTimeline;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AppointmentService
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Get available time slots for a date
     */
    public function getAvailableSlots(Carbon $date, ?User $inspector = null): array
    {
        // Check if it's a work day
        $workDays = Setting::workDays();
        $dayName = strtolower($date->format('l'));

        if (!in_array($dayName, $workDays)) {
            return [];
        }

        // Check if date is in the past
        if ($date->isPast() && !$date->isToday()) {
            return [];
        }

        $hours = Setting::businessHours();
        $duration = Setting::appointmentDuration();
        $maxPerDay = Setting::maxAppointmentsPerDay();

        $startTime = Carbon::parse($date->format('Y-m-d') . ' ' . $hours['start']);
        $endTime = Carbon::parse($date->format('Y-m-d') . ' ' . $hours['end']);

        // Get existing appointments for this date
        $query = UserFormScheduling::whereDate('start', $date)
            ->whereNotIn('status', ['cancelled', 'rescheduled']);

        if ($inspector) {
            $query->where('inspector_id', $inspector->id);
        }

        $existingAppointments = $query->get()->map(fn($a) => $a->start->format('H:i:s'))->toArray();
        $existingCount = count($existingAppointments);

        // If max appointments reached
        if ($existingCount >= $maxPerDay) {
            return [];
        }

        $slots = [];
        $current = $startTime->copy();

        while ($current->copy()->addMinutes($duration)->lte($endTime)) {
            $timeString = $current->format('H:i:s');

            // Check if slot is available
            if (!in_array($timeString, $existingAppointments)) {
                // If today, only show future slots
                if ($date->isToday() && $current->isPast()) {
                    $current->addMinutes($duration);
                    continue;
                }

                $slots[] = [
                    'time' => $timeString,
                    'formatted' => $current->format('g:i A'),
                    'datetime' => $date->format('Y-m-d') . ' ' . $timeString,
                ];
            }

            $current->addMinutes($duration);
        }

        return $slots;
    }

    /**
     * Get available dates for the next N days
     */
    public function getAvailableDates(int $daysAhead = 30, ?User $inspector = null): array
    {
        $dates = [];
        $workDays = Setting::workDays();

        for ($i = 0; $i <= $daysAhead; $i++) {
            $date = now()->addDays($i);
            $dayName = strtolower($date->format('l'));

            if (in_array($dayName, $workDays)) {
                $slots = $this->getAvailableSlots($date, $inspector);

                if (!empty($slots)) {
                    $dates[] = [
                        'date' => $date->format('Y-m-d'),
                        'formatted' => $date->format('l, F j, Y'),
                        'day_name' => $date->format('l'),
                        'available_slots' => count($slots),
                    ];
                }
            }
        }

        return $dates;
    }

    /**
     * Schedule an appointment
     */
    public function scheduleAppointment(
        UserCase $case,
        Carbon $dateTime,
        ?User $inspector = null,
        ?array $locationData = null
    ): UserFormScheduling {
        // Validate case status
        if (!in_array($case->status, ['paid', 'pending_scheduling', 'documents_complete'])) {
            throw new \Exception('Case is not ready for scheduling');
        }

        // Validate slot availability
        $availableSlots = $this->getAvailableSlots($dateTime, $inspector);
        $timeString = $dateTime->format('H:i:s');

        $isAvailable = collect($availableSlots)->contains('time', $timeString);

        if (!$isAvailable) {
            throw new \Exception('Selected time slot is not available');
        }

        $duration = Setting::appointmentDuration();
        $endTime = $dateTime->copy()->addMinutes($duration);

        return DB::transaction(function () use ($case, $dateTime, $endTime, $duration, $inspector, $locationData) {
            // Cancel any existing scheduled appointments
            UserFormScheduling::where('user_case_id', $case->id)
                ->whereIn('status', ['pending', 'confirmed'])
                ->update(['status' => 'cancelled']);

            $scheduling = UserFormScheduling::create([
                'user_id' => $case->user_id,
                'user_case_id' => $case->id,
                'inspector_id' => $inspector?->id ?? $case->assigned_inspector_id,
                'start' => $dateTime,
                'end' => $endTime,
                'duration_minutes' => $duration,
                'subject' => "Vehicle Inspection - Case {$case->case_number}",
                'status' => 'pending',
                'location_name' => $locationData['name'] ?? null,
                'location_address' => $locationData['address'] ?? null,
                'location_city' => $locationData['city'] ?? null,
                'location_state' => $locationData['state'] ?? 'FL',
                'location_zip' => $locationData['zip'] ?? null,
                'location_lat' => $locationData['lat'] ?? null,
                'location_lng' => $locationData['lng'] ?? null,
                'customer_notes' => $locationData['notes'] ?? null,
            ]);

            $case->schedule($dateTime);

            CaseTimeline::log(
                $case->id,
                'appointment_scheduled',
                'Appointment scheduled',
                "Scheduled for {$dateTime->format('l, F j, Y')} at {$dateTime->format('g:i A')}",
                [
                    'start' => $dateTime->toIso8601String(),
                    'end' => $endTime->toIso8601String(),
                    'inspector_id' => $scheduling->inspector_id,
                ]
            );

            $this->notificationService->sendAppointmentConfirmation($scheduling);

            return $scheduling;
        });
    }

    /**
     * Reschedule an appointment
     */
    public function rescheduleAppointment(
        UserFormScheduling $scheduling,
        Carbon $newDateTime,
        ?string $reason = null
    ): UserFormScheduling {
        // Validate new slot
        $availableSlots = $this->getAvailableSlots($newDateTime, $scheduling->inspector);
        $timeString = $newDateTime->format('H:i:s');

        $isAvailable = collect($availableSlots)->contains('time', $timeString);

        if (!$isAvailable) {
            throw new \Exception('Selected time slot is not available');
        }

        $oldStart = $scheduling->start->toIso8601String();
        $duration = $scheduling->duration_minutes;
        $newEndTime = $newDateTime->copy()->addMinutes($duration);

        return DB::transaction(function () use ($scheduling, $newDateTime, $newEndTime, $reason, $oldStart) {
            // Mark old as rescheduled
            $scheduling->update(['status' => 'rescheduled']);

            // Create new appointment
            $newScheduling = UserFormScheduling::create([
                'user_id' => $scheduling->user_id,
                'user_case_id' => $scheduling->user_case_id,
                'inspector_id' => $scheduling->inspector_id,
                'start' => $newDateTime,
                'end' => $newEndTime,
                'duration_minutes' => $scheduling->duration_minutes,
                'subject' => $scheduling->subject,
                'status' => 'pending',
                'location_name' => $scheduling->location_name,
                'location_address' => $scheduling->location_address,
                'location_city' => $scheduling->location_city,
                'location_state' => $scheduling->location_state,
                'location_zip' => $scheduling->location_zip,
                'location_lat' => $scheduling->location_lat,
                'location_lng' => $scheduling->location_lng,
                'customer_notes' => $scheduling->customer_notes,
                'rescheduled_from_id' => $scheduling->id,
            ]);

            $case = $scheduling->userCase;
            $case->update(['scheduled_at' => $newDateTime]);

            CaseTimeline::log(
                $case->id,
                'appointment_rescheduled',
                'Appointment rescheduled',
                $reason ?? "Rescheduled from {$oldStart} to {$newDateTime->toIso8601String()}",
                [
                    'old_start' => $oldStart,
                    'new_start' => $newDateTime->toIso8601String(),
                    'new_end' => $newEndTime->toIso8601String(),
                ]
            );

            $this->notificationService->sendAppointmentRescheduled($newScheduling);

            return $newScheduling;
        });
    }

    /**
     * Cancel appointment
     */
    public function cancelAppointment(UserFormScheduling $scheduling, string $reason): void
    {
        DB::transaction(function () use ($scheduling, $reason) {
            $scheduling->cancel($reason);

            $case = $scheduling->userCase;
            $case->updateStatus('pending_scheduling');

            $this->notificationService->sendAppointmentCancelled($scheduling, $reason);
        });
    }

    /**
     * Confirm appointment
     */
    public function confirmAppointment(UserFormScheduling $scheduling): void
    {
        $scheduling->confirm();
    }

    /**
     * Mark as no-show
     */
    public function markAsNoShow(UserFormScheduling $scheduling): void
    {
        DB::transaction(function () use ($scheduling) {
            $scheduling->markNoShow();

            $case = $scheduling->userCase;
            $case->updateStatus('pending_scheduling');

            $this->notificationService->sendNoShowNotification($scheduling);
        });
    }

    /**
     * Get inspector's schedule for a date
     */
    public function getInspectorSchedule(User $inspector, Carbon $date): Collection
    {
        return UserFormScheduling::where('inspector_id', $inspector->id)
            ->whereDate('start', $date)
            ->whereNotIn('status', ['cancelled', 'rescheduled'])
            ->with(['userCase.user', 'userCase.vehicle'])
            ->orderBy('start')
            ->get();
    }

    /**
     * Get appointments needing reminders
     */
    public function getAppointmentsForReminders(string $type = '24h'): Collection
    {
        $reminderField = $type === '24h' ? 'reminder_24h_sent' : 'reminder_1h_sent';
        $hoursAhead = $type === '24h' ? 24 : 1;

        $windowStart = now()->addHours($hoursAhead - 1);
        $windowEnd = now()->addHours($hoursAhead + 1);

        return UserFormScheduling::where($reminderField, false)
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereBetween('start', [$windowStart, $windowEnd])
            ->with(['userCase.user', 'userCase.vehicle'])
            ->get();
    }

    /**
     * Send reminders for upcoming appointments
     */
    public function sendReminders(): int
    {
        $count = 0;

        // 24-hour reminders
        if (Setting::get('send_reminder_24h', true)) {
            $appointments = $this->getAppointmentsForReminders('24h');

            foreach ($appointments as $appointment) {
                $this->notificationService->sendAppointmentReminder($appointment, '24h');
                $appointment->markReminder24hSent();
                $count++;
            }
        }

        // 1-hour reminders
        if (Setting::get('send_reminder_1h', true)) {
            $appointments = $this->getAppointmentsForReminders('1h');

            foreach ($appointments as $appointment) {
                $this->notificationService->sendAppointmentReminder($appointment, '1h');
                $appointment->markReminder1hSent();
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get today's appointments (all)
     */
    public function getTodaysAppointments(): Collection
    {
        return UserFormScheduling::today()
            ->whereNotIn('status', ['cancelled', 'rescheduled'])
            ->with(['userCase.user', 'userCase.vehicle', 'inspector'])
            ->orderBy('start')
            ->get();
    }

    /**
     * Get upcoming appointments count
     */
    public function getUpcomingCount(?User $inspector = null): int
    {
        $query = UserFormScheduling::upcoming();

        if ($inspector) {
            $query->forInspector($inspector->id);
        }

        return $query->count();
    }

    /**
     * Get scheduled date/time for a scheduling record
     */
    public function getScheduledDate(UserFormScheduling $scheduling): Carbon
    {
        return $scheduling->start;
    }

    /**
     * Get formatted scheduled time
     */
    public function getFormattedTime(UserFormScheduling $scheduling): string
    {
        return $scheduling->start->format('g:i A');
    }
}
