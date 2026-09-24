{{--
    Halaman yang tampil saat tidak ada koneksi. Sengaja berdiri sendiri:
    gayanya ditulis langsung di dalam berkas ini dan tidak memuat aset apa pun
    dari luar, supaya tetap tampil rapi walau berkas CSS belum sempat tersimpan.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#5B2A6E">
    <title>Tidak ada koneksi — {{ config('app.name') }}</title>
    <link rel="icon" href="/icons/icon-192.png" type="image/png">
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: #EEF6FA;
            color: #1D2B36;
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, 'Segoe UI', sans-serif;
            line-height: 1.6;
        }
        .kartu {
            width: 100%;
            max-width: 26rem;
            background: #fff;
            border: 1px solid rgba(91, 42, 110, .12);
            border-radius: 20px;
            padding: 32px 24px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(29, 43, 54, .08);
        }
        img { width: 84px; height: 84px; }
        h1 { margin: 16px 0 8px; font-size: 1.25rem; font-weight: 800; color: #5B2A6E; }
        p { margin: 0 0 8px; font-size: .9rem; color: rgba(29, 43, 54, .65); }
        ul { margin: 16px 0 0; padding-left: 20px; text-align: left; font-size: .85rem; color: rgba(29, 43, 54, .65); }
        li { margin-bottom: 4px; }
        button {
            margin-top: 24px;
            width: 100%;
            min-height: 44px;
            border: 0;
            border-radius: 12px;
            background: #5B2A6E;
            color: #fff;
            font-family: inherit;
            font-size: .9rem;
            font-weight: 700;
            cursor: pointer;
        }
        button:hover { background: #4a2259; }
        button:focus-visible { outline: 3px solid #12A383; outline-offset: 2px; }
        @media (prefers-reduced-motion: no-preference) {
            button { transition: background-color .15s ease; }
        }
    </style>
</head>
<body>
    <main class="kartu">
        <img src="/icons/icon-192.png" alt="{{ config('app.name') }}">

        <h1>Tidak ada koneksi</h1>

        <p>Halaman ini belum bisa dibuka karena HP atau komputer sedang tidak tersambung ke internet.</p>

        <ul>
            <li>Periksa data seluler atau Wi-Fi.</li>
            <li>Halaman yang sudah pernah dibuka biasanya masih bisa dilihat.</li>
            <li>Laporan keuangan selalu butuh koneksi agar angkanya benar.</li>
        </ul>

        <button type="button" onclick="location.reload()">Coba lagi</button>
    </main>
</body>
</html>
