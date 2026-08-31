import Alpine from 'alpinejs';

import { registerCartStore } from './cart';
import { registerThemeStore } from './theme';

registerCartStore();
registerThemeStore();

window.Alpine = Alpine;

Alpine.data('countdown', (endsAt) => ({
    d: 0,
    h: 0,
    m: 0,
    s: 0,
    _timer: null,
    init() {
        this.tick();
        this._timer = window.setInterval(() => this.tick(), 1000);
    },
    destroy() {
        if (this._timer) window.clearInterval(this._timer);
    },
    tick() {
        const diff = Math.max(0, Number(endsAt) - Math.floor(Date.now() / 1000));
        this.d = Math.floor(diff / 86400);
        this.h = Math.floor((diff % 86400) / 3600);
        this.m = Math.floor((diff % 3600) / 60);
        this.s = diff % 60;
    },
}));

Alpine.start();
