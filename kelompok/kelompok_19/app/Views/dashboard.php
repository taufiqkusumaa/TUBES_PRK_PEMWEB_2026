<?php
// app/Views/dashboard.php
// Beranda: sapaan, actions cepat, hal yang perlu ditindak, ringkasan, lalu siklus terdekat.
$title = "Dashboard";
require_once __DIR__ . "/layouts/header.php";
?>
<header class="page-header">
    <h1><?= htmlspecialchars($sapaan) ?>, Tim Kebun.</h1>
    <p class="top-label">Beranda · <time datetime="<?= date('Y-m-d') ?>"><?= htmlspecialchars($tanggalPanjang) ?></time></p>
    <p class="intro">Ini yang perlu dilihat dari kebun hari ini. Angka diambil dari catatan lahan, siklus, inventaris, pekerja, dan panen.</p>
</header>

<section class="quick-actions" aria-labelledby="label-quick-actions">
    <h2 id="label-quick-actions">Aksi cepat</h2>
    <ul>
        <li>
            <a href="/panen">
                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 10h16l-1.5 10h-13z"/><path d="M8 10V8a4 4 0 0 1 8 0v2"/><path d="M12 14v4M10 16h4"/></svg>
                <span>Catat panen</span>
                <small>Berat dan tanggal hasil petik</small>
            </a>
        </li>
        <li>
            <a href="/inventaris">
                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 8.5 12 4l8 4.5v7L12 20l-8-4.5z"/><path d="m4 8.5 8 4.5 8-4.5"/><path d="M12 13v7"/></svg>
                <span>Catat stok keluar</span>
                <small>Pemakaian bibit, pupuk, pestisida</small>
            </a>
        </li>
        <li>
            <a href="/pekerja">
                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.6 2.9-6 6.5-6s6.5 2.4 6.5 6"/><path d="M16 5.5a3.5 3.5 0 0 1 0 7"/><path d="M18 14.5c2.4.6 4 2.6 4 5.5"/></svg>
                <span>Atur pekerja</span>
                <small>Data pekerja dan ringkasan upah</small>
            </a>
        </li>
        <li>
            <a href="/lahan">
                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 20h18"/><path d="M5 20V9l7-5 7 5v11"/><path d="M9 20v-6h6v6"/><path d="M12 4v4"/></svg>
                <span>Tambah petak</span>
                <small>Catat lahan baru dan luasnya</small>
            </a>
        </li>
    </ul>
</section>

<section class="section" aria-labelledby="label-perhatian">
    <h2 class="section-title" id="label-perhatian">Perlu perhatian hari ini</h2>
    <ul class="attention-list" id="daftarStokMenipis" aria-live="polite">
        <li class="list-empty">Memeriksa stok dan jadwal panen...</li>
    </ul>
</section>

<section class="section" aria-labelledby="label-ringkasan">
    <h2 class="section-title" id="label-ringkasan">Ringkasan kebun</h2>
    <dl class="stats" id="ringkasan-stats">
        <dt>Total lahan</dt>
        <dd><span class="numeric" id="stat-lahan">0</span> <span class="row-meta">petak tercatat</span></dd>
        <dt>Siklus aktif</dt>
        <dd><span class="numeric" id="stat-siklus">0</span> <span class="row-meta">sedang ditanam</span></dd>
        <dt>Item inventaris</dt>
        <dd><span class="numeric" id="stat-inventaris">0</span> <span class="row-meta">jenis barang</span></dd>
        <dt>Panen tercatat</dt>
        <dd><span class="numeric" id="stat-panen">0</span> <span class="row-meta">kg total</span></dd>
    </dl>
</section>

<section class="section" aria-labelledby="label-siklus-terdekat">
    <h2 class="section-title" id="label-siklus-terdekat">
        Siklus terdekat panen
        <a class="section-link" href="/siklus">Lihat semua siklus</a>
    </h2>
    <ul class="list" id="daftarSiklusTerdekat" aria-live="polite">
        <li class="list-empty">Memuat siklus terdekat...</li>
    </ul>
</section>

<section class="section" aria-labelledby="label-presensi">
    <h2 class="section-title" id="label-presensi">
        Upah pekerja
        <a class="section-link" href="/pekerja">Buka data pekerja</a>
    </h2>
    <dl class="stats stats--two">
        <dt>Upah tercatat</dt>
        <dd><span class="numeric" id="totalUpahDashboard">Rp 0</span> <span class="row-meta">dari presensi</span></dd>
        <dt>Pekerja harian</dt>
        <dd><span class="numeric" id="jumlahPekerjaDashboard">0</span> <span class="row-meta">orang terdaftar</span></dd>
    </dl>
</section>

<aside class="weather" aria-labelledby="label-weather">
    <h2 class="weather-title" id="label-weather">Cuaca sekarang</h2>
    <p id="weather-widget" aria-live="polite">Memuat data cuaca...</p>
</aside>

<script src="/assets/js/dashboard.js"></script>
<?php
require_once __DIR__ . "/layouts/footer.php";
