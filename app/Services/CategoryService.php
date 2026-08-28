<?php

namespace App\Services;

use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Services\Concerns\SynchronizesCatalogTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CategoryService
{
    use SynchronizesCatalogTranslations;

    protected function translationModel(): string
    {
        return CategoryTranslation::class;
    }

    protected function translationForeignKey(): string
    {
        return 'category_id';
    }

    public function create(array $attributes, array $translations): Category
    {
        $this->validateForCreate($attributes, $translations);

        $category = Category::create($attributes);

        $this->syncTranslations($category, $translations);

        return $category;
    }

    public function update(Model $category, array $attributes, array $translations): Category
    {
        /** @var Category $category */
        $this->validateForUpdate($category, $attributes, $translations);

        $category->update($attributes);

        $this->syncTranslations($category, $translations);

        return $category;
    }

    public function validateForCreate(array $attributes, array $translations): void
    {
        $this->assertDefaultTranslationPresent($translations);
        $this->assertParentAllowed(null, $attributes['parent_id'] ?? null);
    }

    public function validateForUpdate(Model $category, array $attributes, array $translations): void
    {
        /** @var Category $category */
        $this->assertDefaultTranslationPresent($translations);
        $this->assertParentAllowed($category, $attributes['parent_id'] ?? $category->parent_id);
    }

    /**
     * A category can never be its own parent nor a descendant of itself.
     */
    private function assertParentAllowed(?Category $category, ?int $parentId): void
    {
        if ($parentId === null) {
            return;
        }

        if ($category !== null && ! $category->isAllowedParent($parentId)) {
            throw ValidationException::withMessages([
                'parent_id' => 'La catégorie parente sélectionnée créerait un cycle de hiérarchie.',
            ]);
        }
    }
}
