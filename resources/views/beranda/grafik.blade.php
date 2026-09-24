{{-- Skrip grafik beranda. Chart.js dimuat dari bundel Vite (window.Chart). --}}
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const Chart = window.Chart;

            if (!Chart) {
                return;
            }

            const warna = window.warnaKitaa;
            const sumbuRupiah = {
                ticks: { callback: (v) => window.rupiahSingkat(v) },
                grid: { color: warna.frost },
                border: { display: false },
            };

            const dasar = {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { intersect: false, mode: 'index' },
                plugins: {
                    legend: { labels: { boxWidth: 12, usePointStyle: true, padding: 14 } },
                    tooltip: {
                        backgroundColor: warna.ink,
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: (ctx) => {
                                const nilai = ctx.parsed.y ?? ctx.parsed;
                                return ` ${ctx.dataset.label ?? ctx.label}: ${window.rupiah(nilai)}`;
                            },
                        },
                    },
                },
            };

            // Tren setoran & laba enam bulan terakhir.
            const elTren = document.getElementById('grafikTren');
            const tren = @json($tren->values());

            if (elTren && tren.length) {
                new Chart(elTren, {
                    type: 'line',
                    data: {
                        labels: tren.map((t) => t.bulan),
                        datasets: [
                            {
                                label: 'Setoran',
                                data: tren.map((t) => t.setoran),
                                borderColor: warna.berry,
                                backgroundColor: warna.berry + '1f',
                                fill: true,
                                tension: 0.35,
                                borderWidth: 2.5,
                                pointRadius: 3,
                                pointBackgroundColor: warna.berry,
                            },
                            {
                                label: 'Laba bersih',
                                data: tren.map((t) => t.laba_bersih),
                                borderColor: warna.mint,
                                backgroundColor: warna.mint + '1f',
                                fill: true,
                                tension: 0.35,
                                borderWidth: 2.5,
                                pointRadius: 3,
                                pointBackgroundColor: warna.mint,
                            },
                        ],
                    },
                    options: {
                        ...dasar,
                        scales: { y: sumbuRupiah, x: { grid: { display: false }, border: { display: false } } },
                    },
                });
            }

            // Komposisi penjualan per produk.
            const elDonat = document.getElementById('grafikDonat');
            const produk = @json($per_produk->take(6)->values());

            if (elDonat && produk.length) {
                new Chart(elDonat, {
                    type: 'doughnut',
                    data: {
                        labels: produk.map((p) => p.nama),
                        datasets: [{
                            data: produk.map((p) => p.setoran),
                            backgroundColor: warna.seri,
                            borderWidth: 3,
                            borderColor: '#ffffff',
                        }],
                    },
                    options: {
                        ...dasar,
                        cutout: '58%',
                        plugins: {
                            ...dasar.plugins,
                            legend: { position: 'bottom', labels: { boxWidth: 12, usePointStyle: true, padding: 12 } },
                        },
                    },
                });
            }

            // Lima toko dengan setoran terbesar.
            const elToko = document.getElementById('grafikToko');
            const toko = @json($toko_teratas->values());

            if (elToko && toko.length) {
                new Chart(elToko, {
                    type: 'bar',
                    data: {
                        labels: toko.map((t) => t.nama),
                        datasets: [{
                            label: 'Setoran',
                            data: toko.map((t) => t.setoran),
                            backgroundColor: warna.berry,
                            hoverBackgroundColor: warna.mint,
                            borderRadius: 8,
                            maxBarThickness: 46,
                        }],
                    },
                    options: {
                        ...dasar,
                        plugins: { ...dasar.plugins, legend: { display: false } },
                        scales: { y: sumbuRupiah, x: { grid: { display: false }, border: { display: false } } },
                    },
                });
            }
        });
    </script>
@endpush
