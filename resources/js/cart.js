import Alpine from 'alpinejs';

export function registerCartStore() {
    const badge = document.querySelector('[data-cart-base]');

    Alpine.store('cart', {
        count: badge ? (parseInt(badge.dataset.cartCount || '0', 10) || 0) : 0,
        base: badge ? badge.dataset.cartBase : '/cart',
        drawerOpen: false,
        toast: null,
        toastTimer: null,
        busy: false,

        init() {
            const el = document.querySelector('[data-cart-base]');
            if (el) {
                this.base = el.dataset.cartBase;
                this.count = parseInt(el.dataset.cartCount || '0', 10) || 0;
            }
        },

        url(path) {
            return this.base + path;
        },

        csrf() {
            const meta = document.querySelector('meta[name="csrf-token"]');
            return meta ? meta.getAttribute('content') : '';
        },

        headers(method) {
            return {
                'X-CSRF-TOKEN': this.csrf(),
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
            };
        },

        body(data) {
            const params = new URLSearchParams();
            Object.entries(data || {}).forEach(([k, v]) => {
                if (v !== null && v !== undefined) {
                    params.append(k, v);
                }
            });
            return params.toString();
        },

        openDrawer() {
            this.drawerOpen = true;
        },

        closeDrawer() {
            this.drawerOpen = false;
        },

        async refreshDrawer() {
            const inner = document.getElementById('cart-drawer-inner');
            if (!inner) return;
            try {
                const res = await fetch(this.url('/drawer'));
                const html = await res.text();
                inner.innerHTML = html;
                if (window.Alpine && typeof window.Alpine.initTree === 'function') {
                    window.Alpine.initTree(inner);
                }
            } catch (e) {
                /* keep the server-rendered drawer */
            }
        },

        async add(productId, variantId, quantity, options) {
            options = options || {};
            if (this.busy) return;
            this.busy = true;
            try {
                const res = await fetch(this.url('/add'), {
                    method: 'POST',
                    headers: this.headers(),
                    body: this.body({ product_id: productId, variant_id: variantId, quantity }),
                });
                const payload = await res.json().catch(() => ({}));
                this.handle(payload);

                if (options.open !== false && payload.success) {
                    this.openDrawer();
                }
            } catch (e) {
                this.showToast('error');
            } finally {
                this.busy = false;
            }
        },

        async updateQty(key, quantity) {
            quantity = Math.max(0, parseInt(quantity, 10) || 0);
            let payload;
            if (quantity <= 0) {
                payload = await this.request('DELETE', '/items/' + encodeURIComponent(key));
            } else {
                payload = await this.request('PATCH', '/items/' + encodeURIComponent(key), { quantity });
            }
            this.handle(payload);
        },

        async remove(key) {
            const payload = await this.request('DELETE', '/items/' + encodeURIComponent(key));
            this.handle(payload);
        },

        async clear() {
            const payload = await this.request('DELETE', '');
            this.handle(payload);
        },

        async request(method, path, data) {
            try {
                const res = await fetch(this.url(path), {
                    method,
                    headers: this.headers(),
                    body: data ? this.body(data) : undefined,
                });
                return await res.json().catch(() => ({}));
            } catch (e) {
                return { success: false };
            }
        },

        handle(payload) {
            if (!payload) return;
            if (typeof payload.cart_count !== 'undefined') {
                this.count = parseInt(payload.cart_count, 10) || 0;
            }
            if (payload.success && payload.message) {
                this.showToast('success', payload.message);
            } else if (!payload.success && payload.message) {
                this.showToast(payload.type === 'warning' ? 'warning' : 'error', payload.message);
            }
            this.maybeRefreshPage();
        },

        maybeRefreshPage() {
            if (document.getElementById('cart-items') || document.getElementById('cart-summary')) {
                this.refreshCartPage();
            }
        },

        async refreshCartPage() {
            try {
                const res = await fetch(this.url('/fragments'), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const payload = await res.json();
                const items = document.getElementById('cart-items');
                const summary = document.getElementById('cart-summary');
                if (items && payload.items_html) {
                    items.outerHTML = payload.items_html;
                }
                if (summary && payload.summary_html) {
                    summary.outerHTML = payload.summary_html;
                }
                if (typeof payload.cart_count !== 'undefined') {
                    this.count = parseInt(payload.cart_count, 10) || 0;
                }
                if (window.Alpine && typeof window.Alpine.initTree === 'function') {
                    window.Alpine.initTree(document.body);
                }
            } catch (e) {
                /* fall back to full reload */
                window.location.reload();
            }
        },

        showToast(type, message) {
            if (this.toastTimer) clearTimeout(this.toastTimer);
            this.toast = { type: type || 'info', message: message || '' };
            this.toastTimer = setTimeout(() => { this.toast = null; }, 3500);
        },

        clearToast() {
            if (this.toastTimer) clearTimeout(this.toastTimer);
            this.toast = null;
        },
    });
}
