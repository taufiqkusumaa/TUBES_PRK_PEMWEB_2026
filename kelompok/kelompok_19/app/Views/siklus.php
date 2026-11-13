<?php
// app/Views/siklus.php
// CRUD Siklus Tanam: satu tabel, satu dialog. Progres memakai <progress> dan urutan fase.
$title = "Manajemen Siklus Tanam";
require_once __DIR__ . "/layouts/header.php";
?>
<header class="page-header">
    <h1 id="judul-siklus">Siklus tanam</h1>
    <p class="top-label">Catatan siklus · <time datetime="<?= date('Y-m-d') ?>"><?= htmlspecialchars($tanggalPanjang) ?></time></p>
    <p class="intro">Dari pembibitan sampai panen. Tanggal tanam dan estimasi panen menentukan sisa hari serta status siap panen.</p>
    <p class="actions">
        <button class="btn" type="button" data-modal-open="#modalSiklus">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
            Tambah siklus
        </button>
    </p>
</header>

<section class="pita-siap-panen" id="pitaSiapPanen" aria-live="polite" hidden>
    <span id="teksSiapPanen"></span>
    <a href="/panen">Catat panen</a>
</section>

<section class="filter-panel" aria-labelledby="label-filter-siklus">
    <h2 id="label-filter-siklus">Filter siklus</h2>
    <fieldset class="filter-grid">
        <legend class="sr-only">Filter siklus tanam</legend>
        <div class="form-group">
            <label for="filterSiklusQ">Pencarian</label>
            <input type="text" class="form-control" id="filterSiklusQ" placeholder="Nama tanaman atau lokasi...">
        </div>
        <div class="form-group">
            <label for="filterSiklusStatus">Status</label>
            <select class="form-select" id="filterSiklusStatus">
                <option value="">Semua status</option>
                <option value="Aktif">Aktif</option>
                <option value="Panen">Panen</option>
                <option value="Selesai">Selesai</option>
                <option value="Gagal">Gagal</option>
            </select>
        </div>
        <div class="form-group">
            <label for="filterSiklusDari">Dari tanggal</label>
            <input type="date" class="form-control" id="filterSiklusDari">
        </div>
        <div class="form-group">
            <label for="filterSiklusSampai">Sampai tanggal</label>
            <input type="date" class="form-control" id="filterSiklusSampai">
        </div>
        <div class="form-group">
            <label for="filterSiklusLahan">Petak / lokasi</label>
            <select class="form-select" id="filterSiklusLahan">
                <option value="">Semua petak</option>
            </select>
        </div>
        <p class="filter-buttons">
            <button class="btn btn--secondary" type="button" id="btnResetSiklus">Reset</button>
            <button class="btn" type="button" id="btnTerapkanSiklus">Terapkan</button>
        </p>
    </fieldset>
</section>

<div class="table-wrap">
    <table id="tabelSiklus">
        <caption class="sr-only">Daftar siklus tanam per petak, fase, dan progres menuju panen</caption>
        <thead>
            <tr>
                <th scope="col">No</th>
                <th scope="col">Petak &amp; tanaman</th>
                <th scope="col">Fase saat ini</th>
                <th scope="col">Progres</th>
                <th scope="col">Estimasi panen</th>
                <th scope="col">Status</th>
                <th scope="col">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <tr><td colspan="7" class="text-center">Memuat data siklus...</td></tr>
        </tbody>
    </table>
</div>

<dialog class="modal" id="modalSiklus" aria-labelledby="modalSiklusTitle">
    <div class="modal-box">
        <div class="modal-header">
            <h2 class="modal-title" id="modalSiklusTitle">Tambah Siklus</h2>
            <button class="btn-close" type="button" data-modal-close aria-label="Tutup">&times;</button>
        </div>
        <form id="formSiklus" class="modal-form">
            <input type="hidden" name="id" id="siklusId">
            <p id="siklusError" class="form-error" role="alert" hidden></p>
            <div class="form-group">
                <label for="siklusNamaTanaman">Nama tanaman</label>
                <input type="text" class="form-control" name="nama_tanaman" id="siklusNamaTanaman" maxlength="100" autocomplete="off" required>
                <span class="form-hint">Contoh: Cabai rawit, tomat cherry.</span>
            </div>
            <div class="form-group">
                <label for="siklusLahanId">Petak lahan</label>
                <select class="form-select" name="lahan_id" id="siklusLahanId" required>
                    <option value="">Pilih lahan...</option>
                </select>
            </div>
            <div class="form-group">
                <label for="siklusTanggalTanam">Tanggal tanam</label>
                <input type="date" class="form-control" name="tanggal_tanam" id="siklusTanggalTanam" required>
            </div>
            <div class="form-group">
                <label for="siklusTanggalPanenEstimasi">Perkiraan tanggal panen</label>
                <input type="date" class="form-control" name="tanggal_panen_estimasi" id="siklusTanggalPanenEstimasi">
                <span class="form-hint">Boleh dikosongkan bila belum tahu.</span>
            </div>
            <div class="form-group">
                <label for="siklusStatus">Status</label>
                <select class="form-select" name="status" id="siklusStatus" required>
                    <option value="Aktif">Aktif</option>
                    <option value="Panen">Panen</option>
                    <option value="Selesai">Selesai</option>
                    <option value="Gagal">Gagal</option>
                </select>
            </div>
        </form>
        <div class="modal-footer">
            <button class="btn btn--secondary" type="button" data-modal-close>Batal</button>
            <button class="btn" type="submit" form="formSiklus">Simpan siklus</button>
        </div>
    </div>
</dialog>

<script src="/assets/js/siklus.js"></script>
<?php
require_once __DIR__ . "/layouts/footer.php";
?>

