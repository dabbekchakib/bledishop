<?php

namespace App\Providers;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Banner;
use App\Models\Brand;
use App\Models\Campaign;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\DiscountRule;
use App\Models\Menu;
use App\Models\NewsletterSubscriber;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\Promotion;
use App\Models\StockMovement;
use App\Models\UrlRedirect;
use App\Models\User;
use App\Policies\AttributePolicy;
use App\Policies\AttributeValuePolicy;
use App\Policies\BannerPolicy;
use App\Policies\BrandPolicy;
use App\Policies\CampaignPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\CouponPolicy;
use App\Policies\DiscountRulePolicy;
use App\Policies\MenuPolicy;
use App\Policies\NewsletterSubscriberPolicy;
use App\Policies\PagePolicy;
use App\Policies\PermissionPolicy;
use App\Policies\ProductPolicy;
use App\Policies\ProductReviewPolicy;
use App\Policies\PromotionPolicy;
use App\Policies\RolePolicy;
use App\Policies\StockMovementPolicy;
use App\Policies\UrlRedirectPolicy;
use App\Policies\UserPolicy;
use App\Services\WishlistService;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::anonymousComponentPath(
            resource_path('views/components/storefront'),
            'storefront',
        );

        Gate::before(function ($user) {
            if (! $user instanceof User) {
                return null;
            }

            return $user->isSuperAdmin() ? true : null;
        });

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Permission::class, PermissionPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Brand::class, BrandPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Attribute::class, AttributePolicy::class);
        Gate::policy(AttributeValue::class, AttributeValuePolicy::class);
        Gate::policy(StockMovement::class, StockMovementPolicy::class);
        Gate::policy(Page::class, PagePolicy::class);
        Gate::policy(Menu::class, MenuPolicy::class);
        Gate::policy(UrlRedirect::class, UrlRedirectPolicy::class);
        Gate::policy(Coupon::class, CouponPolicy::class);
        Gate::policy(DiscountRule::class, DiscountRulePolicy::class);
        Gate::policy(Promotion::class, PromotionPolicy::class);
        Gate::policy(Campaign::class, CampaignPolicy::class);
        Gate::policy(Banner::class, BannerPolicy::class);
        Gate::policy(NewsletterSubscriber::class, NewsletterSubscriberPolicy::class);
        Gate::policy(ProductReview::class, ProductReviewPolicy::class);

        // Merge the guest's session wishlist into their account wishlist on login.
        Event::listen(function (Login $event): void {
            $user = $event->user;

            if (! $user instanceof User) {
                return;
            }

            $guestKey = request()->session()->get('wishlist_guest_key');

            if (is_string($guestKey) && $guestKey !== '') {
                app(WishlistService::class)->mergeGuestToUser($guestKey, $user);
                request()->session()->forget('wishlist_guest_key');
            }
        });
    }
}
