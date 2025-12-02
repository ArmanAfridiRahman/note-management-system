<?php

namespace App\Policies;

use App\Models\Note;
use App\Models\User;

class NotePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Note $note): bool
    {
        // Owner can view
        if ($user->id === $note->user_id) {
            return true;
        }

        // Check if note is shared with user
        return $note->shares()
            ->where('shared_with_user_id', $user->id)
            ->valid()
            ->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Note $note): bool
    {
        // Only owner can update
        if ($user->id === $note->user_id) {
            return true;
        }

        // Check if user has edit permission via share
        return $note->shares()
            ->where('shared_with_user_id', $user->id)
            ->where('permission', 'edit')
            ->valid()
            ->exists();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Note $note): bool
    {
        // Only owner can delete
        return $user->id === $note->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Note $note): bool
    {
        return $user->id === $note->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Note $note): bool
    {
        return $user->id === $note->user_id;
    }

    /**
     * Determine whether the user can share the model.
     */
    public function share(User $user, Note $note): bool
    {
        return $user->id === $note->user_id;
    }
}
