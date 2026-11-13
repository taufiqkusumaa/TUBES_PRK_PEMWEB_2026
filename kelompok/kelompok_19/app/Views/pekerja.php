<?php
// app/Views/pekerja.php
// CRUD Pekerja Harian: ringkasan upah berkonteks, tabel lega, satu dialog.
// Diisi oleh : Devi Novita

$title = "Manajemen Pekerja";
require_once __DIR__ . "/layouts/header.php";
?>
<header class="page-header">
    <h1 id="judul-pekerja">Pekerja kebun</h1>
    <p class="top-label">Catatan tenaga kerja · <time datetime="<?= date('Y-m-d') ?>"><?= htmlspecialchars($tanggalPanjang) ?></time></p>
    <p class="intro">Nama, peran, dan upah harian. Angka upah di bawah dihitung dari presensi yang sudah tercatat di sistem.</p>
    <p class="actions">
        <button class="btn" type="button" data-modal-open="#modalPekerja">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
            Tambah pekerja
        </button>
    </p>
</header>

<section class="ringkasan-upah" aria-labelledby="label-total-upah">
    <h2 id="label-total-upah">Upah tercatat</h2>
    <p class="nilai numeric" id="totalUpahPekerja">Rp 0</p>
    <p>Dihitung dari presensi yang sudah masuk. <span id="jumlahPekerja">0</span> pekerja terdaftar.</p>
</section>

<div class="table-wrap">
    <table id="tabelPekerja">
        <caption class="sr-only">Daftar pekerja harian beserta jabatan dan upah</caption>
        <thead>
            <tr>
                <th scope="col">No</th>
                <th scope="col">Nama</th>
                <th scope="col">No. telepon</th>
                <th scope="col">Jabatan</th>
                <th scope="col">Upah harian</th>
                <th scope="col">Tanggal bergabung</th>
                <th scope="col">Aksi</th>
            </tr>
        </thead>
        <tbody id="tabelPekerjaBody">
            <tr><td colspan="7" class="text-center">Memuat data pekerja...</td></tr>
        </tbody>
    </table>
</div>

<section class="section" id="bagianPresensi" aria-labelledby="label-presensi">
    <h2 class="section-title" id="label-presensi">
        <span>Presensi pekerja</span>
        <button class="btn" type="button" id="btnTambahPresensi">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
            Catat presensi
        </button>
    </h2>
    <p class="intro">Kehadiran harian tiap pekerja. Dicatat langsung lewat sistem, tanpa akun pengguna. Upah yang tersimpan di sini yang dijumlahkan pada angka di atas.</p>

    <section class="filter-panel" aria-labelledby="label-filter-presensi">
        <h2 id="label-filter-presensi">Filter presensi</h2>
        <fieldset class="filter-grid filter-grid--presensi">
            <legend class="sr-only">Filter presensi pekerja</legend>
            <div class="form-group">
                <label for="filterPresensiTanggal">Tanggal</label>
                <input type="date" class="form-control" id="filterPresensiTanggal">
            </div>
            <div class="form-group">
                <label for="filterPresensiPekerja">Pekerja</label>
                <select class="form-select" id="filterPresensiPekerja">
                    <option value="">Semua pekerja</option>
                </select>
            </div>
            <div class="form-group">
                <label for="filterPresensiSiklus">Siklus tanam</label>
                <select class="form-select" id="filterPresensiSiklus">
                    <option value="">Semua siklus</option>
                </select>
            </div>
            <p class="filter-buttons">
                <button class="btn btn--secondary" type="button" id="btnResetPresensi">Reset</button>
                <button class="btn" type="button" id="btnTerapkanPresensi">Terapkan</button>
            </p>
        </fieldset>
    </section>

    <div class="table-wrap">
        <table id="tabelPresensi">
            <caption class="sr-only">Daftar presensi pekerja beserta siklus tanam dan upah harian</caption>
            <thead>
                <tr>
                    <th scope="col">No</th>
                    <th scope="col">Tanggal</th>
                    <th scope="col">Pekerja</th>
                    <th scope="col">Jabatan</th>
                    <th scope="col">Siklus</th>
                    <th scope="col">Upah</th>
                    <th scope="col">Aksi</th>
                </tr>
            </thead>
            <tbody id="tabelPresensiBody">
                <tr><td colspan="7" class="text-center">Memuat data presensi...</td></tr>
            </tbody>
        </table>
    </div>
