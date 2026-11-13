
<?php
require_once __DIR__ . '/../config/database.php';

// Mengambil kata pencarian
$keyword = isset($_GET['search']) ? trim($_GET['search']) : '';

// Mengambil kategori
$kategori = isset($_GET['kategori']) ? $_GET['kategori'] : '';

// Mengambil daftar kategori
$queryKategori = "SELECT * FROM kategori ORDER BY nama_kategori ASC";
$resultKategori = mysqli_query($conn, $queryKategori);

// Query produk
$query = "SELECT
            p.*,
            k.nama_kategori,
            u.nama AS nama_petani,
            pt.lokasi_kebun,
            pt.no_whatsapp
          FROM produk p
          JOIN kategori k ON p.kategori_id = k.id
          JOIN petani pt ON p.user_id = pt.user_id
          JOIN users u ON pt.user_id = u.id
          WHERE p.status = 'tayang'";

// Filter pencarian
if ($keyword !== '') {
    $keywordAman = mysqli_real_escape_string($conn, $keyword);
    $query .= " AND p.nama_produk LIKE '%$keywordAman%'";
}

// Filter kategori
if ($kategori !== '') {
    $kategoriAman = (int) $kategori;
    $query .= " AND p.kategori_id = $kategoriAman";
}

$query .= " ORDER BY p.created_at DESC";

$resultProduk = mysqli_query($conn, $query);

if (!$resultProduk) {
    die("Gagal mengambil data produk: " . mysqli_error($conn));
}
?>

<div class="container">

    <!-- Form Pencarian dan Filter -->
    <form action="index.php#katalog" method="GET" class="row g-3 mb-4">

        <div class="col-md-6">
            <input
                type="text"
                name="search"
                class="form-control"
                placeholder="Cari nama hasil panen..."
                value="<?= htmlspecialchars($keyword) ?>"
            >
        </div>

        <div class="col-md-4">
            <select name="kategori" class="form-select">
                <option value="">Semua Kategori</option>

                <?php while ($kat = mysqli_fetch_assoc($resultKategori)): ?>
                    <option
                        value="<?= $kat['id'] ?>"
                        <?= ($kategori == $kat['id']) ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars($kat['nama_kategori']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="col-md-2">
            <button type="submit" class="btn btn-success w-100">
                Cari
            </button>
        </div>

    </form>

    <!-- Hasil Produk -->
    <div class="row g-4">

        <?php if (mysqli_num_rows($resultProduk) > 0): ?>

            <?php while ($produk = mysqli_fetch_assoc($resultProduk)): ?>

                <?php
                $whatsapp = preg_replace('/\D/', '', $produk['no_whatsapp']);

                if (substr($whatsapp, 0, 1) === '0') {
                    $whatsapp = '62' . substr($whatsapp, 1);
                }
                ?>

                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="card h-100 shadow-sm border-0">

                        <img
                            src="../<?= htmlspecialchars($produk['foto']) ?>"
                            class="img-produk"
                            alt="<?= htmlspecialchars($produk['nama_produk']) ?>"
                        >

                        <div class="card-body d-flex flex-column">

                            <div class="mb-2">
                                <span class="badge bg-success">
                                    <?= htmlspecialchars($produk['nama_kategori']) ?>
                                </span>
                            </div>

                            <h5 class="card-title fw-bold">
                                <?= htmlspecialchars($produk['nama_produk']) ?>
                            </h5>

                            <p class="text-success fw-bold fs-5 mb-1">
                                Rp <?= number_format($produk['harga'], 0, ',', '.') ?>
                                <small class="text-muted fs-6">
                                    / <?= htmlspecialchars($produk['satuan']) ?>
                                </small>
                            </p>

                            <p class="text-muted small mb-2">
                                Stok: <?= htmlspecialchars($produk['stok']) ?>
                                <?= htmlspecialchars($produk['satuan']) ?>
                            </p>

                            <hr class="my-2">

                            <p class="small text-secondary mb-1">
                                <i class="bi bi-person-fill"></i>
                                <?= htmlspecialchars($produk['nama_petani']) ?>
                            </p>

                            <p class="small text-secondary mb-3">
                                <i class="bi bi-geo-alt-fill"></i>
                                <?= htmlspecialchars($produk['lokasi_kebun']) ?>
                            </p>

                            <a
                                href="https://wa.me/<?= $whatsapp ?>"
                                target="_blank"
                                class="btn btn-outline-success w-100 mt-auto mb-2"
                            >
                                <i class="bi bi-whatsapp"></i> Hubungi Petani
                            </a>

                            <a
                                href="produk/detail-produk.php?id=<?= $produk['id'] ?>"
                                class="btn btn-outline-secondary w-100"
                            >
                                Lihat Detail
                            </a>

                        </div>
                    </div>
                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="col-12 text-center text-muted py-4">
             
            </div>

        <?php endif; ?>

    </div>
</div>
