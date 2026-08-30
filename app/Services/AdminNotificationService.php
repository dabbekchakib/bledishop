<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Enums\Role;
use App\Models\Notification;
use App\Models\User;
use App\Notifications\AdminNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Central routing for admin notifications.
 *
 * Each event is delivered only to active administrators holding the permission
 * declared by the {@see NotificationType}, and is de-duplicated so that an
 * equivalent unread notification (same type + same subject) is never created
 * twice for the same administrator.
 */
class AdminNotificationService
{
    /**
     * Create the notification for every eligible administrator.
     *
     * @param  mixed  $subject  the related model (Order, Product, ...)
     * @param  array<string, string>  $params  explicit translation placeholders
     */
    public function notify(NotificationType $type, mixed $subject = null, array $params = []): void
    {
        foreach ($this->eligibleAdmins($type) as $admin) {
            if ($this->hasUnreadEquivalent($admin, $type, $subject)) {
                continue;
            }

            $admin->notify(new AdminNotification($type, $subject, $params, $admin->locale));
        }
    }

    /**
     * Administrators (with an admin panel role) allowed to receive this event.
     *
     * @return Collection<int, User>
     */
    protected function eligibleAdmins(NotificationType $type): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->role(Role::adminPanelRoles())
            ->get()
            ->filter(fn (User $user): bool => $user->can($type->permission()))
            ->values();
    }

    /**
     * True when the administrator already holds an equivalent unread
     * notification for the same event and subject.
     */
    protected function hasUnreadEquivalent(User $user, NotificationType $type, mixed $subject): bool
    {
        $query = $user->notifications()
            ->whereNull('read_at')
            ->where('type', AdminNotification::class)
            ->where('data->type', $type->value);

        if ($subject instanceof Model) {
            $query->where('data->subject_type', $subject->getMorphClass())
                ->where('data->subject_id', (string) $subject->getKey());
        }

        return $query->exists();
    }

    /**
     * Total number of unread admin notifications for the given user, used for
     * the real-time badge.
     */
    public function unreadCount(User $user): int
    {
        return Notification::query()
            ->where('notifiable_type', $user->getMorphClass())
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }
}
