<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class UserFormScheduling extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_case_id',
        'inspector_id',
        'subject',
        'start',
        'end',
        'duration_minutes',
        'location_name',
        'location_address',
        'location_city',
        'location_state',
        'location_zip',
        'location_lat',
        'location_lng',
        'status',
        'customer_notes',
        'inspector_notes',
        'reminder_24h_sent',
        'reminder_1h_sent',
        'confirmation_sent',
        'status_reason',
        'rescheduled_from_id',
    ];

    protected $casts = [
        'start' => 'datetime',
        'end' => 'datetime',
        'location_lat' => 'decimal:8',
        'location_lng' => 'decimal:8',
        'reminder_24h_sent' => 'boolean',
        'reminder_1h_sent' => 'boolean',
        'confirmation_sent' => 'boolean',
    ];

    public const STATUSES = [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'no_show' => 'No Show',
        'cancelled' => 'Cancelled',
        'rescheduled' => 'Rescheduled',
    ];

    // =====================
    // RELATIONSHIPS
    // =====================

    public function userCase()
    {
        return $this->belongsTo(UserCase::class);
    }

    /**
     * May the customer still move this appointment?
     *
     * Written for the policy, which had been calling it since the policy was
     * written — unreachable until the ->case null bug was fixed, so its
     * absence never crashed. An appointment that has started, completed or
     * already ended can no longer be moved.
     */
    public function canReschedule(): bool
    {
        return in_array($this->status, ['pending', 'confirmed'])
            && $this->end->isFuture();
    }

    /**
     * May the customer still cancel this appointment? Slightly wider than
     * rescheduling: an in-progress inspection can be abandoned but not moved.
     */
    public function canCancel(): bool
    {
        return in_array($this->status, ['pending', 'confirmed', 'in_progress'])
            && $this->end->isFuture();
    }

    public function inspector()
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function rescheduledFrom()
    {
        return $this->belongsTo(self::class, 'rescheduled_from_id');
    }

    public function rescheduledTo()
    {
        return $this->hasOne(self::class, 'rescheduled_from_id');
    }

    // =====================
    // ACCESSORS
    // =====================

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->location_address,
            $this->location_city,
            $this->location_state,
            $this->location_zip,
        ]);

        return implode(', ', $parts);
    }

    public function getFormattedTimeAttribute(): string
    {
        return $this->start->format('M j, Y g:i A') . ' - ' . $this->end->format('g:i A');
    }

    public function getIsUpcomingAttribute(): bool
    {
        return $this->start->isFuture() && !in_array($this->status, ['cancelled', 'rescheduled']);
    }

    public function getIsTodayAttribute(): bool
    {
        return $this->start->isToday();
    }

    public function getIsPastAttribute(): bool
    {
        return $this->start->isPast();
    }

    public function getHasLocationAttribute(): bool
    {
        return $this->location_lat !== null && $this->location_lng !== null;
    }

    public function getNeedsReminder24hAttribute(): bool
    {
        if ($this->reminder_24h_sent || $this->status !== 'confirmed') {
            return false;
        }

        $hours = $this->start->diffInHours(now(), false);
        return $hours >= -24 && $hours < -23;
    }

    public function getNeedsReminder1hAttribute(): bool
    {
        if ($this->reminder_1h_sent || $this->status !== 'confirmed') {
            return false;
        }

        $hours = $this->start->diffInHours(now(), false);
        return $hours >= -1 && $hours < 0;
    }

    // =====================
    // SCOPES
    // =====================

    public function scopeUpcoming($query)
    {
        return $query->where('start', '>', now())
                     ->whereNotIn('status', ['cancelled', 'rescheduled']);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('start', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('start', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeForInspector($query, int $inspectorId)
    {
        return $query->where('inspector_id', $inspectorId);
    }

    public function scopeNeedsReminders($query)
    {
        return $query->where('status', 'confirmed')
                     ->where('start', '>', now())
                     ->where(function ($q) {
                         $q->where(function ($q2) {
                             $q2->where('reminder_24h_sent', false)
                                ->where('start', '<=', now()->addHours(24));
                         })->orWhere(function ($q2) {
                             $q2->where('reminder_1h_sent', false)
                                ->where('start', '<=', now()->addHours(1));
                         });
                     });
    }

    // =====================
    // STATUS METHODS
    // =====================

    public function confirm(): void
    {
        $this->update(['status' => 'confirmed']);

        if ($this->userCase) {
            CaseTimeline::log(
                $this->user_case_id,
                'appointment_confirmed',
                'Appointment confirmed',
                $this->formatted_time
            );
        }
    }

    public function startInspection(): void
    {
        $this->update(['status' => 'in_progress']);
    }

    public function complete(): void
    {
        $this->update(['status' => 'completed']);
    }

    public function cancel(string $reason): void
    {
        $this->update([
            'status' => 'cancelled',
            'status_reason' => $reason,
        ]);

        if ($this->userCase) {
            CaseTimeline::log(
                $this->user_case_id,
                'appointment_cancelled',
                'Appointment cancelled',
                $reason
            );
        }
    }

    public function markNoShow(): void
    {
        $this->update([
            'status' => 'no_show',
            'status_reason' => 'Customer did not show up',
        ]);

        if ($this->userCase) {
            CaseTimeline::log(
                $this->user_case_id,
                'appointment_no_show',
                'Customer no-show'
            );
        }
    }

    public function reschedule(Carbon $newStart, ?string $reason = null): self
    {
        $this->update([
            'status' => 'rescheduled',
            'status_reason' => $reason,
        ]);

        $newEnd = $newStart->copy()->addMinutes($this->duration_minutes);

        $newAppointment = self::create([
            'user_id' => $this->user_id,
            'user_case_id' => $this->user_case_id,
            'inspector_id' => $this->inspector_id,
            'subject' => $this->subject,
            'start' => $newStart,
            'end' => $newEnd,
            'duration_minutes' => $this->duration_minutes,
            'location_name' => $this->location_name,
            'location_address' => $this->location_address,
            'location_city' => $this->location_city,
            'location_state' => $this->location_state,
            'location_zip' => $this->location_zip,
            'location_lat' => $this->location_lat,
            'location_lng' => $this->location_lng,
            'status' => 'pending',
            'customer_notes' => $this->customer_notes,
            'rescheduled_from_id' => $this->id,
        ]);

        if ($this->userCase) {
            CaseTimeline::log(
                $this->user_case_id,
                'appointment_rescheduled',
                'Appointment rescheduled',
                $reason,
                [
                    'old_time' => $this->start->toIso8601String(),
                    'new_time' => $newStart->toIso8601String(),
                ]
            );
        }

        return $newAppointment;
    }

    // =====================
    // REMINDER METHODS
    // =====================

    public function markReminder24hSent(): void
    {
        $this->update(['reminder_24h_sent' => true]);
    }

    public function markReminder1hSent(): void
    {
        $this->update(['reminder_1h_sent' => true]);
    }

    public function markConfirmationSent(): void
    {
        $this->update(['confirmation_sent' => true]);
    }

    // =====================
    // AVAILABILITY CHECK
    // =====================

    public static function isSlotAvailable(
        int $inspectorId,
        Carbon $start,
        Carbon $end,
        ?int $excludeId = null
    ): bool {
        $query = self::where('inspector_id', $inspectorId)
            ->whereNotIn('status', ['cancelled', 'rescheduled'])
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start', [$start, $end])
                  ->orWhereBetween('end', [$start, $end])
                  ->orWhere(function ($q2) use ($start, $end) {
                      $q2->where('start', '<=', $start)
                         ->where('end', '>=', $end);
                  });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return !$query->exists();
    }

    public static function getAvailableSlots(
        int $inspectorId,
        Carbon $date,
        int $durationMinutes = 30
    ): array {
        $businessHours = Setting::businessHours();
        $startHour = (int) explode(':', $businessHours['start'])[0];
        $endHour = (int) explode(':', $businessHours['end'])[0];

        $slots = [];
        $current = $date->copy()->setHour($startHour)->setMinute(0)->setSecond(0);
        $dayEnd = $date->copy()->setHour($endHour)->setMinute(0)->setSecond(0);

        while ($current->lt($dayEnd)) {
            $slotEnd = $current->copy()->addMinutes($durationMinutes);

            if ($slotEnd->lte($dayEnd) && self::isSlotAvailable($inspectorId, $current, $slotEnd)) {
                $slots[] = [
                    'start' => $current->copy(),
                    'end' => $slotEnd->copy(),
                    'formatted' => $current->format('g:i A') . ' - ' . $slotEnd->format('g:i A'),
                ];
            }

            $current->addMinutes($durationMinutes);
        }

        return $slots;
    }
}
