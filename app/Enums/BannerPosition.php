<?php

namespace App\Enums;

enum BannerPosition: string
{
    case Homepage = 'homepage';
    case Category = 'category';
    case Product = 'product';
    case Header = 'header';

    public function label(): string
    {
        return match ($this) {
            self::Homepage => __('admin.marketing.banner_position.homepage'),
            self::Category => __('admin.marketing.banner_position.category'),
            self::Product => __('admin.marketing.banner_position.product'),
            self::Header => __('admin.marketing.banner_position.header'),
        };
    }
}
