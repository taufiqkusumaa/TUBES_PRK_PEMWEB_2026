<?php
include '../../config/koneksi.php';
date_default_timezone_set('Asia/Jakarta');
if (session_status() == PHP_SESSION_NONE) session_start();

$error = '';
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// --- Proses simpan (sebelum header, supaya redirect bisa jalan) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    $id_pakan_raw = $_POST['id_pakan'] ?? '';
    $id_kandang_raw = $_POST['id_kandang'] ?? '';
    $jumlah_input = $_POST['jumlah_kg'] ?? '';
    $tgl_input   = $_POST['tgl_pemberian'] ?? '';
    $id_peternak = $_SESSION['id_user'] ?? null;   // NULL kalau belum login

    $id_pakan = is_string($id_pakan_raw)
        ? filter_var($id_pakan_raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
        : false;
    $id_kandang = is_string($id_kandang_raw)
        ? filter_var($id_kandang_raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
        : false;
    $jumlah_valid = is_string($jumlah_input)
        && preg_match('/^\d+(?:\.\d{1,2})?$/D', $jumlah_input)
        && (float)$jumlah_input > 0
        && (float)$jumlah_input <= 9999.99;
    $jumlah = $jumlah_valid ? (float)$jumlah_input : 0;
    $tanggal = is_string($tgl_input) && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/D', $tgl_input)
        ? DateTime::createFromFormat('!Y-m-d\TH:i', $tgl_input)
        : false;
    $tanggal_valida = $tanggal && $tanggal->format('Y-m-d\TH:i') === $tgl_input;

    if (!is_string($csrf_token) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        $error = 'Sesi formulir telah berakhir. Muat ulang halaman lalu coba lagi.';
    } elseif ($id_pakan === false || $id_kandang === false || !$jumlah_valid || !$tanggal_valida) {
        $error = 'Semua kolom wajib diisi dengan benar, dan jumlah harus lebih dari 0.';
    } else {
        $tgl = $tanggal->format('Y-m-d H:i:s');
        $transaksi_dimulai = false;
        try {
            if (!mysqli_begin_transaction($koneksi)) {
                throw new RuntimeException('Transaksi tidak dapat dimulai.');
            }
            $transaksi_dimulai = true;

            // Kunci baris pakan, lalu cek stok
            $st = mysqli_prepare($koneksi, "SELECT stok_kg FROM pakan WHERE id_pakan = ? FOR UPDATE");
            mysqli_stmt_bind_param($st, 'i', $id_pakan);
            if (!mysqli_stmt_execute($st)) {
                throw new RuntimeException('Pemeriksaan stok gagal.');
            }
            $pakan = mysqli_fetch_assoc(mysqli_stmt_get_result($st));

            if (!$pakan) {
                throw new DomainException('Jenis pakan tidak ditemukan.');
            }
            if ($pakan['stok_kg'] < $jumlah) {
                throw new DomainException('Stok tidak cukup. Sisa stok: ' . number_format($pakan['stok_kg'], 2, ',', '.') . ' kg.');
            }

            $st = mysqli_prepare($koneksi, "SELECT id_kandang FROM kandang WHERE id_kandang = ?");
            mysqli_stmt_bind_param($st, 'i', $id_kandang);
            if (!mysqli_stmt_execute($st)) {
                throw new RuntimeException('Pemeriksaan kandang gagal.');
            }
            if (!mysqli_fetch_assoc(mysqli_stmt_get_result($st))) {
                throw new DomainException('Kandang tidak ditemukan.');
            }

            // Simpan log
            $st = mysqli_prepare($koneksi,
                "INSERT INTO log_pemberian_pakan (id_pakan, id_kandang, id_peternak, tgl_pemberian, jumlah_kg)
                 VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($st, 'iiisd', $id_pakan, $id_kandang, $id_peternak, $tgl, $jumlah);
            if (!mysqli_stmt_execute($st)) {
                throw new RuntimeException('Penyimpanan log gagal.');
            }

            // Kurangi stok
            $st = mysqli_prepare($koneksi, "UPDATE pakan SET stok_kg = stok_kg - ? WHERE id_pakan = ?");
            mysqli_stmt_bind_param($st, 'di', $jumlah, $id_pakan);
            if (!mysqli_stmt_execute($st)) {
                throw new RuntimeException('Pengurangan stok gagal.');
            }
            if (mysqli_stmt_affected_rows($st) !== 1) {
                throw new RuntimeException('Stok pakan tidak berhasil diperbarui.');
            }

            if (!mysqli_commit($koneksi)) {
                throw new RuntimeException('Transaksi tidak berhasil disimpan.');
            }
            header('Location: index.php?status=success&pesan=' . urlencode('Log pemberian pakan berhasil dicatat. Stok sudah dikurangi.'));
            exit;
        } catch (DomainException $e) {
            if ($transaksi_dimulai) mysqli_rollback($koneksi);
            $error = $e->getMessage();
        } catch (Throwable $e) {
            if ($transaksi_dimulai) mysqli_rollback($koneksi);
            error_log('Gagal menyimpan log pemberian pakan: ' . $e->getMessage());
            $error = 'Log gagal disimpan. Silakan coba lagi.';
        }
    }
}

// Data untuk dropdown
$list_pakan   = mysqli_query($koneksi, "SELECT id_pakan, nama_pakan, jenis_pakan, stok_kg FROM pakan ORDER BY nama_pakan");
if (!$list_pakan) {
    error_log('Gagal memuat daftar pakan: ' . mysqli_error($koneksi));
}
$list_kandang = mysqli_query($koneksi, "SELECT id_kandang, nama_kandang FROM kandang ORDER BY nama_kandang");
if (!$list_kandang) {
    error_log('Gagal memuat daftar kandang: ' . mysqli_error($koneksi));
}
if (!$list_pakan || !$list_kandang) {
    $error = 'Data pakan atau kandang gagal dimuat. Silakan coba lagi.';
}

include '../../views/header.php';
?>

<div>
    <h2 class="mb-4"><i class="bi bi-plus-circle me-2"></i>Catat Pemberian Pakan</h2>

    <?php if ($error): ?>
        <div class="alert alert-danger" role="alert">
            <i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i><?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($list_pakan && $list_kandang): ?>
    <div class="row justify-content-center">
        <div class="col-12 col-xl-9">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-clipboard2-plus me-2"></i>Form Pemberian Pakan</h5>
                </div>
                <div class="card-body p-3 p-md-4">
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="id_kandang" class="form-label">Kandang</label>
                                <select id="id_kandang" name="id_kandang" class="form-select" required>
                                    <option value="">-- Pilih kandang --</option>
                                    <?php while ($k = mysqli_fetch_assoc($list_kandang)): ?>
                                        <option value="<?= $k['id_kandang'] ?>"
                                            <?= (($_POST['id_kandang'] ?? '') == $k['id_kandang']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($k['nama_kandang']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="id_pakan" class="form-label">Jenis Pakan</label>
                                <select id="id_pakan" name="id_pakan" class="form-select" required>
                                    <option value="">-- Pilih pakan --</option>
                                    <?php while ($p = mysqli_fetch_assoc($list_pakan)): ?>
                                        <option value="<?= $p['id_pakan'] ?>"
                                            <?= (($_POST['id_pakan'] ?? '') == $p['id_pakan']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($p['nama_pakan']) ?> (<?= htmlspecialchars($p['jenis_pakan']) ?>)
                                            - stok <?= number_format($p['stok_kg'], 2, ',', '.') ?> kg
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="jumlah_kg" class="form-label">Jumlah (kg)</label>
                                <input id="jumlah_kg" type="number" name="jumlah_kg" step="0.01" min="0.01" max="9999.99" class="form-control" required
                                       value="<?= htmlspecialchars($_POST['jumlah_kg'] ?? '') ?>">
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="tgl_pemberian" class="form-label">Tanggal & Jam Pemberian</label>
                                <input id="tgl_pemberian" type="datetime-local" name="tgl_pemberian" class="form-control" required
                                       value="<?= htmlspecialchars($_POST['tgl_pemberian'] ?? date('Y-m-d\TH:i')) ?>">
                            </div>

                            <div class="col-12 d-flex flex-column flex-sm-row gap-2 pt-2">
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-check-circle me-1"></i>Simpan
                                </button>
                                <a href="index.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left me-1"></i>Batal
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include '../../views/footer.php'; ?>