</section>

<dialog class="modal" id="modalPresensi" aria-labelledby="modalPresensiTitle">
    <div class="modal-box">
        <div class="modal-header">
            <h2 class="modal-title" id="modalPresensiTitle">Catat presensi</h2>
            <button class="btn-close" type="button" data-modal-close aria-label="Tutup">&times;</button>
        </div>
        <form id="formPresensi" class="modal-form">
            <input type="hidden" name="id" id="presensiId">
            <p id="presensiError" class="form-error" role="alert" hidden></p>
            <fieldset class="form-grid">
                <legend class="sr-only">Data presensi pekerja</legend>
                <div class="form-group">
                    <label for="presensiPekerja">Pekerja</label>
                    <select class="form-select" name="pekerja_id" id="presensiPekerja" required>
                        <option value="">Pilih pekerja...</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="presensiTanggal">Tanggal</label>
                    <input type="date" class="form-control" name="tanggal" id="presensiTanggal" required>
                </div>
                <div class="form-group">
                    <label for="presensiSiklus">Siklus tanam</label>
                    <select class="form-select" name="siklus_id" id="presensiSiklus" required>
                        <option value="">Pilih siklus...</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="presensiUpah">Upah harian (Rp)</label>
                    <input type="number" class="form-control" name="upah_harian" id="presensiUpah" inputmode="numeric" min="0" step="0.01" required>
                    <span class="form-hint">Terisi otomatis dari upah pekerja, bisa diubah.</span>
                </div>
            </fieldset>
        </form>
        <div class="modal-footer">
            <button class="btn btn--secondary" type="button" data-modal-close>Batal</button>
            <button class="btn" type="submit" form="formPresensi">Simpan presensi</button>
        </div>
    </div>
</dialog>

<dialog class="modal" id="modalPekerja" aria-labelledby="modalPekerjaTitle">
    <div class="modal-box">
        <div class="modal-header">
            <h2 class="modal-title" id="modalPekerjaTitle">Tambah Pekerja</h2>
            <button class="btn-close" type="button" data-modal-close aria-label="Tutup">&times;</button>
        </div>
        <form id="formPekerja" class="modal-form">
            <input type="hidden" name="id" id="pekerjaId">
            <p id="pekerjaError" class="form-error" role="alert" hidden></p>
            <div class="form-group">
                <label for="pekerjaNama">Nama pekerja</label>
                <input type="text" class="form-control" name="nama" id="pekerjaNama" autocomplete="name" required>
            </div>
            <div class="form-group">
                <label for="pekerjaNoTelepon">No. telepon</label>
                <input type="tel" class="form-control" name="no_telepon" id="pekerjaNoTelepon" inputmode="tel" autocomplete="tel" required>
                <span class="form-hint">Contoh: 081234567890.</span>
            </div>
            <div class="form-group">
                <label for="pekerjaJabatan">Jabatan / peran</label>
                <select class="form-select" name="jabatan" id="pekerjaJabatan" required>
                    <option value="">Pilih jabatan...</option>
                    <option value="Kepala Kebun">Kepala Kebun</option>
                    <option value="Petani">Petani</option>
                    <option value="Operator Mesin">Operator Mesin</option>
                </select>
            </div>
            <div class="form-group">
                <label for="pekerjaGajiPokok">Upah harian (Rp)</label>
                <input type="number" class="form-control" name="gaji_pokok" id="pekerjaGajiPokok" inputmode="numeric" required min="0" step="0.01">
                <span class="form-hint">Contoh: 85000.</span>
            </div>
            <div class="form-group">
                <label for="pekerjaTanggalBergabung">Tanggal bergabung</label>
                <input type="date" class="form-control" name="tanggal_bergabung" id="pekerjaTanggalBergabung" required>
            </div>
        </form>
        <div class="modal-footer">
            <button class="btn btn--secondary" type="button" data-modal-close>Batal</button>
            <button class="btn" type="submit" form="formPekerja">Simpan pekerja</button>
        </div>
    </div>
</dialog>

<script src="/assets/js/pekerja.js"></script>
<?php
require_once __DIR__ . "/layouts/footer.php";
?>

