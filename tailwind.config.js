import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Plus Jakarta Sans', ...defaultTheme.fontFamily.sans],
            },

            // Token warna KITAA SEGERIN (lihat bagian F CLAUDE.md).
            colors: {
                frost: '#EEF6FA',       // latar halaman
                ink: '#1D2B36',         // teks utama
                berry: '#5B2A6E',       // warna utama
                mint: '#12A383',        // angka positif, laba, lunas
                strawberry: '#D9435A',  // rugi, jatuh tempo, rusak
                mango: '#E89B2D',       // peringatan
            },

            borderRadius: {
                '2xl': '1rem',
            },

            boxShadow: {
                sm: '0 1px 2px 0 rgb(29 43 54 / 0.05)',
            },
        },
    },

    plugins: [forms],
};
