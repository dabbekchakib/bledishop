<?php

namespace Tests\Feature\Storefront;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductService;
use App\Services\SettingsService;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingsSeeder::class);
        $this->seed(CatalogSeeder::class);
        $this->seed(ProductSeeder::class);
    }

    public function test_the_homepage_renders_catalog_sections_in_french(): void
    {
        $response = $this->get('/fr');

        $response->assertOk();

        $response->assertSee('Smartphone X Pro', false);
        $response->assertSee('Électronique', false);
        $response->assertSee('Apple', false);
        $response->assertSee('Nos catégories', false);
        $response->assertSee('Produits vedettes', false);
        $response->assertSee('Accueil', false);
    }

    public function test_the_shop_page_lists_public_products(): void
    {
        $response = $this->get('/fr/shop');

        $response->assertOk();
        $response->assertSee('Smartphone X Pro', false);
        $response->assertSee('Ordinateur portable Ultra', false);
        $response->assertSee('Trier par', false);
    }

    public function test_language_direction_is_applied_for_arabic(): void
    {
        $response = $this->get('/ar');

        $response->assertOk();
        $response->assertSee('dir="rtl"', false);
        $response->assertSee('lang="ar"', false);
        $response->assertSee('المتجر', false);
    }

    public function test_english_locale_renders_the_storefront(): void
    {
        $response = $this->get('/en/shop');

        $response->assertOk();
        $response->assertSee('Search for a product', false);
        $response->assertSee('Sort by', false);
    }

    public function test_a_category_page_lists_its_products(): void
    {
        $response = $this->get('/fr/category/smartphones');

        $response->assertOk();
        $response->assertSee('Smartphones', false);
        $response->assertSee('Smartphone X Pro', false);
    }

    public function test_a_category_page_nested_products_are_found(): void
    {
        $response = $this->get('/fr/category/ordinateurs-portables');

        $response->assertOk();
        $response->assertSee('Ordinateur portable Ultra', false);
    }

    public function test_a_brand_page_lists_its_products(): void
    {
        $response = $this->get('/fr/brand/apple');

        $response->assertOk();
        $response->assertSee('Apple', false);
        $response->assertSee('Smartphone X Pro', false);
    }

    public function test_a_product_page_renders_its_details(): void
    {
        $response = $this->get('/fr/product/smartphone-x-pro');

        $response->assertOk();
        $response->assertSee('Smartphone X Pro', false);
        $response->assertSee('Ajouter au panier', false);
    }

    public function test_a_variable_product_page_exposes_variant_selection(): void
    {
        $response = $this->get('/fr/product/pull-premium');

        $response->assertOk();
        $response->assertSee('Pull premium', false);
    }

    public function test_search_finds_products(): void
    {
        $response = $this->get('/fr/recherche?q=pro');

        $response->assertOk();
        $response->assertSee('Smartphone X Pro', false);
    }

    public function test_shipping_sorting_order_is_applied_without_errors(): void
    {
        $response = $this->get('/fr/shop?sort=price_asc');

        $response->assertOk();
    }

    public function test_unknown_category_returns_404(): void
    {
        $this->get('/fr/category/categorie-inexistante')->assertNotFound();
    }

    public function test_unknown_brand_returns_404(): void
    {
        $this->get('/fr/brand/marque-inexistante')->assertNotFound();
    }

    public function test_unknown_product_returns_404(): void
    {
        $this->get('/fr/product/produit-inexistant')->assertNotFound();
    }

    public function test_a_draft_product_is_not_publicly_visible(): void
    {
        $category = Category::whereHas('translations', fn ($q) => $q->where('locale', 'fr')->where('slug', 'smartphones'))->firstOrFail();
        $brand = Brand::whereHas('translations', fn ($q) => $q->where('locale', 'fr')->where('slug', 'apple'))->firstOrFail();

        $product = app(ProductService::class)->create(
            [
                'brand_id' => $brand->id,
                'type' => 'simple',
                'status' => 'draft',
                'featured' => false,
                'price' => 10,
                'manage_stock' => false,
                'category_ids' => [$category->id],
                'attribute_ids' => [],
            ],
            ['fr' => ['name' => 'Produit brouillon', 'slug' => 'produit-brouillon']],
        );

        $this->get('/fr/product/produit-brouillon')->assertNotFound();

        $product->forceDelete();
    }

    public function test_out_of_stock_products_are_hidden_by_default_and_shown_when_enabled(): void
    {
        $tShirt = Product::whereHas('translations', fn ($q) => $q->where('locale', 'fr')->where('slug', 't-shirt-classique'))->firstOrFail();
        $this->assertSame(0, (int) $tShirt->stock_quantity);

        $this->get('/fr/shop')->assertDontSee('T-shirt classique');

        app(SettingsService::class)->set('shop.show_out_of_stock', true);
        $this->get('/fr/shop')->assertSee('T-shirt classique');
    }

    public function test_locale_switches_are_isolated_per_url(): void
    {
        App::setLocale('ar');

        $this->get('/en')->assertSee('lang="en"', false);
        $this->get('/fr')->assertSee('lang="fr"', false);
    }
}
