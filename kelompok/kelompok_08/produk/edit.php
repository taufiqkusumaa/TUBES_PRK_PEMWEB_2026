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

$produk_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Kunci kepemilikan berdasarkan user_id petani yang login
$stmt_cek = mysqli_prepare($conn, "SELECT * FROM produk WHERE id = ? AND user_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt_cek, "ii", $produk_id, $user_id);
mysqli_stmt_execute($stmt_cek);
$produk = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_cek));

if (!$produk) {
    echo "<script>alert('Produk tidak ditemukan atau bukan milik akun Anda!'); window.location.href='index.php';</script>";
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
    $nama_foto   = $produk['foto'];

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $ext_diizinkan = ['jpg', 'jpeg', 'png', 'webp'];
        $ext           = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $ext_diizinkan)) {
            $error = "Format foto baru harus JPG, JPEG, PNG, atau WEBP.";
        } elseif ($_FILES['foto']['size'] > 2 * 1024 * 1024) {
            $error = "Ukuran foto baru maksimal 2 MB.";
        } else {
            $nama_file_baru = time() . '_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['foto']['tmp_name'], '../assets/' . $nama_file_baru)) {
                $file_bawaan = ['cabai.jpg', 'pisang.jpg', 'background.jpg'];
                if (!empty($produk['foto']) && !in_array($produk['foto'], $file_bawaan) && file_exists('../assets/' . $produk['foto'])) {
                    unlink('../assets/' . $produk['foto']);
                }
                $nama_foto = $nama_file_baru;
            } else {
                $error = "Gagal mengunggah foto baru.";
            }
        }
    }

    if (empty($error)) {
        $sql = "UPDATE produk 
                SET kategori_id = ?, nama_produk = ?, harga = ?, satuan = ?, stok = ?, deskripsi = ?, foto = ? 
                WHERE id = ? AND user_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "isdsissii", $kategori_id, $nama_produk, $harga, $satuan, $stok, $deskripsi, $nama_foto, $produk_id, $user_id);

        if (mysqli_stmt_execute($stmt)) {
            header("Location: index.php?status=sukses_edit");
            exit;
        } else {
            $error = "Gagal memperbarui data produk: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Produk - BuyPanen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4" style="max-width: 720px;">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-warning fw-bold">Edit Produk: <?= htmlspecialchars($produk['nama_produk']); ?></div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nama Produk</label>
                    <input type="text" name="nama_produk" class="form-control" value="<?= htmlspecialchars($produk['nama_produk']); ?>" required>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Kategori</label>
                        <select name="kategori_id" class="form-select" required>
                            <?php while ($kat = mysqli_fetch_assoc($kategori_res)): ?>
                                <option value="<?= $kat['id']; ?>" <?= ($kat['id'] == $produk['kategori_id']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($kat['nama_kategori']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Satuan</label>
                        <input type="text" name="satuan" class="form-control" value="<?= htmlspecialchars($produk['satuan']); ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Harga per Satuan (Rp)</label>
                        <input type="number" name="harga" class="form-control" min="100" value="<?= (int)$produk['harga']; ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Jumlah Stok</label>
                        <input type="number" name="stok" class="form-control" min="0" value="<?= (int)$produk['stok']; ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Deskripsi Produk</label>
                    <textarea name="deskripsi" rows="3" class="form-control" required><?= htmlspecialchars($produk['deskripsi']); ?></textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold d-block">Foto Produk Saat Ini</label>
                    <img src="../assets/<?= htmlspecialchars($produk['foto']); ?>" width="110" class="rounded border mb-2">
                    <input type="file" name="foto" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                    <small class="text-muted">Biarkan kosong jika tidak ingin mengganti foto produk.</small>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="index.php" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-warning fw-semibold">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>