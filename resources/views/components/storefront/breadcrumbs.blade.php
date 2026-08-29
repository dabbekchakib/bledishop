@props(['items' => []])

<nav aria-label="{{ __('shop.breadcrumb') }}" class="mb-4">
    <ol class="flex flex-wrap items-center gap-1.5 text-sm text-text-muted">
        <li>
            <a href="{{ localized_route('home') }}" class="transition-colors hover:text-primary">{{ __('messages.nav_home') }}</a>
        </li>
        @foreach ($items as $item)
            <li class="flex items-center gap-1.5" aria-current="{{ $loop->last ? 'page' : 'false' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                </svg>
                @if (isset($item['url']) && filled($item['url']))
                    <a href="{{ $item['url'] }}" class="transition-colors hover:text-primary">{{ $item['label'] }}</a>
                @else
                    <span class="text-text truncate">{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
