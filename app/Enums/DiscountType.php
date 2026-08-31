<?php

namespace App\Enums;

/**
 * How a discount is calculated by promotions, rules and coupons.
 */
enum DiscountType: string
{
    case Percentage = 'percentage';
    case Fixed = 'fixed';
    case PromoPrice = 'promo_price';
    case FreeShipping = 'free_shipping';

    public function label(): string
    {
        return match ($this) {
            self::Percentage => __('admin.marketing.discount_type.percentage'),
            self::Fixed => __('admin.marketing.discount_type.fixed'),
            self::PromoPrice => __('admin.marketing.discount_type.promo_price'),
            self::FreeShipping => __('admin.marketing.discount_type.free_shipping'),
        };
    }
}
