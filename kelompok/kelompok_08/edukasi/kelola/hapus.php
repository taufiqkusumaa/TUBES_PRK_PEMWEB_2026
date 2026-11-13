<?php
require_once __DIR__ . '/guard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}
if (!csrf_valid()) {
    http_response_code(400);
    exit('Token tidak valid. Muat ulang halaman lalu coba lagi.');
}

$id = (int) ($_POST['id'] ?? 0);
$stmt = $conn->prepare('DELETE FROM artikel WHERE id = ? AND penulis_id = ?');
$stmt->bind_param('ii', $id, $user_id);
$stmt->execute();

$_SESSION['flash'] = $stmt->affected_rows > 0
    ? 'Artikel berhasil dihapus.'
    : 'Artikel tidak ditemukan atau bukan milik Anda.';

header('Location: index.php');
exit;