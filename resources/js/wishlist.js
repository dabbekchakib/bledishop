import Alpine from 'alpinejs';

export function registerWishlistStore() {
    Alpine.store('wishlist', {
        ids: new Set(),
        toggleUrl: '',
        busy: false,

        init() {
            const state = document.querySelector('[data-wishlist-state]');
            if (state) {
                this.toggleUrl = state.dataset.wishlistToggle || '';
                try {
                    const ids = JSON.parse(state.dataset.wishlistIds || '[]');
                    this.ids = new Set(Array.isArray(ids) ? ids.map(Number) : []);
                } catch (e) {
                    this.ids = new Set();
                }
            }
        },

        contains(productId) {
            return this.ids.has(Number(productId));
        },

        csrf() {
            const meta = document.querySelector('meta[name="csrf-token"]');
            return meta ? meta.getAttribute('content') : '';
        },

        async toggle(productId) {
            if (this.busy) return;
            this.busy = true;
            productId = Number(productId);
            try {
                const res = await fetch(this.toggleUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.csrf(),
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: new URLSearchParams({ product_id: productId }),
                });
                const payload = await res.json().catch(() => ({}));
                if (payload.success) {
                    if (payload.in_wishlist) {
                        this.ids.add(productId);
                    } else {
                        this.ids.delete(productId);
                    }
                }
                if (payload.message) {
                    Alpine.store('cart').showToast(
                        payload.success ? 'success' : (payload.type === 'warning' ? 'warning' : 'error'),
                        payload.message,
                    );
                }
            } catch (e) {
                /* no-op */
            } finally {
                this.busy = false;
            }
        },
    });
}
