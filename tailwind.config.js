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
            fontSize: {
                xs: ['0.875rem', { lineHeight: '1.25rem' }],
                sm: ['1rem', { lineHeight: '1.5rem' }],
                base: ['1.125rem', { lineHeight: '1.75rem' }],
                lg: ['1.25rem', { lineHeight: '1.75rem' }],
                xl: ['1.375rem', { lineHeight: '1.875rem' }],
                '2xl': ['1.625rem', { lineHeight: '2rem' }],
                '3xl': ['2.125rem', { lineHeight: '2.25rem' }],
                '4xl': ['2.5rem', { lineHeight: '2.5rem' }],
                '5xl': ['3.25rem', { lineHeight: '1' }],
                '6xl': ['4rem', { lineHeight: '1' }],
                '7xl': ['5rem', { lineHeight: '1' }],
                '8xl': ['6.5rem', { lineHeight: '1' }],
                '9xl': ['8.5rem', { lineHeight: '1' }],
            },
        },
    },

    plugins: [forms],
};
