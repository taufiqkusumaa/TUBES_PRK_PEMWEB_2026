<?php
$pageTitle = 'Dashboard Operasional & Cetak Dokumen';
require_once __DIR__ . '/layouts/header.php';
require_once __DIR__ . '/layouts/sidebar.php';
?>

<div class="no-print">
    <!-- Page Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom" style="border-color: var(--border-color) !important;">
        <div>
            <h5 class="fw-bold mb-1" style="color: var(--text-primary); letter-spacing: -0.01em;">Ringkasan Operasional Bengkel</h5>
            <div style="color: var(--text-secondary); font-size: 13px;">Pemantauan antrean unit aktif, penyelesaian work order, dan penerbitan dokumen resmi.</div>
        </div>
        <div class="d-flex gap-2 mt-2 mt-sm-0">
            <button type="button" class="btn-surface" id="btnRefreshStat">Muat Ulang</button>
            <a href="index.php?page=transaksi" class="btn-royal text-decoration-none">+ Order Servis Baru</a>
        </div>
    </div>

    <!-- 4 Kartu Metrik Bersih Tanpa Ikon Berlebihan -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="metric-card h-100">
                <div class="metric-label">Antrean Masuk</div>
                <div class="metric-value font-mono" id="statAntre">0</div>
                <div class="metric-meta">Menunggu alokasi mekanik</div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="metric-card h-100">
                <div class="metric-label">Sedang Dikerjakan</div>
                <div class="metric-value font-mono" id="statDikerjakan">0</div>
                <div class="metric-meta">Unit aktif di stall servis</div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="metric-card h-100">
                <div class="metric-label">Selesai / Siap Ambil</div>
                <div class="metric-value font-mono" id="statSelesai">0</div>
                <div class="metric-meta">Siap terbit nota & kuitansi</div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="metric-card h-100">
                <div class="metric-label">Total Nilai Transaksi</div>
                <div class="metric-value font-mono" id="statPendapatan">Rp 0</div>
                <div class="metric-meta">Akumulasi jasa & suku cadang</div>
            </div>
        </div>
    </div>

    <!-- Tabel Daftar Work Order & Cetak Dokumen -->
    <div class="surface-card overflow-hidden">
        <div class="p-3 d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom" style="border-color: var(--border-color) !important;">
            <div class="fw-semibold" style="font-size: 14px; color: var(--text-primary);">Daftar Work Order & Pencetakan Dokumen</div>
            <div class="d-flex flex-wrap gap-2">
                <select class="input-enterprise" id="filterStatus" style="width: 155px;">
                    <option value="">Semua Status</option>
                    <option value="Pending">Pending</option>
                    <option value="Antre">Antre</option>
                    <option value="Dikerjakan">Dikerjakan</option>
                    <option value="Siap Diambil">Siap Diambil</option>
                    <option value="Selesai">Selesai</option>
                    <option value="Dibatalkan">Dibatalkan</option>
                </select>
                <input type="text" class="input-enterprise" id="searchTransaksi" placeholder="Cari No. Order / Nopol / Nama..." style="width: 240px;">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-enterprise">
                <thead>
                    <tr>
                        <th>No. PKB / Order</th>
                        <th>Waktu Masuk</th>
                        <th>Pemilik Kendaraan</th>
                        <th>No. Polisi & Unit</th>
                        <th>Paket Layanan</th>
                        <th>Mekanik PJ</th>
                        <th>Status</th>
                        <th class="text-end">Total Biaya</th>
                        <th class="text-end">Dokumen Resmi</th>
                    </tr>
                </thead>
                <tbody id="tableDashboardBody">
                    <tr>
                        <td colspan="9" class="text-center py-4">Memuat data operasional...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Preview Dokumen -->
