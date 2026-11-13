<?php
require_once __DIR__ . '/../../includes/auth_admin.php';
require_once __DIR__ . '/../../includes/csrf.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title>Kelola Aspirasi - KabarSih</title>
    <link rel="stylesheet" href="../../css/aspirasi.css">
</head>

<body class="adm-body">
<?php include __DIR__ . '/../../components/admin_nav.php'; ?>

<main class="adm-main">
    <h1 class="adm-title">Kelola Aspirasi</h1>
    <div id="alert-box" class="adm-alert" role="status" style="display:none;"></div>

    <div class="adm-card">
        <table class="adm-table">
            <thead>
                <tr>
                    <th>Judul</th>
                    <th>Kategori</th>
                    <th>Pengirim</th>
                    <th>Tanggal</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="tabel-body-aspirasi">
                <tr><td colspan="5" class="adm-empty">Memuat data dari server...</td></tr>
            </tbody>
        </table>
    </div>
</main>
<script src="../../js/app.js"></script>
</body>
</html>