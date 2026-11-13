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
            SELECT k.*, IF(k.is_anonim = 1, "Anonim", u.nama_lengkap) AS pengirim 
            FROM komentar k
            JOIN users u ON k.id_user = u.id_user
            WHERE k.id_komentar = :id LIMIT 1
        ');
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch();
        if ($data) {
            echo json_encode(['status' => 'success', 'data' => $data]);
        } else {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Komentar tidak ditemukan.']);
        }
    } else {
        $id_berita = isset($_GET['id_berita']) ? (int)$_GET['id_berita'] : null;
        $id_aspirasi = isset($_GET['id_aspirasi']) ? (int)$_GET['id_aspirasi'] : null;

        if ($id_berita) {
            $stmt = $pdo->prepare('
                SELECT k.*, IF(k.is_anonim = 1, "Anonim", u.nama_lengkap) AS pengirim 
                FROM komentar k
                JOIN users u ON k.id_user = u.id_user
                WHERE k.id_berita = :id_berita ORDER BY k.id_komentar ASC
            ');
            $stmt->execute([':id_berita' => $id_berita]);
        } elseif ($id_aspirasi) {
            $stmt = $pdo->prepare('
                SELECT k.*, IF(k.is_anonim = 1, "Anonim", u.nama_lengkap) AS pengirim 
                FROM komentar k
                JOIN users u ON k.id_user = u.id_user
                WHERE k.id_aspirasi = :id_aspirasi ORDER BY k.id_komentar ASC
            ');
            $stmt->execute([':id_aspirasi' => $id_aspirasi]);
        } else {
            $stmt = $pdo->query('
                SELECT k.*, IF(k.is_anonim = 1, "Anonim", u.nama_lengkap) AS pengirim 
                FROM komentar k
                JOIN users u ON k.id_user = u.id_user
                ORDER BY k.id_komentar DESC
            ');
        }

        $data = $stmt->fetchAll();
        echo json_encode(['status' => 'success', 'total' => count($data), 'data' => $data]);
    }
}

function handlePost(PDO $pdo): void {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $id_user = (int)($input['id_user'] ?? 0);
    $id_berita = isset($input['id_berita']) ? (int)$input['id_berita'] : null;
    $id_aspirasi = isset($input['id_aspirasi']) ? (int)$input['id_aspirasi'] : null;
    $isi = trim($input['isi_komentar'] ?? '');
    $is_anonim = isset($input['is_anonim']) ? (int)$input['is_anonim'] : 0;

    if (!$id_user || empty($isi) || (!$id_berita && !$id_aspirasi)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'id_user, isi_komentar, dan salah satu dari id_berita/id_aspirasi wajib diisi.']);
        return;
    }

    try {
        $stmt = $pdo->prepare('INSERT INTO komentar (id_user, id_berita, id_aspirasi, isi_komentar, is_anonim) VALUES (:id_user, :id_berita, :id_aspirasi, :isi, :anonim)');
        $stmt->execute([
            ':id_user' => $id_user,
            ':id_berita' => $id_berita,
            ':id_aspirasi' => $id_aspirasi,
            ':isi' => $isi,
            ':anonim' => $is_anonim
        ]);

        http_response_code(201);
        echo json_encode(['status' => 'success', 'message' => 'Komentar berhasil ditambahkan.', 'id_komentar' => (int)$pdo->lastInsertId()]);
    } catch (PDOException $e) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Gagal menambah komentar: ' . $e->getMessage()]);
    }
}

function handlePut(PDO $pdo, ?int $id): void {
    if (!$id) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'ID Komentar wajib disertakan.']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $stmt = $pdo->prepare('UPDATE komentar SET isi_komentar = :isi, is_anonim = :anonim WHERE id_komentar = :id');
    $stmt->execute([
        ':id' => $id,
        ':isi' => trim($input['isi_komentar'] ?? ''),
        ':anonim' => (int)($input['is_anonim'] ?? 0)
    ]);

    echo json_encode(['status' => 'success', 'message' => 'Komentar berhasil diperbarui.']);
}

function handleDelete(PDO $pdo, ?int $id): void {
    if (!$id) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'ID Komentar wajib disertakan.']);
        return;
    }

    $stmt = $pdo->prepare('DELETE FROM komentar WHERE id_komentar = :id');
    $stmt->execute([':id' => $id]);

    echo json_encode(['status' => 'success', 'message' => 'Komentar berhasil dihapus.']);
}