
<?php
require_once __DIR__ . '/../config/database.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Produk tidak ditemukan.");
}

$id = (int) $_GET['id'];

$query = "SELECT 
            p.*,
            k.nama_kategori,
            u.nama AS nama_petani,
            pt.no_whatsapp,
            pt.lokasi_kebun
          FROM produk p
          JOIN kategori k ON p.kategori_id = k.id
          JOIN petani pt ON p.user_id = pt.user_id
          JOIN users u ON pt.user_id = u.id
          WHERE p.id = ? AND p.status = 'tayang'";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$produk = mysqli_fetch_assoc($result);

if (!$produk) {
    die("Produk tidak ditemukan.");
}

$whatsapp = preg_replace('/\D/', '', $produk['no_whatsapp']);

if (substr($whatsapp, 0, 1) === '0') {
    $whatsapp = '62' . substr($whatsapp, 1);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Produk - BuyPanen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container py-5">

    <a href="../index.php" class="btn btn-outline-secondary mb-4">
        &larr; Kembali ke Katalog
    </a>

    <div class="row g-4">

        <div class="col-md-5">
            <?php if (!empty($produk['foto'])): ?>
                <img
                    src="../<?= htmlspecialchars($produk['foto']) ?>"
                    class="img-fluid rounded"
                    alt="<?= htmlspecialchars($produk['nama_produk']) ?>"
                >
            <?php else: ?>
                <div class="bg-light rounded d-flex align-items-center justify-content-center"
                     style="height: 300px;">
                    Foto produk belum tersedia
                </div>
            <?php endif; ?>
        </div>

        <div class="col-md-7">

            <span class="badge bg-success">
                <?= htmlspecialchars($produk['nama_kategori']) ?>
            </span>

            <h2 class="mt-3">
                <?= htmlspecialchars($produk['nama_produk']) ?>
            </h2>

            <h4 class="text-success">
                Rp <?= number_format($produk['harga'], 0, ',', '.') ?>
                / <?= htmlspecialchars($produk['satuan']) ?>
            </h4>

            <p>
                Stok tersedia:
                <strong><?= htmlspecialchars($produk['stok']) ?>
                <?= htmlspecialchars($produk['satuan']) ?></strong>
            </p>

            <hr>

            <h5>Deskripsi Produk</h5>
            <p>
                <?= nl2br(htmlspecialchars(
                    $produk['deskripsi'] ?: 'Belum ada deskripsi produk.'
                )) ?>
            </p>

            <hr>

            <h5>Informasi Petani</h5>
            <p>
                Nama: <?= htmlspecialchars($produk['nama_petani']) ?>
            </p>

            <p>
                Lokasi kebun:
                <?= htmlspecialchars($produk['lokasi_kebun']) ?>
            </p>

            <a
                href="https://wa.me/<?= htmlspecialchars($whatsapp) ?>"
                target="_blank"
                rel="noopener noreferrer"
                class="btn btn-success"
            >
                Hubungi Petani via WhatsApp
            </a>

        </div>
    </div>

</div>

</body>
</html>
