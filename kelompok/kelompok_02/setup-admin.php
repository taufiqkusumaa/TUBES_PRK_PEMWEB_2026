<?php
require_once __DIR__ . '/includes/bootstrap.php';

$count = (int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
$error = '';

if ($count > 0) {
    $alreadyConfigured = true;
} else {
    $alreadyConfigured = false;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['password_confirmation'] ?? '');

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Nama dan alamat email yang valid wajib diisi.';
        } elseif (strlen($password) < 8) {
            $error = 'Password minimal 8 karakter.';
        } elseif ($password !== $confirm) {
            $error = 'Konfirmasi password tidak sama.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO admins (name, email, password_hash) VALUES (:name, :email, :password_hash)');
            try {
                $stmt->execute([
                    'name' => $name,
                    'email' => $email,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                ]);
                set_flash('success', 'Akun administrator berhasil dibuat. Silakan login.');
                redirect('admin/login.php');
            } catch (PDOException $exception) {
                $error = 'Email sudah digunakan atau akun gagal dibuat.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Admin | LABCAT</title>
    <link rel="stylesheet" href="<?= url('assets/css/admin/auth.css') ?>">
    <script defer src="<?= url('assets/js/admin/auth.js') ?>"></script>
</head>
<body class="login-page">
    <div class="login-card setup-card">
        <a class="brand brand-login" href="<?= url('index.php') ?>"><img class="brand-icon brand-icon-navy" src="<?= url('assets/images/flask-mark-navy.svg') ?>" alt=""><span class="brand-copy"><strong>LABCAT</strong><small>LABORATORY CATALOG SYSTEM</small></span></a>
        <?php if ($alreadyConfigured): ?>
            <h1>Administrator sudah dibuat</h1>
            <p>Akun administrator sudah tersedia. Silakan masuk melalui halaman login.</p>
            <a class="btn btn-primary btn-block" href="<?= url('admin/login.php') ?>">Ke Login Admin</a>
        <?php else: ?>
            <h1>Buat Akun Admin Pertama</h1>
            <p>Form ini hanya digunakan satu kali untuk membuat akun administrator awal.</p>
            <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
            <form method="post" class="form-stack">
                <?= csrf_field() ?>
                <label>Nama administrator<input type="text" name="name" required maxlength="100" value="<?= e($_POST['name'] ?? '') ?>"></label>
                <label>Email<input type="email" name="email" required maxlength="150" value="<?= e($_POST['email'] ?? '') ?>"></label>
                <label>Password<input type="password" name="password" required minlength="8"></label>
                <label>Konfirmasi password<input type="password" name="password_confirmation" required minlength="8"></label>
                <button class="btn btn-primary btn-block" type="submit">Buat Akun Admin</button>
            </form>
            <p class="setup-warning">Setelah akun berhasil dibuat, hapus file <strong>setup-admin.php</strong> dari folder project.</p>
        <?php endif; ?>
    </div>
</body>
</html>
