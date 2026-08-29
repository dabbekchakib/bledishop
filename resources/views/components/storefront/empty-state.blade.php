@props(['title' => '', 'message' => ''])

<div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-border bg-surface px-6 py-16 text-center">
    <div class="mb-4 inline-flex h-16 w-16 items-center justify-center rounded-full bg-background text-text-muted">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
        </svg>
    </div>
    @if (filled($title))
        <h2 class="text-lg font-semibold text-heading">{{ $title }}</h2>
    @endif
    @if (filled($message))
        <p class="mt-1 text-sm text-text-muted">{{ $message }}</p>
    @endif
</div>
