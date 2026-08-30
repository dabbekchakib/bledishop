@props([
    'item',
    'size' => 'md',
    'class' => '',
])

@php
    $sizes = [
        'sm' => 'h-8 min-w-8 text-sm',
        'md' => 'h-10 min-w-10',
        'lg' => 'h-11 min-w-11',
    ];
    $btnSize = $sizes[$size] ?? $sizes['md'];
    $inputW = $size === 'sm' ? 'w-10' : 'w-12';
@endphp

<div
    x-data="{ qty: @js((int) $item['quantity']), busy: false }"
    x-on:qty-updated.window="if ($event.detail.key === @js($item['key'])) { qty = $event.detail.quantity; }"
    class="inline-flex items-center rounded-xl border border-border bg-surface {{ $class }}"
>
    <button
        type="button"
        class="{{ $btnSize }} inline-flex items-center justify-center text-text-muted transition-colors hover:text-heading disabled:pointer-events-none disabled:opacity-40"
        x-on:click="qty > 1 && (qty--, $dispatch('qty-change', { key: @js($item['key']), quantity: qty }))"
        x-bind:disabled="qty <= 1 || busy"
        :aria-label="@js(__('shop.qty_decrease'))"
    aria-label="{{ __('shop.qty_decrease') }}"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/></svg>
    </button>

    <input
        type="number"
        min="1"
        x-model.number="qty"
        x-on:change="$dispatch('qty-change', { key: @js($item['key']), quantity: qty })"
        class="{{ $inputW }} no-spinner border-0 bg-transparent text-center text-sm font-semibold text-text focus:ring-0"
        aria-label="{{ __('shop.qty') }}"
    >

    <button
        type="button"
        class="{{ $btnSize }} inline-flex items-center justify-center text-text-muted transition-colors hover:text-heading disabled:pointer-events-none disabled:opacity-40"
        x-on:click="qty++, $dispatch('qty-change', { key: @js($item['key']), quantity: qty })"
        x-bind:disabled="busy"
        :aria-label="@js(__('shop.qty_increase'))"
    aria-label="{{ __('shop.qty_increase') }}"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
    </button>
</div>
