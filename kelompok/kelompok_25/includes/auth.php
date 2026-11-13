<?php
// Fungsi bantu fitur masuk & daftar: login dengan username/email, daftar, "Ingat akun saya",
// lupa kata sandi, dan login media sosial. Dipakai oleh semua halaman di folder auth/.
// Semua data disimpan di database (users, remember_tokens, password_resets, social_accounts, login_gagal).

const AUTH_COOKIE_INGAT = 'rh_ingat';
// Hash palsu: dipakai agar waktu proses sama baik akunnya ada maupun tidak (mencegah penebakan akun lewat waktu).
const AUTH_HASH_PALSU = '$2y$10$shJbSJUZBihgcVM9YP47UuQYSwn6kwqtQ5rjH2dm3OF1qdIcYY0vC';

// ------------------------------------------------------------------ umum

/** Pengaturan dari config/auth.php, ditimpa config/auth.local.php bila ada. */
function auth_config(): array
{
    static $cfg = null;
    if ($cfg === null) {
        $cfg = require __DIR__ . '/../config/auth.php';
        $lokal = __DIR__ . '/../config/auth.local.php';
        if (is_file($lokal)) {
            $cfg = array_replace_recursive($cfg, (array) require $lokal);
        }
    }
    return $cfg;
}

/** Waktu sekarang (zona waktu PHP) dalam format DATETIME. Dipakai untuk semua kolom waktu fitur ini. */
function auth_sekarang(int $tambahDetik = 0): string
{
    return date('Y-m-d H:i:s', time() + $tambahDetik);
}

function auth_ip(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
}

function auth_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
}

