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
                // Couleur primaire Zonetools : #3D63DD
                primary: {
                    50: '#eef2fd',
                    100: '#dfe6fb',
                    200: '#c4d1f8',
                    300: '#9fb3f2',
                    400: '#7590ea',
                    500: '#5a7ce4',
                    600: '#3D63DD',
                    700: '#2f4fc0',
                    800: '#2a439c',
                    900: '#283c7c',
                    950: '#1c264c',
                    DEFAULT: '#3D63DD',
                },
            },
        },
    },

    plugins: [forms],
};
