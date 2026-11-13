
<?php

require_once __DIR__ . '/../includes/cek_login.php';

if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit('Akses ditolak. Hanya admin yang dapat mengakses halaman ini.');
}

$id = $_GET['id'] ?? null;

if (empty($id)) {
    header('Location: dokter_list.php?error=' . urlencode('ID dokter tidak valid.'));
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "SELECT COUNT(*) AS total FROM antrian
         WHERE dokter_id = ? AND status IN ('menunggu', 'dipanggil')"
    );
    $stmt->execute([$id]);
    $result = $stmt->fetch();

    if ($result['total'] > 0) {
        $pdo->rollBack();
        header('Location: dokter_list.php?error=' . urlencode('Dokter tidak dapat dihapus karena masih memiliki antrian yang belum selesai.'));
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM antrian WHERE dokter_id = ? AND status = 'selesai'");
    $stmt->execute([$id]);

    $stmt = $pdo->prepare('DELETE FROM dokter WHERE id = ?');
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        $pdo->commit();
        header('Location: dokter_list.php?success=' . urlencode('Dokter berhasil dihapus.'));
    } else {
        $pdo->rollBack();
        header('Location: dokter_list.php?error=' . urlencode('Dokter tidak ditemukan.'));
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[Dokter Hapus] ' . $e->getMessage());
    header('Location: dokter_list.php?error=' . urlencode('Gagal menghapus dokter.'));
}
exit;
