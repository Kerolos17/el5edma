<?php

namespace App\Policies;

use App\Models\MinistryNotification;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MinistryNotificationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any ministry notifications.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view their own notifications
        return in_array($user->role, ['super_admin', 'service_leader', 'family_leader', 'servant']);
    }

    /**
     * Determine whether the user can view the ministry notification.
     */
    public function view(User $user, MinistryNotification $notification): bool
    {
        // Users can only view their own notifications
        return $notification->user_id === $user->id;
    }

    /**
     * Determine whether the user can create ministry notifications.
     */
    public function create(User $user): bool
    {
        // Only super_admin and service_leader can create notifications
        return in_array($user->role, ['super_admin', 'service_leader']);
    }

    /**
     * Determine whether the user can update the ministry notification.
     */
    public function update(User $user, MinistryNotification $notification): bool
    {
        // Only super_admin and service_leader can update notifications
        return in_array($user->role, ['super_admin', 'service_leader']);
    }

    /**
     * Determine whether the user can delete the ministry notification.
     */
    public function delete(User $user, MinistryNotification $notification): bool
    {
        // Users can delete their own notifications, or admins can delete any
        return $notification->user_id === $user->id || in_array($user->role, ['super_admin', 'service_leader']);
    }

    /**
     * Determine whether the user can restore the ministry notification.
     */
    public function restore(User $user, MinistryNotification $notification): bool
    {
        // Only super_admin and service_leader can restore notifications
        return in_array($user->role, ['super_admin', 'service_leader']);
    }

    /**
     * Determine whether the user can permanently delete the ministry notification.
     */
    public function forceDelete(User $user, MinistryNotification $notification): bool
    {
        // Only super_admin can permanently delete notifications
        return $user->role === 'super_admin';
    }
}
