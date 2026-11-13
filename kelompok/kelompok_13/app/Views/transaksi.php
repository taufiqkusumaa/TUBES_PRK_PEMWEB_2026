<?php
// File: kelompok/kelompok_13/app/Views/transaksi.php
require_once __DIR__ . '/../Config/Database.php';
$pdo = Database::getConnection();

$pageTitle = 'Pemesanan & Tracking Servis';
$activePage = 'transaksi';

// Jika dipanggil langsung tanpa melewati TransaksiController, siapkan variabel datanya
if (!isset($transaksiList)) {
    require_once __DIR__ . '/../Models/TransaksiModel.php';
    $trxModel = new TransaksiModel($pdo);
    $transaksiList = $trxModel->getAll($_GET);
    $stats = $trxModel->getDashboardStats();
    $kendaraanOptions = $pdo->query("SELECT k.id, k.kategori_id, k.no_polisi, k.nama_model, k.tahun_pembuatan, k.warna, u.nama AS pemilik FROM kendaraan k JOIN users u ON k.user_id = u.id ORDER BY k.no_polisi ASC")->fetchAll(PDO::FETCH_ASSOC);
    $layananOptions = $pdo->query("SELECT * FROM layanan_servis WHERE status_layanan = 'Tersedia' ORDER BY nama_paket ASC")->fetchAll(PDO::FETCH_ASSOC);
    $teknisiOptions = $pdo->query("SELECT id, nama FROM users WHERE role = 'teknisi' AND status_aktif = 1 ORDER BY nama ASC")->fetchAll(PDO::FETCH_ASSOC);
    $kategoriOptions = $pdo->query("SELECT id, jenis, merk, tipe FROM kategori_kendaraan ORDER BY jenis, merk, tipe")->fetchAll(PDO::FETCH_ASSOC);
}

require_once __DIR__ . '/layouts/header.php';
require_once __DIR__ . '/layouts/sidebar.php';
?>

