
<?php

session_start();

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Silakan login terlebih dahulu.']);
    exit;
}

$idKeluarga = $_SESSION['user']['id_keluarga'];
$aksi = $_GET['aksi'] ?? '';
$dari = $_GET['dari'] ?? '';
$sampai = $_GET['sampai'] ?? '';

try {

    if (!in_array($aksi, ['total', 'kategori', 'transaksi'], true)) {
        throw new Exception('Aksi laporan tidak dikenali.');
    }

    $tanggalDari = DateTime::createFromFormat('!Y-m-d', $dari);
    $tanggalSampai = DateTime::createFromFormat('!Y-m-d', $sampai);

    if (
        !$tanggalDari ||
        !$tanggalSampai ||
        $tanggalDari->format('Y-m-d') !== $dari ||
        $tanggalSampai->format('Y-m-d') !== $sampai ||
        $dari > $sampai
    ) {
        throw new Exception('Rentang tanggal tidak valid.');
    }

    if ($aksi === 'total') {
        $stmt = $pdo->prepare('CALL sp_laporan_total(?, ?, ?)');
    } elseif ($aksi === 'kategori') {
        $stmt = $pdo->prepare('CALL sp_laporan_kategori(?, ?, ?)');
    } else {
        $stmt = $pdo->prepare('CALL sp_laporan_transaksi(?, ?, ?)');
    }

    $stmt->execute([
        $idKeluarga,
        $dari,
        $sampai
    ]);

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    echo json_encode($data);

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}