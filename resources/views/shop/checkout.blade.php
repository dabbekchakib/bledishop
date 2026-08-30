<x-shop-layout
    :title="__('checkout.title')"
    :meta-description="__('checkout.meta_description')"
    robots="noindex, nofollow"
>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-extrabold text-heading sm:text-3xl">{{ __('checkout.title') }}</h1>
        <p class="mt-1 text-sm text-text-muted">{{ __('checkout.intro') }}</p>

        @if ($errors->any())
            <div class="mt-6 rounded-2xl border border-danger/30 bg-danger/5 p-4 text-sm text-danger">
                <p class="font-semibold">{{ __('checkout.form_errors_title') }}</p>
                <ul class="mt-2 list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form
            method="POST"
            action="{{ localized_route('shop.checkout.store') }}"
            class="mt-8 grid gap-10 lg:grid-cols-[1fr_24rem]"
        >
            @csrf

            {{-- Customer information --}}
            <div class="space-y-8">
                <section class="rounded-2xl border border-border bg-surface p-6">
                    <h2 class="text-lg font-bold text-heading">{{ __('checkout.contact_title') }}</h2>

                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <x-checkout-field
                            name="first_name"
                            :label="__('checkout.first_name')"
                            :value="$prefill['first_name']"
                            required
                            :error="$errors->first('first_name')"
                            autocomplete="given-name"
                        />
                        <x-checkout-field
                            name="last_name"
                            :label="__('checkout.last_name')"
                            :value="$prefill['last_name']"
                            required
                            :error="$errors->first('last_name')"
                            autocomplete="family-name"
                        />
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <x-checkout-field
                            name="email"
                            type="email"
                            :label="__('checkout.email_label')"
                            :value="$prefill['email']"
                            required
                            :error="$errors->first('email')"
                            autocomplete="email"
                        />
                        <x-checkout-field
                            name="phone"
                            type="tel"
                            :label="__('checkout.phone')"
                            :value="$prefill['phone']"
                            required
                            :error="$errors->first('phone')"
                            autocomplete="tel"
                        />
                    </div>
                </section>

                <section class="rounded-2xl border border-border bg-surface p-6">
                    <h2 class="text-lg font-bold text-heading">{{ __('checkout.shipping_title') }}</h2>

                    <div class="mt-5">
                        <x-checkout-field
                            name="address"
                            :label="__('checkout.address')"
                            :value="$prefill['address']"
                            required
                            :error="$errors->first('address')"
                            autocomplete="street-address"
                        />
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <x-checkout-field
                            name="city"
                            :label="__('checkout.city')"
                            :value="$prefill['city']"
                            :error="$errors->first('city')"
                            autocomplete="address-level2"
                        />
                        <x-checkout-field
                            name="postal_code"
                            :label="__('checkout.postal_code')"
                            :value="$prefill['postal_code']"
                            :error="$errors->first('postal_code')"
                            autocomplete="postal-code"
                        />
                    </div>

                    <div class="mt-4">
                        <x-checkout-field
                            name="country"
                            :label="__('checkout.country')"
                            :value="$prefill['country']"
                            :error="$errors->first('country')"
                            autocomplete="country-name"
                        />
                    </div>

                    <div class="mt-4">
                        <x-checkout-field
                            name="notes"
                            type="textarea"
                            :label="__('checkout.notes')"
                            :value="$prefill['notes']"
                            :error="$errors->first('notes')"
                            :rows="3"
                        />
                    </div>
                </section>

                @guest
                    @if ($guestCheckoutEnabled)
                        <section
                            x-data="{ createAccount: false }"
                            class="rounded-2xl border border-border bg-surface p-6"
                        >
                            <h2 class="text-lg font-bold text-heading">{{ __('checkout.account_title') }}</h2>
                            <p class="mt-1 text-sm text-text-muted">{{ __('checkout.account_hint') }}</p>

                            <label class="mt-4 flex cursor-pointer items-start gap-3">
                                <input
                                    type="checkbox"
                                    name="create_account"
                                    value="1"
                                    x-model="createAccount"
                                    class="mt-0.5 h-5 w-5 rounded border-border text-primary focus:ring-primary"
                                >
                                <span class="text-sm text-text">{{ __('checkout.account_create_label') }}</span>
                            </label>

                            <div x-show="createAccount" x-cloak class="mt-4 grid gap-4 sm:grid-cols-2">
                                <x-checkout-field
                                    name="password"
                                    type="password"
                                    :label="__('checkout.password')"
                                    :error="$errors->first('password')"
                                    autocomplete="new-password"
                                    x-bind:required="createAccount"
                                />
                                <x-checkout-field
                                    name="password_confirmation"
                                    type="password"
                                    :label="__('checkout.password_confirmation')"
                                    :error="$errors->first('password_confirmation')"
                                    autocomplete="new-password"
                                    x-bind:required="createAccount"
                                />
                            </div>
                        </section>
                    @endif
                @endguest
            </div>

            {{-- Order summary --}}
            <aside class="lg:sticky lg:top-24 lg:self-start">
                <div class="rounded-2xl border border-border bg-surface p-6">
                    <h2 class="text-lg font-bold text-heading">{{ __('checkout.summary') }}</h2>

                    <ul class="mt-5 space-y-4 divide-y divide-border">
                        @foreach ($cart['items'] as $item)
                            <li class="flex items-start gap-4 pt-4 first:pt-0">
                                @if ($item['image'])
                                    <img
                                        src="{{ $item['image'] }}"
                                        alt="{{ $item['name'] }}"
                                        class="h-16 w-16 shrink-0 rounded-xl border border-border bg-background object-cover"
                                        loading="lazy"
                                    >
                                @endif
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-heading">{{ $item['name'] }}</p>
                                    @if ($item['variant_label'])
                                        <p class="mt-0.5 text-xs text-text-muted">{{ $item['variant_label'] }}</p>
                                    @endif
                                    <p class="mt-1 text-xs text-text-muted">{{ trans_choice('checkout.qty_label', $item['quantity'], ['count' => $item['quantity']]) }}</p>
                                </div>
                                <div class="text-right rtl:text-left">
                                    <p class="text-sm font-semibold text-text">{{ format_price($item['line_total']) }}</p>
                                    @if ($item['old_price'])
                                        <p class="text-xs text-text-muted line-through">{{ format_price($item['old_price']) }}</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    <dl class="mt-6 space-y-3 border-t border-border pt-5 text-sm">
                        <div class="flex items-center justify-between">
                            <dt class="text-text-muted">{{ __('checkout.subtotal') }}</dt>
                            <dd class="font-semibold text-text">{{ format_price($cart['subtotal']) }}</dd>
                        </div>
                        @if ((float) $cart['totals']['tax'] > 0)
                            <div class="flex items-center justify-between">
                                <dt class="text-text-muted">{{ __('checkout.tax') }}</dt>
                                <dd class="font-semibold text-text">{{ format_price($cart['totals']['tax']) }}</dd>
                            </div>
                        @endif
                        <div class="flex items-center justify-between">
                            <dt class="text-text-muted">{{ __('checkout.shipping') }}</dt>
                            <dd class="font-semibold {{ (float) $cart['totals']['shipping'] > 0 ? 'text-text' : 'text-success' }}">
                                {{ (float) $cart['totals']['shipping'] > 0 ? format_price($cart['totals']['shipping']) : __('checkout.shipping_free') }}
                            </dd>
                        </div>
                        <div class="flex items-center justify-between border-t border-border pt-3">
                            <dt class="text-base font-semibold text-heading">{{ __('checkout.total') }}</dt>
                            <dd class="text-2xl font-extrabold text-primary">{{ format_price($cart['total']) }}</dd>
                        </div>
                    </dl>

                    <p class="mt-4 text-xs text-text-muted">{{ __('checkout.shipping_note') }}</p>

                    <button type="submit" class="btn-primary mt-6 w-full justify-center !px-4">
                        {{ __('checkout.place_order') }}
                    </button>
                    <p class="mt-3 text-center text-xs text-text-muted">{{ __('checkout.privacy_hint') }}</p>
                </div>
            </aside>
        </form>
    </div>

</x-shop-layout>
