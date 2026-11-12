<?php
$pageTitle = 'Paket Layanan Servis';
require_once __DIR__ . '/layouts/header.php';
require_once __DIR__ . '/layouts/sidebar.php';
?>

<div id="layanan-app">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom"
         style="border-color: var(--border-color) !important;">
        <div>
            <h1 class="h5 fw-bold mb-1" style="color: var(--text-primary);">Paket Layanan Servis</h1>
            <p class="mb-0 small" style="color: var(--text-secondary);">
                Atur jenis servis mesin, tarif dasar, dan estimasi durasi pengerjaan.
            </p>
        </div>
        <button type="button" class="btn-royal mt-2 mt-sm-0" id="btnMuatLayanan">
            <i class="bi bi-arrow-clockwise"></i> Muat Ulang
        </button>
    </div>

    <div id="layananAlert" aria-live="polite"></div>

    <section class="surface-card p-3 p-lg-4 mb-4" aria-labelledby="judulFormLayanan">
        <h2 class="h6 fw-bold mb-3" id="judulFormLayanan" style="color: var(--text-primary);">Tambah Paket Layanan</h2>
        <form id="formLayanan" class="row g-3" novalidate>
            <input type="hidden" name="id">
            <div class="col-12 col-md-6 col-lg-3">
                <label class="form-label small fw-semibold" for="kategoriLayanan">Kategori kendaraan</label>
                <select class="form-select input-enterprise" id="kategoriLayanan" name="kategori_id" required>
                    <option value="">Memuat kategori kendaraan...</option>
                </select>
            </div>
            <div class="col-12 col-lg-4">
                <label class="form-label small fw-semibold" for="namaPaket">Nama Paket</label>
                <input class="form-control input-enterprise" id="namaPaket" name="nama_paket" maxlength="120"
                       placeholder="Contoh: Tune Up Injeksi" required>
            </div>
            <div class="col-12 col-md-5 col-lg-2">
                <label class="form-label small fw-semibold" for="tarifDasar">Tarif Dasar (Rp)</label>
                <input class="form-control input-enterprise" id="tarifDasar" name="tarif_dasar"
                       type="number" min="0" step="1000" required>
            </div>
            <div class="col-12 col-md-5 col-lg-1">
                <label class="form-label small fw-semibold" for="durasiServis">Estimasi (menit)</label>
                <input class="form-control input-enterprise" id="durasiServis" name="estimasi_durasi_menit"
                       type="number" min="1" max="10080" step="1" required>
            </div>
            <div class="col-12 col-md-2 col-lg-2 d-flex align-items-end gap-2">
                <button class="btn-royal" type="submit" id="btnSimpanLayanan">Simpan</button>
                <button class="btn-surface d-none" type="button" id="btnBatalLayanan">Batal</button>
            </div>
        </form>
    </section>

    <section class="surface-card overflow-hidden" aria-labelledby="judulDaftarLayanan">
        <div class="p-3 border-bottom" style="border-color: var(--border-color) !important;">
            <h2 class="h6 fw-bold mb-0" id="judulDaftarLayanan" style="color: var(--text-primary);">Daftar Paket Servis</h2>
        </div>
        <div class="table-responsive">
            <table class="table table-enterprise align-middle mb-0">
                <thead><tr><th>Nama Paket</th><th>Kategori Kendaraan</th><th>Tarif Dasar</th><th>Estimasi Durasi</th><th class="text-end">Aksi</th></tr></thead>
                <tbody id="tbodyLayanan">
                        <tr><td colspan="5" class="text-center py-4">Memuat paket layanan...</td></tr>
                </tbody>
            </table>
        </div>
    </section>
</div>

<script src="assets/js/layanan.js"></script>
<?php require_once __DIR__ . '/layouts/footer.php'; ?>