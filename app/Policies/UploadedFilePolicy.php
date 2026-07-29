<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UploadedFile;
use Illuminate\Auth\Access\HandlesAuthorization;

class UploadedFilePolicy
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
     * Determine whether the user can view the document.
     */
    public function view(User $user, UploadedFile $file): bool
    {
        // Owner can view their documents
        if ($user->id === $file->user_id) {
            return true;
        }

        // Owner of the case can view
        if ($file->case && $user->id === $file->case->user_id) {
            return true;
        }

        // Inspector assigned to the case can view
        if ($user->role === 'inspector' && $file->case) {
            return $file->case->appointments()
                ->where('inspector_id', $user->id)
                ->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can download the document.
     */
    public function download(User $user, UploadedFile $file): bool
    {
        return $this->view($user, $file);
    }

    /**
     * Determine whether the user can update the document.
     */
    public function update(User $user, UploadedFile $file): bool
    {
        // Owner can re-upload rejected documents
        if ($user->id === $file->user_id || ($file->case && $user->id === $file->case->user_id)) {
            return $file->status === 'rejected';
        }

        // Inspector can update status during inspection
        if ($user->role === 'inspector' && $file->case) {
            return $file->case->appointments()
                ->where('inspector_id', $user->id)
                ->where('status', 'confirmed')
                ->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can delete the document.
     */
    public function delete(User $user, UploadedFile $file): bool
    {
        // Only owner can delete, and only if case is in draft
        if ($user->id === $file->user_id || ($file->case && $user->id === $file->case->user_id)) {
            return $file->case && $file->case->status === 'draft';
        }

        return false;
    }

    /**
     * Determine whether the user can approve/reject documents.
     */
    public function review(User $user, UploadedFile $file): bool
    {
        // Only admin/staff can review (handled by before())
        return false;
    }
}
