<?php

namespace App\Filament\Resources\Concerns;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Filament\Forms\Components\Select;

/**
 * Reusable product / category / brand multi-selects used by the marketing
 * resources. Values are stored as JSON id arrays on each model.
 */
trait UsesCatalogSelects
{
    /**
     * @param  class-string  $model
     */
    private static function catalogSelect(string $name, string $label, string $model): Select
    {
        return Select::make($name)
            ->label($label)
            ->multiple()
            ->searchable()
            ->options(fn (): array => $model::query()
                ->with('translations')
                ->limit(200)
                ->get()
                ->mapWithKeys(fn ($row) => [$row->getKey() => $row->translatedName()])
                ->all())
            ->getSearchResultsUsing(fn (string $search): array => $model::query()
                ->whereHas('translations', fn ($q) => $q->where('name', 'like', '%'.$search.'%'))
                ->with('translations')
                ->limit(50)
                ->get()
                ->mapWithKeys(fn ($row) => [$row->getKey() => $row->translatedName()])
                ->all())
            ->getOptionLabelUsing(fn ($value): ?string => optional($model::query()->with('translations')->find($value))?->translatedName());
    }

    private static function productSelect(string $name, string $label): Select
    {
        return self::catalogSelect($name, $label, Product::class);
    }

    private static function categorySelect(string $name, string $label): Select
    {
        return self::catalogSelect($name, $label, Category::class);
    }

    private static function brandSelect(string $name, string $label): Select
    {
        return self::catalogSelect($name, $label, Brand::class);
    }
}
