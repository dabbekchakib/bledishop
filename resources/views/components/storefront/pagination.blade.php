@props(['paginator'])

@if ($paginator->hasPages())
    <div class="mt-10">
        {{ $paginator->links('pagination::tailwind') }}
    </div>
@endif