<div class="modal fade no-print" id="modalPreviewCetak" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content" style="background-color: var(--surface); border-color: var(--border-color);">
            <div class="modal-header py-2 px-3" style="border-color: var(--border-color);">
                <span class="fw-semibold" id="modalPreviewTitle" style="font-size: 13.5px; color: var(--text-primary);">Pratinjau Dokumen</span>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body p-4" style="background-color: #E2E8F0;">
                <div id="modalPrintPreviewContent"></div>
            </div>
            <div class="modal-footer py-2 px-3" style="border-color: var(--border-color);">
                <button type="button" class="btn-surface" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn-royal" id="btnTriggerPrint">Cetak Dokumen (A4)</button>
            </div>
        </div>
    </div>
</div>

<!-- Area Cetak Fisik -->
<div id="printDocumentContainer" class="print-only"></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const fallbackTransactions = [
        {
            id_transaksi: 'PKB-202610-001',
            tanggal: '30/10/2026 08:30',
            estimasi_selesai: '30/10/2026 10:15',
            nama_pelanggan: 'Budi Santoso',
            telepon: '0812-7890-1122',
            alamat: 'Jl. ZA Pagar Alam No. 42, Kedaton, Bandar Lampung',
            no_polisi: 'BE 1842 YQ',
            kendaraan: 'Honda Vario 160 CBS (2024)',
            no_rangka: 'MH1KF0114RK089211',
            kilometer: '12.450 KM',
            keluhan: 'Tarikan awal bergetar (gredek) pada putaran rendah dan penggantian oli berkala.',
            nama_layanan: 'Tune Up Injeksi & Servis CVT Lengkap',
            kode_layanan: 'SRV-R2-01',
            biaya_layanan: 125000,
            sparepart: [
                { kode: '08232-2MB-K0LN0', nama: 'Oli Mesin MPX2 0.8L AHM', qty: 1, satuan: 'Btl', harga: 65000 },
                { kode: '22123-K1Z-J10', nama: 'Weight Set Clutch / Roller Set', qty: 1, satuan: 'Set', harga: 95000 }
            ],
            catatan_mekanik: 'Mangkok ganda & kampas dibersihkan, roller set diganti baru, reset ECM & kalibrasi TP sensor normal.',
            nama_mekanik: 'Hendro Wibowo',
            stall: 'Pit 02',
            status: 'Siap Diambil'
        },
        {
            id_transaksi: 'PKB-202610-002',
            tanggal: '30/10/2026 09:15',
            estimasi_selesai: '30/10/2026 12:00',
            nama_pelanggan: 'Arief Pratama',
            telepon: '0813-6654-9980',
            alamat: 'Jl. Teuku Umar No. 18, Tanjung Karang, Bandar Lampung',
            no_polisi: 'BE 2045 AA',
            kendaraan: 'Toyota Avanza 1.3 G MT (2022)',
            no_rangka: 'MHKM5EA3JNK014820',
            kilometer: '45.820 KM',
            keluhan: 'RPM idle tidak stabil saat AC menyala dan tarikan terasa berat.',
            nama_layanan: 'Tune Up Mesin & Carbon Gurah',
            kode_layanan: 'SRV-R4-03',
            biaya_layanan: 350000,
            sparepart: [
                { kode: '90919-01275', nama: 'Spark Plug Iridium SC16HR11', qty: 4, satuan: 'Pcs', harga: 90000 },
                { kode: '17801-BZ100', nama: 'Element Air Cleaner / Filter Udara', qty: 1, satuan: 'Pcs', harga: 110000 }
            ],
            catatan_mekanik: 'Throttle body & ISC telah dibersihkan, 4 busi iridium diganti baru, scanning OBD-II bebas fault code.',
            nama_mekanik: 'Rizky Ramadhan',
            stall: 'Stall R4-A',
            status: 'Dikerjakan'
        },
        {
            id_transaksi: 'PKB-202610-003',
            tanggal: '30/10/2026 10:05',
            estimasi_selesai: '30/10/2026 11:30',
            nama_pelanggan: 'Siti Rahmawati',
            telepon: '0857-8812-3341',
            alamat: 'Jl. Pulau Sebesi, Sukarame, Bandar Lampung',
            no_polisi: 'BE 4510 CX',
            kendaraan: 'Yamaha NMAX 155 Connected (2023)',
            no_rangka: 'MH3SG5620PK112098',
            kilometer: '18.120 KM',
            keluhan: 'Rem belakang berdecit dan ganti oli transmisi gardan.',
            nama_layanan: 'Servis Pengereman & Berkala Ringan',
            kode_layanan: 'SRV-R2-04',
            biaya_layanan: 75000,
            sparepart: [
                { kode: '3C1-F5805-10', nama: 'Brake Pad Kit Rear / Kampas Belakang', qty: 1, satuan: 'Set', harga: 60000 },
                { kode: '90793-AJ834', nama: 'Yamalube Gear Motor Oil 150ml', qty: 1, satuan: 'Btl', harga: 25000 }
            ],
            catatan_mekanik: 'Menunggu antrean Pit 01.',
            nama_mekanik: 'Hendro Wibowo',
            stall: 'Pit 01',
            status: 'Antre'
        }
    ];

    let allTransactions = [];
    let loadError = '';
    const tableBody = document.getElementById('tableDashboardBody');
    const filterStatus = document.getElementById('filterStatus');
    const searchInput = document.getElementById('searchTransaksi');
    const modalPreview = new bootstrap.Modal(document.getElementById('modalPreviewCetak'));
    const previewContainer = document.getElementById('modalPrintPreviewContent');
    const printContainer = document.getElementById('printDocumentContainer');

    function formatRupiah(angka) {
        return 'Rp ' + Number(angka || 0).toLocaleString('id-ID');
    }

    function hitungSubtotalPart(items) {
        if (!Array.isArray(items)) return 0;
        return items.reduce((acc, sp) => acc + (Number(sp.qty || 1) * Number(sp.harga || 0)), 0);
    }

    function hitungTotal(row) {
        if (row.total_biaya !== undefined) return Number(row.total_biaya);
        return Number(row.biaya_layanan || 0) + hitungSubtotalPart(row.sparepart);
    }

    function renderStatusBadge(status) {
        const s = (status || '').toLowerCase();
        if (s === 'pending' || s === 'antre' || s === 'dibatalkan') {
            return `<span class="status-badge status-pending"><span class="dot"></span>${status}</span>`;
        } else if (s === 'dikerjakan' || s === 'process') {
            return `<span class="status-badge status-process"><span class="dot"></span>${status}</span>`;
        } else {
            return `<span class="status-badge status-success"><span class="dot"></span>${status}</span>`;
        }
    }

    function formatTanggal(value) {
        const date = new Date(String(value || '').replace(' ', 'T'));
        return Number.isNaN(date.getTime()) ? '-' : date.toLocaleString('id-ID');
    }

    async function loadDashboardData() {
        try {
            const res = await fetch('api.php?endpoint=transaksi', { headers: { 'Accept': 'application/json' } });
            if (!res.ok) throw new Error(`Permintaan gagal (HTTP ${res.status}).`);
            const json = await res.json();
            if (!json.status || !Array.isArray(json.data)) {
                throw new Error(json.message || 'Data transaksi tidak valid.');
            }
            allTransactions = json.data.map(row => ({
                id_transaksi: String(row.kode_transaksi || row.id),
                tanggal: formatTanggal(row.tanggal_pesan),
                nama_pelanggan: row.nama_customer || '-',
                telepon: row.telp_customer || '',
                alamat: '',
                no_polisi: row.no_polisi || '-',
                kendaraan: `${row.nama_model || '-'}${row.tahun_pembuatan ? ` (${row.tahun_pembuatan})` : ''}`,
                no_rangka: '',
                kilometer: '',
                keluhan: row.keluhan_mesin || '',
                nama_layanan: row.nama_paket || '-',
                kode_layanan: `SRV-${row.layanan_id || ''}`,
                biaya_layanan: Number(row.tarif_dasar) || 0,
                sparepart: (row.sparepart || []).map(part => ({
                    nama: part.nama || '-',
                    qty: Number(part.qty) || 0,
                    satuan: 'Pcs',
                    harga: Number(part.harga) || 0,
                })),
                catatan_mekanik: row.catatan_admin || '',
                nama_mekanik: row.nama_teknisi || '',
                stall: '',
                status: row.status_servis || 'Pending',
                total_biaya: Number(row.total_biaya) || 0,
            }));
            loadError = '';
        } catch (e) {
            allTransactions = [];
            loadError = e.message || 'Gagal memuat data transaksi.';
        }
        updateStatCards(allTransactions);
        renderTable();
    }

    function updateStatCards(data) {
        let antre = 0, proses = 0, selesai = 0, total = 0;
        data.forEach(item => {
            const st = (item.status || '').toLowerCase();
            if (st === 'dibatalkan') return;
            if (st === 'pending' || st === 'antre') antre++;
            else if (st === 'dikerjakan' || st === 'process') proses++;
            else if (st === 'siap diambil' || st === 'selesai') selesai++;
            total += hitungTotal(item);
        });

        document.getElementById('statAntre').textContent = antre;
        document.getElementById('statDikerjakan').textContent = proses;
        document.getElementById('statSelesai').textContent = selesai;
        document.getElementById('statPendapatan').textContent = formatRupiah(total);
    }

    function renderTable() {
        const statusVal = filterStatus.value.toLowerCase();
        const kw = searchInput.value.toLowerCase().trim();

        const filtered = allTransactions.filter(item => {
            const mStatus = !statusVal || (item.status || '').toLowerCase() === statusVal;
            const mKw = !kw ||
                (item.id_transaksi || '').toLowerCase().includes(kw) ||
                (item.no_polisi || '').toLowerCase().includes(kw) ||
                (item.nama_pelanggan || '').toLowerCase().includes(kw);
            return mStatus && mKw;
        });

        if (filtered.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="9" class="text-center py-4">${loadError || 'Tidak ada data work order yang cocok.'}</td></tr>`;
            return;
        }

        tableBody.innerHTML = filtered.map(row => {
            const total = hitungTotal(row);
            return `
                <tr>
                    <td class="font-mono fw-semibold">${row.id_transaksi}</td>
                    <td style="color: var(--text-secondary); white-space: nowrap;">${row.tanggal}</td>
                    <td>
                        <div class="fw-semibold">${row.nama_pelanggan}</div>
                        <div style="color: var(--text-muted); font-size: 11.5px;">${row.telepon || '-'}</div>
                    </td>
                    <td>
                        <span class="plate-badge mb-1">${row.no_polisi}</span>
                        <div style="color: var(--text-secondary); font-size: 12px;">${row.kendaraan}</div>
                    </td>
                    <td>${row.nama_layanan}</td>
                    <td style="white-space: nowrap;">${row.nama_mekanik || '-'}</td>
                    <td>${renderStatusBadge(row.status)}</td>
                    <td class="text-end font-mono fw-semibold" style="white-space: nowrap;">${formatRupiah(total)}</td>
                    <td class="text-end" style="white-space: nowrap;">
                        <button type="button" class="btn-surface py-1 px-2 me-1 btn-cetak-nota" data-id="${row.id_transaksi}">Nota PKB</button>
                        <button type="button" class="btn-royal py-1 px-2 btn-cetak-kuitansi" data-id="${row.id_transaksi}" ${row.status === 'Selesai' ? '' : 'disabled title="Kuitansi tersedia setelah servis selesai."'}>Kuitansi</button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    // DOKUMEN 1: NOTA PENGAMBILAN & PERINTAH KERJA BENGKEL (DEALER STANDARD)
    function buildDealerNotaHTML(d) {
        const parts = Array.isArray(d.sparepart) ? d.sparepart : [];
        const partRows = parts.length > 0
            ? parts.map((sp, i) => `
                <tr>
                    <td class="text-center font-mono">${i + 1}</td>
                    <td class="font-mono">${sp.kode || 'PART-' + (i + 1)}</td>
                    <td>${sp.nama}</td>
                    <td class="text-center">${sp.qty} ${sp.satuan || 'Pcs'}</td>
                    <td class="text-center">Diganti Baru</td>
                </tr>
            `).join('')
            : `<tr><td colspan="5" class="text-center">Tidak ada penggantian suku cadang</td></tr>`;

        return `
            <div class="dealer-doc">
                <div class="dealer-header">
                    <div>
                        <div class="dealer-company-name">PT BENGKEL SERVIS OTOMOTIF NUSANTARA</div>
                        <div>Authorized Service Center & Workshop Division — Cabang Bandar Lampung</div>
                        <div>Jl. Prof. Dr. Ir. Sumantri Brojonegoro No. 1, Gedong Meneng, Bandar Lampung 35145</div>
                        <div>Telp. (0721) 701609 | Layanan Pelanggan: cs@bengkelservis.co.id</div>
                    </div>
                    <div class="text-end">
                        <div class="dealer-doc-title">NOTA PENGAMBILAN & BUKTI SERAH TERIMA</div>
                        <div class="font-mono mt-1"><strong>No. PKB : ${d.id_transaksi}</strong></div>
                        <div>Tgl. Masuk : ${d.tanggal}</div>
                    </div>
                </div>

                <div class="dealer-meta-grid">
                    <table class="dealer-meta-table">
                        <tr><td class="label">Nama Pelanggan</td><td>: <strong>${d.nama_pelanggan}</strong></td></tr>
                        <tr><td class="label">No. Telepon</td><td>: ${d.telepon || '-'}</td></tr>
                        <tr><td class="label">Alamat</td><td>: ${d.alamat || 'Bandar Lampung'}</td></tr>
                        <tr><td class="label">Status Pekerjaan</td><td>: <strong>${d.status.toUpperCase()}</strong></td></tr>
                    </table>
                    <table class="dealer-meta-table">
                        <tr><td class="label">Nomor Polisi</td><td>: <strong class="font-mono">${d.no_polisi}</strong></td></tr>
                        <tr><td class="label">Unit Kendaraan</td><td>: ${d.kendaraan}</td></tr>
                        <tr><td class="label">No. Rangka / KM</td><td>: <span class="font-mono">${d.no_rangka || '-'}</span> / ${d.kilometer || '-'}</td></tr>
                        <tr><td class="label">Mekanik / Stall</td><td>: ${d.nama_mekanik || '-'} (${d.stall || 'Reguler'})</td></tr>
                    </table>
                </div>

                <table class="dealer-table">
                    <thead>
                        <tr>
                            <th style="width: 34%;">Keluhan Awal Pelanggan</th>
                            <th style="width: 30%;">Instruksi Paket Pekerjaan</th>
                            <th style="width: 36%;">Hasil Pemeriksaan & Tindakan Teknisi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>${d.keluhan || '-'}</td>
                            <td><strong>${d.nama_layanan}</strong><br><span class="font-mono">Kode: ${d.kode_layanan || 'SRV-GEN'}</span></td>
                            <td>${d.catatan_mekanik || 'Pemeriksaan standar selesai dilaksanakan.'}</td>
                        </tr>
                    </tbody>
                </table>

                <div class="fw-bold mb-1" style="font-size: 11px; text-transform: uppercase;">Daftar Komponen / Suku Cadang Yang Diganti:</div>
                <table class="dealer-table">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 40px;">No</th>
                            <th style="width: 140px;">Nomor Part</th>
                            <th>Nama Suku Cadang</th>
                            <th class="text-center" style="width: 80px;">Kuantitas</th>
                            <th class="text-center" style="width: 120px;">Keterangan Part Lama</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${partRows}
                    </tbody>
                </table>

                <table class="dealer-table">
                    <thead>
                        <tr>
                            <th colspan="4">Checklist Pemeriksaan Akhir (Final Inspection Quality Control)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="width: 25%;">[ &#10003; ] Uji Jalan / Test Ride Normal</td>
                            <td style="width: 25%;">[ &#10003; ] Kebersihan Unit Kendaraan</td>
                            <td style="width: 25%;">[ &#10003; ] Tekanan Ban & Rem Sesuai</td>
                            <td style="width: 25%;">[ &#10003; ] Part Bekas Diserahkan</td>
                        </tr>
                    </tbody>
                </table>

                <div style="font-size: 10.5px; border: 1px solid #000; padding: 6px 8px; margin-bottom: 10px;">
                    <strong>Pernyataan Serah Terima Unit:</strong> Pemilik/pembawa kendaraan menyatakan telah menerima kembali unit kendaraan beserta komponen bekas yang diganti dalam kondisi baik dan telah diuji sesuai keluhan awal.
                </div>

                <div class="dealer-sign-grid">
                    <div class="dealer-sign-box">
                        <div>Pelanggan / Pemilik Unit,</div>
                        <div class="dealer-sign-space"></div>
                        <div class="fw-bold">${d.nama_pelanggan}</div>
                    </div>
                    <div class="dealer-sign-box">
                        <div>Mekanik Pelaksana / Final Inspector,</div>
                        <div class="dealer-sign-space"></div>
                        <div class="fw-bold">${d.nama_mekanik || 'Mekanik Bengkel'}</div>
                    </div>
                    <div class="dealer-sign-box">
                        <div>Service Advisor / Petugas Serah Terima,</div>
                        <div class="dealer-sign-space"></div>
                        <div class="fw-bold"><?= htmlspecialchars($userName) ?></div>
                    </div>
                </div>
            </div>
        `;
    }

    // DOKUMEN 2: FAKTUR & KUITANSI PEMBAYARAN SERVIS RESMI (DEALER STANDARD)
    function buildDealerKuitansiHTML(d) {
        const jasa = Number(d.biaya_layanan || 0);
        const subtotalPart = hitungSubtotalPart(d.sparepart);
        const grandTotal = jasa + subtotalPart;

        let rowsHTML = `
            <tr>
                <td class="text-center font-mono">1</td>
                <td class="font-mono">${d.kode_layanan || 'JASA-001'}</td>
                <td>Jasa Pekerjaan: <strong>${d.nama_layanan}</strong></td>
                <td class="text-center">JASA</td>
                <td class="text-center">1 Pkt</td>
                <td class="text-end font-mono">${formatRupiah(jasa)}</td>
                <td class="text-end font-mono">${formatRupiah(jasa)}</td>
            </tr>
        `;

        (d.sparepart || []).forEach((sp, i) => {
            const sub = Number(sp.qty || 1) * Number(sp.harga || 0);
            rowsHTML += `
                <tr>
                    <td class="text-center font-mono">${i + 2}</td>
                    <td class="font-mono">${sp.kode || 'PART-00' + (i + 1)}</td>
                    <td>${sp.nama}</td>
                    <td class="text-center">SUKU CADANG</td>
                    <td class="text-center">${sp.qty} ${sp.satuan || 'Pcs'}</td>
                    <td class="text-end font-mono">${formatRupiah(sp.harga)}</td>
                    <td class="text-end font-mono">${formatRupiah(sub)}</td>
                </tr>
            `;
        });

        return `
            <div class="dealer-doc">
                <div class="dealer-header">
                    <div>
                        <div class="dealer-company-name">PT BENGKEL SERVIS OTOMOTIF NUSANTARA</div>
                        <div>Authorized Service Center & Sparepart — Cabang Bandar Lampung</div>
                        <div>Jl. Prof. Dr. Ir. Sumantri Brojonegoro No. 1, Gedong Meneng, Bandar Lampung 35145</div>
                        <div>NPWP: 01.348.921.6-322.000 | Telp. (0721) 701609</div>
                    </div>
                    <div class="text-end">
                        <div class="dealer-doc-title">FAKTUR & KUITANSI PEMBAYARAN</div>
                        <div class="font-mono mt-1"><strong>No. Faktur : INV/${d.id_transaksi}</strong></div>
                        <div>Tgl. Cetak : <?= date('d/m/Y H:i') ?></div>
                    </div>
                </div>

                <div class="dealer-meta-grid">
                    <table class="dealer-meta-table">
                        <tr><td class="label">Terima Dari</td><td>: <strong>${d.nama_pelanggan}</strong></td></tr>
                        <tr><td class="label">No. Telepon</td><td>: ${d.telepon || '-'}</td></tr>
                        <tr><td class="label">Referensi PKB</td><td>: <span class="font-mono">${d.id_transaksi}</span></td></tr>
                    </table>
                    <table class="dealer-meta-table">
                        <tr><td class="label">Nomor Polisi</td><td>: <strong class="font-mono">${d.no_polisi}</strong></td></tr>
                        <tr><td class="label">Tipe Kendaraan</td><td>: ${d.kendaraan}</td></tr>
                        <tr><td class="label">Status transaksi</td><td>: <strong>${d.status}</strong></td></tr>
                    </table>
                </div>

                <table class="dealer-table">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 36px;">No</th>
                            <th style="width: 120px;">Kode Item</th>
                            <th>Deskripsi Jasa & Suku Cadang</th>
                            <th class="text-center" style="width: 95px;">Kategori</th>
                            <th class="text-center" style="width: 65px;">Qty</th>
                            <th class="text-end" style="width: 115px;">Harga Satuan</th>
                            <th class="text-end" style="width: 120px;">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${rowsHTML}
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5" rowspan="3" style="vertical-align: top; font-weight: 400; font-size: 10.5px;">
                                <strong>Ketentuan Garansi Servis Resmi:</strong><br>
                                1. Garansi pekerjaan servis berlaku 7 hari atau 500 KM sejak tanggal faktur diterbitkan.<br>
                                2. Klaim garansi wajib menyertakan dokumen Faktur/Kuitansi asli ini.<br>
                                3. Suku cadang asli yang telah dibeli dan dipasang tidak dapat dikembalikan.
                            </td>
                            <td class="text-end">Total Jasa</td>
                            <td class="text-end font-mono">${formatRupiah(jasa)}</td>
                        </tr>
                        <tr>
                            <td class="text-end">Total Suku Cadang</td>
                            <td class="text-end font-mono">${formatRupiah(subtotalPart)}</td>
                        </tr>
                        <tr>
                            <td class="text-end" style="font-size: 12.5px;"><strong>TOTAL BAYAR</strong></td>
                            <td class="text-end font-mono" style="font-size: 12.5px;"><strong>${formatRupiah(grandTotal)}</strong></td>
                        </tr>
                    </tfoot>
                </table>

                <div class="dealer-sign-grid" style="grid-template-columns: 1fr 1fr;">
                    <div class="dealer-sign-box">
                        <div>Pelanggan / Penyetor,</div>
                        <div class="dealer-sign-space"></div>
                        <div class="fw-bold">${d.nama_pelanggan}</div>
                    </div>
                    <div class="dealer-sign-box">
                        <div>Bandar Lampung, <?= date('d/m/Y') ?> — Kasir Administrasi,</div>
                        <div class="dealer-sign-space"></div>
                        <div class="fw-bold"><?= htmlspecialchars($userName) ?></div>
                    </div>
                </div>
            </div>
        `;
    }

    tableBody.addEventListener('click', function (e) {
        const btnNota = e.target.closest('.btn-cetak-nota');
        const btnKwt = e.target.closest('.btn-cetak-kuitansi');
        if (!btnNota && !btnKwt) return;

        const id = (btnNota || btnKwt).dataset.id;
        const trx = allTransactions.find(t => t.id_transaksi === id);
        if (!trx) return;

        if (btnNota) {
            document.getElementById('modalPreviewTitle').textContent = 'Pratinjau Nota Pengambilan & PKB — ' + trx.id_transaksi;
            const html = buildDealerNotaHTML(trx);
            previewContainer.innerHTML = html;
            printContainer.innerHTML = html;
            modalPreview.show();
        } else {
            document.getElementById('modalPreviewTitle').textContent = 'Pratinjau Faktur & Kuitansi — ' + trx.id_transaksi;
            const html = buildDealerKuitansiHTML(trx);
            previewContainer.innerHTML = html;
            printContainer.innerHTML = html;
            modalPreview.show();
        }
    });

    document.getElementById('btnTriggerPrint').addEventListener('click', () => window.print());
    filterStatus.addEventListener('change', renderTable);
    searchInput.addEventListener('input', renderTable);
    document.getElementById('btnRefreshStat').addEventListener('click', loadDashboardData);

    loadDashboardData();
});
</script>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>