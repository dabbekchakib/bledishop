import Alpine from 'alpinejs';

const STORAGE_KEY = 'bledishop-theme';

function readStoredTheme() {
    try {
        const value = localStorage.getItem(STORAGE_KEY);
        if (value === 'dark' || value === 'light') {
            return value;
        }
    } catch (_e) {
        // storage unavailable — ignore
    }
    return null;
}

function systemTheme() {
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

function enabledFromRoot() {
    return typeof document !== 'undefined' && document.body.dataset.themeEnabled === '1';
}

export function registerThemeStore() {
    const enabled = enabledFromRoot();
    const initial = readStoredTheme() ?? (enabled ? systemTheme() : 'light');

    const store = {
        enabled,
        current: initial,

        apply() {
            const root = document.documentElement;
            if (this.current === 'dark') {
                root.setAttribute('data-theme', 'dark');
            } else {
                root.removeAttribute('data-theme');
            }

            if (this.enabled) {
                try {
                    localStorage.setItem(STORAGE_KEY, this.current);
                } catch (_e) {
                    // storage unavailable — ignore
                }
            }
        },

        toggle() {
            if (!this.enabled) {
                return;
            }
            this.current = this.current === 'dark' ? 'light' : 'dark';
            this.apply();
        },
    };

    store.apply();

    Alpine.store('theme', store);
}
