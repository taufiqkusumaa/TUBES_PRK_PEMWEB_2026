<?php
// Proteksi halaman khusus petani
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../config/database.php';

// Mengambil data produk dari database
$query = "SELECT
            p.id,
            p.nama_produk,
            p.harga,
            p.satuan,
            p.stok,
            p.deskripsi,
            p.foto,
            u.nama AS nama_petani,
            pt.lokasi_kebun,
            pt.no_whatsapp,
            k.nama_kategori
          FROM produk p
          JOIN users u ON p.user_id = u.id
          JOIN petani pt ON u.id = pt.user_id
          JOIN kategori k ON p.kategori_id = k.id
          WHERE p.status = 'tayang'
          AND p.stok > 0
          ORDER BY p.created_at DESC";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Gagal mengambil data produk: " . mysqli_error($conn));
}

// Mengambil kategori untuk filter
$query_kategori = mysqli_query(
    $conn,
    "SELECT * FROM kategori ORDER BY nama_kategori ASC"
);

$nama_petani = $_SESSION['nama'] ?? 'Petani';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard Petani - BUYPANEN</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <style>
        :root {
            --dark-green: #14532d;
            --green: #218838;
        }

        body {
            background: #f8faf8;
        }

        /* Navbar */
        .navbar {
            background-color: var(--green) !important;
        }

        .navbar-brand {
            font-weight: 700;
        }

        /* Hero */
        .hero-section {
            background:
                linear-gradient(
                    rgba(20, 83, 45, 0.55),
                    rgba(33, 136, 56, 0.65)
                ),
                url('../assets/background.jpg');

            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            padding: 65px 0;
            border-radius: 0 0 16px 16px;
        }

        /* Gambar produk */
        .img-produk {
            width: 100%;
            height: 200px;
            object-fit: cover;
            object-position: center;
        }

        /* Kartu produk */
        .card {
            border: none;
            transition: transform 0.3s ease,
                        box-shadow 0.3s ease;
            border-radius: 12px;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.12);
        }

        .btn-success {
            background-color: var(--green);
            border-color: var(--green);
        }

        .btn-success:hover {
            background-color: var(--dark-green);
        }

        .welcome-text {
            color: var(--dark-green);
        }

        .search-box {
            border-radius: 10px;
            padding: 12px;
        }

        footer {
            margin-top: 60px;
        }
    </style>
</head>

<body>

<!-- NAVBAR DASHBOARD PETANI -->

<nav class="navbar navbar-expand-lg navbar-dark sticky-top shadow-sm">

    <div class="container">

        <a class="navbar-brand" href="dashboard.php">
            🌾 BUYPANEN
        </a>

        <button class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navbarNav">

            <span class="navbar-toggler-icon"></span>

        </button>

        <div class="collapse navbar-collapse" id="navbarNav">

            <ul class="navbar-nav ms-auto align-items-lg-center">

                <li class="nav-item">
                    <a class="nav-link text-white" href="#hero">
                        Beranda
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link text-white" href="#katalog">
                        Katalog
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link text-white" href="../produk/index.php">
                        Produk
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link text-white"
                       href="../petani/profil.php">
                        profil
                    </a>
                </li>

                <li class="nav-item ms-lg-3 mt-2 mt-lg-0">
                    <a class="btn btn-light btn-sm px-3"
                       href="../config/logout.php"
                       onclick="return confirm('Yakin ingin logout?')">

                        <i class="bi bi-box-arrow-right"></i>
                        Logout

                    </a>
                </li>

            </ul>

        </div>

    </div>

</nav>

<!-- HERO BANNER -->

<section id="hero" class="hero-section text-white text-center">

    <div class="container py-4">

        <h1 class="display-4 fw-bold mb-3">
            Panen Segar Langsung dari Petani
        </h1>

        <p class="lead mb-3">
            Selamat datang,
            <strong><?= htmlspecialchars($nama_petani) ?></strong>!
        </p>

        <p class="mb-4">
            Kelola hasil panen dan temukan berbagai produk pertanian
            berkualitas di BUYPANEN.
        </p>

        <a href="#katalog"
           class="btn btn-warning btn-lg fw-semibold px-4 shadow">

            <i class="bi bi-basket"></i>
            Lihat Katalog

        </a>

    </div>

</section>

<!-- KATALOG PRODUK -->

