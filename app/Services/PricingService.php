<?php

namespace App\Services;

/**
 * Centralizes VAT (TVA) and shipping computations so every entry point (cart,
 * checkout, order, storefront) derives identical figures from the configured
 * settings. All calculations are performed in integer cents on the server;
 * the browser can never influence amounts.
 *
 * Pricing model:
 *  - stored product prices are the base price;
 *  - when VAT is enabled the customer-facing (gross) price either already
 *    includes the tax (tax.included_in_price) or has the tax added on top;
 *  - the tax amount reported in the cart / order is the share of the gross
 *    amount that corresponds to VAT;
 *  - shipping is a flat configured cost, waived when free shipping is enabled
 *    and the goods subtotal reaches the configured threshold.
 */
class PricingService
{
    public function taxEnabled(): bool
    {
        return (bool) setting('tax.enabled', false);
    }

    public function taxRate(): float
    {
        return max(0.0, (float) setting('tax.rate', 0));
    }

    /**
     * Whether the configured prices already include the VAT. The shop uses a
     * dedicated toggle but falls back to the legacy shop.price_includes_tax
     * flag to remain compatible with either configuration UI.
     */
    public function taxIncluded(): bool
    {
        if (app(SettingsService::class)->has('tax.included_in_price')) {
            return (bool) setting('tax.included_in_price', false);
        }

        return (bool) setting('shop.price_includes_tax', true);
    }

    /**
     * Customer-facing (gross) price derived from a stored base price.
     */
    public function grossPrice(float $base): float
    {
        if (! $this->taxEnabled()) {
            return round($base, 2);
        }

        if ($this->taxIncluded()) {
            return round($base, 2);
        }

        return round($this->fromCents($this->toCents($base) + $this->taxOnBaseCents($base)), 2);
    }

    /**
     * Gross amount applied on top of a given base amount (used when tax is
     * not included in the stored price). Returns 0 when VAT is disabled.
     */
    public function taxOnBase(float $base): float
    {
        if (! $this->taxEnabled()) {
            return 0.0;
        }

        if ($this->taxIncluded()) {
            return 0.0;
        }

        return round($this->fromCents($this->taxOnBaseCents($base)), 2);
    }

    private function taxOnBaseCents(float $base): int
    {
        return (int) round($this->toCents($base) * $this->taxRate() / 100);
    }

    /**
     * Amount of VAT contained within a gross (customer-facing) amount.
     * Returns 0 when VAT is disabled.
     */
    public function taxAmountFromGross(float $gross): float
    {
        if (! $this->taxEnabled() || $this->taxRate() <= 0) {
            return 0.0;
        }

        $grossCents = $this->toCents($gross);
        $taxCents = (int) round($grossCents * $this->taxRate() / (100 + $this->taxRate()));

        return $this->fromCents($taxCents);
    }

    /**
     * Shipping cost for a given (gross) goods subtotal, in currency units.
     */
    public function shippingCost(float $goodsSubtotal): float
    {
        if (! (bool) setting('shipping.enabled', false)) {
            return 0.0;
        }

        if ((bool) setting('shipping.free_shipping_enabled', false)) {
            $threshold = (float) setting('shipping.free_shipping_threshold', 0);

            if ($threshold > 0 && $goodsSubtotal >= $threshold) {
                return 0.0;
            }
        }

        return round((float) setting('shipping.default_cost', 0), 2);
    }

    private function toCents(float $value): int
    {
        return (int) round($value * 100);
    }

    private function fromCents(int $cents): float
    {
        return round($cents / 100, 2);
    }
}
