document.addEventListener('DOMContentLoaded', function () {

    const NAMA_BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
                        'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    function rupiah(n) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0
        }).format(Number(n) || 0);
    }

    function ringkas(v) {
        if (v >= 1000000) {
            return 'Rp ' + (v / 1000000).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' jt';
        }
        if (v >= 1000) return 'Rp ' + (v / 1000) + ' rb';
        return 'Rp ' + v;
    }

    function setText(id, value) {
        const el = document.getElementById(id);
        if (el) el.textContent = value;
    }

    async function getJson(url) {
        const res = await fetch(url, { headers: { Accept: 'application/json' } });

        if (res.status === 401 || res.redirected) {
            window.location.href = '../login.php';
            throw new Error('Sesi berakhir.');
        }

        const data = await res.json();

        if (!res.ok || data.error) {
            throw new Error(data.error || 'Terjadi kesalahan.');
        }

        return data;
    }

    async function loadRingkasan() {
        const d = await getJson('../backend/dashboard_total.php');

        setText('cardPemasukan', rupiah(d.total_pemasukan));
        setText('cardPengeluaran', rupiah(d.total_pengeluaran));
        setText('cardSaldo', rupiah(d.saldo));
        setText('cardTabungan', rupiah(d.total_tabungan));

        setText('infoPemasukan', rupiah(d.total_pemasukan));
        setText('infoPengeluaran', rupiah(d.total_pengeluaran));
        setText('infoSetoran', rupiah(d.total_setoran));
        setText('infoSaldo', rupiah(d.saldo));
    }

    function showChartMessage(message) {
        const canvas = document.getElementById('financeChart');
        const status = document.getElementById('financeChartMessage');

        if (canvas) canvas.hidden = true;
        if (status) {
            status.textContent = message;
            status.hidden = false;
        }
    }

    function enamBulanTerakhir() {
        const now = new Date();
        const hasil = [];

        for (let i = 5; i >= 0; i--) {
            const d = new Date(now.getFullYear(), now.getMonth() - i, 1);
            hasil.push({ tahun: d.getFullYear(), bulan: d.getMonth() + 1 });
        }

        return hasil;
    }

    async function loadGrafik() {
        const canvas = document.getElementById('financeChart');
        if (!canvas) return;

        if (typeof Chart === 'undefined') {
            showChartMessage('Grafik tidak tersedia. Muat ulang halaman untuk mencoba kembali.');
            return;
        }

        const rows = await getJson('../backend/grafik.php');
        const peta = {};
        rows.forEach(r => { peta[r.tahun + '-' + Number(r.bulan)] = r; });

        const bulan = enamBulanTerakhir();
        const labels = bulan.map(b => NAMA_BULAN[b.bulan - 1]);
        const pemasukan = bulan.map(b => Number((peta[b.tahun + '-' + b.bulan] || {}).total_pemasukan) || 0);
        const pengeluaran = bulan.map(b => Number((peta[b.tahun + '-' + b.bulan] || {}).total_pengeluaran) || 0);
        const hasActivity = pemasukan.some(value => value !== 0) || pengeluaran.some(value => value !== 0);

        if (!hasActivity) {
            showChartMessage('Belum ada pemasukan atau pengeluaran yang tercatat selama enam bulan terakhir.');
            return;
        }

        const status = document.getElementById('financeChartMessage');
        if (status) status.hidden = true;
        canvas.hidden = false;

        const incomeColor = getComputedStyle(document.documentElement).getPropertyValue('--p-accent').trim() || '#176b50';
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Pemasukan',
                        data: pemasukan,
                        backgroundColor: incomeColor,
                        borderRadius: 7,
                        barThickness: 18,
                        maxBarThickness: 24
                    },
                    {
                        label: 'Pengeluaran',
                        data: pengeluaran,
                        backgroundColor: '#d9827c',
                        borderRadius: 7,
                        barThickness: 18,
                        maxBarThickness: 24
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { intersect: false, mode: 'index' },
                animation: reducedMotion ? false : { duration: 650, easing: 'easeOutQuart' },
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'end',
                        labels: {
                            color: '#5d6b64',
                            usePointStyle: true,
                            pointStyle: 'circle',
                            boxWidth: 8,
                            boxHeight: 8,
                            padding: 14,
                            font: { family: 'Geist, system-ui, sans-serif', size: 11 }
                        }
                    },
                    tooltip: {
                        backgroundColor: '#172a22',
                        titleColor: '#ffffff',
                        bodyColor: '#eaf2ed',
                        cornerRadius: 10,
                        padding: 12,
                        callbacks: {
                            label: ctx => ctx.dataset.label + ': ' + rupiah(ctx.raw)
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        border: { display: false },
                        ticks: {
                            color: '#53655b',
                            maxTicksLimit: 5,
                            padding: 8,
                            callback: v => ringkas(v),
                            font: { family: 'Geist, system-ui, sans-serif', size: 10 }
                        },
                        grid: { color: 'rgba(23, 42, 34, 0.08)', drawTicks: false }
                    },
                    x: {
                        border: { display: false },
                        grid: { display: false },
                        ticks: {
                            color: '#53655b',
                            font: { family: 'Geist, system-ui, sans-serif', size: 10 }
                        }
                    }
                }
            }
        });
    }

    loadRingkasan().catch(err => {
        console.error(err);
        ['cardPemasukan', 'cardPengeluaran', 'cardSaldo', 'cardTabungan',
            'infoPemasukan', 'infoPengeluaran', 'infoSetoran', 'infoSaldo']
            .forEach(id => setText(id, '—'));

        const error = document.getElementById('dashboardError');
        if (error) {
            error.textContent = 'Ringkasan keuangan tidak dapat dimuat. Muat ulang halaman untuk mencoba kembali.';
            error.hidden = false;
        }
    });

    loadGrafik().catch(err => {
        console.error(err);
        showChartMessage('Grafik tidak dapat dimuat. Muat ulang halaman untuk mencoba kembali.');
    });
});
