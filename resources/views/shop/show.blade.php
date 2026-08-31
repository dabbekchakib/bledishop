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
