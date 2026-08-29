@props([
    'title' => '',
    'subtitle' => '',
    'actionUrl' => null,
    'actionLabel' => '',
    'actionRight' => '',
    'class' => '',
])

<section class="{{ $class }}" aria-label="{{ $title }}">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            @if ($subtitle)
                <p class="mb-1 text-xs font-semibold uppercase tracking-widest text-primary">{{ $subtitle }}</p>
            @endif
            <h2 class="text-2xl font-bold tracking-tight text-heading sm:text-3xl">{{ $title }}</h2>
        </div>
        <div class="flex items-center gap-3">
            @if ($actionRight)
                {{ $actionRight }}
            @endif
            @if ($actionUrl)
                <a href="{{ $actionUrl }}" class="group inline-flex items-center gap-1 text-sm font-semibold text-primary transition-colors hover:text-heading">
                    {{ $actionLabel }}
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform group-hover:translate-x-0.5 rtl:group-hover:-translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                    </svg>
                </a>
            @endif
        </div>
    </div>
    {{ $slot }}
</section>
