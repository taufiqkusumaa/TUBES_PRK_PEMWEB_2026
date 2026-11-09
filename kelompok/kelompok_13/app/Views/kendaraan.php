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
                Kelola kategori roda dua/empat, merk, tipe, dan data kendaraan bengkel.
            </p>
        </div>
        <button type="button" class="btn-royal mt-2 mt-sm-0" id="btnMuatKendaraan">
            <i class="bi bi-arrow-clockwise"></i> Muat Ulang
        </button>
    </div>

    <div id="kendaraanAlert" aria-live="polite"></div>

    <section class="surface-card p-3 p-lg-4 mb-4" aria-labelledby="judulKategori">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2 class="h6 fw-bold mb-1" id="judulKategori" style="color: var(--text-primary);">Kategori Kendaraan</h2>
                <p class="small mb-0" style="color: var(--text-secondary);">Jenis kendaraan, merk, dan tipe/model.</p>
            </div>
            <button type="button" class="btn-surface" id="btnResetKategori">Kategori Baru</button>
        </div>

        <form id="formKategori" class="row g-3 mb-4" novalidate>
            <input type="hidden" name="id">
            <div class="col-12 col-md-3">
                <label class="form-label small fw-semibold" for="kategoriJenis">Jenis</label>
                <select class="form-select input-enterprise" id="kategoriJenis" name="jenis" required>
                    <option value="">Pilih jenis</option>
                    <option value="Roda 2">Roda 2</option>
                    <option value="Roda 4">Roda 4</option>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small fw-semibold" for="kategoriMerk">Merk</label>
                <input class="form-control input-enterprise" id="kategoriMerk" name="merk" maxlength="80" required>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small fw-semibold" for="kategoriTipe">Tipe / Model</label>
                <input class="form-control input-enterprise" id="kategoriTipe" name="tipe" maxlength="100" required>
            </div>
            <div class="col-12 col-md-3 d-flex align-items-end gap-2">
                <button class="btn-royal" type="submit" id="btnSimpanKategori">Simpan Kategori</button>
                <button class="btn-surface d-none" type="button" id="btnBatalKategori">Batal</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-enterprise align-middle mb-0">
                <thead><tr><th>Jenis</th><th>Merk</th><th>Tipe</th><th>Kendaraan</th><th class="text-end">Aksi</th></tr></thead>
                <tbody id="tbodyKategori">
                    <tr><td colspan="5" class="text-center py-4">Memuat kategori...</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="surface-card p-3 p-lg-4" aria-labelledby="judulKendaraan">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h2 class="h6 fw-bold mb-1" id="judulKendaraan" style="color: var(--text-primary);">Data Kendaraan</h2>
                <p class="small mb-0" style="color: var(--text-secondary);">Nomor polisi harus unik dan terkait kategori kendaraan.</p>
            </div>
            <button type="button" class="btn-surface" id="btnResetKendaraan">Kendaraan Baru</button>
        </div>

        <form id="formKendaraan" class="row g-3 mb-4" novalidate>
            <input type="hidden" name="id">
            <div class="col-12 col-md-3">
                <label class="form-label small fw-semibold" for="kendaraanKategori">Kategori</label>
                <select class="form-select input-enterprise" id="kendaraanKategori" name="kategori_id" required>
                    <option value="">Pilih kategori</option>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small fw-semibold" for="kendaraanNopol">Nomor Polisi</label>
                <input class="form-control input-enterprise text-uppercase" id="kendaraanNopol" name="no_polisi"
                       maxlength="12" placeholder="BE 1234 AB" required>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small fw-semibold" for="kendaraanModel">Nama Model</label>
                <input class="form-control input-enterprise" id="kendaraanModel" name="nama_model" maxlength="100" required>
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label small fw-semibold" for="kendaraanWarna">Warna</label>
                <input class="form-control input-enterprise" id="kendaraanWarna" name="warna" maxlength="50">
            </div>
            <div class="col-12 col-md-1 d-flex align-items-end">
                <button class="btn-royal w-100" type="submit" aria-label="Simpan kendaraan"><i class="bi bi-check-lg"></i></button>
            </div>
            <div class="col-12">
                <button class="btn-surface d-none" type="button" id="btnBatalKendaraan">Batal edit</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-enterprise align-middle mb-0">
                <thead><tr><th>Nomor Polisi</th><th>Jenis</th><th>Merk / Tipe</th><th>Model / Warna</th><th class="text-end">Aksi</th></tr></thead>
                <tbody id="tbodyKendaraan">
                    <tr><td colspan="5" class="text-center py-4">Memuat kendaraan...</td></tr>
                </tbody>
            </table>
        </div>
    </section>
</div>

<script src="assets/js/kendaraan.js"></script>
<?php require_once __DIR__ . '/layouts/footer.php'; ?>