/** Alamat lengkap (https://host/...) untuk redirect OAuth dan tautan reset. */
function auth_url_mutlak(string $path): string
{
    $dasar = rtrim((string) (auth_config()['base_publik'] ?? ''), '/');
    if ($dasar === '') {
        $dasar = (auth_https() ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    }
    return $dasar . url($path);
}

/** True bila tabel/kolom baru sudah ada (migrasi sudah dijalankan). */
function auth_skema_siap(): bool
{
    static $siap = null;
    if ($siap === null) {
        try {
            $pdo = db();
            $pdo->query('SELECT username, perusahaan, telepon, terms_at, last_login_at FROM users LIMIT 1')->fetchAll();
            foreach (['remember_tokens', 'password_resets', 'social_accounts', 'login_gagal'] as $t) {
                $pdo->query('SELECT 1 FROM ' . $t . ' LIMIT 1')->fetchAll();
            }
            $siap = true;
        } catch (PDOException $e) {
            $siap = false;
        }
    }
    return $siap;
}

/** Tampilkan pesan ramah bila database belum diperbarui, lalu hentikan halaman. */
function auth_halaman_skema_belum_siap(): never
{
    $judul = 'Database perlu diperbarui';
    require __DIR__ . '/header.php';
    ?>
    <div class="container py-5">
      <div class="card-rh p-4 p-md-5 mx-auto" style="max-width: 640px;">
        <h2 class="h4"><i class="bi bi-database-exclamation text-warning"></i> Database belum diperbarui</h2>
        <p class="text-secondary mb-2">Fitur masuk dan daftar yang baru butuh beberapa kolom dan tabel tambahan.</p>
        <p class="mb-0">Buka <strong>phpMyAdmin</strong>, pilih database <code>ruang_hukum</code>, klik tab <strong>SQL</strong>,
          tempel seluruh isi file <code>database/migrasi_auth.sql</code>, lalu klik <strong>Go</strong>. Setelah itu muat ulang halaman ini.</p>
      </div>
    </div>
    <?php
    require __DIR__ . '/footer.php';
    exit;
}

// ------------------------------------------------------------------ validasi isian

/** Nomor HP Indonesia -> bentuk baku 08xxxxxxxxxx. Mengembalikan null bila tidak valid. */
function auth_normalisasi_telepon(string $t): ?string
{
    $t = preg_replace('/[\s\-().]/', '', trim($t));
    if (str_starts_with($t, '+62')) {
        $t = '0' . substr($t, 3);
    } elseif (str_starts_with($t, '62')) {
        $t = '0' . substr($t, 2);
    }
    return preg_match('/^08[1-9][0-9]{7,10}$/', $t) === 1 ? $t : null;
}

/** Aturan format username. Mengembalikan pesan galat, atau null bila boleh dipakai. */
function auth_cek_username(string $u): ?string
{
    if (preg_match('/^[a-z][a-z0-9._]{3,29}$/', $u) !== 1) {
        return 'Username 4 sampai 30 karakter, diawali huruf, hanya huruf kecil, angka, titik, dan garis bawah.';
    }
    if (preg_match('/[._]{2}/', $u) === 1 || preg_match('/[._]$/', $u) === 1) {
        return 'Username tidak boleh memuat titik atau garis bawah berturut-turut, dan tidak boleh diakhiri keduanya.';
    }
    if (in_array($u, ['admin', 'administrator', 'root', 'system', 'support', 'ruanghukum', 'moderator', 'staff', 'owner'], true)) {
        return 'Username ini dicadangkan. Pilih yang lain.';
    }
    return null;
}

function auth_username_terpakai(string $u, int $abaikanId = 0): bool
{
    $st = db()->prepare('SELECT COUNT(*) FROM users WHERE LOWER(username) = ? AND id <> ?');
    $st->execute([mb_strtolower($u), $abaikanId]);
    return (int) $st->fetchColumn() > 0;
}

function auth_email_terpakai(string $email): bool
{
    $st = db()->prepare('SELECT COUNT(*) FROM users WHERE LOWER(email) = ?');
    $st->execute([mb_strtolower($email)]);
    return (int) $st->fetchColumn() > 0;
}

/** Aturan kata sandi. Mengembalikan pesan galat, atau null bila memenuhi syarat. */
function auth_cek_sandi(string $p): ?string
{
    if (strlen($p) < 8) {
        return 'Kata sandi minimal 8 karakter.';
    }
    if (strlen($p) > 72) {
        return 'Kata sandi maksimal 72 karakter.';
    }
    if (preg_match('/[A-Za-z]/', $p) !== 1 || preg_match('/\d/', $p) !== 1) {
        return 'Kata sandi harus memuat huruf dan angka.';
    }
    return null;
}

/** Buat username unik dari teks bebas (dipakai saat daftar lewat media sosial). */
function auth_username_unik(string $dasar): string
{
    $u = mb_strtolower($dasar);
    $u = preg_replace('/[^a-z0-9._]+/', '.', $u);
    $u = trim(preg_replace('/[._]{2,}/', '.', $u), '._');
    if ($u === '' || !ctype_alpha($u[0])) {
        $u = 'user' . $u;
    }
    $u = rtrim(substr($u, 0, 24), '._');
    if (strlen($u) < 4) {
        $u = str_pad($u, 4, 'x');
    }
    $calon = $u;
    for ($i = 2; auth_username_terpakai($calon); $i++) {
        $calon = $u . $i;
    }
    return $calon;
}

// ------------------------------------------------------------------ masuk

/** Cari pengguna dari username ATAU email (tidak membedakan huruf besar/kecil). */
function auth_cari_pengguna(string $identitas): ?array
{
    $id = mb_strtolower(trim($identitas));
    if ($id === '') {
        return null;
    }
    $kolom = str_contains($id, '@') ? 'LOWER(email)' : 'LOWER(username)';
    $st = db()->prepare('SELECT * FROM users WHERE ' . $kolom . ' = ? LIMIT 1');
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

/** Pesan bila status akun belum/tidak boleh masuk. Null = boleh masuk. */
function auth_pesan_status(array $u): ?string
{
    return match ($u['status']) {
        'aktif'    => null,
        'menunggu' => 'Akun pengacara kamu masih menunggu verifikasi admin.',
        'ditolak'  => 'Pendaftaran akun ini ditolak admin. Hubungi admin untuk informasi lebih lanjut.',
        default    => 'Akun ini tidak aktif. Hubungi admin.',
    };
}

function auth_gagal_identitas(string $identitas): string
{
    return mb_substr(mb_strtolower(trim($identitas)), 0, 120);
}

/** True bila akun + IP ini sudah terlalu sering gagal masuk. */
function auth_terkunci(string $identitas): bool
{
    $cfg   = auth_config();
    $batas = auth_sekarang(-60 * (int) $cfg['gagal_menit']);
    $pdo   = db();

    $st = $pdo->prepare('SELECT COUNT(*) FROM login_gagal WHERE identitas = ? AND ip = ? AND created_at > ?');
    $st->execute([auth_gagal_identitas($identitas), auth_ip(), $batas]);
    if ((int) $st->fetchColumn() >= (int) $cfg['gagal_maks']) {
        return true;
    }
    // Batas longgar untuk satu IP secara keseluruhan (mencegah menebak banyak akun sekaligus).
    $st = $pdo->prepare('SELECT COUNT(*) FROM login_gagal WHERE ip = ? AND created_at > ?');
    $st->execute([auth_ip(), $batas]);
    return (int) $st->fetchColumn() >= (int) $cfg['gagal_maks'] * 6;
}

function auth_gagal_catat(string $identitas): void
{
    $pdo = db();
    $pdo->prepare('INSERT INTO login_gagal (identitas, ip, created_at) VALUES (?, ?, ?)')
        ->execute([auth_gagal_identitas($identitas), auth_ip(), auth_sekarang()]);
    $pdo->prepare('DELETE FROM login_gagal WHERE created_at < ?')->execute([auth_sekarang(-86400)]);
}

function auth_gagal_hapus(string $identitas): void
{
    db()->prepare('DELETE FROM login_gagal WHERE identitas = ? AND ip = ?')
        ->execute([auth_gagal_identitas($identitas), auth_ip()]);
}

/** Isi sesi untuk pengguna ini (ganti ID sesi agar aman dari session fixation). */
function auth_set_sesi(array $u): void
{
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id'       => (int) $u['id'],
        'nama'     => $u['nama'],
        'username' => $u['username'] ?? null,
        'email'    => $u['email'],
        'role'     => $u['role'],
    ];
}

/** Masuk: sesi + catat waktu login terakhir + (opsional) "Ingat akun saya". */
function auth_masuk(array $u, bool $ingat = false): void
{
    auth_set_sesi($u);
    db()->prepare('UPDATE users SET last_login_at = ? WHERE id = ?')->execute([auth_sekarang(), (int) $u['id']]);
    if ($ingat) {
        auth_ingat_buat((int) $u['id']);
    }
}

// ------------------------------------------------------------------ "Ingat akun saya"

function auth_cookie_set(string $nilai, int $kedaluwarsa): void
{
    setcookie(AUTH_COOKIE_INGAT, $nilai, [
        'expires'  => $kedaluwarsa,
        'path'     => (BASE_URL === '' ? '' : BASE_URL) . '/',
        'secure'   => auth_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function auth_cookie_hapus(): void
{
    auth_cookie_set('', time() - 3600);
    unset($_COOKIE[AUTH_COOKIE_INGAT]);
}

/**
 * Simpan token untuk perangkat ini. Cookie berisi "selector:validator"; di database hanya
 * tersimpan hash validator, jadi isi database yang bocor pun tidak bisa dipakai masuk.
 */
function auth_ingat_buat(int $userId): void
{
    $selector  = bin2hex(random_bytes(12));
    $validator = bin2hex(random_bytes(32));
    $berakhir  = time() + 86400 * (int) auth_config()['ingat_hari'];

    $pdo = db();
    $pdo->prepare('INSERT INTO remember_tokens (user_id, selector, validator_hash, expires_at, user_agent) VALUES (?,?,?,?,?)')
        ->execute([$userId, $selector, hash('sha256', $validator), date('Y-m-d H:i:s', $berakhir),
                   mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 200)]);
    $pdo->prepare('DELETE FROM remember_tokens WHERE expires_at < ?')->execute([auth_sekarang()]);
    auth_cookie_set($selector . ':' . $validator, $berakhir);
}

/** Dipanggil di config/app.php: masuk otomatis bila ada cookie "Ingat akun saya" yang sah. */
function auth_pulihkan_ingat(): void
{
    if (is_logged_in() || empty($_COOKIE[AUTH_COOKIE_INGAT]) || !is_string($_COOKIE[AUTH_COOKIE_INGAT])) {
        return;
    }
    try {
        $bagian = explode(':', $_COOKIE[AUTH_COOKIE_INGAT], 2);
        if (count($bagian) !== 2
            || preg_match('/^[a-f0-9]{24}$/', $bagian[0]) !== 1
            || preg_match('/^[a-f0-9]{64}$/', $bagian[1]) !== 1) {
            auth_cookie_hapus();
            return;
        }
        [$selector, $validator] = $bagian;
        $pdo = db();

        $st = $pdo->prepare('SELECT id, user_id, validator_hash, expires_at FROM remember_tokens WHERE selector = ?');
        $st->execute([$selector]);
        $t = $st->fetch();
        if (!$t) {
            auth_cookie_hapus();
            return;
        }
        if (!hash_equals($t['validator_hash'], hash('sha256', $validator))) {
            // Selector benar tapi kode salah: cookie kemungkinan dicuri. Cabut semua perangkat pengguna ini.
            auth_ingat_cabut_semua((int) $t['user_id']);
            auth_cookie_hapus();
            return;
        }
        if ($t['expires_at'] <= auth_sekarang()) {
            $pdo->prepare('DELETE FROM remember_tokens WHERE id = ?')->execute([$t['id']]);
            auth_cookie_hapus();
            return;
        }
        $st = $pdo->prepare('SELECT * FROM users WHERE id = ?');
        $st->execute([(int) $t['user_id']]);
        $u = $st->fetch();
        if (!$u || $u['status'] !== 'aktif') {
            $pdo->prepare('DELETE FROM remember_tokens WHERE id = ?')->execute([$t['id']]);
            auth_cookie_hapus();
            return;
        }
        auth_set_sesi($u);
    } catch (Throwable $e) {
        // Tabel belum ada (migrasi belum dijalankan) atau galat lain: abaikan, anggap belum login.
    }
}

/** Keluar dari perangkat ini: hapus token di database dan cookie-nya. */
function auth_ingat_cabut(): void
{
    $c = $_COOKIE[AUTH_COOKIE_INGAT] ?? '';
    if (is_string($c) && preg_match('/^([a-f0-9]{24}):/', $c, $m) === 1) {
        try {
            db()->prepare('DELETE FROM remember_tokens WHERE selector = ?')->execute([$m[1]]);
        } catch (Throwable $e) {
            // abaikan
        }
    }
    auth_cookie_hapus();
}

function auth_ingat_cabut_semua(int $userId): void
{
    db()->prepare('DELETE FROM remember_tokens WHERE user_id = ?')->execute([$userId]);
}

// ------------------------------------------------------------------ lupa kata sandi

/** Buat token reset (sekali pakai). Yang disimpan hanya hash-nya; token asli dikembalikan untuk dikirim. */
function auth_reset_buat(int $userId): string
{
    $pdo = db();
    $pdo->prepare('UPDATE password_resets SET used_at = ? WHERE user_id = ? AND used_at IS NULL')
        ->execute([auth_sekarang(), $userId]);
    $token = bin2hex(random_bytes(32));
    $pdo->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?,?,?)')
        ->execute([$userId, hash('sha256', $token), auth_sekarang(60 * (int) auth_config()['reset_menit'])]);
    return $token;
}

/** Token yang masih berlaku (belum dipakai, belum kedaluwarsa) beserta data penggunanya. */
function auth_reset_cari(string $token): ?array
{
    if (preg_match('/^[a-f0-9]{64}$/', $token) !== 1) {
        return null;
    }
    $st = db()->prepare(
        'SELECT r.id AS reset_id, r.user_id, u.nama, u.email, u.username
         FROM password_resets r JOIN users u ON u.id = r.user_id
         WHERE r.token_hash = ? AND r.used_at IS NULL AND r.expires_at > ?'
    );
    $st->execute([hash('sha256', $token), auth_sekarang()]);
    return $st->fetch() ?: null;
}

/** Terlalu banyak permintaan reset untuk akun ini dalam satu jam? */
function auth_reset_terlalu_sering(int $userId): bool
{
    $st = db()->prepare('SELECT COUNT(*) FROM password_resets WHERE user_id = ? AND created_at > ?');
    $st->execute([$userId, auth_sekarang(-3600)]);
    return (int) $st->fetchColumn() >= 5;
}

/** Kirim email reset lewat mail() bawaan PHP. Di XAMPP biasanya belum aktif, jadi bisa mengembalikan false. */
function auth_kirim_email_reset(array $u, string $tautan): bool
{
    $isi = "Halo {$u['nama']},\r\n\r\nKami menerima permintaan untuk mengatur ulang kata sandi akun Ruang Hukum kamu.\r\n"
         . "Klik tautan berikut (berlaku " . (int) auth_config()['reset_menit'] . " menit):\r\n\r\n{$tautan}\r\n\r\n"
         . "Kalau bukan kamu yang meminta, abaikan saja email ini. Kata sandimu tidak berubah.\r\n\r\nRuang Hukum";
    return (bool) @mail($u['email'], 'Atur ulang kata sandi Ruang Hukum', $isi,
        "Content-Type: text/plain; charset=UTF-8\r\nFrom: Ruang Hukum <no-reply@ruanghukum.test>");
}

// ------------------------------------------------------------------ login media sosial

/** Penyedia yang aktif: ['google' => ['nama','ikon','mode' => 'real'|'demo'], ...] */
function auth_sosial_daftar(): array
{
    $cfg   = auth_config();
    $hasil = [];
    foreach ($cfg['sosial'] as $kunci => $p) {
        if (!empty($p['client_id']) && !empty($p['client_secret'])) {
            $mode = 'real';
        } elseif (!empty($cfg['demo_sosial'])) {
            $mode = 'demo';
        } else {
            continue;
        }
        $hasil[$kunci] = ['nama' => $p['nama'], 'ikon' => $p['ikon'], 'mode' => $mode];
    }
    return $hasil;
}

/** Blok tombol "Atau masuk dengan" (kosong bila tidak ada penyedia aktif). */
function auth_sosial_blok(string $kata): string
{
    $daftar = auth_sosial_daftar();
    if (!$daftar) {
        return '';
    }
    $html = '<div class="auth-pemisah"><span>' . e($kata) . '</span></div><div class="auth-sosial">';
    foreach ($daftar as $kunci => $p) {
        $ikon = $kunci === 'google'
            ? '<img class="ikon-google" src="' . e(url('assets/img/google-g.svg')) . '" alt="" width="18" height="18">'
            : '<i class="bi ' . e($p['ikon']) . '"></i>';
        $html .= '<a class="btn-sosial sosial-' . e($kunci) . '" href="' . e(url('auth/sosial.php?p=' . $kunci)) . '" title="' . e($p['nama']) . '">'
               . $ikon . '<span>' . e($p['nama']) . '</span></a>';
    }
    return $html . '</div>';
}

/** Permintaan HTTP sederhana yang mengembalikan JSON (array) atau null bila gagal. */
function auth_http_json(string $url, string $metode = 'GET', array $form = [], array $header = []): ?array
{
    $header = array_merge(['Accept: application/json'], $header);
    $isi    = false;
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTPHEADER     => $header,
        ]);
        if ($metode === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($form));
        }
        $isi = curl_exec($ch);
        if ($isi === false) {
            error_log('[auth sosial] curl: ' . curl_error($ch));
        }
        curl_close($ch);
    } else {
        $opsi = ['http' => ['method' => $metode, 'timeout' => 15, 'ignore_errors' => true, 'header' => implode("\r\n", $header)]];
        if ($metode === 'POST') {
            $opsi['http']['header'] .= "\r\nContent-Type: application/x-www-form-urlencoded";
            $opsi['http']['content'] = http_build_query($form);
        }
        $isi = @file_get_contents($url, false, stream_context_create($opsi));
    }
    $json = json_decode((string) $isi, true);
    return is_array($json) ? $json : null;
}

