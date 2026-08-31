<?php

namespace App\Support;

/**
 * Immutable result of the server-side discount calculation for a cart or order.
 *
 * Holds the total discount amount (in gross currency units, i.e. decimal)
 * together with an itemised breakdown of every applied coupon / rule /
 * free-shipping benefit so it can be persisted into order_discounts.
 */
final class DiscountResult
{
    /** @var array<int, array<string, mixed>> */
    public array $items = [];

    public function __construct(
        public float $total = 0.0,
        public bool $freeShipping = false,
        public array $errors = [],
    ) {
    }

    public function add(array $line): void
    {
        $this->items[] = $line;
        $this->total += (float) ($line['amount'] ?? 0);
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }
}
