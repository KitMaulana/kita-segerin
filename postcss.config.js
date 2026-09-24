export default {
    plugins: {
        // postcss-import diperlukan agar @import font @fontsource diproses
        // lebih dulu sebelum aturan Tailwind.
        'postcss-import': {},
        tailwindcss: {},
        autoprefixer: {},
    },
};
