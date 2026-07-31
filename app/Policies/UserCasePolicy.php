<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserCase;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserCasePolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     * Admins and staff can do anything.
     */
    public function before(User $user, string $ability): ?bool
    {
        if (in_array($user->role, ['admin', 'staff'])) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view any cases.
     */
    public function viewAny(User $user): bool
    {
        return true; // Filtered by query in controller
    }

    /**
     * Determine whether the user can view the case.
     */
    public function view(User $user, UserCase $case): bool
    {
        // Customer can view their own cases
        if ($user->id === $case->user_id) {
            return true;
        }

        // Inspector can view assigned cases
        if ($user->role === 'inspector') {
            return $case->appointments()
                ->where('inspector_id', $user->id)
                ->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can create cases.
     */
    public function create(User $user): bool
    {
        // Any authenticated user can create a case
        return true;
    }

    /**
     * Determine whether the user can update the case.
     */
    public function update(User $user, UserCase $case): bool
    {
        // Customer can update their own cases while they still have actions to take
        if ($user->id === $case->user_id) {
            return in_array($case->status, [
                'draft',
                'pending_documents',
                'pending_payment',
                'paid',
                'documents_complete',
                'pending_scheduling'
            ]);
        }

        // Inspector can update cases they're assigned to during inspection
        if ($user->role === 'inspector') {
            return $case->appointments()
                ->where('inspector_id', $user->id)
                ->where('status', 'confirmed')
                ->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can delete the case.
     */
    public function delete(User $user, UserCase $case): bool
    {
        // Only owner can delete draft cases
        return $user->id === $case->user_id && $case->status === 'draft';
    }

    /**
     * Determine whether the user can upload documents.
     */
    public function uploadDocuments(User $user, UserCase $case): bool
    {
        if ($user->id === $case->user_id) {
            return in_array($case->status, [
                'draft',
                'submitted',
                'pending_documents',
                'documents_rejected'
            ]);
        }

        return false;
    }

    /**
     * Determine whether the user can schedule an appointment.
     */
    public function schedule(User $user, UserCase $case): bool
    {
        if ($user->id !== $case->user_id) {
            return false;
        }

        return in_array($case->status, [
            'paid',
            'documents_complete',
            'pending_scheduling'
        ]);
    }

    /**
     * Determine whether the user can pay for the case.
     */
    public function pay(User $user, UserCase $case): bool
    {
        return $user->id === $case->user_id && $case->status === 'pending_payment';
    }

    /**
     * Determine whether the user can view the inspection results.
     */
    public function viewInspection(User $user, UserCase $case): bool
    {
        if ($user->id === $case->user_id) {
            return $case->inspection !== null;
        }

        if ($user->role === 'inspector') {
            return $case->inspection?->inspector_id === $user->id;
        }

        return false;
    }
}
