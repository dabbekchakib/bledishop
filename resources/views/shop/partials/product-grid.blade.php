@php
    $resultsLabel = trans_choice('shop.results_count', $products->total(), ['count' => $products->total()]);
@endphp

<div class="flex items-center justify-between gap-4 border-b border-border pb-4">
    <p class="text-sm text-text-muted">{{ $resultsLabel }}</p>

    <label class="flex items-center gap-2 text-sm">
        <span class="hidden sm:inline text-text-muted">{{ __('shop.sort_by') }}</span>
        <select
            name="sort"
            class="rounded-lg border-border bg-surface px-3 py-1.5 text-sm text-text focus:border-primary focus:ring-primary"
            x-on:change="$event.target.form.submit()"
        >
            @foreach ($sortOptions as $value => $label)
                <option value="{{ $value }}" @selected((string) $sort === (string) $value)>{{ $label }}</option>
            @endforeach
        </select>
    </label>
</div>

@if ($products->isEmpty())
    <x-storefront.empty-state :title="$emptyTitle" :message="$emptyMessage" />
@else
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($products as $product)
            <x-storefront.product-card :product="$product" />
        @endforeach
    </div>
    <x-storefront.pagination :paginator="$products" />
@endif
