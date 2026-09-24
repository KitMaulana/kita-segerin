/*
 * Pemasangan aplikasi (PWA).
 *
 * 1. Mendaftarkan public/sw.js dengan penanda versi dari <meta name="versi-aset">.
 *    Versi itu berubah tiap kali Vite membangun ulang, sehingga peramban
 *    memasang service worker baru dan membuang cache lama dengan sendirinya.
 * 2. Menyiapkan tombol "Pasang aplikasi" di halaman Lainnya.
 */

const meta = document.querySelector('meta[name="versi-aset"]');
const versi = meta ? meta.content : 'dev';

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker
            .register(`/sw.js?v=${encodeURIComponent(versi)}`, { scope: '/' })
            .catch(() => {
                // Gagal mendaftar (mis. dibuka lewat HTTP biasa) bukan hal fatal:
                // aplikasi tetap berjalan normal, hanya tidak bisa dipasang.
            });
    });
}

/** Menyimpan event bawaan Chrome/Edge supaya bisa dipanggil saat tombol ditekan. */
let tawaranPasang = null;

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    tawaranPasang = event;
    perbaruiTampilan();
});

window.addEventListener('appinstalled', () => {
    tawaranPasang = null;
    perbaruiTampilan('terpasang');
});

document.addEventListener('DOMContentLoaded', () => {
    const tombol = document.querySelector('[data-pasang-aplikasi]');

    if (tombol) {
        tombol.addEventListener('click', async () => {
            if (! tawaranPasang) {
                return;
            }

            tawaranPasang.prompt();
            await tawaranPasang.userChoice;
            tawaranPasang = null;
            perbaruiTampilan();
        });
    }

    perbaruiTampilan();
});

/** True di iPhone/iPad, yang tidak punya beforeinstallprompt. */
function iniIos() {
    return /iphone|ipad|ipod/i.test(navigator.userAgent);
}

/** True bila aplikasi sudah dibuka dari layar utama, bukan dari peramban. */
function sudahTerpasang() {
    return window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
}

/**
 * Menampilkan salah satu dari tiga keadaan di halaman Lainnya:
 * tombol pasang (Android/desktop), petunjuk manual (iPhone), atau
 * keterangan bahwa aplikasi sudah terpasang.
 */
function perbaruiTampilan(paksa = null) {
    const wadah = document.querySelector('[data-pasang-wadah]');

    if (! wadah) {
        return;
    }

    const keadaan = paksa
        || (sudahTerpasang() ? 'terpasang' : (tawaranPasang ? 'siap' : (iniIos() ? 'ios' : 'belum')));

    wadah.querySelectorAll('[data-pasang-keadaan]').forEach((bagian) => {
        bagian.hidden = bagian.dataset.pasangKeadaan !== keadaan;
    });
}
