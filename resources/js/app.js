import Alpine from 'alpinejs';

import { registerCartStore } from './cart';

registerCartStore();

window.Alpine = Alpine;

Alpine.start();
