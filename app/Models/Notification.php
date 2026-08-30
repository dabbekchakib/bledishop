<?php

namespace App\Models;

use App\Enums\NotificationPriority;
use App\Enums\NotificationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Eloquent model over Laravel's `notifications` table, specialised for the
 * admin notification centre. Adds typed access to the admin payload stored in
 * the `data` JSON column, in addition to the native read/unread helpers.
 */
class Notification extends DatabaseNotification
{
    protected $table = 'notifications';

    /**
     * The admin notification event type stored in the payload.
     */
    public function notificationType(): ?NotificationType
    {
        $type = $this->data['type'] ?? null;

        if (filled($type) && in_array($type, array_column(NotificationType::cases(), 'value'), true)) {
            return NotificationType::tryFrom((string) $type);
        }

        return null;
    }

    public function notificationPriority(): NotificationPriority
    {
        $priority = $this->data['priority'] ?? NotificationPriority::Info->value;

        return NotificationPriority::tryFrom((string) $priority) ?? NotificationPriority::Info;
    }

    /**
     * Whether this is an admin notification produced by this system.
     */
    public function isAdminNotification(): bool
    {
        return array_key_exists('type', $this->data);
    }

    public function title(): string
    {
        return (string) ($this->data['title'] ?? '');
    }

    public function message(): string
    {
        return (string) ($this->data['message'] ?? '');
    }

    public function actionUrl(): ?string
    {
        $url = $this->data['action_url'] ?? null;

        return filled($url) ? (string) $url : null;
    }

    /**
     * True when this notification targets the given type/entity pair, used for
     * de-duplication checks.
     */
    public function matchesTypeEntity(NotificationType $type, mixed $subject): bool
    {
        $subjectType = $subject instanceof Model ? $subject->getMorphClass() : get_class($subject);
        $subjectId = $subject instanceof Model ? (string) $subject->getKey() : (string) $subject;

        return ($this->data['type'] ?? null) === $type->value
            && ($this->data['subject_type'] ?? null) === $subjectType
            && (string) ($this->data['subject_id'] ?? '') === $subjectId;
    }
}
