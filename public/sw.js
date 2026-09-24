/*
 * Service worker KITAA SEGERIN.
 *
 * Didaftarkan dari resources/js/pwa.js sebagai "/sw.js?v=<versi>". Versi itu
 * berubah otomatis setiap kali Vite membangun ulang aset (lihat helper
 * versi_aset() di app/Support/helpers.php), sehingga peramban menganggapnya
 * service worker baru, memasang ulang, lalu membuang cache versi lama.
 *
 * Aturan penyimpanan:
 *   - Aset hasil build, font, dan ikon  -> cache-first  (jarang berubah, namanya berhak)
 *   - Halaman HTML                      -> network-first (data keuangan harus segar)
 *   - Laporan keuangan, PDF, Excel, POST-> tidak pernah disimpan
 */

const VERSI = new URL(self.location).searchParams.get('v') || 'dev';
const CACHE_ASET = `kitaa-aset-${VERSI}`;
const CACHE_HALAMAN = `kitaa-halaman-${VERSI}`;
const HALAMAN_OFFLINE = '/offline';

/** Berkas yang selalu disiapkan lebih dulu supaya halaman offline pasti tampil. */
const KERANGKA = [
    HALAMAN_OFFLINE,
    '/manifest.webmanifest',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
    '/icons/icon-maskable-512.png',
    '/icons/apple-touch-icon.png',
];

/** Awalan jalur yang isinya tidak boleh disimpan sama sekali. */
const JANGAN_SIMPAN = [
    '/laporan',        // seluruh laporan keuangan, termasuk PDF & Excel-nya
    '/beranda/cetak',  // infografis siap cetak
    '/login',
    '/logout',
];

/** Akhiran jalur yang isinya tidak boleh disimpan (dokumen cetak). */
const AKHIRAN_JANGAN_SIMPAN = ['/cetak', '/struk', '/pdf', '/excel', '.pdf', '.xlsx'];

/** True bila permintaan ini harus selalu langsung ke jaringan. */
function dilarangDisimpan(url) {
    return JANGAN_SIMPAN.some((awalan) => url.pathname === awalan || url.pathname.startsWith(awalan + '/'))
        || AKHIRAN_JANGAN_SIMPAN.some((akhiran) => url.pathname.endsWith(akhiran));
}

/** True untuk aset statis yang aman disimpan lama. */
function asetStatis(url) {
    return url.pathname.startsWith('/build/')
        || url.pathname.startsWith('/icons/')
        || url.pathname === '/manifest.webmanifest'
        || url.pathname === '/favicon.ico';
}

self.addEventListener('install', (event) => {
    event.waitUntil(
        (async () => {
            const cache = await caches.open(CACHE_ASET);

            // Disimpan satu per satu supaya satu berkas yang gagal tidak
            // membatalkan seluruh pemasangan.
            await Promise.all(KERANGKA.map((jalur) => cache.add(jalur).catch(() => null)));
            await simpanAsetBuild(cache);

            await self.skipWaiting();
        })()
    );
});

/**
 * Membaca manifest Vite lalu menyimpan seluruh berkas hasil build: JS, CSS,
 * dan font Plus Jakarta Sans yang ikut dibundel. Saat "npm run dev" berjalan
 * manifestnya belum ada, dan itu bukan masalah.
 */
async function simpanAsetBuild(cache) {
    try {
        const jawaban = await fetch('/build/manifest.json', { cache: 'no-cache' });

        if (! jawaban.ok) {
            return;
        }

        const manifest = await jawaban.json();
        const berkas = new Set();

        for (const entri of Object.values(manifest)) {
            if (entri.file) {
                berkas.add('/build/' + entri.file);
            }

            for (const tambahan of [...(entri.css || []), ...(entri.assets || [])]) {
                berkas.add('/build/' + tambahan);
            }
        }

        await Promise.all([...berkas].map((jalur) => cache.add(jalur).catch(() => null)));
    } catch (e) {
        // Tidak ada koneksi atau belum ada hasil build: lewati saja.
    }
}

self.addEventListener('activate', (event) => {
    event.waitUntil(
        (async () => {
            const nama = await caches.keys();

            await Promise.all(
                nama
                    .filter((n) => n.startsWith('kitaa-') && n !== CACHE_ASET && n !== CACHE_HALAMAN)
                    .map((n) => caches.delete(n))
            );

            await self.clients.claim();
        })()
    );
});

self.addEventListener('fetch', (event) => {
    const permintaan = event.request;

    // POST, PUT, DELETE, dan permintaan ke domain lain dibiarkan apa adanya.
    if (permintaan.method !== 'GET') {
        return;
    }

    const url = new URL(permintaan.url);

    if (url.origin !== self.location.origin) {
        return;
    }

    // Halaman yang dibuka pengguna tetap dilayani agar saat tidak ada koneksi
    // muncul halaman /offline, bukan halaman error peramban. Halaman terlarang
    // hanya tidak ikut disimpan.
    if (permintaan.mode === 'navigate') {
        event.respondWith(dahulukanJaringan(permintaan, ! dilarangDisimpan(url)));

        return;
    }

    if (dilarangDisimpan(url)) {
        return;
    }

    if (asetStatis(url)) {
        event.respondWith(dahulukanCache(permintaan));
    }
});

/** Aset statis: pakai salinan tersimpan bila ada, kalau belum ambil & simpan. */
async function dahulukanCache(permintaan) {
    const cache = await caches.open(CACHE_ASET);
    const tersimpan = await cache.match(permintaan);

    if (tersimpan) {
        return tersimpan;
    }

    const jawaban = await fetch(permintaan);

    if (jawaban.ok) {
        cache.put(permintaan, jawaban.clone());
    }

    return jawaban;
}

/**
 * Halaman HTML: selalu coba jaringan lebih dulu supaya angkanya terbaru.
 * Kalau gagal, tampilkan salinan terakhir halaman itu; kalau belum ada,
 * tampilkan halaman /offline.
 */
async function dahulukanJaringan(permintaan, bolehSimpan) {
    const cache = await caches.open(CACHE_HALAMAN);

    try {
        const jawaban = await fetch(permintaan);

        if (bolehSimpan && jawaban.ok) {
            cache.put(permintaan, jawaban.clone());
        }

        return jawaban;
    } catch (e) {
        const tersimpan = bolehSimpan ? await cache.match(permintaan) : null;

        if (tersimpan) {
            return tersimpan;
        }

        const offline = await caches.match(HALAMAN_OFFLINE);

        return offline || new Response(
            '<h1>Tidak ada koneksi</h1>',
            { status: 503, headers: { 'Content-Type': 'text/html; charset=utf-8' } }
        );
    }
}
