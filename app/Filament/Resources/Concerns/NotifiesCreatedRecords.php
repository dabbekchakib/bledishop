<?php

namespace App\Filament\Resources\Concerns;

use App\Enums\NotificationType;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\UrlRedirect;
use App\Models\User;
use App\Services\AdminNotificationService;

/**
 * Fires an admin notification when a catalogue / content record is created
 * through the admin panel. The type is inferred from the persisted model.
 *
 * Pages using a translations-sync trait get this automatically (the sync trait
 * calls notifyCreatedRecord() after create); pages with their own afterCreate()
 * hook should call $this->notifyCreatedRecord() inside it.
 */
trait NotifiesCreatedRecords
{
    protected function notifyCreatedRecord(): void
    {
        $type = $this->createdNotificationType();

        if ($type !== null && $this->record !== null) {
            app(AdminNotificationService::class)->notify($type, $this->record);
        }
    }

    /**
     * The notification type emitted for the just-created record, if any.
     */
    protected function createdNotificationType(): ?NotificationType
    {
        return match (true) {
            $this->record instanceof Product => NotificationType::ProductCreated,
            $this->record instanceof Category => NotificationType::CategoryCreated,
            $this->record instanceof Brand => NotificationType::BrandCreated,
            $this->record instanceof UrlRedirect => NotificationType::RedirectCreated,
            $this->record instanceof User => NotificationType::UserCreated,
            $this->record instanceof Page => NotificationType::PageCreated,
            default => null,
        };
    }
}
