<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserFormScheduling;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserFormSchedulingPolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        if (in_array($user->role, ['admin', 'staff'])) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view any appointments.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the appointment.
     */
    public function view(User $user, UserFormScheduling $appointment): bool
    {
        // Customer can view their appointments. The relation is userCase:
        // ->case does not exist and silently evaluated to null, which made
        // this (and update/cancel below) unreachable, so no customer could
        // ever view or reschedule their own appointment.
        if ($appointment->userCase && $user->id === $appointment->userCase->user_id) {
            return true;
        }

        // Inspector can view assigned appointments
        if ($user->role === 'inspector' && $user->id === $appointment->inspector_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create appointments.
     */
    public function create(User $user): bool
    {
        return true; // Validated in controller
    }

    /**
     * Determine whether the user can update the appointment.
     */
    public function update(User $user, UserFormScheduling $appointment): bool
    {
        // Customer can reschedule their appointments
        if ($appointment->userCase && $user->id === $appointment->userCase->user_id) {
            return $appointment->canReschedule();
        }

        // Inspector can update their assigned appointments
        if ($user->role === 'inspector' && $user->id === $appointment->inspector_id) {
            return in_array($appointment->status, ['confirmed', 'in_progress']);
        }

        return false;
    }

    /**
     * Determine whether the user can cancel the appointment.
     */
    public function cancel(User $user, UserFormScheduling $appointment): bool
    {
        // Customer can cancel
        if ($appointment->userCase && $user->id === $appointment->userCase->user_id) {
            return $appointment->canCancel();
        }

        return false;
    }

    /**
     * Determine whether the user can delete the appointment.
     */
    public function delete(User $user, UserFormScheduling $appointment): bool
    {
        // Only admin/staff can delete (handled by before())
        return false;
    }

    /**
     * Determine whether the user can start the inspection.
     */
    public function startInspection(User $user, UserFormScheduling $appointment): bool
    {
        if ($user->role !== 'inspector') {
            return false;
        }

        return $user->id === $appointment->inspector_id
            && $appointment->status === 'confirmed';
    }

    /**
     * Determine whether the user can complete the inspection.
     */
    public function completeInspection(User $user, UserFormScheduling $appointment): bool
    {
        if ($user->role !== 'inspector') {
            return false;
        }

        return $user->id === $appointment->inspector_id
            && $appointment->status === 'in_progress';
    }
}
