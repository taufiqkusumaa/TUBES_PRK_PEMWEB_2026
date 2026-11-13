const endpoint = '../backend/laporan/laporan_process.php';

const tanggalDari = document.getElementById('tanggalDari');
const tanggalSampai = document.getElementById('tanggalSampai');
const filterButton = document.getElementById('filterButton');

let reportChart;

const formatRupiah = (angka) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
    }).format(Number(angka) || 0);
};

const escapeHTML = (value) => {
    return String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    })[char]);
};

const getTanggalAwal = () => {
    const sekarang = new Date();
    return `${sekarang.getFullYear()}-${String(sekarang.getMonth() + 1).padStart(2, '0')}-01`;
};

const getTanggalAkhir = () => {
    const sekarang = new Date();
    const akhir = new Date(sekarang.getFullYear(), sekarang.getMonth() + 1, 0);
    return `${akhir.getFullYear()}-${String(akhir.getMonth() + 1).padStart(2, '0')}-${String(akhir.getDate()).padStart(2, '0')}`;
};

async function ambilData(aksi, dari, sampai) {
    const url = `${endpoint}?aksi=${aksi}&dari=${dari}&sampai=${sampai}`;
    const response = await fetch(url);
    const data = await response.json();

    if (!response.ok) {
        throw new Error(data.error || 'Gagal mengambil data laporan.');
    }

    return data;
}

function tampilkanRingkasan(data) {
    let pemasukan = 0;
    let pengeluaran = 0;

    data.forEach(item => {
        if (item.jenis === 'pemasukan') {
            pemasukan = Number(item.total) || 0;
        }

        if (item.jenis === 'pengeluaran') {
            pengeluaran = Number(item.total) || 0;
        }
    });

    document.getElementById('totalPemasukan').textContent = formatRupiah(pemasukan);
    document.getElementById('totalPengeluaran').textContent = formatRupiah(pengeluaran);
    document.getElementById('saldoLaporan').textContent = formatRupiah(pemasukan - pengeluaran);

    tampilkanGrafik(pemasukan, pengeluaran);
}

function tampilkanGrafik(pemasukan, pengeluaran) {
    const context = document.getElementById('reportChart');

    if (reportChart) {
        reportChart.destroy();
    }

    reportChart = new Chart(context, {
        type: 'bar',
        data: {
            labels: ['Pemasukan', 'Pengeluaran'],
            datasets: [{
                label: 'Jumlah Keuangan',
                data: [pemasukan, pengeluaran],
                backgroundColor: ['#79a982', '#e58c8c'],
                borderRadius: 8,
                maxBarThickness: 75
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: context => formatRupiah(context.raw)
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: value => formatRupiah(value)
                    }
                }
            }
        }
    });
}

function tampilkanKategori(data) {
    const container = document.getElementById('categoryList');

    if (!data.length) {
        container.innerHTML = '<p class="empty-message">Belum ada data kategori pada periode ini.</p>';
        return;
    }

    container.innerHTML = data.map(item => `
        <div class="category-item">
            <div class="category-info">
                <strong>${escapeHTML(item.nama_kategori)}</strong>
                <small>${item.jenis === 'pemasukan' ? 'Pemasukan' : 'Pengeluaran'}</small>
            </div>
            <strong>${formatRupiah(item.total)}</strong>
        </div>
    `).join('');
}

function tampilkanTransaksi(data) {
    const container = document.getElementById('transactionList');

    if (!data.length) {
        container.innerHTML = '<tr><td colspan="5" class="empty-message">Belum ada transaksi pada periode ini.</td></tr>';
        return;
    }

    container.innerHTML = data.map(item => {
        const jenis = item.jenis === 'pemasukan' ? 'income' : 'expense';
        const label = item.jenis === 'pemasukan' ? 'Pemasukan' : 'Pengeluaran';

        return `
            <tr>
                <td>${escapeHTML(item.tanggal)}</td>
                <td>${escapeHTML(item.keterangan || '-')}</td>
                <td>${escapeHTML(item.nama_kategori)}</td>
                <td><span class="transaction-type ${jenis}">${label}</span></td>
                <td>${formatRupiah(item.jumlah)}</td>
            </tr>
        `;
    }).join('');
}

async function muatLaporan() {
    const dari = tanggalDari.value;
    const sampai = tanggalSampai.value;

    if (!dari || !sampai || dari > sampai) {
        alert('Silakan pilih rentang tanggal yang valid.');
        return;
    }

    filterButton.disabled = true;
    filterButton.textContent = 'Memuat...';

    try {
        const [total, kategori, transaksi] = await Promise.all([
            ambilData('total', dari, sampai),
            ambilData('kategori', dari, sampai),
            ambilData('transaksi', dari, sampai)
        ]);

        tampilkanRingkasan(total);
        tampilkanKategori(kategori);
        tampilkanTransaksi(transaksi);
    } catch (error) {
        alert(error.message);
    } finally {
        filterButton.disabled = false;
        filterButton.textContent = 'Tampilkan';
    }
}

tanggalDari.value = getTanggalAwal();
tanggalSampai.value = getTanggalAkhir();

filterButton.addEventListener('click', muatLaporan);

muatLaporan();