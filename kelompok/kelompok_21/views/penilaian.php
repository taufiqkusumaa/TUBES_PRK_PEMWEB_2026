<?php
$sesiKonteks = $_SESSION['konteks_penilaian'] ?? [];
$sesiKelas   = $sesiKonteks['id_kelas']   ?? '';
$sesiMapel   = $sesiKonteks['id_mapel']   ?? '';
$sesiPeriode = $sesiKonteks['id_periode'] ?? '';
?>
<div id="pn-root"
     data-kelas="<?= e($sesiKelas) ?>"
     data-mapel="<?= e($sesiMapel) ?>"
     data-periode="<?= e($sesiPeriode) ?>">

<div class="page-heading">
    <div>
        <p class="eyebrow">MANAJEMEN PENILAIAN TERPADU</p>
        <h1>Penilaian</h1>
        <p>Kelola Penilaian Tugas, Kuis, UTS, dan UAS.</p>
    </div>
    <button id="pn-add" class="primary" type="button" hidden>+ Tambah penilaian</button>
</div>

<section class="panel">
    <div class="table-toolbar" style="margin-bottom:0;flex-wrap:wrap">
        <div style="display:flex;gap:14px;flex-wrap:wrap;flex:1">
            <label class="search-label" style="max-width:220px;margin-bottom:0">Kelas
                <select id="pn-kelas"><option value="">Pilih kelas</option></select>
            </label>
            <label class="search-label" style="max-width:260px;margin-bottom:0">Mata pelajaran
                <select id="pn-mapel"><option value="">Pilih mata pelajaran</option></select>
            </label>
            <label class="search-label" style="max-width:240px;margin-bottom:0">Periode penilaian
                <select id="pn-periode"><option value="">Pilih periode</option></select>
            </label>
        </div>
        <span id="pn-count" class="pill">—</span>
    </div>
</section>

<section class="panel" id="pn-daftar-panel" style="margin-top:22px" hidden>
    <div class="section-title">
        <div>
            <p class="eyebrow">DAFTAR PENILAIAN</p>
            <h2>Komponen penilaian</h2>
        </div>
    </div>
    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>No.</th><th>Judul</th><th>Jenis</th><th>Tenggat waktu</th><th>Terisi</th><th>Rata-rata</th><th>Aksi</th>
                </tr>
            </thead>
            <tbody id="pn-body"></tbody>
        </table>
    </div>
    <p class="table-note">Gunakan "Input nilai" untuk mengisi atau mengubah nilai seluruh siswa pada kelas ini.</p>
</section>

<section class="panel" id="pn-nilai-panel" style="margin-top:22px" hidden>
    <div class="section-title">
        <div>
            <p class="eyebrow">INPUT NILAI</p>
            <h2 id="pn-nilai-title">Input nilai</h2>
        </div>
        <span class="pill">Rentang 0–100</span>
    </div>
    <form id="pn-nilai-form">
        <div class="table-scroll">
            <table>
                <thead>
                    <tr><th>No.</th><th>NISN</th><th>Nama siswa</th><th>Nilai</th></tr>
                </thead>
                <tbody id="pn-nilai-body"></tbody>
            </table>
        </div>
        <p id="pn-nilai-error" class="error-text" role="alert"></p>
        <div class="form-actions">
            <button type="button" id="pn-nilai-tutup">Tutup</button>
            <button type="submit" class="primary">Simpan nilai</button>
        </div>
    </form>
</section>

<section class="panel" id="pn-rekap-panel" style="margin-top:22px" hidden>
    <div class="section-title">
        <div>
            <p class="eyebrow">REKAP KELAS</p>
            <h2>Rata-rata nilai per komponen</h2>
        </div>
    </div>
    <div class="table-scroll">
        <table>
            <thead>
                <tr><th>No.</th><th>NISN</th><th>Nama siswa</th><th>Tugas</th><th>Kuis</th><th>UTS</th><th>UAS</th></tr>
            </thead>
            <tbody id="pn-rekap-body"></tbody>
        </table>
    </div>
</section>

<dialog id="pn-dialog">
    <div class="dialog-heading">
        <h2 id="pn-dialog-title">Tambah penilaian</h2>
        <button type="button" id="pn-dialog-x" aria-label="Tutup formulir">×</button>
    </div>
    <form id="pn-form">
        <div>
            <label>Judul penilaian
                <input name="judul_penilaian" required maxlength="100" placeholder="Contoh: Tugas Bab 1">
            </label>
            <label>Jenis komponen
                <select name="jenis_komponen" id="pn-jenis" required>
                    <option value="">Pilih jenis komponen</option>
                    <option value="Tugas">Tugas</option>
                    <option value="Kuis">Kuis</option>
                    <option value="UTS">UTS</option>
                    <option value="UAS">UAS</option>
                </select>
            </label>
            <label id="pn-tenggat-wrap" hidden>Tenggat waktu pengumpulan
                <input type="datetime-local" name="tenggat_waktu" id="pn-tenggat">
            </label>
        </div>
        <p id="pn-form-error" class="error-text" role="alert"></p>
        <div class="form-actions">
            <button type="button" id="pn-dialog-batal">Batal</button>
            <button type="submit" class="primary">Simpan data</button>
        </div>
    </form>
</dialog>

</div>