<section id="katalog" class="container py-5">

    <div class="text-center mb-5">

        <h2 class="fw-bold">
            Katalog Hasil Panen
        </h2>

        <p class="text-muted">
            Pilih produk segar dan hubungi petaninya secara langsung.
        </p>

    </div>

    <!-- PENCARIAN DAN FILTER -->

    <div class="row mb-4">

        <div class="col-md-7 mb-2">

            <input type="text"
                   id="searchProduk"
                   class="form-control search-box"
                   placeholder="Cari nama produk atau petani...">

        </div>

        <div class="col-md-5 mb-2">

            <select id="filterKategori"
                    class="form-select search-box">

                <option value="">Semua Kategori</option>

                <?php while ($kategori = mysqli_fetch_assoc($query_kategori)): ?>

                    <option value="<?= htmlspecialchars($kategori['nama_kategori']) ?>">
                        <?= htmlspecialchars($kategori['nama_kategori']) ?>
                    </option>

                <?php endwhile; ?>

            </select>

        </div>

    </div>

    <!-- DAFTAR PRODUK -->

    <div class="row g-4" id="daftarProduk">

        <?php if (mysqli_num_rows($result) > 0): ?>

            <?php while ($row = mysqli_fetch_assoc($result)): ?>

                <?php
                $foto = !empty($row['foto'])
                    ? '../' . ltrim($row['foto'], '/')
                    : 'https://placehold.co/600x400/eaf5e9/218838?text=Produk+BUYPANEN';

                $nomor_wa = preg_replace(
                    '/\D/',
                    '',
                    $row['no_whatsapp']
                );

                // Ubah format 08 menjadi 628
                if (substr($nomor_wa, 0, 1) === '0') {
                    $nomor_wa = '62' . substr($nomor_wa, 1);
                }

                $pesan_wa = "Halo " . $row['nama_petani'] .
                    ", saya tertarik membeli " . $row['nama_produk'] .
                    " yang tersedia di BUYPANEN.";

                $link_wa = "https://wa.me/" . $nomor_wa .
                    "?text=" . rawurlencode($pesan_wa);
                ?>

                <div class="col-lg-3 col-md-4 col-sm-6 item-produk"
                     data-nama="<?= htmlspecialchars(strtolower($row['nama_produk'] . ' ' . $row['nama_petani'])) ?>"
                     data-kategori="<?= htmlspecialchars($row['nama_kategori']) ?>">

                    <div class="card h-100 shadow-sm">

                        <img
                            src="<?= htmlspecialchars($foto) ?>"
                            class="img-produk"
                            alt="<?= htmlspecialchars($row['nama_produk']) ?>"
                            onerror="this.src='https://placehold.co/600x400/eaf5e9/218838?text=Produk+BUYPANEN'"
                        >

                        <div class="card-body d-flex flex-column">

                            <div class="mb-2">

                                <span class="badge bg-success">
                                    <?= htmlspecialchars($row['nama_kategori']) ?>
                                </span>

                            </div>

                            <h5 class="card-title fw-bold">
                                <?= htmlspecialchars($row['nama_produk']) ?>
                            </h5>

                            <p class="text-success fw-bold fs-5 mb-1">
                                Rp <?= number_format($row['harga'], 0, ',', '.') ?>

                                <small class="text-muted fs-6">
                                    / <?= htmlspecialchars($row['satuan']) ?>
                                </small>
                            </p>

                            <p class="text-muted small mb-2">
                                Stok:
                                <?= htmlspecialchars($row['stok']) ?>
                                <?= htmlspecialchars($row['satuan']) ?>
                            </p>

                            <hr class="my-2">

                            <p class="small text-secondary mb-1">
                                <i class="bi bi-person-fill"></i>
                                <?= htmlspecialchars($row['nama_petani']) ?>
                            </p>

                            <p class="small text-secondary mb-3">
                                <i class="bi bi-geo-alt-fill"></i>
                                <?= htmlspecialchars($row['lokasi_kebun']) ?>
                            </p>

                            <a href="<?= htmlspecialchars($link_wa) ?>"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="btn btn-outline-success w-100 mt-auto">

                                <i class="bi bi-whatsapp"></i>
                                Hubungi Petani

                            </a>

                        </div>

                    </div>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="col-12 text-center py-5">

                <i class="bi bi-basket display-3 text-muted"></i>

                <h5 class="mt-3">
                    Belum Ada Produk
                </h5>

                <p class="text-muted">
                    Saat ini belum ada produk yang tersedia di katalog.
                </p>

            </div>

        <?php endif; ?>

    </div>

    <div id="produkTidakDitemukan"
         class="text-center py-5"
         style="display:none;">

        <h5>Produk tidak ditemukan.</h5>

        <p class="text-muted">
            Coba gunakan kata kunci pencarian lainnya.
        </p>

    </div>

</section>

<!-- FOOTER -->

<footer class="bg-dark text-white py-4">

    <div class="container text-center">

        <p class="mb-0">
            &copy; 2026 BUYPANEN -
            Tugas Besar Pemrograman Web Kelompok 08
        </p>

    </div>

</footer>

<!-- JAVASCRIPT -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>

// Fitur pencarian dan filter produk
const searchProduk = document.getElementById('searchProduk');
const filterKategori = document.getElementById('filterKategori');

const semuaProduk = document.querySelectorAll('.item-produk');
const pesanKosong = document.getElementById('produkTidakDitemukan');

function filterProduk() {

    const keyword = searchProduk.value.toLowerCase().trim();
    const kategori = filterKategori.value.toLowerCase();

    let jumlahTampil = 0;

    semuaProduk.forEach(function(produk) {

        const nama = produk.dataset.nama.toLowerCase();
        const kategoriProduk = produk.dataset.kategori.toLowerCase();

        const cocokNama = nama.includes(keyword);
        const cocokKategori = kategori === '' ||
                              kategoriProduk === kategori;

        if (cocokNama && cocokKategori) {

            produk.style.display = '';

            jumlahTampil++;

        } else {

            produk.style.display = 'none';

        }

    });

    pesanKosong.style.display =
        jumlahTampil === 0 ? 'block' : 'none';

}

searchProduk.addEventListener('input', filterProduk);
filterKategori.addEventListener('change', filterProduk);

</script>

</body>
</html>
