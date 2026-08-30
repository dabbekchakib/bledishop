import Alpine from 'alpinejs';

import { registerCartStore } from './cart';
import { registerThemeStore } from './theme';

registerCartStore();
registerThemeStore();

window.Alpine = Alpine;

Alpine.start();
