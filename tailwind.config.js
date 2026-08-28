import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: 'rgb(var(--color-primary) / <alpha-value>)',
                'primary-hover': 'rgb(var(--color-primary-hover) / <alpha-value>)',
                secondary: 'rgb(var(--color-secondary) / <alpha-value>)',
                'secondary-hover': 'rgb(var(--color-secondary-hover) / <alpha-value>)',
                accent: 'rgb(var(--color-accent) / <alpha-value>)',
                success: 'rgb(var(--color-success) / <alpha-value>)',
                warning: 'rgb(var(--color-warning) / <alpha-value>)',
                danger: 'rgb(var(--color-danger) / <alpha-value>)',
                info: 'rgb(var(--color-info) / <alpha-value>)',
                text: 'rgb(var(--color-text) / <alpha-value>)',
                'text-muted': 'rgb(var(--color-text-muted) / <alpha-value>)',
                heading: 'rgb(var(--color-heading) / <alpha-value>)',
                background: 'rgb(var(--color-background) / <alpha-value>)',
                surface: 'rgb(var(--color-surface) / <alpha-value>)',
                border: 'rgb(var(--color-border) / <alpha-value>)',
                header: 'rgb(var(--color-header) / <alpha-value>)',
                'header-text': 'rgb(var(--color-header-text) / <alpha-value>)',
                footer: 'rgb(var(--color-footer) / <alpha-value>)',
                'footer-text': 'rgb(var(--color-footer-text) / <alpha-value>)',
                'dark-background': 'rgb(var(--color-dark-background) / <alpha-value>)',
                'dark-surface': 'rgb(var(--color-dark-surface) / <alpha-value>)',
                'dark-text': 'rgb(var(--color-dark-text) / <alpha-value>)',
                'dark-text-muted': 'rgb(var(--color-dark-text-muted) / <alpha-value>)',
                'dark-border': 'rgb(var(--color-dark-border) / <alpha-value>)',
                'dark-heading': 'rgb(var(--color-dark-heading) / <alpha-value>)',
            },
        },
    },

    plugins: [forms],
};
