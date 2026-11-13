<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($method === 'PUT') {
    require_once __DIR__ . '/../includes/auth_admin.php';
    require_once __DIR__ . '/../includes/csrf.php';

    $csrfHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($csrfHeader) || !hash_equals(csrf_token(), $csrfHeader)) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Token CSRF tidak valid. Muat ulang halaman lalu coba lagi.']);
        exit;
    }
}

switch ($method) {
    case 'GET':
        handleGet($pdo, $id);
        break;
    case 'POST':
        handlePost($pdo);
        break;
    case 'PUT':
        handlePut($pdo, $id);
        break;
    case 'DELETE':
        handleDelete($pdo, $id);
        break;
    default:
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method tidak diizinkan.']);
        break;
}

function handleGet(PDO $pdo, ?int $id): void {
    if ($id) {
        $stmt = $pdo->prepare('
            SELECT a.*, 
                   IF(a.is_anonim = 1, "Anonim", u.nama_lengkap) AS pengirim, 
                   k.nama_kategori 
            FROM aspirasi a
            JOIN users u ON a.id_user = u.id_user
            JOIN kategori k ON a.id_kategori = k.id_kategori
            WHERE a.id_aspirasi = :id LIMIT 1
        ');
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch();
        if ($data) {
            echo json_encode(['status' => 'success', 'data' => $data]);
        } else {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Aspirasi tidak ditemukan.']);
        }
    } else {
        $stmt = $pdo->query('
            SELECT a.*, 
                   IF(a.is_anonim = 1, "Anonim", u.nama_lengkap) AS pengirim, 
                   k.nama_kategori 
            FROM aspirasi a
            JOIN users u ON a.id_user = u.id_user
            JOIN kategori k ON a.id_kategori = k.id_kategori
            ORDER BY a.id_aspirasi DESC
        ');
        $data = $stmt->fetchAll();
        echo json_encode(['status' => 'success', 'total' => count($data), 'data' => $data]);
    }
}

function handlePost(PDO $pdo): void {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $id_user = (int)($input['id_user'] ?? 0);
    $id_kategori = (int)($input['id_kategori'] ?? 0);
    $judul = trim($input['judul_aspirasi'] ?? '');
    $isi = trim($input['isi_aspirasi'] ?? '');
    $is_anonim = isset($input['is_anonim']) ? (int)$input['is_anonim'] : 0;

    if (!$id_user || !$id_kategori || empty($judul) || empty($isi)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'id_user, id_kategori, judul_aspirasi, dan isi_aspirasi wajib diisi.']);
        return;
    }

    try {
        $stmt = $pdo->prepare('INSERT INTO aspirasi (id_user, id_kategori, judul_aspirasi, isi_aspirasi, is_anonim) VALUES (:id_user, :id_kategori, :judul, :isi, :anonim)');
        $stmt->execute([
            ':id_user' => $id_user,
            ':id_kategori' => $id_kategori,
            ':judul' => $judul,
            ':isi' => $isi,
            ':anonim' => $is_anonim
        ]);

        http_response_code(201);
        echo json_encode(['status' => 'success', 'message' => 'Aspirasi berhasil dikirim.', 'id_aspirasi' => (int)$pdo->lastInsertId()]);
    } catch (PDOException $e) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Gagal mengirim aspirasi: ' . $e->getMessage()]);
    }
}

function handlePut(PDO $pdo, ?int $id): void {
    if (!$id) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'ID Aspirasi wajib disertakan.']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Body permintaan harus berupa JSON object.']);
        return;
    }
    
    // Status can only use values supported by the aspirasi schema.
    if (isset($input['status'])) {
        $status = is_string($input['status']) ? trim($input['status']) : '';
        $statusValid = ['menunggu', 'ditolak', 'publikasi',];
        if (!in_array($status, $statusValid, true)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Status aspirasi tidak valid.']);
            return;
        }

        $stmt = $pdo->prepare('UPDATE aspirasi SET status = :status WHERE id_aspirasi = :id');
        $stmt->execute([':id' => $id, ':status' => $status]);
        if ($stmt->rowCount() === 0) {
            $check = $pdo->prepare('SELECT 1 FROM aspirasi WHERE id_aspirasi = :id');
            $check->execute([':id' => $id]);
            if (!$check->fetchColumn()) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Aspirasi tidak ditemukan.']);
                return;
            }
        }
        echo json_encode(['status' => 'success', 'message' => 'Status aspirasi berhasil diperbarui.']);
        return;
    }

    // Update full data aspirasi
    $stmt = $pdo->prepare('UPDATE aspirasi SET id_kategori = :id_kategori, judul_aspirasi = :judul, isi_aspirasi = :isi, is_anonim = :anonim WHERE id_aspirasi = :id');
    $stmt->execute([
        ':id' => $id,
        ':id_kategori' => (int)($input['id_kategori'] ?? 0),
        ':judul' => trim($input['judul_aspirasi'] ?? ''),
        ':isi' => trim($input['isi_aspirasi'] ?? ''),
        ':anonim' => (int)($input['is_anonim'] ?? 0)
    ]);

    echo json_encode(['status' => 'success', 'message' => 'Aspirasi berhasil diperbarui.']);
}

function handleDelete(PDO $pdo, ?int $id): void {
    if (!$id) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'ID Aspirasi wajib disertakan.']);
        return;
    }

    $stmt = $pdo->prepare('DELETE FROM aspirasi WHERE id_aspirasi = :id');
    $stmt->execute([':id' => $id]);

    echo json_encode(['status' => 'success', 'message' => 'Aspirasi berhasil dihapus.']);
}