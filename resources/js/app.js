import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

/**
 * Format angka menjadi Rupiah di sisi peramban, mis. 261000 => "Rp261.000".
 * Dipakai untuk pratinjau langsung pada form (harga, setoran, laba).
 */
window.rupiah = (angka) =>
    'Rp' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Math.round(Number(angka) || 0));
