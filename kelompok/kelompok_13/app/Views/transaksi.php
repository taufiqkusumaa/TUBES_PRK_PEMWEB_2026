<?php
/**
 * @var array|null $transaksiAktif
 * @var array|null $paketLayanan
 * @var array|null $riwayatServis
 */
$transaksiAktif = (isset($transaksiAktif) && is_array($transaksiAktif)) ? $transaksiAktif : null;
$paketLayanan   = (isset($paketLayanan) && is_iterable($paketLayanan)) ? $paketLayanan : [];
$riwayatServis  = (isset($riwayatServis) && is_iterable($riwayatServis)) ? $riwayatServis : [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BENGKEL-SERVIS | Sistem Manajemen Servis Kendaraan & Work Order</title>
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f6f9;
            color: #1e293b;
        }
        .navbar-brand {
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.05);
        }
        /* Styling Stepper Tracking Progres Kendaraan */
        .stepper-wrapper {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin: 1.5rem 0;
        }
        .stepper-wrapper::before {
            content: "";
            position: absolute;
            top: 24px;
            left: 10%;
            right: 10%;
            height: 4px;
            background-color: #e2e8f0;
            z-index: 1;
        }
        .stepper-item {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            z-index: 2;
        }
        .step-counter {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #ffffff;
            border: 3px solid #cbd5e1;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.25rem;
            color: #64748b;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }
        .stepper-item.completed .step-counter {
            background-color: #10b981;
            border-color: #10b981;
            color: #ffffff;
        }
        .stepper-item.active .step-counter {
            background-color: #2563eb;
            border-color: #2563eb;
            color: #ffffff;
            box-shadow: 0 0 0 6px rgba(37, 99, 235, 0.18);
            transform: scale(1.08);
        }
        .step-name {
            font-weight: 600;
            font-size: 0.875rem;
            color: #475569;
        }
        .stepper-item.active .step-name {
            color: #2563eb;
            font-weight: 700;
        }
        .stepper-item.completed .step-name {
            color: #10b981;
        }
        .plate-badge {
            background: #0f172a;
            color: #ffffff;
            border: 2px solid #334155;
            font-family: monospace;
            font-weight: 700;
            padding: 0.25rem 0.65rem;
            border-radius: 0.4rem;
            letter-spacing: 1px;
        }
        .riwayat-row {
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        .riwayat-row:hover {
            background-color: #f1f5f9;
        }
        .riwayat-row.selected-row {
            background-color: #eff6ff !important;
            border-left: 4px solid #2563eb;
        }
    </style>
</head>
<body>

<!-- Navbar Utama -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark py-3 sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
            <span class="bg-primary text-white rounded-3 p-2 d-inline-flex align-items-center justify-content-center">
                <i class="bi bi-wrench-adjustable-circle-fill fs-5"></i>
            </span>
            <span>BENGKEL-SERVIS</span>
        </a>
        <div class="d-flex align-items-center gap-3 text-white">
            <div class="text-end d-none d-sm-block">
                <div class="fw-semibold small"><?= htmlspecialchars($_SESSION['user_nama'] ?? 'Budi Santoso') ?></div>
                <div class="text-secondary" style="font-size: 0.75rem;">Pelanggan Terdaftar (ID #<?= (int)($_SESSION['user_id'] ?? 1) ?>)</div>
            </div>
            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2">
                <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i> Online
            </span>
        </div>
    </div>
</nav>

<main class="container py-4">
    <!-- Notifikasi Alert Dinamis -->
    <div id="alertContainer"></div>

    <!-- BAGIAN 1: STEPPER TRACKING PROGRES KENDARAAN -->
    <div class="card mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                <div>
                    <span class="badge bg-primary-subtle text-primary fw-semibold mb-1">LIVE WORK ORDER TRACKER</span>
                    <h4 class="fw-bold mb-0" id="trackerTitle">
                        <?php if (!empty($transaksiAktif) && !empty($transaksiAktif['pelat_nomor'])): ?>
                            Status Kendaraan: <span class="plate-badge ms-1" id="trackerPelat"><?= htmlspecialchars($transaksiAktif['pelat_nomor']) ?></span>
                        <?php else: ?>
                            Status Kendaraan: <span class="text-muted fs-6 fw-normal ms-1" id="trackerPelat">Belum Ada Servis Berjalan</span>
                        <?php endif; ?>
                    </h4>
                </div>
                <div class="text-md-end">
                    <div class="small text-muted">Nomor Work Order</div>
                    <div class="fw-bold text-primary fs-5" id="trackerKodeWo">
                        <?= htmlspecialchars($transaksiAktif['kode_wo'] ?? '-') ?>
                    </div>
                </div>
            </div>

            <!-- Visualisasi 4 Tahap Stepper: Pending -> Antre -> Dikerjakan -> Siap Diambil -->
            <?php
            $stages = [
                'Pending'      => ['icon' => 'bi-clipboard2-check', 'desc' => 'Verifikasi Order'],
                'Antre'        => ['icon' => 'bi-hourglass-split', 'desc' => 'Antrean Pit/Stall'],
                'Dikerjakan'   => ['icon' => 'bi-gear-wide-connected', 'desc' => 'Ditangani Mekanik'],
                'Siap Diambil' => ['icon' => 'bi-check2-circle', 'desc' => 'Selesai & Siap Ambil']
            ];
            $currentStatus = $transaksiAktif['status_servis'] ?? 'Pending';
            $stageKeys     = array_keys($stages);
            $currentIndex  = array_search($currentStatus, $stageKeys, true);
            if ($currentIndex === false) {
                $currentIndex = 0;
            }
            ?>
            <div class="stepper-wrapper" id="stepperWrapper" data-current-status="<?= htmlspecialchars($currentStatus) ?>">
                <?php foreach ($stages as $stageName => $meta): 
                    $idx = array_search($stageName, $stageKeys, true);
                    $stateClass = '';
                    if ($idx < $currentIndex) {
                        $stateClass = 'completed';
                    } elseif ($idx === $currentIndex) {
                        $stateClass = 'active';
                    }
                ?>
                <div class="stepper-item <?= $stateClass ?>" data-step="<?= htmlspecialchars($stageName) ?>">
                    <div class="step-counter">
                        <i class="bi <?= $meta['icon'] ?>"></i>
                    </div>
                    <div class="step-name"><?= htmlspecialchars($stageName) ?></div>
                    <div class="text-muted d-none d-md-block" style="font-size: 0.75rem;"><?= htmlspecialchars($meta['desc']) ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Informasi Detail & Tombol Simulasi Update Status Mekanik -->
            <div class="bg-light rounded-3 p-3 mt-3 border d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <div class="small text-secondary fw-semibold mb-1">
                        <i class="bi bi-chat-square-text me-1"></i> Catatan Mekanik / Service Advisor:
                    </div>
                    <div class="fw-medium text-dark" id="trackerCatatan">
                        <?= htmlspecialchars($transaksiAktif['catatan_mekanik'] ?? 'Pilih salah satu transaksi pada tabel riwayat untuk memantau progres.') ?>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="small text-muted me-1">Simulasi Update Status:</span>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-secondary btn-simulasi-status" data-status="Pending">Pending</button>
                        <button type="button" class="btn btn-outline-warning btn-simulasi-status" data-status="Antre">Antre</button>
                        <button type="button" class="btn btn-outline-primary btn-simulasi-status" data-status="Dikerjakan">Dikerjakan</button>
                        <button type="button" class="btn btn-outline-success btn-simulasi-status" data-status="Siap Diambil">Siap Diambil</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- BAGIAN 2: FORMULIR PEMESANAN SERVIS BARU -->
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h5 class="fw-bold mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-plus-circle-fill text-primary"></i>
                        Formulir Pemesanan Servis Baru
                    </h5>
                    <p class="text-muted small mb-0 mt-1">Isi data kendaraan & keluhan mesin untuk membuat Work Order baru.</p>
                </div>
                <div class="card-body p-4">
                    <form id="formBookingServis" enctype="multipart/form-data" novalidate>
                        <input type="hidden" name="action" value="create_booking">
                        <input type="hidden" id="activeTransaksiId" value="<?= (int)($transaksiAktif['id'] ?? 0) ?>">

                        <div class="row g-3 mb-3">
                            <div class="col-sm-5">
                                <label for="pelat_nomor" class="form-label fw-semibold small">Pelat Nomor <span class="text-danger">*</span></label>
                                <input type="text" class="form-control text-uppercase fw-bold" id="pelat_nomor" name="pelat_nomor" placeholder="BE 1234 XX" required>
                            </div>
                            <div class="col-sm-7">
                                <label for="merk_tipe_kendaraan" class="form-label fw-semibold small">Merk & Tipe Kendaraan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="merk_tipe_kendaraan" name="merk_tipe_kendaraan" placeholder="Contoh: Toyota Innova Reborn" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="paket_id" class="form-label fw-semibold small">Pilih Paket Layanan <span class="text-danger">*</span></label>
                            <select class="form-select" id="paket_id" name="paket_id" required>
                                <option value="" data-harga="0" data-estimasi="0">-- Pilih Paket Servis --</option>
                                <?php if (!empty($paketLayanan)): ?>
                                    <?php foreach ($paketLayanan as $pkt): ?>
                                        <option value="<?= (int)$pkt['id'] ?>" 
                                                data-harga="<?= (float)$pkt['harga'] ?>" 
                                                data-estimasi="<?= (int)$pkt['estimasi_menit'] ?>"
                                                data-deskripsi="<?= htmlspecialchars($pkt['deskripsi'] ?? '') ?>">
                                            <?= htmlspecialchars($pkt['nama_paket']) ?> — Rp <?= number_format((float)$pkt['harga'], 0, ',', '.') ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <div id="infoPaketBox" class="form-text text-primary mt-2 d-none"></div>
                        </div>

                        <div class="mb-3">
                            <label for="keluhan_mesin" class="form-label fw-semibold small">Keluhan Mesin / Kendala Kendaraan <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="keluhan_mesin" name="keluhan_mesin" rows="3" placeholder="Jelaskan gejala atau kendala mesin yang dialami..." required></textarea>
                        </div>

                        <div class="mb-4">
                            <label for="foto_kendala" class="form-label fw-semibold small">
                                Upload Foto Kendala <span class="text-muted fw-normal">(Opsional, disimpan ke <code>public/uploads/</code>)</span>
                            </label>
                            <input class="form-control" type="file" id="foto_kendala" name="foto_kendala" accept="image/jpeg,image/png,image/webp">
                            <div class="form-text small">Format: JPG, PNG, WEBP (Maks. 5MB).</div>
                            <!-- Preview gambar sebelum diunggah -->
                            <div id="previewContainer" class="mt-2 d-none">
                                <img id="fotoPreview" src="" alt="Preview Foto Kendala" class="img-thumbnail" style="max-height: 140px;">
                            </div>
                        </div>

                        <button type="submit" id="btnSubmitBooking" class="btn btn-primary w-100 py-2 fw-semibold">
                            <i class="bi bi-send-check me-1"></i> Buat Pesanan Work Order
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- BAGIAN 3: DAFTAR RIWAYAT SERVIS MILIK AKUN PELANGGAN -->
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold mb-0 d-flex align-items-center gap-2">
                            <i class="bi bi-clock-history text-primary"></i>
                            Riwayat Servis Akun Saya
                        </h5>
                        <p class="text-muted small mb-0 mt-1">Klik salah satu baris transaksi untuk melihat progres pada Stepper di atas.</p>
                    </div>
                    <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-2" id="totalRiwayatBadge">
                        <?= count($riwayatServis) ?> Work Order
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="tabelRiwayatServis">
                            <thead class="table-light small text-uppercase text-secondary">
                                <tr>
                                    <th class="ps-4">Work Order & Waktu</th>
                                    <th>Kendaraan & Paket</th>
                                    <th>Foto</th>
                                    <th class="pe-4 text-end">Status Progres</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyRiwayat">
                                <?php if (empty($riwayatServis)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">
                                            Belum ada riwayat pemesanan servis pada akun Anda.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($riwayatServis as $idx => $item): 
                                        $badgeMap = [
                                            'Pending'      => 'bg-secondary-subtle text-secondary border-secondary-subtle',
                                            'Antre'        => 'bg-warning-subtle text-warning-emphasis border-warning-subtle',
                                            'Dikerjakan'   => 'bg-primary-subtle text-primary border-primary-subtle',
                                            'Siap Diambil' => 'bg-success-subtle text-success border-success-subtle'
                                        ];
                                        $badgeStyle = $badgeMap[$item['status_servis']] ?? 'bg-secondary';
                                    ?>
                                    <tr class="riwayat-row <?= $idx === 0 ? 'selected-row' : '' ?>"
                                        data-id="<?= (int)$item['id'] ?>"
                                        data-wo="<?= htmlspecialchars($item['kode_wo']) ?>"
                                        data-pelat="<?= htmlspecialchars($item['pelat_nomor']) ?>"
                                        data-status="<?= htmlspecialchars($item['status_servis']) ?>"
                                        data-catatan="<?= htmlspecialchars($item['catatan_mekanik'] ?? '') ?>">
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($item['kode_wo']) ?></div>
                                            <div class="small text-muted"><?= date('d M Y, H:i', strtotime($item['tanggal_booking'])) ?></div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <span class="plate-badge" style="font-size: 0.75rem;"><?= htmlspecialchars($item['pelat_nomor']) ?></span>
                                                <span class="fw-semibold small"><?= htmlspecialchars($item['merk_tipe_kendaraan']) ?></span>
                                            </div>
                                            <div class="small text-primary fw-medium"><?= htmlspecialchars($item['nama_paket'] ?? 'Paket Kustom') ?></div>
                                            <div class="small text-muted text-truncate" style="max-width: 240px;" title="<?= htmlspecialchars($item['keluhan_mesin']) ?>">
                                                "<?= htmlspecialchars($item['keluhan_mesin']) ?>"
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (!empty($item['foto_kendala'])): ?>
                                                <a href="uploads/<?= urlencode($item['foto_kendala']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                                                    <i class="bi bi-image"></i> Lihat
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted small">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="pe-4 text-end">
                                            <span class="badge border rounded-pill px-3 py-2 <?= $badgeStyle ?>">
                                                <?= htmlspecialchars($item['status_servis']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Script Interaktif Transaksi & Stepper -->
<script src="assets/js/transaksi.js"></script>
</body>
</html>