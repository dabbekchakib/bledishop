<?php

namespace App\Filament\Pages;

use App\Enums\NotificationType;
use App\Models\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

/**
 * Administration notifications centre.
 *
 * Only ever lists the authenticated administrator's own notifications (the
 * notifiable is always the current user), so cross-user access is impossible.
 * Supports read/unread/type/period filters, search, pagination and the
 * read/read-all/delete actions.
 */
class Notifications extends Page
{
    protected string $view = 'filament.pages.notifications';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    protected static ?string $slug = 'notifications';

    protected static ?int $navigationSort = 90;

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('admin.nav.configuration');
    }

    public function getTitle(): string
    {
        return __('admin.notifications.page_title');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.notifications.page_title');
    }

    public static function canAccess(): bool
    {
        return (bool) (auth()->user()?->can('notifications.view') ?? false);
    }

    public ?string $state = 'all';

    public ?string $type = null;

    public string $period = 'all';

    public string $search = '';

    public int $page = 1;

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
    }

    /**
     * @return array<int, string> the translatable priority labels
     */
    public function priorities(): array
    {
        return [
            'info' => __('admin.notifications.priority.info'),
            'success' => __('admin.notifications.priority.success'),
            'warning' => __('admin.notifications.priority.warning'),
            'danger' => __('admin.notifications.priority.danger'),
        ];
    }

    /**
     * @return array<string, string> type value => translated label
     */
    public function typeOptions(): array
    {
        $options = [];

        foreach (NotificationType::cases() as $type) {
            $options[$type->value] = __($type->titleKey());
        }

        return $options;
    }

    /**
     * The authenticated user's notifications matching the current filters.
     */
    public function getNotificationsProperty(): LengthAwarePaginator
    {
        $user = auth()->user();

        $query = Notification::query()
            ->where('notifiable_type', $user->getMorphClass())
            ->where('notifiable_id', $user->id)
            ->latest('created_at');

        $query = $this->applyFilters($query);

        return $query->paginate(15, page: max(1, $this->page));
    }

    public function getUnreadCountProperty(): int
    {
        $user = auth()->user();

        return Notification::query()
            ->where('notifiable_type', $user->getMorphClass())
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    public function getFilteredUnreadCountProperty(): int
    {
        $user = auth()->user();

        $query = Notification::query()
            ->where('notifiable_type', $user->getMorphClass())
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at');

        return $this->applyFilters($query)->count();
    }

    private function applyFilters(Builder $query): Builder
    {
        if ($this->state === 'unread') {
            $query->whereNull('read_at');
        } elseif ($this->state === 'read') {
            $query->whereNotNull('read_at');
        }

        if (filled($this->type)) {
            $query->where('data->type', $this->type);
        }

        if ($this->period !== 'all') {
            $query->where('created_at', '>=', $this->periodStart());
        }

        if (filled($this->search)) {
            $query->where(function (Builder $q): void {
                $q->where('data->title', 'like', '%'.$this->search.'%')
                    ->orWhere('data->message', 'like', '%'.$this->search.'%');
            });
        }

        return $query;
    }

    private function periodStart(): Carbon
    {
        return match ($this->period) {
            'today' => now()->startOfDay(),
            '7d' => now()->subDays(7)->startOfDay(),
            '30d' => now()->subDays(30)->startOfDay(),
            'month' => now()->startOfMonth(),
            default => now()->subYear(1)->startOfDay(),
        };
    }

    public function filterBy(string $state): void
    {
        $this->state = in_array($state, ['all', 'unread', 'read'], true) ? $state : 'all';
        $this->page = 1;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
        $this->page = 1;
    }

    public function setPeriod(string $period): void
    {
        $this->period = $period;
        $this->page = 1;
    }

    public function updatedSearch(): void
    {
        $this->page = 1;
    }

    public function markRead(string $id): void
    {
        $notification = $this->ownNotification($id);

        if ($notification !== null) {
            $notification->markAsRead();
        }
    }

    /**
     * Mark the notification as read and redirect to its action URL.
     */
    public function open(string $id): void
    {
        $notification = $this->ownNotification($id);

        $url = null;

        if ($notification !== null) {
            $notification->markAsRead();
            $url = $notification->actionUrl();
        }

        if (filled($url) && $this->urlExists($url)) {
            $this->redirect($url);

            return;
        }

        $this->dispatch('toast', [
            'message' => __('admin.notifications.item_unavailable'),
        ]);
    }

    public function markAllRead(): void
    {
        $user = auth()->user();

        Notification::query()
            ->where('notifiable_type', $user->getMorphClass())
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function delete(string $id): void
    {
        $notification = $this->ownNotification($id);

        if ($notification !== null) {
            $notification->delete();
        }
    }

    private function ownNotification(string $id): ?Notification
    {
        $user = auth()->user();

        return Notification::query()
            ->where('id', $id)
            ->where('notifiable_type', $user->getMorphClass())
            ->where('notifiable_id', $user->id)
            ->first();
    }

    private function urlExists(string $url): bool
    {
        try {
            $path = (string) parse_url($url, PHP_URL_PATH);

            return filled($path) && $path !== '/dashboard';
        } catch (\Throwable) {
            return false;
        }
    }
}
