<?php

namespace App\Enums;

use Illuminate\Support\Carbon;

/**
 * Period state of a dated promotion, derived from its dates. Never stored:
 * always computed from starts_at / ends_at relative to now().
 */
enum PromotionStatus: string
{
    case Upcoming = 'upcoming';
    case Active = 'active';
    case Ended = 'ended';

    public function label(): string
    {
        return match ($this) {
            self::Upcoming => __('admin.marketing.promotion_status.upcoming'),
            self::Active => __('admin.marketing.promotion_status.active'),
            self::Ended => __('admin.marketing.promotion_status.ended'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Upcoming => 'gray',
            self::Active => 'success',
            self::Ended => 'danger',
        };
    }

    public static function fromDates(?Carbon $startsAt, ?Carbon $endsAt, \Illuminate\Support\Carbon $now = null): self
    {
        $now ??= now();

        if ($endsAt !== null && $now->greaterThan($endsAt)) {
            return self::Ended;
        }

        if ($startsAt !== null && $now->lessThan($startsAt)) {
            return self::Upcoming;
        }

        return self::Active;
    }
}
