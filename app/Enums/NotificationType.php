<?php

namespace App\Enums;

use App\Filament\Resources\BrandResource;
use App\Filament\Resources\CategoryResource;
use App\Filament\Resources\OrdersResource;
use App\Filament\Resources\PageResource;
use App\Filament\Resources\ProductResource;
use App\Filament\Resources\RedirectResource;
use App\Filament\Resources\UserResource;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\UrlRedirect;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Central registry of every admin notification event.
 *
 * Each case declares:
 *   - the permission an administrator needs to receive it,
 *   - its priority,
 *   - the translation keys used for the title and message,
 *   - a default action URL builder to the related resource.
 *
 * Adding a new notification type only requires a new enum case plus the
 * lang keys - the recipients, dedup and rendering are all handled generically.
 */
enum NotificationType: string
{
    // Orders
    case OrderCreated = 'order.created';
    case OrderStatusChanged = 'order.status_changed';
    case OrderNeedsAttention = 'order.needs_attention';

    // Stock
    case LowStock = 'stock.low';
    case OutOfStock = 'stock.out_of_stock';
    case StockRestocked = 'stock.restocked';

    // Products
    case ProductCreated = 'product.created';
    case ProductInactive = 'product.inactive';

    // Catalogue
    case CategoryCreated = 'category.created';
    case BrandCreated = 'brand.created';

    // Users / customers
    case UserCreated = 'user.created';

    // CMS
    case PageCreated = 'page.created';
    case PagePublished = 'page.published';

    // SEO
    case RedirectCreated = 'redirect.created';

    // Configuration
    case ConfigChanged = 'config.changed';

    /**
     * The permission an administrator must hold to receive this event.
     */
    public function permission(): string
    {
        return match ($this) {
            self::OrderCreated,
            self::OrderStatusChanged,
            self::OrderNeedsAttention => 'orders.view',

            self::LowStock,
            self::OutOfStock,
            self::StockRestocked => 'stock.view',

            self::ProductCreated,
            self::ProductInactive => 'products.view',

            self::CategoryCreated => 'categories.view',
            self::BrandCreated => 'brands.view',

            self::UserCreated => 'users.view',

            self::PageCreated,
            self::PagePublished => 'pages.view',

            self::RedirectCreated => 'redirects.view',

            self::ConfigChanged => 'settings.view',
        };
    }

    public function priority(): NotificationPriority
    {
        return match ($this) {
            self::OrderNeedsAttention,
            self::OutOfStock => NotificationPriority::Danger,

            self::LowStock => NotificationPriority::Warning,

            self::OrderStatusChanged,
            self::StockRestocked,
            self::PageCreated,
            self::PagePublished,
            self::ProductCreated,
            self::CategoryCreated,
            self::BrandCreated,
            self::UserCreated,
            self::RedirectCreated,
            self::ConfigChanged,
            self::OrderCreated,
            self::ProductInactive => NotificationPriority::Info,
        };
    }

    public function titleKey(): string
    {
        return match ($this) {
            self::OrderCreated => 'admin.notifications.titles.order_created',
            self::OrderStatusChanged => 'admin.notifications.titles.order_status_changed',
            self::OrderNeedsAttention => 'admin.notifications.titles.order_needs_attention',

            self::LowStock => 'admin.notifications.titles.low_stock',
            self::OutOfStock => 'admin.notifications.titles.out_of_stock',
            self::StockRestocked => 'admin.notifications.titles.stock_restocked',

            self::ProductCreated => 'admin.notifications.titles.product_created',
            self::ProductInactive => 'admin.notifications.titles.product_inactive',

            self::CategoryCreated => 'admin.notifications.titles.category_created',
            self::BrandCreated => 'admin.notifications.titles.brand_created',

            self::UserCreated => 'admin.notifications.titles.user_created',

            self::PageCreated => 'admin.notifications.titles.page_created',
            self::PagePublished => 'admin.notifications.titles.page_published',

            self::RedirectCreated => 'admin.notifications.titles.redirect_created',

            self::ConfigChanged => 'admin.notifications.titles.config_changed',
        };
    }

    public function messageKey(): string
    {
        return match ($this) {
            self::OrderCreated => 'admin.notifications.messages.order_created',
            self::OrderStatusChanged => 'admin.notifications.messages.order_status_changed',
            self::OrderNeedsAttention => 'admin.notifications.messages.order_needs_attention',

            self::LowStock => 'admin.notifications.messages.low_stock',
            self::OutOfStock => 'admin.notifications.messages.out_of_stock',
            self::StockRestocked => 'admin.notifications.messages.stock_restocked',

            self::ProductCreated => 'admin.notifications.messages.product_created',
            self::ProductInactive => 'admin.notifications.messages.product_inactive',

            self::CategoryCreated => 'admin.notifications.messages.category_created',
            self::BrandCreated => 'admin.notifications.messages.brand_created',

            self::UserCreated => 'admin.notifications.messages.user_created',

            self::PageCreated => 'admin.notifications.messages.page_created',
            self::PagePublished => 'admin.notifications.messages.page_published',

            self::RedirectCreated => 'admin.notifications.messages.redirect_created',

            self::ConfigChanged => 'admin.notifications.messages.config_changed',
        };
    }

    /**
     * Build the default action URL to the related resource, if one exists.
     *
     * @param  mixed  $subject  the related model (Order, Product, ...)
     */
    public function url(mixed $subject = null): ?string
    {
        if ($subject instanceof Order) {
            return OrdersResource::getUrl('view', ['record' => $subject]);
        }

        if ($subject instanceof Product || $subject instanceof ProductVariant) {
            $product = $subject instanceof Product ? $subject : $subject->product;

            if ($product !== null) {
                return ProductResource::getUrl('edit', ['record' => $product]);
            }

            return null;
        }

        if ($subject instanceof Category) {
            return CategoryResource::getUrl('edit', ['record' => $subject]);
        }

        if ($subject instanceof Brand) {
            return BrandResource::getUrl('edit', ['record' => $subject]);
        }

        if ($subject instanceof User) {
            return UserResource::getUrl('edit', ['record' => $subject]);
        }

        if ($subject instanceof Page) {
            return PageResource::getUrl('edit', ['record' => $subject]);
        }

        if ($subject instanceof UrlRedirect) {
            return RedirectResource::getUrl('edit', ['record' => $subject]);
        }

        return null;
    }

    /**
     * A stable fingerprint used for de-duplication. When two notifications of
     * the same type share a subject, they are considered equivalent and only
     * the most recent one is kept as an unread alert.
     *
     * @param  mixed  $subject  the related model
     */
    public function dedupKey(mixed $subject = null): string
    {
        $entityId = $subject instanceof Model ? (string) $subject->getKey() : '';

        return $this->value.':'.$entityId;
    }
}
