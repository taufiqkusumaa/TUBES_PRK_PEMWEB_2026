document.addEventListener('DOMContentLoaded', function () {
    const endpoint = window.budgetConfig.endpoint;
    const filterBulan = document.getElementById('filterBulan');
    const filterTahun = document.getElementById('filterTahun');
    const inputBulan = document.getElementById('inputBulan');
    const inputTahun = document.getElementById('inputTahun');

    const totalAnggaran = document.getElementById('totalAnggaran');
    const totalTerpakai = document.getElementById('totalTerpakai');
    const sisaAnggaran = document.getElementById('sisaAnggaran');
    const persenPenggunaan = document.getElementById('persenPenggunaan');

    const daftarAnggaran = document.getElementById('daftarAnggaran');
    const progressContainer = document.getElementById('progressContainer');

    function formatRupiah(angka) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0
        }).format(Number(angka) || 0);
    }

    async function loadAnggaran() {
        const bulan = filterBulan.value;
        const tahun = filterTahun.value;

        inputBulan.value = bulan;
        inputTahun.value = tahun;

        daftarAnggaran.innerHTML = `
            <tr>
                <td colspan="6" class="table-empty">Memuat data anggaran...</td>
            </tr>
        `;

        try {
            const response = await fetch(
                `${endpoint}?aksi=daftar&bulan=${bulan}&tahun=${tahun}`
            );

            if (!response.ok) {
                throw new Error('Gagal mengambil data anggaran.');
            }

            const data = await response.json();

            if (!Array.isArray(data)) {
                throw new Error('Format data anggaran tidak valid.');
            }

            renderAnggaran(data);
        } catch (error) {
            daftarAnggaran.innerHTML = `
                <tr>
                    <td colspan="6" class="table-empty">
                        ${error.message}
                    </td>
                </tr>
            `;

            progressContainer.innerHTML = `
                <div class="empty-state">
                    <h4>Data tidak dapat dimuat</h4>
                    <p>Periksa koneksi dan coba muat ulang halaman.</p>
                </div>
            `;
        }
    }

    function renderAnggaran(data) {
        let total = 0;
        let terpakai = 0;

        data.forEach(item => {
            total += Number(item.jumlah_anggaran) || 0;
            terpakai += Number(item.realisasi) || 0;
        });

        const sisa = total - terpakai;
        const persen = total > 0 ? (terpakai / total) * 100 : 0;

        totalAnggaran.textContent = formatRupiah(total);
        totalTerpakai.textContent = formatRupiah(terpakai);
        sisaAnggaran.textContent = formatRupiah(sisa);
        persenPenggunaan.textContent = `${persen.toFixed(1)}%`;

        if (data.length === 0) {
            daftarAnggaran.innerHTML = `
                <tr>
                    <td colspan="6" class="table-empty">
                        Belum ada anggaran untuk periode ini.
                    </td>
                </tr>
            `;

            progressContainer.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon">▤</div>
                    <h4>Belum Ada Anggaran</h4>
                    <p>Tambahkan anggaran untuk melihat progres penggunaannya.</p>
                </div>
            `;
            return;
        }

        daftarAnggaran.innerHTML = data.map(item => {
            const anggaran = Number(item.jumlah_anggaran) || 0;
            const realisasi = Number(item.realisasi) || 0;
            const sisaKategori = Number(item.sisa_anggaran);
            const persenKategori = anggaran > 0
                ? (realisasi / anggaran) * 100
                : 0;

            return `
                <tr>
                    <td><strong>${escapeHTML(item.nama_kategori)}</strong></td>
                    <td>${formatRupiah(anggaran)}</td>
                    <td>${formatRupiah(realisasi)}</td>
                    <td>${formatRupiah(sisaKategori)}</td>
                    <td>${Math.max(0, persenKategori).toFixed(1)}%</td>
                    <td>
                        <form action="${endpoint}" method="POST"
                            onsubmit="return confirm('Yakin ingin menghapus anggaran ini?')">
                            <input type="hidden" name="aksi" value="hapus">
                            <input type="hidden" name="id" value="${Number(item.id_anggaran)}">
                            <button type="submit" class="btn-delete">Hapus</button>
                        </form>
                    </td>
                </tr>
            `;
        }).join('');

        progressContainer.innerHTML = data.map(item => {
            const anggaran = Number(item.jumlah_anggaran) || 0;
            const realisasi = Number(item.realisasi) || 0;
            const persenKategori = anggaran > 0
                ? (realisasi / anggaran) * 100
                : 0;

            const lebar = Math.min(persenKategori, 100);
            let status = '';

            if (persenKategori >= 100) {
                status = 'danger';
            } else if (persenKategori >= 75) {
                status = 'warning';
            }

            return `
                <div class="progress-item">
                    <div class="progress-info">
                        <strong>${escapeHTML(item.nama_kategori)}</strong>
                        <span>${persenKategori.toFixed(1)}%</span>
                    </div>
                    <div class="progress-track">
                        <div class="progress-fill ${status}" style="width: ${lebar}%"></div>
                    </div>
                </div>
            `;
        }).join('');
    }

    function escapeHTML(value) {
        return String(value ?? '').replace(/[&<>"']/g, function (char) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[char];
        });
    }

    filterBulan.addEventListener('change', loadAnggaran);
    filterTahun.addEventListener('change', loadAnggaran);

    loadAnggaran();
});