<style>
    /* Penyesuaian komponen Transaksi agar 100% identik dengan tema Dashboard & Cetak (Light & Dark Mode) */
    .trx-card {
        background: var(--bg-surface, #ffffff);
        border: 1px solid var(--border-color, #e2e8f0);
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
        transition: background-color 0.2s ease, border-color 0.2s ease;
    }
    [data-bs-theme="dark"] .trx-card,
    body.dark-mode .trx-card,
    [data-theme="dark"] .trx-card {
        background: #111827 !important;
        border-color: #1f2937 !important;
        color: #f8fafc !important;
    }
    .trx-metric-label {
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: #64748b;
    }
    .trx-metric-value {
        font-size: 1.75rem;
        font-weight: 800;
        line-height: 1.2;
        margin: 0.35rem 0;
    }
    .trx-table th {
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
        background: rgba(100, 116, 139, 0.05) !important;
        border-bottom: 1px solid var(--border-color, #e2e8f0);
        padding: 0.85rem 1rem;
    }
    .trx-table td {
        padding: 1rem;
        vertical-align: middle;
        border-bottom: 1px solid var(--border-color, #e2e8f0);
        background: transparent !important;
        color: inherit !important;
    }
    [data-bs-theme="dark"] .trx-table th,
    [data-bs-theme="dark"] .trx-table td,
    body.dark-mode .trx-table th,
    body.dark-mode .trx-table td,
    [data-theme="dark"] .trx-table th,
    [data-theme="dark"] .trx-table td {
        border-color: #1f2937 !important;
        color: #e2e8f0 !important;
    }
    .plate-box {
        display: inline-block;
        font-family: 'JetBrains Mono', monospace;
        font-size: 0.78rem;
        font-weight: 700;
        padding: 0.2rem 0.55rem;
        border-radius: 6px;
        background: #f1f5f9;
        color: #0f172a;
        border: 1px solid #cbd5e1;
    }
    [data-bs-theme="dark"] .plate-box,
    body.dark-mode .plate-box,
    [data-theme="dark"] .plate-box {
        background: #1e293b;
        color: #f8fafc;
        border-color: #334155;
    }
    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 0.28rem 0.75rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .status-pill .dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
    }
    .status-antre { background: rgba(245, 158, 11, 0.12); color: #d97706; }
    .status-antre .dot { background: #f59e0b; }
    .status-dikerjakan { background: rgba(6, 182, 212, 0.12); color: #0891b2; }
    .status-dikerjakan .dot { background: #06b6d4; }
    .status-siap { background: rgba(16, 185, 129, 0.12); color: #059669; }
    .status-siap .dot { background: #10b981; }
    .status-selesai { background: rgba(37, 99, 235, 0.12); color: #2563eb; }
    .status-selesai .dot { background: #3b82f6; }
    .status-batal { background: rgba(239, 68, 68, 0.12); color: #dc2626; }
    .status-batal .dot { background: #ef4444; }

    [data-bs-theme="dark"] .modal-content,
    body.dark-mode .modal-content,
    [data-theme="dark"] .modal-content {
        background-color: #111827 !important;
        border: 1px solid #1f2937 !important;
        color: #f8fafc !important;
    }
    [data-bs-theme="dark"] .form-control,
    [data-bs-theme="dark"] .form-select,
    body.dark-mode .form-control,
    body.dark-mode .form-select,
    [data-theme="dark"] .form-control,
    [data-theme="dark"] .form-select {
        background-color: #1e293b !important;
        border-color: #334155 !important;
        color: #f8fafc !important;
    }
    .stepper-box {
        background: rgba(100, 116, 139, 0.08);
        border: 1px solid var(--border-color, #e2e8f0);
        border-radius: 10px;
        padding: 1rem;
    }
    .tracking-step {
        flex: 1 1 0;
        min-width: 0;
        text-align: center;
    }
    .tracking-step-number {
        display: inline-flex;
        width: 2rem;
        height: 2rem;
        align-items: center;
        justify-content: center;
        border: 1px solid #cbd5e1;
        border-radius: 50%;
        background: #e2e8f0;
        color: #334155;
        font-weight: 700;
    }
    .tracking-step-label {
        color: #334155;
        font-size: 0.78rem;
        font-weight: 600;
        line-height: 1.25;
    }
    .tracking-step.is-reached .tracking-step-number {
        border-color: #0047ab;
        background: #0047ab;
        color: #ffffff;
    }
    .tracking-step.is-reached .tracking-step-label {
        color: #0047ab;
        font-weight: 700;
    }
    [data-bs-theme="dark"] .stepper-box,
    body.dark-mode .stepper-box,
    [data-theme="dark"] .stepper-box {
        background: #1e293b;
        border-color: #334155;
    }
    [data-theme="dark"] .tracking-step-number {
        border-color: #475569;
        background: #334155;
        color: #f8fafc;
    }
    [data-theme="dark"] .tracking-step-label {
        color: #cbd5e1;
    }
    [data-theme="dark"] .tracking-step.is-reached .tracking-step-number {
        border-color: #60a5fa;
        background: #2563eb;
    }
    [data-theme="dark"] .tracking-step.is-reached .tracking-step-label {
        color: #93c5fd;
    }
    @media (max-width: 575.98px) {
        .stepper-box.tracking-stepper {
            gap: 0.25rem;
            padding: 0.75rem 0.4rem;
        }
        .tracking-step-label {
            font-size: 0.68rem;
        }
    }
</style>

<div class="container-fluid py-4 px-4">
    <!-- Header Section Seragam dengan Dashboard -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1"><?= $canManage ? 'Pemesanan & Tracking Servis' : 'Booking & Tracking Servis Saya' ?></h4>
            <p class="text-secondary small mb-0"><?= $canManage ? 'Kelola pendaftaran servis kendaraan, penugasan mekanik, dan pemantauan progres pengerjaan.' : 'Buat booking kendaraan dan pantau progres servis Anda.' ?></p>
        </div>
        <div class="d-flex gap-2">
            <a href="index.php?page=transaksi" class="btn btn-outline-secondary btn-sm px-3 py-2 fw-semibold">
                Muat Ulang
            </a>
            <button class="btn btn-primary btn-sm px-3 py-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalBookingBaru">
                + Booking Servis Baru
            </button>
        </div>
    </div>

    <?php if (!empty($successMsg)): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
            <?= htmlspecialchars($successMsg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($errorMsg)): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
            <?= htmlspecialchars($errorMsg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- 4 Kartu Metrik Seragam dengan Dashboard -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="trx-card p-3 h-100">
                <div class="trx-metric-label">ANTREAN MASUK</div>
                <div class="trx-metric-value"><?= (int)($stats['total_antre'] ?? 0) ?></div>
                <div class="text-secondary small">Menunggu alokasi mekanik</div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="trx-card p-3 h-100">
                <div class="trx-metric-label">SEDANG DIKERJAKAN</div>
                <div class="trx-metric-value"><?= (int)($stats['total_dikerjakan'] ?? 0) ?></div>
                <div class="text-secondary small">Unit aktif di stall servis</div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="trx-card p-3 h-100">
                <div class="trx-metric-label">SELESAI / SIAP AMBIL</div>
                <div class="trx-metric-value"><?= (int)($stats['total_selesai'] ?? 0) ?></div>
                <div class="text-secondary small">Siap terbit nota & kuitansi</div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="trx-card p-3 h-100">
                <div class="trx-metric-label">TOTAL NILAI TRANSAKSI</div>
                <div class="trx-metric-value">Rp <?= number_format((float)($stats['total_pendapatan'] ?? 0), 0, ',', '.') ?></div>
                <div class="text-secondary small">Akumulasi jasa & suku cadang</div>
            </div>
        </div>
    </div>

    <!-- Tabel Utama dengan Filter Real-Time -->
    <div class="trx-card overflow-hidden">
        <div class="p-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2" style="border-color: var(--border-color, #e2e8f0) !important;">
            <h6 class="fw-bold mb-0">Daftar Booking & Tracking Work Order</h6>
            <div class="d-flex flex-wrap gap-2">
                <select id="filterStatusTrx" class="form-select form-select-sm" style="width: auto; min-width: 150px;">
                    <option value="">Semua Status</option>
                    <option value="Pending">Pending</option>
                    <option value="Antre">Antre</option>
                    <option value="Dikerjakan">Dikerjakan</option>
                    <option value="Siap Diambil">Siap Diambil</option>
                    <option value="Selesai">Selesai</option>
                </select>
                <input type="text" id="searchInputTrx" class="form-control form-control-sm" placeholder="Cari No. Order / Nopol / Nama..." style="min-width: 230px;">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table trx-table mb-0" id="tableTransaksi">
                <thead>
                    <tr>
                        <th>NO. PKB / ORDER</th>
                        <th>WAKTU MASUK</th>
                        <th>PEMILIK KENDARAAN</th>
                        <th>NO. POLISI & UNIT</th>
                        <th>PAKET LAYANAN & KELUHAN</th>
                        <th>MEKANIK PJ</th>
                        <th>STATUS</th>
                        <th>TOTAL BIAYA</th>
                        <th class="text-end">TINDAKAN</th>
                    </tr>
                </thead>
                <?php $trackingModals = ''; ?>
                <tbody>
                    <?php if (empty($transaksiList)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-secondary">Belum ada data transaksi servis terdaftar.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transaksiList as $tr):
                            $grandTotal = (float)$tr['tarif_dasar'] + (float)$tr['biaya_sparepart'];
                            $pillClass = match($tr['status_servis']) {
                                'Pending', 'Antre' => 'status-antre',
                                'Dikerjakan'       => 'status-dikerjakan',
                                'Siap Diambil'     => 'status-siap',
                                'Selesai'          => 'status-selesai',
                                'Dibatalkan'       => 'status-batal',
                                default            => 'status-antre'
                            };
                        ?>
                            <tr class="trx-row" data-status="<?= htmlspecialchars($tr['status_servis']) ?>">
                                <td class="fw-bold font-monospace"><?= htmlspecialchars($tr['kode_transaksi']) ?></td>
                                <td class="text-secondary small"><?= date('d/m/Y H:i', strtotime($tr['tanggal_pesan'])) ?></td>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($tr['nama_customer']) ?></div>
                                    <div class="text-secondary small"><?= htmlspecialchars($tr['telp_customer'] ?? '-') ?></div>
                                </td>
                                <td>
                                    <span class="plate-box mb-1"><?= htmlspecialchars($tr['no_polisi']) ?></span>
                                    <div class="text-secondary small"><?= htmlspecialchars($tr['nama_model']) ?> <?= !empty($tr['tahun_pembuatan']) ? '('.$tr['tahun_pembuatan'].')' : '' ?></div>
                                </td>
                                <td style="max-width: 240px;">
                                    <div class="fw-semibold"><?= htmlspecialchars($tr['nama_paket']) ?></div>
                                    <div class="text-secondary small text-truncate" title="<?= htmlspecialchars($tr['keluhan_mesin']) ?>">
                                        <?= htmlspecialchars($tr['keluhan_mesin']) ?>
                                    </div>
                                </td>
                                <td>
                                    <?= $tr['nama_teknisi'] ? htmlspecialchars($tr['nama_teknisi']) : '<span class="text-secondary fst-italic small">Belum dialokasikan</span>' ?>
                                </td>
                                <td>
                                    <span class="status-pill <?= $pillClass ?>">
                                        <span class="dot"></span>
                                        <?= htmlspecialchars($tr['status_servis']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold">Rp <?= number_format($grandTotal, 0, ',', '.') ?></div>
                                    <div class="text-secondary" style="font-size: 0.72rem;">
                                        <?= $tr['biaya_sparepart'] > 0 ? 'Jasa + Suku Cadang' : 'Tarif Dasar Paket' ?>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <button class="btn btn-sm btn-outline-primary fw-semibold"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalUpdateStatus<?= $tr['id'] ?>">
                                            Tracking & Status
                                        </button>
                                        <?php if ($canManage): ?>
                                            <form method="POST" action="index.php?page=transaksi" class="d-inline" onsubmit="return confirm('Hapus order <?= htmlspecialchars($tr['kode_transaksi']) ?>?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $tr['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>

                            <!-- Modal Tracking & Update Status -->
                            <?php ob_start(); ?>
                            <div class="modal fade" id="modalUpdateStatus<?= $tr['id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <form method="POST" action="index.php?page=transaksi" class="modal-content">
                                        <div class="modal-header border-bottom-0 pb-0">
                                            <h5 class="modal-title fw-bold">Tracking & Update Work Order: <?= htmlspecialchars($tr['kode_transaksi']) ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <?php if ($canManage): ?>
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="id" value="<?= $tr['id'] ?>">
                                            <?php endif; ?>

                                            <?php
                                            $steps = ['Pending', 'Antre', 'Dikerjakan', 'Siap Diambil', 'Selesai'];
                                            $currentIdx = array_search($tr['status_servis'], $steps);
                                            ?>
                                            <div class="stepper-box tracking-stepper d-flex justify-content-between align-items-center mb-4">
                                                <?php foreach ($steps as $idx => $stepName):
                                                    $isDone = ($currentIdx !== false && $idx <= $currentIdx);
                                                ?>
                                                    <div class="tracking-step <?= $isDone ? 'is-reached' : '' ?>">
                                                        <span class="tracking-step-number mb-1">
                                                            <?= $idx + 1 ?>
                                                        </span>
                                                        <div class="tracking-step-label"><?= $stepName ?></div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>

                                            <?php if ($canManage): ?>
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label small fw-bold text-uppercase text-secondary">Status Pengerjaan</label>
                                                    <select name="status_servis" class="form-select" required>
                                                        <?php foreach (['Pending', 'Antre', 'Dikerjakan', 'Siap Diambil', 'Selesai', 'Dibatalkan'] as $st): ?>
                                                            <option value="<?= $st ?>" <?= $tr['status_servis'] === $st ? 'selected' : '' ?>><?= $st ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label small fw-bold text-uppercase text-secondary">Mekanik Penanggung Jawab</label>
                                                    <select name="teknisi_id" class="form-select">
                                                        <option value="">-- Pilih Mekanik --</option>
                                                        <?php foreach ($teknisiOptions as $tek): ?>
                                                            <option value="<?= $tek['id'] ?>" <?= $tr['teknisi_id'] == $tek['id'] ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($tek['nama']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label small fw-bold text-uppercase text-secondary">Keluhan Mesin Pelanggan</label>
                                                    <textarea class="form-control" rows="2" readonly><?= htmlspecialchars($tr['keluhan_mesin']) ?></textarea>
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label small fw-bold text-uppercase text-secondary">Catatan Pemeriksaan / Admin</label>
                                                    <textarea name="catatan_admin" class="form-control" rows="2"><?= htmlspecialchars($tr['catatan_admin'] ?? '') ?></textarea>
                                                </div>
                                                <div class="col-12">
                                                    <div class="stepper-box d-flex justify-content-between align-items-center">
                                                        <div class="small">
                                                            <div>Tarif Paket (<?= htmlspecialchars($tr['nama_paket']) ?>): <strong>Rp <?= number_format($tr['tarif_dasar'], 0, ',', '.') ?></strong></div>
                                                            <div>Komponen Suku Cadang (Work Order): <strong>Rp <?= number_format($tr['biaya_sparepart'], 0, ',', '.') ?></strong></div>
                                                        </div>
                                                        <div class="text-end">
                                                            <div class="text-secondary small">Total Biaya Aktual</div>
                                                            <h5 class="fw-bold text-primary mb-0">Rp <?= number_format($grandTotal, 0, ',', '.') ?></h5>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php else: ?>
                                                <p class="mb-2"><strong>Status saat ini:</strong> <?= htmlspecialchars($tr['status_servis']) ?></p>
                                                <p class="mb-2"><strong>Keluhan:</strong> <?= htmlspecialchars($tr['keluhan_mesin']) ?></p>
                                                <p class="mb-0"><strong>Total biaya:</strong> Rp <?= number_format($grandTotal, 0, ',', '.') ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="modal-footer border-top-0">
                                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                                            <?php if ($canManage): ?>
                                                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                            <?php endif; ?>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <?php $trackingModals .= ob_get_clean(); ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?= $trackingModals ?>
    </div>
</div>

<!-- Modal Buat Booking Servis Baru -->
<div class="modal fade" id="modalBookingBaru" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="index.php?page=transaksi" enctype="multipart/form-data" class="modal-content">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold">Pendaftaran Order Servis Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="create_booking">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-uppercase text-secondary"><?= $canManage ? 'Unit Kendaraan Pelanggan' : 'Kendaraan Anda' ?></label>
                        <select name="kendaraan_id" class="form-select" required>
                            <option value="">-- Pilih Nomor Polisi & Unit --</option>
                            <?php foreach ($kendaraanOptions as $ko): ?>
                                <option value="<?= (int) $ko['id'] ?>"
                                        data-kategori-id="<?= (int) $ko['kategori_id'] ?>"
                                        data-no-polisi="<?= htmlspecialchars($ko['no_polisi'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-nama-model="<?= htmlspecialchars($ko['nama_model'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-tahun="<?= htmlspecialchars((string) ($ko['tahun_pembuatan'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        data-warna="<?= htmlspecialchars((string) ($ko['warna'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($ko['no_polisi'] . ' - ' . $ko['nama_model'] . ' (' . $ko['pemilik'] . ')') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!$canManage): ?>
                            <?php if (empty($kendaraanOptions)): ?>
                                <div class="form-text text-warning">Anda belum memiliki kendaraan terdaftar. Daftarkan kendaraan terlebih dahulu untuk booking.</div>
                            <?php endif; ?>
                            <button type="button" class="btn btn-link btn-sm px-0 text-decoration-none"
                                    id="btnBukaTambahKendaraan" <?= empty($kategoriOptions) ? 'disabled' : '' ?>>
                                + Daftarkan kendaraan baru
                            </button>
                            <button type="button" class="btn btn-link btn-sm px-0 ms-3 text-decoration-none"
                                    id="btnUbahKendaraan" disabled>
                                Ubah kendaraan terpilih
                            </button>
                            <?php if (empty($kategoriOptions)): ?>
                                <div class="form-text text-danger">Kategori kendaraan belum tersedia. Silakan hubungi admin bengkel.</div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-uppercase text-secondary">Paket Layanan Servis</label>
                        <select name="layanan_id" class="form-select" required>
                            <option value="">-- Pilih Paket Layanan --</option>
                            <?php foreach ($layananOptions as $lo): ?>
                                <option value="<?= (int) $lo['id'] ?>" data-kategori-id="<?= (int) $lo['kategori_id'] ?>">
                                    <?= htmlspecialchars($lo['nama_paket']) ?> — Rp <?= number_format($lo['tarif_dasar'], 0, ',', '.') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($canManage): ?>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-uppercase text-secondary">Alokasi Mekanik</label>
                        <select name="teknisi_id" class="form-select">
                            <option value="">-- Alokasikan Nanti --</option>
                            <?php foreach ($teknisiOptions as $tek): ?>
                                <option value="<?= $tek['id'] ?>"><?= htmlspecialchars($tek['nama']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-uppercase text-secondary">Status Awal</label>
                        <select name="status_servis" class="form-select">
                            <option value="Antre" selected>Antre</option>
                            <option value="Pending">Pending</option>
                            <option value="Dikerjakan">Langsung Dikerjakan</option>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="col-12">
                        <label class="form-label small fw-bold text-uppercase text-secondary">Keluhan Mesin & Gejala Kerusakan</label>
                        <textarea name="keluhan_mesin" class="form-control" rows="3" placeholder="Deskripsikan keluhan mesin kendaraan..." required></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-uppercase text-secondary">Foto Dokumentasi Kendala (Opsional)</label>
                        <input type="file" name="foto_kendala" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                    </div>
                    <?php if ($canManage): ?>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-uppercase text-secondary">Catatan Tambahan Admin</label>
                        <input type="text" name="catatan_admin" class="form-control" placeholder="Contoh: Menunggu antrean stall 2">
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Terbitkan Order Servis</button>
            </div>
        </form>
    </div>
</div>

<?php if (!$canManage): ?>
<div class="modal fade" id="modalTambahKendaraan" tabindex="-1" aria-labelledby="judulModalTambahKendaraan" aria-hidden="true">
    <div class="modal-dialog">
        <form id="formKendaraanPelanggan" class="modal-content">
            <input type="hidden" name="id">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="judulModalTambahKendaraan">Daftarkan Kendaraan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div id="alertKendaraanPelanggan" class="alert d-none" role="alert"></div>
                <div class="mb-3">
                    <label class="form-label" for="kendaraanKategoriPelanggan">Kategori kendaraan</label>
                    <select class="form-select" id="kendaraanKategoriPelanggan" name="kategori_id" required>
                        <option value="">Pilih kategori</option>
                        <?php foreach ($kategoriOptions as $kategori): ?>
                            <option value="<?= (int) $kategori['id'] ?>">
                                <?= htmlspecialchars($kategori['jenis'] . ' — ' . $kategori['merk'] . ' ' . $kategori['tipe'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($kategoriOptions)): ?>
                        <div class="form-text text-danger">Belum ada kategori kendaraan. Silakan hubungi admin bengkel.</div>
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="kendaraanNopolPelanggan">Nomor polisi</label>
                    <input class="form-control" id="kendaraanNopolPelanggan" name="no_polisi"
                           maxlength="15" placeholder="BE 1234 ABC" required>
                    <div class="form-text">Contoh format: BE 1234 ABC.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="kendaraanModelPelanggan">Model kendaraan</label>
                    <input class="form-control" id="kendaraanModelPelanggan" name="nama_model"
                           maxlength="100" placeholder="Contoh: Honda Vario 160" required>
                </div>
                <div class="row">
                    <div class="col-sm-6 mb-3">
                        <label class="form-label" for="kendaraanTahunPelanggan">Tahun pembuatan</label>
                        <input class="form-control" id="kendaraanTahunPelanggan" name="tahun_pembuatan"
                               type="number" min="1901" max="<?= (int) date('Y') + 1 ?>">
                    </div>
                    <div class="col-sm-6 mb-3">
                        <label class="form-label" for="kendaraanWarnaPelanggan">Warna</label>
                        <input class="form-control" id="kendaraanWarnaPelanggan" name="warna" maxlength="30">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary" <?= empty($kategoriOptions) ? 'disabled' : '' ?>>
                    Simpan Kendaraan
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script src="assets/js/transaksi.js"></script>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>