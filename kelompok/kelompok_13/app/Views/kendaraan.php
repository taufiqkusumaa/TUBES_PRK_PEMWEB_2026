<?php
$pageTitle = 'Master Data Kendaraan';
require_once __DIR__ . '/layouts/header.php';
require_once __DIR__ . '/layouts/sidebar.php';
?>

<div id="kendaraan-app">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom"
         style="border-color: var(--border-color) !important;">
        <div>
            <h1 class="h5 fw-bold mb-1" style="color: var(--text-primary);">Master Data Kendaraan</h1>
            <p class="mb-0 small" style="color: var(--text-secondary);">
                Kelola kategori, kendaraan, dan pemilik kendaraan pelanggan.
            </p>
        </div>
        <button type="button" class="btn-royal mt-2 mt-sm-0" id="btnMuatKendaraan">Muat Ulang</button>
    </div>

    <div id="kendaraanAlert" aria-live="polite"></div>

    <section class="surface-card p-3 p-lg-4 mb-4" aria-labelledby="judulKategori">
        <h2 class="h6 fw-bold mb-3" id="judulKategori">Kategori Kendaraan</h2>
        <form id="formKategori" class="row g-3 mb-4">
            <input type="hidden" name="id">
            <div class="col-12 col-md-2">
                <label class="form-label" for="kategoriJenis">Jenis</label>
                <select class="form-select input-enterprise" id="kategoriJenis" name="jenis" required>
                    <option value="">Pilih jenis</option>
                    <option value="Roda 2">Roda 2</option>
                    <option value="Roda 4">Roda 4</option>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label" for="kategoriMerk">Merk</label>
                <input class="form-control input-enterprise" id="kategoriMerk" name="merk" maxlength="50" required>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label" for="kategoriTipe">Tipe</label>
                <input class="form-control input-enterprise" id="kategoriTipe" name="tipe" maxlength="50" required>
            </div>
            <div class="col-12 col-md-4 d-flex align-items-end gap-2">
                <button class="btn-royal" type="submit" id="btnSimpanKategori">Simpan Kategori</button>
                <button class="btn-surface d-none" type="button" id="btnBatalKategori">Batal</button>
                <button class="btn-surface" type="button" id="btnResetKategori">Kosongkan</button>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-enterprise align-middle mb-0">
                <thead><tr><th>Jenis</th><th>Merk</th><th>Tipe</th><th>Kendaraan</th><th class="text-end">Aksi</th></tr></thead>
                <tbody id="tbodyKategori"><tr><td colspan="5" class="text-center py-4">Memuat kategori...</td></tr></tbody>
            </table>
        </div>
    </section>

    <section class="surface-card p-3 p-lg-4 mb-4" aria-labelledby="judulFormKendaraan">
        <h2 class="h6 fw-bold mb-3" id="judulFormKendaraan">Tambah Kendaraan</h2>
        <form id="formKendaraan" class="row g-3">
            <input type="hidden" name="id">
            <div class="col-12 col-lg-4">
                <label class="form-label" for="kendaraanPemilik">Pemilik</label>
                <select class="form-select input-enterprise" id="kendaraanPemilik" name="user_id" required>
                    <option value="">Memuat pelanggan...</option>
                </select>
            </div>
            <div class="col-12 col-lg-4">
                <label class="form-label" for="kendaraanKategori">Kategori</label>
                <select class="form-select input-enterprise" id="kendaraanKategori" name="kategori_id" required>
                    <option value="">Memuat kategori...</option>
                </select>
            </div>
            <div class="col-12 col-md-4 col-lg-2">
                <label class="form-label" for="kendaraanNopol">Nomor polisi</label>
                <input class="form-control input-enterprise" id="kendaraanNopol" name="no_polisi" maxlength="15"
                       placeholder="BE 1234 ABC" required>
            </div>
            <div class="col-12 col-md-4 col-lg-2">
                <label class="form-label" for="kendaraanTahun">Tahun</label>
                <input class="form-control input-enterprise" id="kendaraanTahun" name="tahun_pembuatan"
                       type="number" min="1901" max="<?= (int) date('Y') + 1 ?>">
            </div>
            <div class="col-12 col-md-5">
                <label class="form-label" for="kendaraanModel">Model kendaraan</label>
                <input class="form-control input-enterprise" id="kendaraanModel" name="nama_model" maxlength="100" required>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label" for="kendaraanWarna">Warna</label>
                <input class="form-control input-enterprise" id="kendaraanWarna" name="warna" maxlength="30">
            </div>
            <div class="col-12 col-md-3 d-flex align-items-end gap-2">
                <button class="btn-royal" type="submit">Simpan Kendaraan</button>
                <button class="btn-surface d-none" type="button" id="btnBatalKendaraan">Batal</button>
                <button class="btn-surface" type="button" id="btnResetKendaraan">Kosongkan</button>
            </div>
        </form>
    </section>

    <section class="surface-card overflow-hidden" aria-labelledby="judulDaftarKendaraan">
        <div class="p-3 border-bottom" style="border-color: var(--border-color) !important;">
            <h2 class="h6 fw-bold mb-0" id="judulDaftarKendaraan">Daftar Kendaraan Terdaftar</h2>
        </div>
        <div class="table-responsive">
            <table class="table table-enterprise align-middle mb-0">
                <thead><tr><th>Nomor Polisi</th><th>Jenis</th><th>Merk / Tipe</th><th>Model / Tahun / Warna</th><th>Pemilik</th><th class="text-end">Aksi</th></tr></thead>
                <tbody id="tbodyKendaraan"><tr><td colspan="6" class="text-center py-4">Memuat kendaraan...</td></tr></tbody>
            </table>
        </div>
    </section>
</div>

<script src="assets/js/kendaraan.js"></script>
<?php require_once __DIR__ . '/layouts/footer.php'; ?>
