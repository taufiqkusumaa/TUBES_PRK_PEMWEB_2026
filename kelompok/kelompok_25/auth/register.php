<?php
require_once __DIR__ . '/../config/app.php';

if (is_logged_in()) {
    redirect(dashboard_path(current_user()['role']));
}
if (!auth_skema_siap()) {
    auth_halaman_skema_belum_siap();
}

$pdo = db();
$bidang = $pdo->query('SELECT id, nama FROM bidang_hukum ORDER BY nama')->fetchAll();
$galat = [];
$in = ['role' => 'klien', 'nama' => '', 'username' => '', 'perusahaan' => '', 'telepon' => '', 'email' => '', 'bidang_id' => 0, 'no_izin' => ''];

// Tautan "Gabung sebagai Pengacara" di footer membuka formulir ini dengan peran pengacara sudah terpilih.
if (($_GET['role'] ?? '') === 'pengacara') {
    $in['role'] = 'pengacara';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $in['role']       =($_POST['role'] ?? 'klien') === 'pengacara' ? 'pengacara' : 'klien';
    $in['nama']       = trim((string) ($_POST['nama'] ?? ''));
    $in['username']   = mb_strtolower(trim((string) ($_POST['username'] ?? '')));
    $in['perusahaan'] = trim((string) ($_POST['perusahaan'] ?? ''));
    $in['telepon']    = trim((string) ($_POST['telepon'] ?? ''));
    $in['email']      = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
    $in['bidang_id']  = (int) ($_POST['bidang_id'] ?? 0);
    $in['no_izin']    = trim((string) ($_POST['no_izin'] ?? ''));
    $pass  = (string) ($_POST['password'] ?? '');
    $pass2 = (string) ($_POST['password2'] ?? '');
    $setuju = isset($_POST['setuju']);

    if (!csrf_valid()) {
        $galat['_'] = 'Sesi formulir tidak valid. Muat ulang halaman.';
    }
    if (mb_strlen($in['nama']) < 3 || mb_strlen($in['nama']) > 100) {
        $galat['nama'] = 'Nama lengkap 3 sampai 100 karakter.';
    }
    if ($m = auth_cek_username($in['username'])) {
        $galat['username'] = $m;
    } elseif (auth_username_terpakai($in['username'])) {
        $galat['username'] = 'Username ini sudah dipakai.';
    }
    if (mb_strlen($in['perusahaan']) > 120) {
        $galat['perusahaan'] = 'Nama perusahaan maksimal 120 karakter.';
    }
    $telepon = auth_normalisasi_telepon($in['telepon']);
    if ($telepon === null) {
        $galat['telepon'] = 'Nomor handphone tidak valid. Contoh: 081234567890 atau +6281234567890.';
    }
    if (!filter_var($in['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($in['email']) > 120) {
        $galat['email'] = 'Format email tidak valid.';
    } elseif (auth_email_terpakai($in['email'])) {
        $galat['email'] = 'Email sudah terdaftar. Coba masuk, atau gunakan "Lupa kata sandi".';
    }
    if ($m = auth_cek_sandi($pass)) {
        $galat['password'] = $m;
    }
    if ($pass !== $pass2) {
        $galat['password2'] = 'Konfirmasi kata sandi tidak sama.';
    }
    if ($in['role'] === 'pengacara') {
        $ada = $pdo->prepare('SELECT COUNT(*) FROM bidang_hukum WHERE id = ?');
        $ada->execute([$in['bidang_id']]);
        if ((int) $ada->fetchColumn() === 0) {
            $galat['bidang_id'] = 'Pilih bidang keahlian.';
        }
        if ($in['no_izin'] === '' || mb_strlen($in['no_izin']) > 60) {
            $galat['no_izin'] = 'Nomor izin advokat wajib diisi (maksimal 60 karakter).';
        }
    }
    if (!$setuju) {
        $galat['setuju'] = 'Kamu perlu menyetujui Syarat Penggunaan Layanan untuk membuat akun.';
    }

    if (!$galat) {
        $status = $in['role'] === 'pengacara' ? 'menunggu' : 'aktif';
        try {
            $pdo->beginTransaction();
            $pdo->prepare('INSERT INTO users (nama, username, email, password, role, perusahaan, telepon, status, terms_at) VALUES (?,?,?,?,?,?,?,?,?)')
                ->execute([$in['nama'], $in['username'], $in['email'], password_hash($pass, PASSWORD_DEFAULT), $in['role'],
                           $in['perusahaan'] !== '' ? $in['perusahaan'] : null, $telepon, $status, auth_sekarang()]);
            $baru = (int) $pdo->lastInsertId();
            if ($in['role'] === 'pengacara') {
                $pdo->prepare('INSERT INTO profil_pengacara (user_id, bidang_id, no_izin) VALUES (?,?,?)')
                    ->execute([$baru, $in['bidang_id'], $in['no_izin']]);
            }
            $pdo->commit();
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            if ($ex->getCode() !== '23000') { throw $ex; }
            $galat['_'] = 'Username atau email baru saja dipakai orang lain. Coba yang lain.';
        }
        if (!$galat) {
            if ($in['role'] === 'pengacara') {
                flash('success', 'Pendaftaran berhasil. Akunmu akan aktif setelah diverifikasi admin.');
                redirect('auth/login.php');
            }
            $st = $pdo->prepare('SELECT * FROM users WHERE id = ?');
            $st->execute([$baru]);
            auth_masuk($st->fetch(), false);
            flash('success', 'Akun berhasil dibuat. Selamat datang, ' . $in['nama'] . '!');
            redirect('klien/dashboard.php');
        }
    }
}

$judul = 'Daftar';
$tanpaKerangka = true;   // halaman penuh tanpa navbar/footer situs
require_once __DIR__ . '/../includes/header.php';

/** Satu kolom isian dengan ikon dan pesan galat dari server. */
function kolom(string $id, string $label, string $ikon, array $in, array $galat, array $atr = []): void
{
    $tipe = $atr['type'] ?? 'text';
    unset($atr['type']);
    $extra = '';
    foreach ($atr as $k => $v) { $extra .= ' ' . $k . '="' . e($v) . '"'; }
    $nilai = $in[$id] ?? '';
    ?>
    <label class="form-label" for="<?= e($id) ?>"><?= e($label) ?></label>
    <div class="input-group">
      <span class="input-group-text"><i class="bi <?= e($ikon) ?>"></i></span>
      <input type="<?= e($tipe) ?>" class="form-control <?= isset($galat[$id]) ? 'is-invalid' : '' ?>" id="<?= e($id) ?>" name="<?= e($id) ?>" value="<?= e($nilai) ?>"<?= $extra ?>>
    </div>
    <?php if (isset($galat[$id])): ?><div class="invalid-feedback d-block"><?= e($galat[$id]) ?></div><?php endif;
}
?>
<link rel="stylesheet" href="<?= e(url('assets/css/auth.css')) ?>">
<div class="auth-layar">
  <?php auth_panel('Mulai konsultasi hukummu hari ini.', [
      ['bi-lightning-charge', 'Daftar gratis dalam hitungan menit'],
      ['bi-patch-check', 'Pilih pengacara terverifikasi sesuai bidangmu'],
      ['bi-clock-history', 'Riwayat konsultasi tersimpan di dashboard'],
  ]); ?>

  <div class="auth-sisi">
    <div class="auth-atas"><?= auth_logo('d-lg-none') ?><?= auth_kembali() ?></div>

      <section class="auth-isi auth-lebar">
        <h1 class="auth-judul">Mendaftar Akun</h1>
        <p class="text-secondary mb-4">Buat akun untuk berkonsultasi dengan pengacara, atau bergabung sebagai pengacara.</p>

        <?php if (isset($galat['_'])): ?><div class="alert alert-danger py-2" role="alert"><?= e($galat['_']) ?></div><?php endif; ?>

        <form method="post" novalidate id="formDaftar">
          <?= csrf_field() ?>
          <div class="mb-3">
            <span class="form-label d-block">Daftar sebagai</span>
            <div class="btn-group w-100" role="group" aria-label="Peran">
              <input type="radio" class="btn-check" name="role" id="r-klien" value="klien" <?= $in['role'] !== 'pengacara' ? 'checked' : '' ?>>
              <label class="btn btn-outline-secondary" for="r-klien"><i class="bi bi-person"></i> Klien</label>
              <input type="radio" class="btn-check" name="role" id="r-pengacara" value="pengacara" <?= $in['role'] === 'pengacara' ? 'checked' : '' ?>>
              <label class="btn btn-outline-secondary" for="r-pengacara"><i class="bi bi-briefcase"></i> Pengacara</label>
            </div>
          </div>

          <div class="row g-3">
            <div class="col-md-6"><?php kolom('nama', 'Nama Lengkap', 'bi-person-vcard', $in, $galat, ['maxlength' => 100, 'required' => 'required', 'autocomplete' => 'name', 'data-pesan-wajib' => 'Nama lengkap wajib diisi.']); ?></div>
            <div class="col-md-6">
              <?php kolom('username', 'Username', 'bi-at', $in, $galat, ['maxlength' => 30, 'required' => 'required', 'autocomplete' => 'username', 'data-cek-username' => url('auth/cek_username.php'), 'data-pesan-wajib' => 'Username wajib diisi.']); ?>
              <div class="form-text" id="infoUsername">4-30 karakter: huruf kecil, angka, titik, garis bawah.</div>
            </div>
            <div class="col-md-6"><?php kolom('perusahaan', 'Perusahaan / Instansi (opsional)', 'bi-building', $in, $galat, ['maxlength' => 120, 'autocomplete' => 'organization']); ?></div>
            <div class="col-md-6"><?php kolom('telepon', 'Nomor Handphone', 'bi-phone', $in, $galat, ['type' => 'tel', 'maxlength' => 20, 'required' => 'required', 'autocomplete' => 'tel', 'inputmode' => 'tel', 'placeholder' => '0812 3456 7890', 'data-telepon' => '1', 'data-pesan-wajib' => 'Nomor handphone wajib diisi.']); ?></div>
            <div class="col-12"><?php kolom('email', 'Alamat Email', 'bi-envelope', $in, $galat, ['type' => 'email', 'maxlength' => 120, 'required' => 'required', 'autocomplete' => 'email', 'data-pesan-wajib' => 'Alamat email wajib diisi.']); ?></div>
            <div class="col-md-6">
              <?php auth_input_sandi('password', 'password', 'Kata Sandi', ['autocomplete' => 'new-password', 'minlength' => 8, 'galat' => $galat['password'] ?? '']); ?>
              <div class="meter-sandi" data-meter-untuk="password"><div class="batang"><span></span></div><small class="teks">Minimal 8 karakter, memuat huruf dan angka.</small></div>
            </div>
            <div class="col-md-6"><?php auth_input_sandi('password2', 'password2', 'Konfirmasi Kata Sandi', ['autocomplete' => 'new-password', 'galat' => $galat['password2'] ?? '']); ?></div>
          </div>

          <div id="kolom-pengacara" class="kotak-pengacara mt-3" <?= $in['role'] === 'pengacara' ? '' : 'hidden' ?>>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label" for="bidang_id">Bidang keahlian</label>
                <select class="form-select <?= isset($galat['bidang_id']) ? 'is-invalid' : '' ?>" id="bidang_id" name="bidang_id" <?= $in['role'] === 'pengacara' ? 'required' : 'disabled' ?>>
                  <option value="">Pilih bidang</option>
                  <?php foreach ($bidang as $b): ?>
                    <option value="<?= (int) $b['id'] ?>" <?= $in['bidang_id'] === (int) $b['id'] ? 'selected' : '' ?>><?= e($b['nama']) ?></option>
                  <?php endforeach; ?>
                </select>
                <?php if (isset($galat['bidang_id'])): ?><div class="invalid-feedback d-block"><?= e($galat['bidang_id']) ?></div><?php endif; ?>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="no_izin">Nomor izin advokat</label>
                <input class="form-control <?= isset($galat['no_izin']) ? 'is-invalid' : '' ?>" id="no_izin" name="no_izin" maxlength="60" value="<?= e($in['no_izin']) ?>" <?= $in['role'] === 'pengacara' ? 'required' : 'disabled' ?>>
                <?php if (isset($galat['no_izin'])): ?><div class="invalid-feedback d-block"><?= e($galat['no_izin']) ?></div><?php endif; ?>
              </div>
              <div class="col-12 form-text mt-2">Akun pengacara aktif setelah diverifikasi admin.</div>
            </div>
          </div>

          <div class="form-check mt-4">
            <input class="form-check-input <?= isset($galat['setuju']) ? 'is-invalid' : '' ?>" type="checkbox" id="setuju" name="setuju" value="1" required
                   data-pesan-wajib="Centang persetujuan Syarat Penggunaan Layanan untuk melanjutkan." <?= isset($_POST['setuju']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="setuju">Dengan membuat akun ini, saya menyetujui <a href="<?= e(url('syarat.php')) ?>" target="_blank" rel="noopener">Syarat Penggunaan Layanan</a> Ruang Hukum.</label>
            <?php if (isset($galat['setuju'])): ?><div class="invalid-feedback d-block"><?= e($galat['setuju']) ?></div><?php endif; ?>
          </div>

          <button class="btn btn-navy w-100 py-2 mt-4" type="submit" data-memproses="Membuat akun...">Daftar</button>
        </form>

        <?= auth_sosial_blok('Atau daftar dengan') ?>
        <?php if (auth_sosial_daftar()): ?><p class="text-center text-secondary small mt-2 mb-0">Pendaftaran lewat media sosial khusus untuk klien.</p><?php endif; ?>

        <p class="text-center mt-4 mb-0 small">Sudah punya akun? <a href="<?= e(url('auth/login.php')) ?>">Login</a></p>
      </section>
  </div>
</div>
<script src="<?= e(url('assets/js/auth.js')) ?>"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
