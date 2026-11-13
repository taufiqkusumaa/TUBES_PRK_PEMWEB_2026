<?php
// Bagian admin pada halaman "Profil saya": kelola akun guru.
// Di-include oleh index.php hanya untuk admin. Logika ada di js/akun_guru.js.
?>
<style>
.akun-section { margin-top: 22px; }
.akun-section td small { color: var(--muted); }
.peran-admin { background: #e3edf8; color: #35628f; }
.peran-guru { background: #e1f0e5; color: #215c38; }
</style>
<section class="panel akun-section" id="akun-guru">
    <div class="section-title">
        <div>
            <p class="eyebrow">KHUSUS ADMIN</p>
            <h2>Kelola akun guru</h2>
        </div>
        <button id="akun-add" class="primary" type="button">+ Tambah akun</button>
    </div>
    <div class="table-scroll">
        <table>
            <thead>
                <tr><th>No.</th><th>Nama</th><th>NIP</th><th>Email</th><th>Peran</th><th>Aksi</th></tr>
            </thead>
            <tbody id="akun-body">
                <tr><td class="empty" colspan="6">Memuat akun…</td></tr>
            </tbody>
        </table>
    </div>
    <p class="table-note">Akun yang masih menjadi wali kelas, punya penugasan mengajar, atau punya catatan bimbingan tidak dapat dihapus.</p>
</section>
<dialog id="akun-dialog">
    <div class="dialog-heading">
        <h2 id="akun-dialog-title">Tambah akun</h2>
        <button type="button" id="akun-close" aria-label="Tutup formulir">×</button>
    </div>
    <form id="akun-form">
        <label>Nama lengkap<input name="nama_guru" required maxlength="100"></label>
        <label>NIP<input name="nip" required maxlength="50"></label>
        <label>Email<input name="email" type="email" required maxlength="100"></label>
        <label>Peran
            <select name="peran" required>
                <option value="guru">Guru</option>
                <option value="admin">Admin</option>
            </select>
        </label>
        <label><span id="akun-password-label">Password</span>
            <input name="password" type="password" minlength="8" maxlength="72" autocomplete="new-password">
        </label>
        <p id="akun-error" class="error-text" role="alert"></p>
        <div class="form-actions">
            <button id="akun-cancel" type="button">Batal</button>
            <button class="primary" type="submit">Simpan akun</button>
        </div>
    </form>
</dialog>
