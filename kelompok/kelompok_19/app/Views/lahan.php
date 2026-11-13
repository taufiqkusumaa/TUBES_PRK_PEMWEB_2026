<?php
// app/Views/lahan.php
// CRUD Lahan: satu tabel, satu dialog. ID yang dipakai JavaScript dipertahankan.
$title = "Manajemen Lahan";
require_once __DIR__ . "/layouts/header.php";
?>
<header class="page-header">
    <h1 id="judul-lahan">Petak lahan</h1>
    <p class="top-label">Catatan lahan · <time datetime="<?= date('Y-m-d') ?>"><?= htmlspecialchars($tanggalPanjang) ?></time></p>
    <p class="intro">Luas, lokasi, dan jenis tanah tiap petak. Nama petak dipakai lagi saat mencatat siklus tanam.</p>
    <p class="actions">
        <button class="btn" type="button" data-modal-open="#modalLahan">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
            Tambah petak
        </button>
    </p>
</header>

<div class="table-wrap">
    <table id="tabelLahan">
        <caption class="sr-only">Daftar petak lahan beserta lokasi, luas, dan jenis tanah</caption>
        <thead>
            <tr>
                <th scope="col">No</th>
                <th scope="col">Nama petak</th>
                <th scope="col">Lokasi</th>
                <th scope="col">Luas (m²)</th>
                <th scope="col">Jenis tanah</th>
                <th scope="col">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <tr id="placeholderLahan"><td colspan="6" class="text-center">Memuat data lahan...</td></tr>
        </tbody>
    </table>
</div>

<dialog class="modal" id="modalLahan" aria-labelledby="modalLahanTitle">
    <div class="modal-box">
        <div class="modal-header">
            <h2 class="modal-title" id="modalLahanTitle">Tambah Petak</h2>
            <button class="btn-close" type="button" data-modal-close aria-label="Tutup">&times;</button>
        </div>
        <form id="formLahan" class="modal-form">
            <input type="hidden" name="id" id="lahanId">
            <div class="form-group">
                <label for="lahanNama">Nama petak</label>
                <input type="text" class="form-control" name="nama" id="lahanNama" autocomplete="off" required>
                <span class="form-hint">Contoh: Petak Utara, Bedeng 2.</span>
            </div>
            <div class="form-group">
                <label for="lahanLokasi">Lokasi</label>
                <input type="text" class="form-control" name="lokasi" id="lahanLokasi" autocomplete="off" required>
                <span class="form-hint">Contoh: Cisarua, belakang gudang.</span>
            </div>
            <div class="form-group">
                <label for="lahanLuas">Luas (m²)</label>
                <input type="number" class="form-control" name="luas" id="lahanLuas" inputmode="decimal" required min="0" step="0.01">
                <span class="form-hint">Tulis angka saja, contoh 450.</span>
            </div>
            <div class="form-group">
                <label for="lahanJenisTanah">Jenis tanah</label>
                <select class="form-select" name="jenis_tanah" id="lahanJenisTanah" required>
                    <option value="">Pilih jenis tanah...</option>
                    <option value="Aluvial">Aluvial</option>
                    <option value="Andosol">Andosol</option>
                    <option value="Latosol">Latosol</option>
                    <option value="Grumosol">Grumosol</option>
                    <option value="Regosol">Regosol</option>
                    <option value="Lainnya">Lainnya</option>
                </select>
            </div>
        </form>
        <div class="modal-footer">
            <button class="btn btn--secondary" type="button" data-modal-close>Batal</button>
            <button class="btn" type="submit" form="formLahan" id="btnSimpanLahan">Simpan petak</button>
        </div>
    </div>
</dialog>

<script src="/assets/js/lahan.js"></script>
<?php
require_once __DIR__ . "/layouts/footer.php";
?>

