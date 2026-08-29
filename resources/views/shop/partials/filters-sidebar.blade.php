@php
    $showCategories = $showCategories ?? false;
    $categories = $categories ?? collect();
    $availableBrands = $availableBrands ?? collect();
    $attributes = $attributes ?? collect();
    $activeFilters = $activeFilters ?? [];

    $attributeIds = array_keys((array) ($activeFilters['attributes'] ?? []));
    $activeCategory = (string) ($activeFilters['category'] ?? '');
    $activeBrand = (string) ($activeFilters['brand'] ?? '');
    $hasActiveFilters = filled(($activeFilters['q'] ?? null))
        || filled($activeFilters['category'] ?? null)
        || filled($activeFilters['brand'] ?? null)
        || filled($activeFilters['min_price'] ?? null)
        || filled($activeFilters['max_price'] ?? null)
        || filled($activeFilters['availability'] ?? null)
        || filled($attributeIds);
@endphp

<aside class="rounded-2xl border border-border bg-surface p-5" aria-label="{{ __('shop.filters') }}">
    <div class="flex items-center justify-between border-b border-border pb-3">
        <h2 class="text-sm font-semibold uppercase tracking-wider text-heading">{{ __('shop.filters') }}</h2>
        @if ($hasActiveFilters)
            <a href="{{ url()->current() }}" class="text-xs font-medium text-primary hover:text-heading">{{ __('shop.clear_filters') }}</a>
        @endif
    </div>

    <div class="mt-5 flex items-center justify-center gap-2">
        <button type="submit" class="btn-primary w-full justify-center">{{ __('shop.apply_filters') }}</button>
    </div>

    {{-- Categories --}}
    @if ($showCategories && $categories->isNotEmpty())
        <div class="mt-6">
            <h3 class="mb-3 text-sm font-semibold text-heading">{{ __('shop.nav_categories') }}</h3>
            <select name="category" class="input w-full">
                <option value="">@lang('shop.all_categories')</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->translatedSlug() }}" @selected($activeCategory === $category->translatedSlug())>{{ $category->translatedName() }}</option>
                    @foreach ($category->children as $child)
                        <option value="{{ $child->translatedSlug() }}" @selected($activeCategory === $child->translatedSlug())>
                            &nbsp;&nbsp;— {{ $child->translatedName() }}
                        </option>
                    @endforeach
                @endforeach
            </select>
        </div>
    @endif

    {{-- Brands --}}
    @if ($availableBrands->isNotEmpty())
        <div class="mt-6">
            <h3 class="mb-3 text-sm font-semibold text-heading">{{ __('shop.nav_brands') }}</h3>
            <select name="brand" class="input w-full">
                <option value="">@lang('shop.all_brands')</option>
                @foreach ($availableBrands as $brand)
                    <option value="{{ $brand->translatedSlug() }}" @selected($activeBrand === $brand->translatedSlug())>{{ $brand->translatedName() }}</option>
                @endforeach
            </select>
        </div>
    @endif

    {{-- Price --}}
    <div class="mt-6">
        <h3 class="mb-3 text-sm font-semibold text-heading">{{ __('shop.price') }}</h3>
        <div class="grid grid-cols-2 gap-2">
            <div>
                <label for="min_price" class="sr-only">{{ __('shop.min_price') }}</label>
                <input id="min_price" type="number" name="min_price" min="0" step="0.001" value="{{ $activeFilters['min_price'] ?? '' }}"
                       placeholder="{{ __('shop.min') }}" class="input w-full" inputmode="decimal">
            </div>
            <div>
                <label for="max_price" class="sr-only">{{ __('shop.max_price') }}</label>
                <input id="max_price" type="number" name="max_price" min="0" step="0.001" value="{{ $activeFilters['max_price'] ?? '' }}"
                       placeholder="{{ __('shop.max') }}" class="input w-full" inputmode="decimal">
            </div>
        </div>
    </div>

    {{-- Availability --}}
    <div class="mt-6">
        <h3 class="mb-3 text-sm font-semibold text-heading">{{ __('shop.availability') }}</h3>
        <label class="flex items-start gap-2">
            <input type="radio" name="availability" value="" @checked(!filled($activeFilters['availability'] ?? null)) class="mt-1 text-primary focus:ring-primary">
            <span class="text-sm text-text">{{ __('shop.availability_all') }}</span>
        </label>
        <label class="mt-2 flex items-start gap-2">
            <input type="radio" name="availability" value="in_stock" @checked(($activeFilters['availability'] ?? null) === 'in_stock') class="mt-1 text-primary focus:ring-primary">
            <span class="text-sm text-text">{{ __('shop.availability_in_stock') }}</span>
        </label>
        <label class="mt-2 flex items-start gap-2">
            <input type="radio" name="availability" value="out_of_stock" @checked(($activeFilters['availability'] ?? null) === 'out_of_stock') class="mt-1 text-primary focus:ring-primary">
            <span class="text-sm text-text">{{ __('shop.availability_out_of_stock') }}</span>
        </label>
    </div>

    {{-- Attributes --}}
    @foreach ($attributes as $attribute)
        @if ($attribute->values->isNotEmpty())
            <div class="mt-6">
                <h3 class="mb-3 text-sm font-semibold text-heading">{{ $attribute->translatedName() }}</h3>
                <div class="space-y-2">
                    @foreach ($attribute->values as $value)
                        @php($checkedIds = (array) ($activeFilters['attributes'][$attribute->id] ?? []))
                        <label class="flex items-start gap-2">
                            <input type="checkbox"
                                   name="attributes[{{ $attribute->id }}][]"
                                   value="{{ $value->id }}"
                                   @checked(in_array((string) $value->id, $checkedIds, true))
                                   class="mt-1 rounded border-border text-primary focus:ring-primary">
                            <span class="text-sm text-text">{{ $value->translatedLabel() }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endif
    @endforeach

    <div class="mt-8 flex items-center justify-center gap-2">
        <button type="submit" class="btn-primary w-full justify-center">{{ __('shop.apply_filters') }}</button>
    </div>
</aside>
