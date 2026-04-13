import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        // Laravel core
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',

        // App views
        './resources/views/**/*.blade.php',
        './resources/views/filament/**/*.blade.php',

        // JS / Vue
        './resources/**/*.js',
        './resources/**/*.vue',

        // 🔥 FILAMENT (WAJIB)
        './vendor/filament/**/*.blade.php',
        './app/Filament/**/*.php',
        './app/Livewire/**/*.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [
        forms,
    ],
};
