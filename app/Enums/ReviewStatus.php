<?php

namespace App\Enums;

enum ReviewStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('admin.reviews.status_pending'),
            self::Approved => __('admin.reviews.status_approved'),
            self::Rejected => __('admin.reviews.status_rejected'),
        };
    }

    public function isVisiblePublicly(): bool
    {
        return $this === self::Approved;
    }

    /**
     * @return array<string, string> value => label
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $status) {
            $options[$status->value] = $status->label();
        }

        return $options;
    }
}
