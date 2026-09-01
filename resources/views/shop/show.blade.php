<x-shop-layout
    :title="$product->translatedName()"
    :meta-description="$product->translation()?->meta_description ?: $product->translatedShortDescription()"
    :canonical="localized_route('shop.product.show', ['slug' => $product->translatedSlug()])"
    :og-image="$product->primaryImageUrl"
    og-type="product"
    :robots="$product->translation()?->robots"
    :schema="[
        app(\App\Services\SeoService::class)->productSchema([
            'name' => $product->translatedName(),
            'image' => $product->primaryImageUrl,
            'description' => $product->translation()?->meta_description ?: $product->translatedShortDescription(),
            'sku' => $product->sku,
            'brand' => $product->brand?->translatedName(),
            'price' => app(\App\Services\PricingService::class)->grossPrice((float) ($product->displayPrice() ?? 0)),
            'in_stock' => $product->isAvailable(),
            'url' => localized_route('shop.product.show', ['slug' => $product->translatedSlug()]),
        ]),
        app(\App\Services\SeoService::class)->breadcrumbSchema([
            ['name' => __('messages.nav_home'), 'url' => localized_route('home')],
            ['name' => __('shop.nav_shop'), 'url' => localized_route('shop.index')],
            $product->brand ? ['name' => $product->brand->translatedName(), 'url' => localized_route('shop.brand.show', ['slug' => $product->brand->translatedSlug()])] : null,
            ['name' => $product->translatedName()],
        ]),
    ]"
