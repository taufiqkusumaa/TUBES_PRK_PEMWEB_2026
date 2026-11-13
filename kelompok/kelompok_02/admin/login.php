<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (admin_logged_in()) {
    redirect('admin/dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    $stmt = $pdo->prepare('SELECT id, name, email, password_hash FROM admins WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin'] = [
            'id' => (int) $admin['id'],
            'name' => $admin['name'],
            'email' => $admin['email'],
        ];
        set_flash('success', 'Selamat datang, ' . $admin['name'] . '.');
        redirect('admin/dashboard.php');
    }

    $error = 'Email atau password tidak sesuai.';
}
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin | LABCAT</title>
    <link rel="stylesheet" href="<?= url('assets/css/admin/auth.css') ?>">
    <script defer src="<?= url('assets/js/admin/auth.js') ?>"></script>
</head>
<body class="login-page">
    <div class="login-card">
        <a class="brand brand-login" href="<?= url('index.php') ?>"><img class="brand-icon brand-icon-navy" src="<?= url('assets/images/flask-mark-navy.svg') ?>" alt=""><span class="brand-copy"><strong>LABCAT</strong><small>LABORATORY CATALOG SYSTEM</small></span></a>
        <div class="login-heading"><span class="eyebrow eyebrow-blue">ADMINISTRATOR AREA</span><h1>Login Admin</h1><p>Masuk untuk mengelola data alat laboratorium.</p></div>
        <?php if ($flash): ?><div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <form method="post" class="form-stack">
            <?= csrf_field() ?>
            <label>Email / Username
                <span class="input-icon">✉</span>
                <input type="email" name="email" placeholder="admin@labcat.ac.id" required autocomplete="username" value="<?= e($_POST['email'] ?? '') ?>">
            </label>
            <label>Password
                <span class="input-icon">▣</span>
                <input type="password" name="password" placeholder="Masukkan password" required autocomplete="current-password" data-password>
                <button class="password-toggle" type="button" data-password-toggle>◉</button>
            </label>
            <button class="btn btn-primary btn-block" type="submit">Masuk ke Dashboard <span>→</span></button>
        </form>
        <a class="login-back" href="<?= url('index.php') ?>">← Kembali ke Katalog Publik</a>
    </div>
</body>
</html>
