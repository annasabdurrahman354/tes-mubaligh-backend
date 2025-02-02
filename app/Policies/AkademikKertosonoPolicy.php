<?php

namespace App\Policies;

use App\Models\User;
use App\Models\AkademikKertosono;
use Illuminate\Auth\Access\HandlesAuthorization;

class AkademikKertosonoPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_akademik::kertosono');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AkademikKertosono $akademikKertosono): bool
    {
        return $user->can('view_akademik::kertosono');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_akademik::kertosono');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AkademikKertosono $akademikKertosono): bool
    {
        return $user->can('update_akademik::kertosono');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AkademikKertosono $akademikKertosono): bool
    {
        return $user->can('delete_akademik::kertosono');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_akademik::kertosono');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, AkademikKertosono $akademikKertosono): bool
    {
        return $user->can('force_delete_akademik::kertosono');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_akademik::kertosono');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, AkademikKertosono $akademikKertosono): bool
    {
        return $user->can('restore_akademik::kertosono');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_akademik::kertosono');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, AkademikKertosono $akademikKertosono): bool
    {
        return $user->can('replicate_akademik::kertosono');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_akademik::kertosono');
    }
}
