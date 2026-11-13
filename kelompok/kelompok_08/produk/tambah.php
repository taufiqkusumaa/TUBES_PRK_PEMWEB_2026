<?php
require_once '../config/database.php';
require_once '../middleware/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
$role    = $_SESSION['role'] ?? '';

if (!$user_id || $role !== 'petani') {
    header("Location: ../auth/login.php");
    exit;
}

$kategori_res = mysqli_query($conn, "SELECT id, nama_kategori FROM kategori ORDER BY nama_kategori ASC");
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_produk = trim($_POST['nama_produk']);
    $kategori_id = (int)$_POST['kategori_id'];
    $harga       = (float)$_POST['harga'];
    $satuan      = trim($_POST['satuan']);
    $stok        = (int)$_POST['stok'];
    $deskripsi   = trim($_POST['deskripsi']);

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $ext_diizinkan = ['jpg', 'jpeg', 'png', 'webp'];
        $ext           = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $ext_diizinkan)) {
            $error = "Format foto harus JPG, JPEG, PNG, atau WEBP.";
        } elseif ($_FILES['foto']['size'] > 2 * 1024 * 1024) {
            $error = "Ukuran foto maksimal 2 MB.";
        } else {
            $nama_file_baru = time() . '_' . uniqid() . '.' . $ext;
            $tujuan_upload  = '../assets/' . $nama_file_baru;

            if (move_uploaded_file($_FILES['foto']['tmp_name'], $tujuan_upload)) {
                $sql = "INSERT INTO produk (user_id, kategori_id, nama_produk, harga, satuan, stok, deskripsi, foto) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "iisdsiss", $user_id, $kategori_id, $nama_produk, $harga, $satuan, $stok, $deskripsi, $nama_file_baru);

                if (mysqli_stmt_execute($stmt)) {
                    header("Location: index.php?status=sukses_tambah");
                    exit;
                } else {
                    $error = "Gagal menyimpan data produk: " . mysqli_error($conn);
                }
            } else {
                $error = "Gagal mengunggah foto ke folder assets.";
            }
        }
    } else {
        $error = "Foto produk wajib diunggah.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Produk - BuyPanen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4" style="max-width: 720px;">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-success text-white fw-bold">Tambah Produk Hasil Panen</div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nama Produk</label>
                    <input type="text" name="nama_produk" class="form-control" placeholder="Contoh: Cabai Merah Keriting" required>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Kategori</label>
                        <select name="kategori_id" class="form-select" required>
                            <option value="">-- Pilih Kategori --</option>
                            <?php while ($kat = mysqli_fetch_assoc($kategori_res)): ?>
                                <option value="<?= $kat['id']; ?>"><?= htmlspecialchars($kat['nama_kategori']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Satuan</label>
                        <input type="text" name="satuan" class="form-control" placeholder="Contoh: kg, ikat, sisir, karung" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Harga per Satuan (Rp)</label>
                        <input type="number" name="harga" class="form-control" min="100" placeholder="25000" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Jumlah Stok</label>
                        <input type="number" name="stok" class="form-control" min="0" placeholder="50" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Deskripsi Produk</label>
                    <textarea name="deskripsi" rows="3" class="form-control" placeholder="Jelaskan kualitas hasil panen, waktu petik, atau info pemesanan..." required></textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Foto Produk (JPG/PNG/WEBP, Maks. 2MB)</label>
                    <input type="file" name="foto" class="form-control" accept=".jpg,.jpeg,.png,.webp" required>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="index.php" class="btn btn-secondary">Kembali</a>
                    <button type="submit" class="btn btn-success fw-semibold">Simpan Produk</button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>