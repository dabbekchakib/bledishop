<?php

namespace App\Livewire;

use App\Models\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Top-bar notification bell for the Filament admin header.
 *
 * Shows an unread badge and a dropdown of the latest notifications. The
 * counter refreshes via gentle polling (every 15s) so no WebSocket
 * infrastructure is required, and stays in sync after reading / marking all.
 */
class AdminNotificationBell extends Component
{
    public int $unreadCount = 0;

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $recent = [];

    public function mount(): void
    {
        $this->loadNotifications();
    }

    public function refresh(): void
    {
        $this->loadNotifications();
    }

    public function render(): View
    {
        return view('livewire.admin-notification-bell');
    }

    public function markAllRead(): void
    {
        $user = auth()->user();

        if ($user === null) {
            return;
        }

        Notification::query()
            ->where('notifiable_type', $user->getMorphClass())
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $this->loadNotifications();
    }

    public function open(string $id): void
    {
        $user = auth()->user();

        $notification = Notification::query()
            ->where('id', $id)
            ->where('notifiable_type', $user?->getMorphClass())
            ->where('notifiable_id', $user?->id)
            ->first();

        $url = null;

        if ($notification !== null) {
            $notification->markAsRead();
            $url = $notification->actionUrl();
        }

        $this->loadNotifications();

        if (filled($url)) {
            $this->redirect($url);
        }
    }

    protected function loadNotifications(): void
    {
        $user = auth()->user();

        if ($user === null) {
            $this->unreadCount = 0;
            $this->recent = [];

            return;
        }

        $this->unreadCount = Notification::query()
            ->where('notifiable_type', $user->getMorphClass())
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->count();

        $items = Notification::query()
            ->where('notifiable_type', $user->getMorphClass())
            ->where('notifiable_id', $user->id)
            ->latest('created_at')
            ->limit(8)
            ->get();

        $this->recent = $items->map(function (Notification $notification): array {
            return [
                'id' => $notification->id,
                'title' => $notification->data['title'] ?? $notification->title(),
                'message' => $notification->data['message'] ?? $notification->message(),
                'priority' => $notification->data['priority'] ?? 'info',
                'read' => $notification->read_at !== null,
                'time' => $notification->created_at->diffForHumans(),
                'created_at' => $notification->created_at,
            ];
        })->all();
    }

    /**
     * Badge text: 0, 5, 99+.
     */
    public function badgeText(): string
    {
        if ($this->unreadCount > 99) {
            return '99+';
        }

        return (string) $this->unreadCount;
    }
}