>

    @php
        $galleryImages = collect($gallery)->map(fn ($image) => [
            'src' => storefront_image($image->path),
            'alt' => $image->translatedAlt() ?: $product->translatedName(),
        ])->values()->all();

        if (empty($galleryImages)) {
            $galleryImages = [['src' => $product->primaryImageUrl ?? null, 'alt' => $product->translatedName()]];
        }

        $isAvailable = $product->isAvailable();
        $isPromoted = $product->isPromoted();
        $discount = $product->discountPercent();
        $brandName = $product->brand?->translatedName();
        $brandUrl = $product->brand ? localized_route('shop.brand.show', ['slug' => $product->brand->translatedSlug()]) : null;
        $pricing = app(\App\Services\PricingService::class);
        $minPrice = $pricing->grossPrice((float) ($product->displayPrice() ?? 0));
        $comparePrice = $product->displayCompareAtPrice() !== null
            ? $pricing->grossPrice((float) $product->displayCompareAtPrice())
            : null;
        $activePromotion = $product->activePromotion();
        $promoPrice = $product->promoDisplayPrice();
        if (! $product->isVariable() && $promoPrice !== null && (float) $promoPrice < (float) $product->displayPrice()) {
            $minPrice = $pricing->grossPrice((float) $promoPrice);
            $comparePrice = max($comparePrice ?? 0, $pricing->grossPrice((float) $product->displayPrice()));
            $isPromoted = true;
            $promoPercent = $product->promoDiscountPercent();
            if ($promoPercent !== null) {
                $discount = $promoPercent;
            }
        }

        $shareUrl = localized_route('shop.product.show', ['slug' => $product->translatedSlug()]);
        $shareText = __('shop.share.text', [
            'product' => $product->translatedName(),
            'site' => setting('site.name', config('app.name')),
        ]);
        $shareLinks = [
            'facebook' => 'https://www.facebook.com/sharer/sharer.php?u='.urlencode($shareUrl),
            'x' => 'https://twitter.com/intent/tweet?url='.urlencode($shareUrl).'&text='.urlencode($shareText),
            'tiktok' => 'https://www.tiktok.com/share?url='.urlencode($shareUrl),
            'whatsapp' => 'https://wa.me/?text='.urlencode($shareText.' — '.$shareUrl),
        ];
    @endphp

    <div x-data="productPage({
        productId: @js((int) $product->id),
        isVariable: @js($product->isVariable()),
        galleryImages: @js($galleryImages),
        attributes: @js($attributes),
        variants: @js($variants),
        defaultPrice: @js(format_price($minPrice)),
        defaultComparePrice: @js($isPromoted ? $comparePrice : null),
        available: @js($isAvailable),
    })" class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8" x-cloak>

        <x-storefront.breadcrumbs :items="[
            ['label' => __('shop.nav_shop'), 'url' => localized_route('shop.index')],
            $product->brand ? ['label' => $brandName, 'url' => $brandUrl] : null,
            ['label' => $product->translatedName()],
        ]" />

        <div class="lg:grid lg:grid-cols-2 lg:gap-12">

            {{-- Gallery --}}
            <div>
                <div class="relative aspect-square overflow-hidden rounded-3xl border border-border bg-surface">
                    <template x-for="(image, index) in galleryImages" :key="index">
                        <img
                            :src="image.src ?? null"
                            :alt="image.alt"
                            x-show="activeImage === index"
                            x-transition:enter="transition-opacity duration-200"
                            x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100"
                            class="h-full w-full object-contain"
                            loading="eager"
                        >
                    </template>

                    @if ($discount > 0)
                        <x-storefront.badge color="danger" class="absolute start-4 top-4">-{{ $discount }}%</x-storefront.badge>
                    @endif
                    @if (!$isAvailable)
                        <div class="absolute end-4 top-4">
                            <x-storefront.badge color="muted" class="border border-border">{{ __('shop.out_of_stock') }}</x-storefront.badge>
                        </div>
                    @endif
                </div>

                @if (count($galleryImages) > 1)
                    <div class="mt-4 flex gap-3 overflow-x-auto pb-1">
                        <template x-for="(image, index) in galleryImages" :key="index">
                            <button
                                type="button"
                                class="h-20 w-20 shrink-0 overflow-hidden rounded-xl border-2 transition-colors"
                                :class="activeImage === index ? 'border-primary' : 'border-border hover:border-text-muted'"
                                x-on:click="activeImage = index"
                                :aria-label="@js(__('shop.view_image')) + ' ' + (index + 1)"
                            >
                                <img :src="image.src ?? null" :alt="image.alt" class="h-full w-full object-cover">
                            </button>
                        </template>
                    </div>
                @endif
            </div>

            {{-- Information --}}
            <div class="mt-8 lg:mt-0">
                @if ($brandName && $brandUrl)
                    <a href="{{ $brandUrl }}" class="text-sm font-semibold text-primary hover:text-heading">{{ $brandName }}</a>
                @endif

                <h1 class="mt-1 text-3xl font-extrabold tracking-tight text-heading sm:text-4xl">{{ $product->translatedName() }}</h1>

                @if (filled($product->translatedShortDescription()))
                    <p class="mt-4 text-text-muted">{{ $product->translatedShortDescription() }}</p>
                @endif

                {{-- Price --}}
                <div class="mt-5 flex items-baseline gap-3">
                    <span class="text-3xl font-extrabold text-heading" x-text="price"></span>
                    <span class="text-xl text-text-muted line-through" x-show="comparePrice" x-text="comparePrice"></span>
                </div>

                @if ($isPromoted && $discount)
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <x-storefront.badge color="danger">-{{ $discount }}%</x-storefront.badge>
                    </div>
                @endif

                @if ($activePromotion && $activePromotion->is_countdown && $activePromotion->ends_at
                    && (bool) setting('marketing.countdown_enabled', true))
                    <div class="mt-4 rounded-xl border border-danger/20 bg-danger/5 p-4" x-data="countdown(@js($activePromotion->ends_at->getTimestamp()))">
                        <p class="text-sm font-semibold text-heading">
                            {{ $activePromotion->countdown_title ?: ($activePromotion->name ?: __('shop.marketing.countdown_ends_in')) }}
                        </p>
                        <p class="mt-2 flex items-center gap-2 text-2xl font-extrabold tabular-nums text-primary">
                            <span x-text="d"></span><span class="text-xs text-text-muted">{{ __('shop.marketing.days') }}</span>
                            <span x-text="h"></span><span class="text-xs text-text-muted">{{ __('shop.marketing.hours') }}</span>
                            <span x-text="m"></span><span class="text-xs text-text-muted">{{ __('shop.marketing.minutes') }}</span>
                            <span x-text="s"></span><span class="text-xs text-text-muted">{{ __('shop.marketing.seconds') }}</span>
                        </p>
                    </div>
                @endif

                {{-- Availability --}}
                <p class="mt-3 inline-flex items-center gap-2 text-sm font-medium"
                   :class="available ? 'text-success' : 'text-danger'">
                    <span class="h-2 w-2 rounded-full" :class="available ? 'bg-success' : 'bg-danger'" aria-hidden="true"></span>
                    <span x-text="available ? @js(__('shop.in_stock')) : @js(__('shop.out_of_stock'))"></span>
                </p>

                @if (filled($product->sku))
                    <p class="mt-2 text-sm text-text-muted">{{ __('shop.sku') }}: <span class="font-mono text-text">{{ $product->sku }}</span></p>
                @endif

                {{-- Variant selection --}}
                <div class="mt-6 space-y-5">
                    <template x-for="attribute in attributes" :key="attribute.id">
                        <div>
                            <h3 class="mb-2 text-sm font-semibold text-heading" x-text="attribute.name"></h3>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="value in attribute.values" :key="value.id">
                                    <button
                                        type="button"
                                        x-show="attribute.type === 'color'"
                                        x-on:click="selectAttribute(attribute.id, value.id)"
                                        class="h-10 w-10 rounded-full border-2 transition-transform"
                                        :style="{ backgroundColor: value.color || '#cccccc' }"
                                        :class="isSelected(attribute.id, value.id) ? 'border-primary scale-110' : 'border-border'"
                                        :aria-pressed="isSelected(attribute.id, value.id).toString()"
                                        :title="value.label"
                                    >
                                    </button>
                                    <button
                                        type="button"
                                        x-show="attribute.type !== 'color'"
                                        x-on:click="selectAttribute(attribute.id, value.id)"
                                        class="rounded-lg border px-4 py-2 text-sm font-medium transition-colors"
                                        :class="isSelected(attribute.id, value.id)
                                            ? 'border-primary bg-primary text-white'
                                            : 'border-border text-text hover:border-primary hover:text-primary'"
                                        :aria-pressed="isSelected(attribute.id, value.id).toString()"
                                        x-text="value.label"
                                    >
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                <p class="mt-3 text-sm" x-show="!variantAvailable" x-cloak>{{ __('shop.variant_unavailable') }}</p>

                {{-- Quantity + Add to cart --}}
                <div class="mt-8 flex items-stretch gap-3">
                    <div class="inline-flex items-center rounded-xl border border-border bg-surface">
                        <button type="button" class="px-3 text-lg text-text-muted hover:text-heading" x-on:click="quantity > 1 && quantity--" aria-label="{{ __('shop.qty_decrease') }}" :disabled="quantity <= 1">−</button>
                        <input type="number" x-model.number="quantity" min="1" class="w-12 no-spinner border-0 bg-transparent text-center text-sm font-semibold text-text focus:ring-0" aria-label="{{ __('shop.qty') }}">
                        <button type="button" class="px-3 text-lg text-text-muted hover:text-heading" x-on:click="quantity++" aria-label="{{ __('shop.qty_increase') }}">+</button>
                    </div>
                    <button
                        type="button"
                        x-on:click="addToCart()"
                        x-bind:disabled="!addable || adding || $store.cart.busy"
                        x-text="adding ? @js(__('shop.adding')) : @js(__('shop.add_to_cart'))"
                        class="btn-primary flex-1 justify-center !px-4"
                        :class="{ 'cursor-not-allowed opacity-60': !addable || adding || $store.cart.busy }"
                    >{{ __('shop.add_to_cart') }}</button>

                    @if ((bool) setting('shop.wishlist_enabled', false))
                        <button
                            type="button"
                            x-data
                            x-on:click="$store.wishlist.toggle(@js((int) $product->id))"
                            :disabled="$store.wishlist.busy"
                            :aria-pressed="$store.wishlist.contains(@js((int) $product->id))"
                            aria-label="{{ __('shop.wishlist.toggle') }}"
                            class="inline-flex w-12 items-center justify-center rounded-xl border border-border bg-surface transition-colors hover:border-primary disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            <svg
                                :class="{ 'text-danger fill-danger': $store.wishlist.contains(@js((int) $product->id)), 'text-text-muted': !$store.wishlist.contains(@js((int) $product->id)) }"
                                class="h-6 w-6"
                                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"
                            >
                                <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
                            </svg>
                        </button>
                    @endif
                </div>
                <p class="mt-2 text-xs text-text-muted" x-show="isVariable && !selectedVariantId" x-cloak>{{ __('shop.select_variant_to_add') }}</p>

                {{-- Delivery / perks --}}
                <div class="mt-8 grid grid-cols-1 gap-3 rounded-2xl border border-border bg-surface p-4 text-sm sm:grid-cols-2">
                    <div class="flex items-center gap-2 text-text">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/></svg>
                        {{ __('shop.delivery_note') }}
                    </div>
                    <div class="flex items-center gap-2 text-text">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"/></svg>
                        {{ __('shop.payment_note') }}
                    </div>
                </div>

                {{-- Share --}}
                <div class="mt-8">
                    <p class="text-sm font-semibold text-heading">{{ __('shop.share.title') }}</p>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <a href="{{ $shareLinks['facebook'] }}" target="_blank" rel="noopener noreferrer"
                           class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-[#1877F2]/10 text-[#1877F2] transition-colors hover:bg-[#1877F2] hover:text-white"
                           aria-label="{{ __('shop.share.on_facebook') }}" title="{{ __('shop.share.on_facebook') }}">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>
                        <a href="{{ $shareLinks['x'] }}" target="_blank" rel="noopener noreferrer"
                           class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-slate-200/70 text-slate-900 transition-colors hover:bg-slate-900 hover:text-white"
                           aria-label="{{ __('shop.share.on_x') }}" title="{{ __('shop.share.on_x') }}">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                        </a>
                        <a href="{{ $shareLinks['tiktok'] }}" target="_blank" rel="noopener noreferrer"
                           class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-slate-200/70 text-slate-900 transition-colors hover:bg-slate-900 hover:text-white"
                           aria-label="{{ __('shop.share.on_tiktok') }}" title="{{ __('shop.share.on_tiktok') }}">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>
                        </a>
                        <a href="{{ $shareLinks['whatsapp'] }}" target="_blank" rel="noopener noreferrer"
                           class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-[#25D366]/10 text-[#25D366] transition-colors hover:bg-[#25D366] hover:text-white"
                           aria-label="{{ __('shop.share.on_whatsapp') }}" title="{{ __('shop.share.on_whatsapp') }}">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                        </a>
                        <button
                            type="button"
                            x-data="{
                                copied: false,
                                copy() {
                                    const url = @js($shareUrl);
                                    const fallback = () => {
                                        const el = document.createElement('textarea');
                                        el.value = url;
                                        el.style.position = 'fixed';
                                        el.style.opacity = '0';
                                        document.body.appendChild(el);
                                        el.select();
                                        document.execCommand('copy');
                                        document.body.removeChild(el);
                                    };
                                    const done = () => { this.copied = true; setTimeout(() => this.copied = false, 2000); };
                                    if (navigator.clipboard && window.isSecureContext) {
                                        navigator.clipboard.writeText(url).then(done);
                                    } else {
                                        fallback();
                                        done();
                                    }
                                },
                            }"
                            x-on:click="copy()"
                            class="inline-flex h-10 items-center gap-2 rounded-full border border-border bg-surface px-4 text-sm font-medium text-text transition-colors hover:border-primary hover:text-primary"
                            :class="copied && 'border-success text-success'"
                            :aria-label="copied ? @js(__('shop.share.copied')) : @js(__('shop.share.copy_link'))"
                            :title="copied ? @js(__('shop.share.copied')) : @js(__('shop.share.copy_link'))"
                        >
                            <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"/></svg>
                            <span x-text="copied ? @js(__('shop.share.copied')) : @js(__('shop.share.copy_link'))"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Description & attributes --}}
        <div class="mt-12 grid gap-8 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <h2 class="text-xl font-bold text-heading">{{ __('shop.description') }}</h2>
                <div class="mt-4 space-y-3 leading-relaxed text-text">
                    {!! \App\Support\Sanitizer::clean($product->translatedDescription()) !!}
                </div>
            </div>

            @if (! empty($attributes))
                <div>
                    <h2 class="text-xl font-bold text-heading">{{ __('shop.specifications') }}</h2>
                    <dl class="mt-4 divide-y divide-border rounded-2xl border border-border">
                        @foreach ($attributes as $attribute)
                            <div class="flex justify-between gap-4 px-4 py-3 text-sm">
                                <dt class="font-medium text-text-muted">{{ $attribute['name'] }}</dt>
                                <dd class="text-end text-text">
                                    {{ collect($attribute['values'])->pluck('label')->implode(', ') }}
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            @endif
        </div>

        {{-- Reviews --}}
        @if ((bool) setting('shop.reviews_enabled', false))
            <div class="mt-16 grid gap-10 lg:grid-cols-3" x-data="reviewForm">
                <div>
                    <h2 class="text-xl font-bold text-heading">{{ __('shop.reviews.title') }}</h2>

                    @if ($review_stats['count'] > 0)
                        <div class="mt-4 flex items-end gap-3">
                            <span class="text-5xl font-extrabold text-heading">{{ number_format($review_stats['average'], 1) }}</span>
                            <div class="pb-1">
                                <div class="flex text-amber-400">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                             class="h-5 w-5 {{ $i <= round($review_stats['average']) ? 'fill-current' : 'fill-transparent stroke-current' }}"
                                             fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z"/>
                                        </svg>
                                    @endfor
                                </div>
                                <p class="mt-1 text-sm text-text-muted">{{ __('shop.reviews.based_on', ['count' => $review_stats['count']]) }}</p>
                            </div>
                        </div>

                        <div class="mt-5 space-y-2">
                            @foreach ([5, 4, 3, 2, 1] as $star)
                                @php($starCount = $review_stats['distribution'][$star] ?? 0)
                                @php($pct = $review_stats['count'] > 0 ? round(($starCount / $review_stats['count']) * 100) : 0)
                                <div class="flex items-center gap-3 text-sm">
                                    <span class="w-8 shrink-0 text-text-muted">{{ $star }} ★</span>
                                    <div class="h-2 flex-1 overflow-hidden rounded-full bg-border">
                                        <div class="h-full rounded-full bg-amber-400" style="width: {{ $pct }}%"></div>
                                    </div>
                                    <span class="w-8 shrink-0 text-end text-text-muted">{{ $starCount }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="mt-3 text-text-muted">{{ __('shop.reviews.no_reviews') }}</p>
                    @endif
                </div>

                <div class="lg:col-span-2">
                    @if ($can_review && ! $has_reviewed)
                        <form x-on:submit.prevent="submit" class="mb-10 rounded-2xl border border-border bg-surface p-5">
                            <h3 class="text-base font-bold text-heading">{{ __('shop.reviews.form_title') }}</h3>

                            <div class="mt-4">
                                <span class="text-sm font-medium text-text">{{ __('shop.reviews.your_rating') }}</span>
                                <div class="mt-2 flex gap-1">
                                    <template x-for="star in [1, 2, 3, 4, 5]" :key="star">
                                        <button type="button" x-on:click="rating = star"
                                            :aria-label="'{{ __('shop.reviews.rate') }} ' + star"
                                            class="text-3xl transition-colors"
                                            :class="star <= rating ? 'text-amber-400' : 'text-border hover:text-amber-300'">
                                            ★
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <label class="mt-4 block">
                                <span class="text-sm font-medium text-text">{{ __('shop.reviews.title_label') }}</span>
                                <input type="text" x-model="title" maxlength="150"
                                       placeholder="{{ __('shop.reviews.title_placeholder') }}"
                                       class="mt-1 w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-text focus:border-primary focus:outline-none">
                            </label>

                            <label class="mt-4 block">
                                <span class="text-sm font-medium text-text">{{ __('shop.reviews.comment_label') }}</span>
                                <textarea x-model="comment" rows="4"
                                          placeholder="{{ __('shop.reviews.comment_placeholder') }}"
                                          class="mt-1 w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-text focus:border-primary focus:outline-none"></textarea>
                            </label>

                            <p class="mt-3 text-xs text-text-muted">{{ __('shop.reviews.moderation_notice') }}</p>

                            <button type="submit" :disabled="busy || rating < 1"
                                    class="btn-primary mt-4 justify-center !px-6"
                                    :class="{ 'cursor-not-allowed opacity-60': busy || rating < 1 }">
                                <span x-show="!busy">{{ __('shop.reviews.submit') }}</span>
                                <span x-show="busy" class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                            </button>
                        </form>
                    @elseif ($can_review && $has_reviewed)
                        <p class="mb-10 rounded-xl border border-border bg-surface p-4 text-sm text-text-muted">{{ __('shop.reviews.already') }}</p>
                    @endif

                    @if ($reviews->isNotEmpty())
                        <div class="space-y-5">
                            @foreach ($reviews as $review)
                                <article class="rounded-2xl border border-border bg-surface p-5">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="flex items-center gap-2">
                                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-bold text-primary">
                                                {{ mb_strtoupper(mb_substr($review->authorName(), 0, 1)) }}
                                            </span>
                                            <div>
                                                <p class="text-sm font-semibold text-heading">{{ $review->authorName() }}</p>
                                                <p class="text-xs text-text-muted">{{ $review->created_at->format('d M Y') }}
                                                    @if ($review->verified_purchase)
                                                        <span class="text-success"> · {{ __('shop.reviews.verified') }}</span>
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                        <div class="flex text-amber-400">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                                     class="h-4 w-4 {{ $i <= $review->rating ? 'fill-current' : 'fill-transparent stroke-current' }}"
                                                     fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z"/>
                                                </svg>
                                            @endfor
                                        </div>
                                    </div>
                                    @if (filled($review->title))
                                        <h4 class="mt-3 font-semibold text-heading">{{ $review->title }}</h4>
                                    @endif
                                    @if (filled($review->comment))
                                        <p class="mt-2 text-sm leading-relaxed text-text">{{ $review->comment }}</p>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    @else
                        <p class="rounded-2xl border border-dashed border-border p-8 text-center text-sm text-text-muted">{{ __('shop.reviews.no_reviews_yet') }}</p>
                    @endif
                </div>
            </div>

            <script>
                document.addEventListener('alpine:init', () => {
                    Alpine.data('reviewForm', () => ({
                        rating: 0,
                        title: '',
                        comment: '',
                        busy: false,
                        async submit() {
                            if (this.busy || this.rating < 1) return;
                            this.busy = true;
                            try {
                                const res = await fetch('{{ localized_route('shop.reviews.store', ['slug' => $product->translatedSlug()]) }}', {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                        'Accept': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest',
                                    },
                                    body: new URLSearchParams({ rating: this.rating, title: this.title, comment: this.comment }),
                                });
                                const payload = await res.json().catch(() => ({}));
                                Alpine.store('cart').showToast(payload.success ? 'success' : (payload.type === 'warning' ? 'warning' : 'error'), payload.message || '');
                                if (payload.success) {
                                    await new Promise(r => setTimeout(r, 1200));
                                    window.location.reload();
                                }
                            } catch (e) {
                                Alpine.store('cart').showToast('error', '');
                            } finally {
                                this.busy = false;
                            }
                        },
                    }));
                });
            </script>
        @endif
    </div>

    {{-- Related products --}}
    @if ($related->isNotEmpty())
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <x-storefront.section-heading
                subtitle="{{ __('shop.related_tagline') }}"
                :title="__('shop.related_title')"
                class="px-0"
            >
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($related as $product)
                        <x-storefront.product-card :product="$product" />
                    @endforeach
                </div>
            </x-storefront.section-heading>
        </div>
    @endif

    {{-- Product page Alpine component --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('productPage', (config) => ({
                galleryImages: config.galleryImages,
                attributes: config.attributes,
                variants: config.variants,
                activeImage: 0,
                selected: {},
                quantity: 1,
                available: config.available,
                variantAvailable: true,
                isVariable: config.isVariable,                price: config.defaultPrice,
                comparePrice: config.defaultComparePrice,
                selectedVariantId: null,
                adding: false,

                isSelected(attributeId, valueId) {
                    return this.selected[attributeId] === valueId;
                },

                selectAttribute(attributeId, valueId) {
                    const next = { ...this.selected };
                    if (next[attributeId] === valueId) {
                        delete next[attributeId];
                    } else {
                        next[attributeId] = valueId;
                    }
                    this.selected = next;
                    this.updateFromSelection();
                },

                updateFromSelection() {
                    const idKeys = Object.keys(this.selected);

                    if (idKeys.length === 0) {
                        this.price = config.defaultPrice;
                        this.comparePrice = config.defaultComparePrice;
                        this.variantAvailable = true;
                        this.available = config.available;
                        this.selectedVariantId = null;
                        this.updateImage(null);
                        return;
                    }

                    const matched = this.variants.find((variant) => {
                        const sel = variant.selection || {};
                        return idKeys.every((id) => sel[id] === Number(this.selected[id]));
                    });

                    if (matched) {
                        this.price = matched.price;
                        this.comparePrice = matched.compare_at_price;
                        this.variantAvailable = true;
                        this.available = matched.available;
                        this.selectedVariantId = matched.id ?? null;
                        this.updateImage(matched.image);
                    } else {
                        this.variantAvailable = false;
                        this.available = false;
                        this.selectedVariantId = null;
                    }
                },

                get addable() {
                    if (!config.isVariable) {
                        return this.available;
                    }
                    return this.selectedVariantId !== null && this.available;
                },

                async addToCart() {
                    if (!this.addable || this.adding || this.$store.cart.busy) return;
                    this.adding = true;
                    await this.$store.cart.add(config.productId, config.isVariable ? this.selectedVariantId : null, this.quantity, { open: false });
                    this.adding = false;
                },

                updateImage(url) {
                    if (url && this.galleryImages.length) {
                        const idx = this.galleryImages.findIndex((img) => img.src === url);
                        if (idx !== -1) {
                            this.activeImage = idx;
                        }
                    }
                },
            }));
        });
    </script>

</x-shop-layout>
