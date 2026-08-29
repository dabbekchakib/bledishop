<div
    x-data
    x-show="$store.cart.toast"
    x-cloak
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 translate-y-2"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="pointer-events-none fixed bottom-4 start-1/2 z-[60] w-[calc(100%-2rem)] max-w-sm -translate-x-1/2 sm:start-auto sm:end-4 sm:w-auto sm:-translate-x-0 rtl:sm:start-4 rtl:sm:end-auto"
    role="status"
    aria-live="polite"
>
    <template x-if="$store.cart.toast">
        <div
            class="pointer-events-auto flex items-start gap-3 rounded-xl border p-4 shadow-xl"
            :class="{
                'border-border bg-background text-text': $store.cart.toast.type === 'info',
                'border-success/40 bg-background text-text': $store.cart.toast.type === 'success',
                'border-amber-300 bg-background text-text': $store.cart.toast.type === 'warning',
                'border-danger/40 bg-background text-text': $store.cart.toast.type === 'error',
            }"
        >
            <span
                class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full"
                :class="{
                    'bg-primary/10 text-primary': $store.cart.toast.type === 'info',
                    'bg-success/10 text-success': $store.cart.toast.type === 'success',
                    'bg-amber-100 text-amber-700': $store.cart.toast.type === 'warning',
                    'bg-danger/10 text-danger': $store.cart.toast.type === 'error',
                }"
            >
                <svg x-show="$store.cart.toast.type === 'success'" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                <svg x-show="$store.cart.toast.type !== 'success'" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h-14.71c-1.73 0-2.813-1.874-1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
            </span>
            <p class="flex-1 text-sm font-medium" x-text="$store.cart.toast.message"></p>
            <button
                type="button"
                x-on:click="$store.cart.clearToast()"
                class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-md text-text-muted hover:text-heading"
                aria-label="{{ __('shop.close') }}"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </template>
</div>
