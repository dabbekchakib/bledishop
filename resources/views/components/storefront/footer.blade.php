@props(['categories' => [], 'brands' => []])

@php
    $siteName = setting('site.name', config('app.name', 'BlediShop'));
    $logo = storefront_logo();
    $phone = setting('site.phone');
    $email = setting('site.email');
    $address = setting('site.address');
    $social = setting('social');
    $copyright = setting('site.copyright');
    $socialLabels = [
        'facebook' => __('shop.social_facebook'),
        'instagram' => __('shop.social_instagram'),
        'linkedin' => __('shop.social_linkedin'),
        'youtube' => __('shop.social_youtube'),
        'twitter' => __('shop.social_twitter'),
    ];
    $navLinks = [
        ['label' => __('messages.nav_home'), 'url' => localized_route('home')],
        ['label' => __('shop.nav_shop'), 'url' => localized_route('shop.index')],
    ];
@endphp

<footer class="bg-footer text-footer-text">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-10 sm:grid-cols-2 lg:grid-cols-4">

            {{-- Brand --}}
            <div>
                @if ($logo)
                    <img src="{{ $logo }}" alt="{{ $siteName }}" class="mb-4 h-9 w-auto object-contain">
                @else
                    <p class="mb-4 text-xl font-extrabold tracking-tight">{{ $siteName }}</p>
                @endif
                <p class="text-sm leading-relaxed text-footer-text/80">
                    {{ setting('site.description', '') }}
                </p>
                @if ($social && is_array($social))
                    <div class="mt-5 flex gap-3">
                        @foreach ($social as $key => $url)
                            @if (filled($url))
                                <a href="{{ $url }}" target="_blank" rel="noopener" class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-surface text-footer-text transition-colors hover:bg-primary hover:text-white"
                                    aria-label="{{ $socialLabels[$key] ?? $key }}">
                                    {{ ucfirst($key[0]) }}
                                </a>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Navigation --}}
            <div>
                <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-footer-text">{{ __('messages.main_navigation') }}</h3>
                <ul class="space-y-2.5 text-sm">
                    @foreach ($navLinks as $link)
                        <li><a href="{{ $link['url'] }}" class="text-footer-text/80 transition-colors hover:text-primary">{{ $link['label'] }}</a></li>
                    @endforeach
                    <li>
                        <a href="{{ localized_route('shop.search') }}" class="text-footer-text/80 transition-colors hover:text-primary">{{ __('shop.footer_search') }}</a>
                    </li>
                </ul>
            </div>

            {{-- Categories --}}
            @if ($categories->isNotEmpty())
                <div>
                    <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-footer-text">{{ __('shop.footer_categories') }}</h3>
                    <ul class="space-y-2.5 text-sm">
                        @foreach ($categories->take(6) as $category)
                            <li>
                                <a href="{{ localized_route('shop.category.show', ['slug' => $category->translatedSlug()]) }}"
                                   class="text-footer-text/80 transition-colors hover:text-primary">
                                    {{ $category->translatedName() }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Contact --}}
            <div>
                <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-footer-text">{{ __('shop.footer_contact') }}</h3>
                <ul class="space-y-2.5 text-sm text-footer-text/80">
                    @if (filled($phone))
                        <li class="flex items-start gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 shrink-0 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
                            </svg>
                            <a href="tel:{{ $phone }}" class="transition-colors hover:text-primary" dir="ltr">{{ $phone }}</a>
                        </li>
                    @endif
                    @if (filled($email))
                        <li class="flex items-start gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 shrink-0 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                            </svg>
                            <a href="mailto:{{ $email }}" class="break-all transition-colors hover:text-primary">{{ $email }}</a>
                        </li>
                    @endif
                    @if (filled($address))
                        <li class="flex items-start gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 shrink-0 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
                            </svg>
                            <span>{{ $address }}</span>
                        </li>
                    @endif
                </ul>

                <div class="mt-5">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-footer-text/70">{{ __('messages.language') }}</p>
                    <x-language-switcher />
                </div>
            </div>
        </div>

        <div class="mt-10 flex flex-col items-center justify-between gap-4 border-t border-border pt-6 text-sm text-footer-text/70 sm:flex-row">
            <p>{{ filled($copyright) ? $copyright : __('shop.footer_copyright', ['year' => now()->format('Y'), 'name' => $siteName]) }}</p>
            <p class="flex items-center gap-1.5">
                {{ __('shop.footer_rights') }}
            </p>
        </div>
    </div>
</footer>
