<?php
// Menjaga akses halaman kelola artikel: hanya petani yang sudah login.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit;
}
if (($_SESSION['role'] ?? '') !== 'petani') {
    http_response_code(403);
    exit('Akses ditolak! Halaman ini khusus petani.');
}

require_once __DIR__ . '/../../config/database.php';

$user_id = (int) $_SESSION['user_id'];
$prefix  = '../../'; // dipakai header.php agar link tetap benar dari folder ini

// Token CSRF
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . htmlspecialchars($_SESSION['csrf']) . '">';
}
function csrf_valid(): bool {
    return isset($_POST['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}

// Slug dari judul, dijamin unik
function buat_slug(mysqli $conn, string $judul): string {
    $dasar = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $judul), '-'));
    $dasar = substr($dasar !== '' ? $dasar : 'artikel', 0, 150);
    $calon = $dasar;
    $i = 2;
    while (true) {
        $stmt = $conn->prepare('SELECT id FROM artikel WHERE slug = ? LIMIT 1');
        $stmt->bind_param('s', $calon);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            return $calon;
        }
        $calon = $dasar . '-' . $i++;
    }
}