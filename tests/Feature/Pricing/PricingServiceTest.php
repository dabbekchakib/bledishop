<?php

namespace Tests\Feature\Pricing;

use App\Services\PricingService;
use App\Services\SettingsService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingServiceTest extends TestCase
{
    use RefreshDatabase;

    private PricingService $pricing;

    private SettingsService $settings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingsSeeder::class);

        $this->pricing = app(PricingService::class);
        $this->settings = app(SettingsService::class);
    }

    public function test_tax_disabled_returns_the_base_price_and_no_tax(): void
    {
        $this->settings->set('tax.enabled', false);

        $this->assertSame(100.0, $this->pricing->grossPrice(100.0));
        $this->assertSame(0.0, $this->pricing->taxAmountFromGross(100.0));
        $this->assertSame(0.0, $this->pricing->taxOnBase(100.0));
    }

    public function test_tax_excluded_adds_tax_on_top_of_the_base_price(): void
    {
        $this->settings->set('tax.enabled', true);
        $this->settings->set('tax.included_in_price', false);
        $this->settings->set('tax.rate', 19);

        // 100.00 + 19% = 119.00
        $this->assertSame(119.0, $this->pricing->grossPrice(100.0));
        $this->assertSame(19.0, $this->pricing->taxOnBase(100.0));

        // tax contained in the gross 119.00 @ 19%  => 119 * 19 / 119 = 19.00
        $this->assertSame(19.0, $this->pricing->taxAmountFromGross(119.0));
    }

    public function test_tax_included_price_is_unchanged_but_tax_is_extracted(): void
    {
        $this->settings->set('tax.enabled', true);
        $this->settings->set('tax.included_in_price', true);
        $this->settings->set('tax.rate', 19);

        $this->assertSame(119.0, $this->pricing->grossPrice(119.0));
        $this->assertSame(0.0, $this->pricing->taxOnBase(119.0));

        // 119.00 gross includes 19% VAT => 119 * 19 / 119 = 19.00
        $this->assertSame(19.0, $this->pricing->taxAmountFromGross(119.0));
    }

    public function test_shipping_is_zero_when_disabled(): void
    {
        $this->settings->set('shipping.enabled', false);
        $this->settings->set('shipping.default_cost', 9.5);

        $this->assertSame(0.0, $this->pricing->shippingCost(50.0));
    }

    public function test_shipping_uses_the_configured_cost(): void
    {
        $this->settings->set('shipping.enabled', true);
        $this->settings->set('shipping.default_cost', 9.5);
        $this->settings->set('shipping.free_shipping_enabled', false);

        $this->assertSame(9.5, $this->pricing->shippingCost(50.0));
    }

    public function test_free_shipping_applies_when_threshold_is_reached(): void
    {
        $this->settings->set('shipping.enabled', true);
        $this->settings->set('shipping.default_cost', 9.5);
        $this->settings->set('shipping.free_shipping_enabled', true);
        $this->settings->set('shipping.free_shipping_threshold', 100);

        $this->assertSame(9.5, $this->pricing->shippingCost(99.99));
        $this->assertSame(0.0, $this->pricing->shippingCost(100.0));
        $this->assertSame(0.0, $this->pricing->shippingCost(150.0));
    }
}
