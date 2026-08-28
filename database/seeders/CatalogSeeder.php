<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Services\BrandService;
use App\Services\CategoryService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Demonstration catalog (categories + brands) for the three shop languages.
 *
 * Standalone so it can be run on top of a real install:
 *   php artisan db:seed --class=CatalogSeeder
 * Idempotent: existing records are detected by their French slug and skipped.
 */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedCategories();
            $this->seedBrands();
        });
    }

    private function seedCategories(): void
    {
        $definition = [
            [
                'slug' => 'electronique',
                'children' => [
                    ['slug' => 'smartphones', 'children' => []],
                    [
                        'slug' => 'ordinateurs',
                        'children' => [
                            ['slug' => 'ordinateurs-portables'],
                            ['slug' => 'ordinateurs-de-bureau'],
                        ],
                    ],
                    ['slug' => 'accessoires', 'children' => []],
                ],
            ],
            [
                'slug' => 'mode',
                'children' => [
                    ['slug' => 'vetements', 'children' => []],
                    ['slug' => 'chaussures', 'children' => []],
                ],
            ],
        ];

        $service = app(CategoryService::class);

        foreach ($definition as $root) {
            $this->seedCategory($service, null, $root);
        }
    }

    private function seedCategory(CategoryService $service, ?int $parentId, array $node): void
    {
        $names = static::namesForSlug($node['slug']);

        $category = Category::query()
            ->whereHas('translations', function ($query) use ($node): void {
                $query->where('locale', 'fr')->where('slug', $node['slug']);
            })
            ->first();

        if ($category === null) {
            $category = $service->create(
                [
                    'parent_id' => $parentId,
                    'status' => 'active',
                    'sort_order' => 0,
                    'is_featured' => in_array($node['slug'], ['electronique', 'mode'], true),
                ],
                [
                    'fr' => ['name' => $names['fr'], 'slug' => $node['slug']],
                    'ar' => ['name' => $names['ar'], 'slug' => ''],
                    'en' => ['name' => $names['en'], 'slug' => ''],
                ],
            );
        }

        foreach ($node['children'] ?? [] as $child) {
            $this->seedCategory($service, $category->id, $child);
        }
    }

    private function seedBrands(): void
    {
        $brands = [
            [
                'slug' => 'apple',
                'fr' => 'Apple',
                'ar' => 'آبل',
                'en' => 'Apple',
                'featured' => true,
            ],
            [
                'slug' => 'samsung',
                'fr' => 'Samsung',
                'ar' => 'سامسونج',
                'en' => 'Samsung',
                'featured' => true,
            ],
            [
                'slug' => 'nike',
                'fr' => 'Nike',
                'ar' => 'نايكي',
                'en' => 'Nike',
                'featured' => true,
            ],
            [
                'slug' => 'zara',
                'fr' => 'Zara',
                'ar' => 'زارا',
                'en' => 'Zara',
                'featured' => false,
            ],
        ];

        $service = app(BrandService::class);

        foreach ($brands as $brand) {
            $exists = Brand::query()->whereHas('translations', function ($query) use ($brand): void {
                $query->where('locale', 'fr')->where('slug', $brand['slug']);
            })->exists();

            if ($exists) {
                continue;
            }

            $service->create(
                [
                    'status' => 'active',
                    'sort_order' => 0,
                    'is_featured' => $brand['featured'],
                ],
                [
                    'fr' => ['name' => $brand['fr'], 'slug' => $brand['slug']],
                    'ar' => ['name' => $brand['ar'], 'slug' => ''],
                    'en' => ['name' => $brand['en'], 'slug' => ''],
                ],
            );
        }
    }

    /**
     * @return array{fr: string, ar: string, en: string}
     */
    private static function namesForSlug(string $slug): array
    {
        return match ($slug) {
            'electronique' => ['fr' => 'Électronique', 'ar' => 'الإلكترونيات', 'en' => 'Electronics'],
            'smartphones' => ['fr' => 'Smartphones', 'ar' => 'الهواتف الذكية', 'en' => 'Smartphones'],
            'ordinateurs' => ['fr' => 'Ordinateurs', 'ar' => 'الحواسيب', 'en' => 'Computers'],
            'ordinateurs-portables' => ['fr' => 'Portables', 'ar' => 'أجهزة محمولة', 'en' => 'Laptops'],
            'ordinateurs-de-bureau' => ['fr' => 'Bureau', 'ar' => 'أجهزة مكتبية', 'en' => 'Desktops'],
            'accessoires' => ['fr' => 'Accessoires', 'ar' => 'الإكسسوارات', 'en' => 'Accessories'],
            'mode' => ['fr' => 'Mode', 'ar' => 'الموضة', 'en' => 'Fashion'],
            'vetements' => ['fr' => 'Vêtements', 'ar' => 'ملابس', 'en' => 'Clothing'],
            'chaussures' => ['fr' => 'Chaussures', 'ar' => 'أحذية', 'en' => 'Shoes'],
            default => ['fr' => ucfirst($slug), 'ar' => ucfirst($slug), 'en' => ucfirst($slug)],
        };
    }
}
