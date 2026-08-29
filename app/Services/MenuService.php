<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class MenuService
{
    /**
     * The active top-level items for a menu location, with their nested
     * children eager loaded. Cached because storefront headers/footers render
     * the same navigation on every request.
     *
     * Eloquent models are intentionally NOT cached: the database cache driver
     * can unserialize them as __PHP_Incomplete_Class. Instead we cache a plain
     * array tree and rehydrate lightweight MenuItem models on each call.
     *
     * @return Collection<int, MenuItem>
     */
    public function tree(string $location): Collection
    {
        $raw = Cache::remember($this->cacheKey($location), now()->addDay(), function () use ($location): array {
            return $this->buildTreeArray($location);
        });

        return $this->treeArrayToModels($raw);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildTreeArray(string $location): array
    {
        $menu = Menu::query()->active()->where('location', $location)->first();

        if (! $menu) {
            return [];
        }

        $all = MenuItem::query()
            ->where('menu_id', $menu->id)
            ->active()
            ->ordered()
            ->get();

        $byParent = $all->groupBy(fn (MenuItem $item): int => (int) ($item->parent_id ?? 0));

        return $all->whereNull('parent_id')
            ->values()
            ->map(fn (MenuItem $item): array => $this->flatItemToArray($item, $byParent))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function flatItemToArray(MenuItem $item, Collection $byParent): array
    {
        $data = $item->only([
            'id', 'type', 'label', 'target_id', 'target_url',
            'is_external', 'target_blank', 'css_class', 'sort_order', 'is_active',
        ]);

        $data['children'] = $byParent->get($item->id, collect())
            ->values()
            ->map(fn (MenuItem $child): array => $this->flatItemToArray($child, $byParent))
            ->all();

        return $data;
    }

    /**
     * @param  array<int, array<string, mixed>>  $tree
     * @return Collection<int, MenuItem>
     */
    private function treeArrayToModels(array $tree): Collection
    {
        return collect($tree)->map(fn (array $data): MenuItem => $this->arrayToItem($data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function arrayToItem(array $data): MenuItem
    {
        $children = $data['children'] ?? [];

        unset($data['children']);

        $item = new MenuItem;
        $item->forceFill($data);
        $item->exists = true;

        $item->setRelation('children', collect($children)
            ->map(fn (array $child): MenuItem => $this->arrayToItem($child)));

        return $item;
    }

    public function clearCache(?string $location = null): void
    {
        if ($location !== null) {
            Cache::forget($this->cacheKey($location));

            return;
        }

        foreach (Menu::pluck('location') as $loc) {
            Cache::forget($this->cacheKey((string) $loc));
        }
        Cache::forget($this->cacheKey('*'));
    }

    /**
     * Persist the full item tree submitted by the admin form. Removed items
     * are deleted, existing items updated by their stored id, new items
     * created. The hierarchy is rebuilt from the nested structure.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    public function syncItems(Menu $menu, array $items): void
    {
        $keep = [];
        $this->persistLevel($menu, null, $items, $keep);

        MenuItem::query()
            ->where('menu_id', $menu->id)
            ->whereNotIn('id', $keep === [] ? [0] : $keep)
            ->delete();

        $this->clearCache($menu->location);
    }

    /**
     * The nested item structure expected by the admin form.
     *
     * @return array<int, array<string, mixed>>
     */
    public function hydrateItems(Menu $menu): array
    {
        return MenuItem::query()
            ->where('menu_id', $menu->id)
            ->whereNull('parent_id')
            ->ordered()
            ->get()
            ->map(fn (MenuItem $item): array => $this->itemToArray($item))
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<int, int>  $keep
     */
    private function persistLevel(Menu $menu, ?int $parentId, array $items, array &$keep): void
    {
        foreach (array_values($items) as $index => $raw) {
            $item = isset($raw['id']) ? MenuItem::find((int) $raw['id']) : null;
            $item = $item ?: new MenuItem;

            $item->menu_id = $menu->id;
            $item->parent_id = $parentId;
            $item->type = (string) ($raw['type'] ?? 'url');
            $item->label = is_array($raw['label'] ?? null) ? ($raw['label'] ?? []) : [];
            $item->target_url = $raw['target_url'] ?? null;
            $item->target_id = ($raw['target_id'] ?? null) ? (int) $raw['target_id'] : null;
            $item->is_external = (bool) ($raw['is_external'] ?? false);
            $item->target_blank = (bool) ($raw['target_blank'] ?? false);
            $item->css_class = $raw['css_class'] ?? null;
            $item->sort_order = $index;
            $item->is_active = (bool) ($raw['is_active'] ?? true);
            $item->save();

            $keep[] = $item->id;

            if (isset($raw['children']) && is_array($raw['children'])) {
                $this->persistLevel($menu, $item->id, $raw['children'], $keep);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function itemToArray(MenuItem $item): array
    {
        return [
            'id' => $item->id,
            'type' => $item->type,
            'label' => $item->label ?? [],
            'target_id' => $item->target_id,
            'target_url' => $item->target_url,
            'is_external' => $item->is_external,
            'target_blank' => $item->target_blank,
            'css_class' => $item->css_class,
            'sort_order' => $item->sort_order,
            'is_active' => $item->is_active,
            'children' => $item->children()->ordered()->get()
                ->map(fn (MenuItem $child): array => $this->itemToArray($child))
                ->all(),
        ];
    }

    private function cacheKey(string $location): string
    {
        return "bledishop.menu.{$location}";
    }
}
