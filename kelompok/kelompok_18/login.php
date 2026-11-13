<?php

declare(strict_types=1);

require_once __DIR__ . '/config/auth.php';
startAppSession();

if (isLoggedIn()) {
    redirectToDashboard();
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['_csrf_token'] ?? null)) {
        http_response_code(403);
        $error = 'Permintaan tidak valid. Muat ulang halaman lalu coba kembali.';
    } else {
    $usernameInput = $_POST['username'] ?? null;
    $passwordInput = $_POST['password'] ?? null;
    $username = is_string($usernameInput) ? trim($usernameInput) : '';
    $password = is_string($passwordInput) ? $passwordInput : '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        require __DIR__ . '/config/database.php';

        try {
            $statement = $pdo->prepare(
                'SELECT id, username, password_hash, role, is_active
                 FROM users
                 WHERE username = :username
                 LIMIT 1'
            );
            $statement->execute(['username' => $username]);
            $user = $statement->fetch();
        } catch (PDOException $exception) {
            error_log('ATTENDARA login query failed: ' . $exception->getMessage());
            http_response_code(500);
            exit('Login sementara tidak dapat diproses.');
        }

        if (
            is_array($user)
            && (bool) $user['is_active']
            && in_array($user['role'], ['admin', 'guru'], true)
            && password_verify($password, $user['password_hash'])
        ) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['username'] = (string) $user['username'];
            $_SESSION['role'] = (string) $user['role'];
            $_SESSION['auth_created_at'] = time();
            $_SESSION['auth_last_activity'] = time();

            redirectToDashboard();
        }

        $error = 'Username atau password tidak valid, atau akun tidak aktif.';
    }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk | ATTENDARA</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex min-h-screen items-center justify-center bg-[#f3f5f2] px-4 py-8 text-slate-900 sm:px-6">
    <main class="w-full max-w-5xl">
        <section class="overflow-hidden rounded-3xl border border-[#dfe6de] bg-white shadow-[0_18px_55px_rgba(40,56,43,0.10)] md:grid md:min-h-[570px] md:grid-cols-[0.92fr_1.08fr]" aria-labelledby="login-title">
            <aside class="relative isolate overflow-hidden bg-[#4e7a5d] px-6 py-7 text-white sm:px-9 sm:py-9 md:flex md:flex-col md:justify-between md:px-10 md:py-10">
                <div aria-hidden="true" class="absolute -right-20 -top-24 -z-10 h-72 w-72 rounded-full border-[38px] border-white/[0.07]"></div>
                <div aria-hidden="true" class="absolute -bottom-24 -left-16 -z-10 h-56 w-56 rounded-full bg-[#315b3e]/35"></div>
                <a href="login.php" class="inline-flex w-fit items-center gap-3 rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#4e7a5d]">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/20">
                        <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m3 10 9-5 9 5-9 5z"/><path d="M7 12v5c3 2 7 2 10 0v-5M21 10v6"/></svg>
                    </span>
                    <span><span class="block text-sm font-bold tracking-[0.1em]">ATTENDARA</span><span class="mt-0.5 block text-xs text-white/75">Administrasi pembelajaran</span></span>
                </a>

                <div class="relative mt-10 max-w-md md:my-auto md:py-12">
                    <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1.5 text-xs font-medium text-white/90"><span class="h-1.5 w-1.5 rounded-full bg-[#d5e4d5]"></span>Ruang kerja sekolah</span>
                    <h2 class="mt-5 text-3xl font-semibold leading-tight tracking-tight sm:text-4xl">Semua kegiatan belajar, lebih tertata.</h2>
                    <p class="mt-3 max-w-sm text-sm leading-6 text-white/80">Satu tempat untuk mengelola kelas dan mendukung aktivitas pembelajaran.</p>

                    <div class="mt-8 grid gap-2.5 sm:grid-cols-2 md:grid-cols-1">
                        <div class="flex items-center gap-3 rounded-xl border border-white/15 bg-white/[0.08] px-3.5 py-3"><span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white/10"><svg aria-hidden="true" class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18"/></svg></span><span class="text-sm font-medium">Jadwal dan kelas</span></div>
                        <div class="flex items-center gap-3 rounded-xl border border-white/15 bg-white/[0.08] px-3.5 py-3"><span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white/10"><svg aria-hidden="true" class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="10" cy="7" r="4"/><path d="M20 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg></span><span class="text-sm font-medium">Data guru dan siswa</span></div>
                    </div>
                </div>
                <p class="hidden text-xs text-white/60 md:block">Portal untuk Admin dan Guru</p>
            </aside>

            <div class="flex items-center justify-center px-6 py-8 sm:px-10 sm:py-10 md:px-12 md:py-12">
                <div class="w-full max-w-sm">
                    <div class="mb-8">
                        <p class="text-xs font-semibold uppercase tracking-[0.13em] text-[#64806a]">Selamat datang</p>
                        <h1 id="login-title" class="mt-2 text-2xl font-semibold tracking-tight text-[#303832]">Masuk ke akun</h1>
                        <p class="mt-2 text-sm leading-5 text-slate-600">Gunakan username dan password akun Admin atau Guru.</p>
                    </div>

                    <?php if ($error !== ''): ?>
                        <p class="mb-5 flex gap-2.5 rounded-xl border border-red-200 bg-red-50 p-3.5 text-sm leading-5 text-red-800" role="alert">
                            <svg aria-hidden="true" class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg>
                            <span><?= h($error) ?></span>
                        </p>
                    <?php endif; ?>

                    <form method="post" action="login.php" class="space-y-5" autocomplete="on">
                        <input type="hidden" name="_csrf_token" value="<?= h(csrfToken()) ?>">
                        <div>
                            <label for="username" class="mb-1.5 block text-sm font-medium text-[#414942]">Username</label>
                            <input id="username" name="username" type="text" required maxlength="100" autocomplete="username" value="<?= h($username) ?>" class="min-h-12 w-full rounded-xl border border-[#dfe6de] bg-white px-3.5 text-sm text-slate-900 placeholder:text-slate-400 outline-none transition focus:border-[#4e7a5d] focus:ring-4 focus:ring-[#4e7a5d]/10">
                        </div>
                        <div>
                            <label for="password" class="mb-1.5 block text-sm font-medium text-[#414942]">Password</label>
                            <input id="password" name="password" type="password" required autocomplete="current-password" class="min-h-12 w-full rounded-xl border border-[#dfe6de] bg-white px-3.5 text-sm text-slate-900 placeholder:text-slate-400 outline-none transition focus:border-[#4e7a5d] focus:ring-4 focus:ring-[#4e7a5d]/10">
                        </div>
                        <button type="submit" class="mt-1 inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-[#4e7a5d] px-4 text-sm font-semibold text-white transition-colors hover:bg-[#41684f] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#679173] focus-visible:ring-offset-2">
                            Masuk <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
                        </button>
                    </form>
                    <div class="mt-7 flex items-center gap-3 text-xs text-slate-500"><span class="h-px flex-1 bg-[#e8ece7]"></span><span>Akses khusus Admin dan Guru</span><span class="h-px flex-1 bg-[#e8ece7]"></span></div>
                </div>
            </div>
        </section>
        <p class="mt-5 text-center text-xs text-slate-500">ATTENDARA <span class="mx-1 text-slate-300">·</span> Sistem administrasi sekolah</p>
    </main>
</body>
</html>