/** Arahkan pengguna ke penyedia (asli) atau ke halaman simulasi (mode demo). */
function auth_sosial_mulai(string $kunci): never
{
    $daftar = auth_sosial_daftar();
    if (!isset($daftar[$kunci])) {
        flash('danger', 'Masuk dengan media sosial ini belum diaktifkan.');
        redirect('auth/login.php');
    }
    if ($daftar[$kunci]['mode'] === 'demo') {
        redirect('auth/sosial_demo.php?p=' . $kunci);
    }
    $p     = auth_config()['sosial'][$kunci];
    $state = bin2hex(random_bytes(16));
    $_SESSION['oauth'] = ['state' => $state, 'provider' => $kunci, 'waktu' => time()];

    $query = http_build_query(array_merge([
        'client_id'     => $p['client_id'],
        'redirect_uri'  => auth_url_mutlak('auth/sosial_callback.php'),
        'response_type' => 'code',
        'scope'         => $p['scope'],
        'state'         => $state,
    ], $p['extra'] ?? []));
    header('Location: ' . $p['auth_url'] . (str_contains($p['auth_url'], '?') ? '&' : '?') . $query);
    exit;
}

/** Tukar kode dengan token lalu ambil profil. Mengembalikan profil baku atau null. */
function auth_sosial_ambil_profil(string $kunci, string $kode): ?array
{
    $p = auth_config()['sosial'][$kunci];
    $token = auth_http_json($p['token_url'], 'POST', [
        'grant_type'    => 'authorization_code',
        'code'          => $kode,
        'redirect_uri'  => auth_url_mutlak('auth/sosial_callback.php'),
        'client_id'     => $p['client_id'],
        'client_secret' => $p['client_secret'],
    ]);
    if (!$token || empty($token['access_token']) || !is_string($token['access_token'])) {
        return null;
    }
    $data = auth_http_json($p['userinfo_url'], 'GET', [], ['Authorization: Bearer ' . $token['access_token']]);
    if (!$data) {
        return null;
    }
    if (($p['format'] ?? 'oidc') === 'facebook') {
        $uid = (string) ($data['id'] ?? '');
        $verifikasi = false;     // Facebook tidak memberi tanda email terverifikasi
    } else {
        $uid = (string) ($data['sub'] ?? '');
        $verifikasi = filter_var($data['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }
    if ($uid === '') {
        return null;
    }
    $nama = trim((string) ($data['name'] ?? trim(($data['given_name'] ?? '') . ' ' . ($data['family_name'] ?? ''))));
    return [
        'uid'   => mb_substr($uid, 0, 191),
        'email' => mb_strtolower(trim((string) ($data['email'] ?? ''))),
        'nama'  => $nama,
        'email_terverifikasi' => $verifikasi,
    ];
}

/**
 * Inti login sosial (dipakai alur asli dan halaman simulasi).
 *  1) Akun sosial ini sudah tertaut -> masuk.
 *  2) Email sudah terdaftar -> hanya ditautkan bila penyedia menjamin email terverifikasi dan akunnya bukan admin.
 *  3) Selain itu -> buat akun klien baru.
 */
function auth_sosial_proses(string $kunci, array $profil): never
{
    $pdo  = db();
    $nama = auth_config()['sosial'][$kunci]['nama'];

    $st = $pdo->prepare('SELECT u.* FROM social_accounts s JOIN users u ON u.id = s.user_id WHERE s.provider = ? AND s.provider_uid = ?');
    $st->execute([$kunci, $profil['uid']]);
    if ($u = $st->fetch()) {
        auth_sosial_masuk($u);
    }

    $email = $profil['email'];
    if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false || mb_strlen($email) > 120) {
        flash('danger', 'Akun ' . $nama . ' kamu tidak membagikan alamat email, jadi akun belum bisa dibuat. Silakan daftar dengan formulir.');
        redirect('auth/register.php');
    }

    $ada = $pdo->prepare('SELECT * FROM users WHERE LOWER(email) = ?');
    $ada->execute([$email]);
    $u = $ada->fetch();

    if ($u) {
        if (!$profil['email_terverifikasi'] || $u['role'] === 'admin') {
            flash('warning', 'Email ' . $email . ' sudah terdaftar. Masuklah dengan kata sandi akunmu.');
            redirect('auth/login.php');
        }
        $pdo->prepare('INSERT INTO social_accounts (user_id, provider, provider_uid, email) VALUES (?,?,?,?)')
            ->execute([(int) $u['id'], $kunci, $profil['uid'], $email]);
        auth_sosial_masuk($u);
    }

    $local = explode('@', $email)[0];
    $namaLengkap = $profil['nama'] !== '' ? $profil['nama'] : $local;
    if (mb_strlen($namaLengkap) < 3) {
        $namaLengkap = str_pad($namaLengkap, 3, '.');
    }
    $pdo->beginTransaction();
    try {
        $pdo->prepare('INSERT INTO users (nama, username, email, password, role, status, terms_at) VALUES (?,?,?,?,?,?,?)')
            ->execute([
                mb_substr($namaLengkap, 0, 100), auth_username_unik($local), $email,
                password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),   // sandi acak: atur sendiri lewat "Lupa kata sandi"
                'klien', 'aktif', auth_sekarang(),
            ]);
        $baru = (int) $pdo->lastInsertId();
        $pdo->prepare('INSERT INTO social_accounts (user_id, provider, provider_uid, email) VALUES (?,?,?,?)')
            ->execute([$baru, $kunci, $profil['uid'], $email]);
        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        flash('danger', 'Akun belum bisa dibuat. Coba lagi sebentar lagi.');
        redirect('auth/login.php');
    }
    $st = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $st->execute([$baru]);
    flash('success', 'Akun dibuat lewat ' . $nama . '. Kamu bisa mengatur kata sandi lewat menu "Lupa kata sandi".');
    auth_sosial_masuk($st->fetch());
}

/** Masuk setelah login sosial berhasil (memeriksa status akun). */
function auth_sosial_masuk(array $u): never
{
    if ($pesan = auth_pesan_status($u)) {
        flash('danger', $pesan);
        redirect('auth/login.php');
    }
    auth_masuk($u, false);
    flash('success', 'Selamat datang, ' . $u['nama'] . '!');
    redirect(dashboard_path($u['role']));
}

// ------------------------------------------------------------------ tampilan

/** Angka singkat untuk panel samping halaman masuk/daftar. */
function auth_statistik(): array
{
    try {
        $pdo = db();
        return [
            'pengacara' => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'pengacara' AND status = 'aktif'")->fetchColumn(),
            'konsultasi' => (int) $pdo->query("SELECT COUNT(*) FROM konsultasi WHERE status IN ('dijawab','selesai')")->fetchColumn(),
        ];
    } catch (PDOException $e) {
        return ['pengacara' => 0, 'konsultasi' => 0];
    }
}

/** Kolom kata sandi dengan ikon gembok dan tombol mata (tampilkan/sembunyikan). */
function auth_input_sandi(string $id, string $nama, string $label, array $opsi = []): void
{
    $placeholder = $opsi['placeholder'] ?? '';
    $autocomplete = $opsi['autocomplete'] ?? 'current-password';
    $minlength = isset($opsi['minlength']) ? ' minlength="' . (int) $opsi['minlength'] . '"' : '';
    $galat = $opsi['galat'] ?? '';
    ?>
    <label class="form-label" for="<?= e($id) ?>"><?= e($label) ?></label>
    <div class="input-group">
      <span class="input-group-text"><i class="bi bi-lock"></i></span>
      <input type="password" class="form-control <?= $galat !== '' ? 'is-invalid' : '' ?>" id="<?= e($id) ?>" name="<?= e($nama) ?>"
             placeholder="<?= e($placeholder) ?>" autocomplete="<?= e($autocomplete) ?>" maxlength="72"<?= $minlength ?> required>
      <button class="btn btn-mata" type="button" data-toggle-sandi="<?= e($id) ?>" aria-label="Tampilkan kata sandi" aria-pressed="false">
        <i class="bi bi-eye"></i>
      </button>
    </div>
    <?php if ($galat !== ''): ?><div class="invalid-feedback d-block"><?= e($galat) ?></div><?php endif; ?>
    <?php
}

// ------------------------------------------------------------------ profil di navbar

/** Foto dan waktu login terakhir untuk kotak profil di navbar. Kosong bila kolomnya belum ada. */
function auth_profil_saya(int $userId): array
{
    try {
        $st = db()->prepare('SELECT foto, last_login_at FROM users WHERE id = ?');
        $st->execute([$userId]);
        return $st->fetch() ?: [];
    } catch (PDOException $e) {
        return [];
    }
}

/** 12 November 2026 -> "Kamis, 12 November 2026, 12:16:36" */
function auth_tgl_panjang(string $waktu): string
{
    $hari  = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $t = strtotime($waktu);
    return $hari[(int) date('w', $t)] . ', ' . date('j', $t) . ' ' . $bulan[(int) date('n', $t)] . ' ' . date('Y', $t) . ', ' . date('H:i:s', $t);
}

/** Lingkaran foto profil; bila belum ada foto tampil siluet. $ukuran: 'kecil' | 'besar'. */
function auth_avatar_html(?string $foto, string $ukuran = 'kecil', bool $idModal = false): string
{
    $kelas = 'avatar avatar-' . $ukuran;
    $id = $idModal ? ' id="pratinjauFoto"' : '';
    if ($foto !== null && $foto !== '' && preg_match('/^[A-Za-z0-9_.-]+$/', $foto) === 1) {
        return '<span class="' . $kelas . '"' . $id . '><img src="' . e(url('uploads/foto/' . $foto)) . '" alt="Foto profil"></span>';
    }
    return '<span class="' . $kelas . ' avatar-kosong"' . $id . '><i class="bi bi-person-fill"></i></span>';
}

// ---------------------------------------------------------------------------
// Tata letak halaman polos (masuk, daftar, lupa/reset sandi): tanpa navbar dan footer situs.
// ---------------------------------------------------------------------------

/** Logo Ruang Hukum yang menuju beranda. $kelas menambah kelas CSS. */
function auth_logo(string $kelas = ''): string
{
    return '<a class="auth-logo ' . e($kelas) . '" href="' . e(url('index.php')) . '" aria-label="Ruang Hukum, ke beranda">'
         . '<i class="bi bi-bank2" aria-hidden="true"></i> Ruang Hukum</a>';
}

/** Tautan kembali ke beranda (jalan keluar karena halaman ini tidak punya navbar). */
function auth_kembali(string $kelas = ''): string
{
    return '<a class="auth-kembali ' . e($kelas) . '" href="' . e(url('index.php')) . '">'
         . '<i class="bi bi-arrow-left" aria-hidden="true"></i> Kembali ke beranda</a>';
}

/** Panel biru di sisi kiri halaman masuk/daftar. $manfaat = [[ikon, teks], ...]. */
function auth_panel(string $judul, array $manfaat): void
{
    $stat = auth_statistik();
    ?>
    <aside class="auth-panel d-none d-lg-flex">
      <div>
        <?= auth_logo() ?>
        <h2><?= e($judul) ?></h2>
        <ul class="auth-manfaat">
          <?php foreach ($manfaat as [$ikon, $teks]): ?>
            <li><i class="bi <?= e($ikon) ?>" aria-hidden="true"></i> <?= e($teks) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div class="auth-angka">
        <div><b><?= (int) $stat['pengacara'] ?></b><span>pengacara aktif</span></div>
        <div><b><?= (int) $stat['konsultasi'] ?></b><span>konsultasi dijawab</span></div>
      </div>
    </aside>
    <?php
}
