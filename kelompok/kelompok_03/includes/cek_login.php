
<?php

require_once __DIR__ . '/init.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

if (
    !isset($_SESSION['role']) ||
    !in_array($_SESSION['role'], ['admin', 'pasien'], true)
) {
    http_response_code(403);
    exit('Akses ditolak.');
}
