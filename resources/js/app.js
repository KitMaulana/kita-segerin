import './bootstrap';
import './pwa';

import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;

Alpine.start();

// Chart.js dipakai langsung dari halaman beranda lewat window.Chart,
// supaya tidak perlu bundel terpisah per halaman.
window.Chart = Chart;

Chart.defaults.font.family = 'Plus Jakarta Sans, system-ui, sans-serif';
Chart.defaults.font.weight = 500;
Chart.defaults.color = '#1D2B36';

/**
 * Format angka menjadi Rupiah di sisi peramban, mis. 261000 => "Rp261.000".
 * Dipakai untuk pratinjau langsung pada form (harga, setoran, laba).
 */
window.rupiah = (angka) =>
    'Rp' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Math.round(Number(angka) || 0));

/**
 * Rupiah versi ringkas untuk sumbu grafik, mis. 1250000 => "Rp1,3 jt".
 */
window.rupiahSingkat = (angka) => {
    const n = Number(angka) || 0;

    if (Math.abs(n) >= 1_000_000) {
        return 'Rp' + (n / 1_000_000).toFixed(1).replace('.', ',') + ' jt';
    }

    if (Math.abs(n) >= 1_000) {
        return 'Rp' + Math.round(n / 1_000) + ' rb';
    }

    return 'Rp' + n;
};

/**
 * Warna grafik mengikuti token Tailwind di tailwind.config.js.
 */
window.warnaKitaa = {
    berry: '#5B2A6E',
    mint: '#12A383',
    mango: '#E89B2D',
    strawberry: '#D9435A',
    ink: '#1D2B36',
    frost: '#EEF6FA',
    stik: '#C9A227',
    seri: ['#5B2A6E', '#12A383', '#E89B2D', '#D9435A', '#7B5EA7', '#4FB3A0'],
};
