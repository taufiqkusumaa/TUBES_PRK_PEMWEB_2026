<?php
// Tampilan halaman Catatan Kualitatif & Bimbingan (Nova).
// Di-include oleh index.php saat ?halaman=kualitatif. Logika ada di js/kualitatif.js.
?>
<style>
textarea {
    display: block;
    width: 100%;
    min-height: 84px;
    padding: 11px 12px;
    border: 1px solid #d9e1d6;
    border-radius: 7px;
    background: white;
    color: var(--text);
    margin-top: 7px;
    resize: vertical;
}
textarea:focus {
    outline: 2px solid #90b6a0;
    border-color: var(--green);
}
#catatan-dialog {
    width: min(560px, calc(100% - 30px));
}
.catatan-filter {
    display: flex;
    align-items: flex-end;
    gap: 14px;
    margin-bottom: 14px;
}
.catatan-filter label {
    flex: 1;
    max-width: 320px;
    margin: 0;
}
.catatan-detail {
    margin-top: 22px;
}
.catatan-rekomendasi {
    background: var(--mint);
    border-radius: 10px;
    padding: 14px 16px;
    margin-bottom: 16px;
    font-size: 13px;
}
.catatan-rekomendasi p {
    display: block;
    margin: 6px 0 0;
}
.catatan-card {
    border: 1px solid var(--line);
    border-radius: 12px;
    padding: 16px 18px;
    margin-bottom: 12px;
}
.catatan-card-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 10px;
}
.catatan-card-head small {
    display: block;
    color: var(--muted);
    margin-top: 3px;
}
.catatan-field {
    margin-bottom: 10px;
}
.catatan-field small {
    display: block;
    color: var(--muted);
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .04em;
}
.catatan-field p {
    display: block;
    margin: 3px 0 0;
    white-space: pre-line;
    font-size: 13px;
}
.status-belum { background: #fdf3d9; color: #8a6a1c; }
.status-berjalan { background: #e3edf8; color: #35628f; }
.status-selesai { background: #e1f0e5; color: #215c38; }
</style>
<div class="page-heading">
    <div>
        <p class="eyebrow">PENDAMPINGAN SISWA</p>
        <h1>Catatan kualitatif &amp; bimbingan</h1>
        <p>Catat interaksi, rencana tindakan, dan perkembangan siswa dari waktu ke waktu.</p>
    </div>
</div>
<section class="panel">
    <div class="catatan-filter">
        <label>Kelas
            <select id="catatan-kelas"><option value="">Memuat kelas…</option></select>
        </label>
        <span id="catatan-count" class="pill">—</span>
    </div>
    <div class="table-scroll">
        <table>
            <thead>
                <tr><th>No.</th><th>NISN</th><th>Nama siswa</th><th>Catatan</th><th>Belum selesai</th><th>Interaksi terakhir</th><th>Aksi</th></tr>
            </thead>
            <tbody id="catatan-siswa-body">
                <tr><td class="empty" colspan="7">Pilih kelas terlebih dahulu.</td></tr>
            </tbody>
        </table>
    </div>
</section>
<section class="panel catatan-detail" id="catatan-detail" hidden>
    <div class="section-title">
        <div>
            <p class="eyebrow">RIWAYAT BIMBINGAN</p>
            <h2 id="catatan-nama">—</h2>
        </div>
        <button id="catatan-add" class="primary" type="button">+ Tambah catatan</button>
    </div>
    <div id="catatan-rekomendasi" class="catatan-rekomendasi" hidden></div>
    <div id="catatan-list"></div>
</section>
<dialog id="catatan-dialog">
    <div class="dialog-heading">
        <h2 id="catatan-dialog-title">Tambah catatan</h2>
        <button type="button" id="catatan-close" aria-label="Tutup formulir">×</button>
    </div>
    <form id="catatan-form">
        <label>Tanggal interaksi<input type="datetime-local" name="tanggal_interaksi" required></label>
        <label>Catatan<textarea name="catatan_kualitatif" required maxlength="2000" placeholder="Apa yang diamati atau dibicarakan dengan siswa?"></textarea></label>
        <label>Rencana tindakan (opsional)<textarea name="rencana_tindakan" maxlength="1000" placeholder="Langkah yang akan dilakukan"></textarea></label>
        <label>Status tindak lanjut
            <select name="status_tindak_lanjut" required>
                <option value="Belum ditindaklanjuti">Belum ditindaklanjuti</option>
                <option value="Sedang berjalan">Sedang berjalan</option>
                <option value="Selesai">Selesai</option>
            </select>
        </label>
        <label>Evaluasi hasil (wajib jika status Selesai)<textarea name="evaluasi_hasil" maxlength="1000" placeholder="Apakah tindakan membantu perkembangan siswa?"></textarea></label>
        <p id="catatan-error" class="error-text" role="alert"></p>
        <div class="form-actions">
            <button id="catatan-cancel" type="button">Batal</button>
            <button class="primary" type="submit">Simpan catatan</button>
        </div>
    </form>
</dialog>
