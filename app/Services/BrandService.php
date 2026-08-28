<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\BrandTranslation;
use App\Services\Concerns\SynchronizesCatalogTranslations;
use Illuminate\Database\Eloquent\Model;

class BrandService
{
    use SynchronizesCatalogTranslations;

    protected function translationModel(): string
    {
        return BrandTranslation::class;
    }

    protected function translationForeignKey(): string
    {
        return 'brand_id';
    }

    public function create(array $attributes, array $translations): Brand
    {
        $this->validateForCreate($attributes, $translations);

        $brand = Brand::create($attributes);

        $this->syncTranslations($brand, $translations);

        return $brand;
    }

    public function update(Model $brand, array $attributes, array $translations): Brand
    {
        $this->validateForUpdate($brand, $attributes, $translations);

        $brand->update($attributes);

        $this->syncTranslations($brand, $translations);

        return $brand;
    }

    public function validateForCreate(array $attributes, array $translations): void
    {
        $this->assertDefaultTranslationPresent($translations);
    }

    public function validateForUpdate(Model $brand, array $attributes, array $translations): void
    {
        $this->assertDefaultTranslationPresent($translations);
    }
}
