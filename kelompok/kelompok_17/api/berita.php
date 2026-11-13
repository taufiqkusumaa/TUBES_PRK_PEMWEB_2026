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
            SELECT b.*, u.nama_lengkap AS penulis, k.nama_kategori 
            FROM berita b
            JOIN users u ON b.id_user = u.id_user
            JOIN kategori k ON b.id_kategori = k.id_kategori
            WHERE b.id_berita = :id LIMIT 1
        ');
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch();
        if ($data) {
            echo json_encode(['status' => 'success', 'data' => $data]);
        } else {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Berita tidak ditemukan.']);
        }
    } else {
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        if ($search !== '') {
            $stmt = $pdo->prepare('
                SELECT b.*, u.nama_lengkap AS penulis, k.nama_kategori 
                FROM berita b
                JOIN users u ON b.id_user = u.id_user
                JOIN kategori k ON b.id_kategori = k.id_kategori
                WHERE b.judul_berita LIKE :kw OR b.isi_berita LIKE :kw
                ORDER BY b.id_berita DESC
            ');
            $stmt->execute([':kw' => "%{$search}%"]);
        } else {
            $stmt = $pdo->query('
                SELECT b.*, u.nama_lengkap AS penulis, k.nama_kategori 
                FROM berita b
                JOIN users u ON b.id_user = u.id_user
                JOIN kategori k ON b.id_kategori = k.id_kategori
                ORDER BY b.id_berita DESC
            ');
        }
        $data = $stmt->fetchAll();
        echo json_encode(['status' => 'success', 'total' => count($data), 'data' => $data]);
    }
}

function handlePost(PDO $pdo): void {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $id_user = (int)($input['id_user'] ?? 0);
    $id_kategori = (int)($input['id_kategori'] ?? 0);
    $judul = trim($input['judul_berita'] ?? '');
    $isi = trim($input['isi_berita'] ?? '');
    $gambar_url = trim($input['gambar_url'] ?? '');

    if (!$id_user || !$id_kategori || empty($judul) || empty($isi)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'id_user, id_kategori, judul_berita, dan isi_berita wajib diisi.']);
        return;
    }

    try {
        $stmt = $pdo->prepare('INSERT INTO berita (id_user, id_kategori, judul_berita, isi_berita, gambar_url) VALUES (:id_user, :id_kategori, :judul, :isi, :gambar)');
        $stmt->execute([
            ':id_user' => $id_user,
            ':id_kategori' => $id_kategori,
            ':judul' => $judul,
            ':isi' => $isi,
            ':gambar' => $gambar_url ?: null
        ]);

        http_response_code(201);
        echo json_encode(['status' => 'success', 'message' => 'Berita berhasil dipublikasikan.', 'id_berita' => (int)$pdo->lastInsertId()]);
    } catch (PDOException $e) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Gagal menambah berita: ' . $e->getMessage()]);
    }
}

function handlePut(PDO $pdo, ?int $id): void {
    if (!$id) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'ID Berita wajib disertakan.']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $stmt = $pdo->prepare('UPDATE berita SET id_kategori = :id_kategori, judul_berita = :judul, isi_berita = :isi, gambar_url = :gambar WHERE id_berita = :id');
    $stmt->execute([
        ':id' => $id,
        ':id_kategori' => (int)($input['id_kategori'] ?? 0),
        ':judul' => trim($input['judul_berita'] ?? ''),
        ':isi' => trim($input['isi_berita'] ?? ''),
        ':gambar' => trim($input['gambar_url'] ?? '') ?: null
    ]);

    echo json_encode(['status' => 'success', 'message' => 'Berita berhasil diperbarui.']);
}

function handleDelete(PDO $pdo, ?int $id): void {
    if (!$id) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'ID Berita wajib disertakan.']);
        return;
    }

    $stmt = $pdo->prepare('DELETE FROM berita WHERE id_berita = :id');
    $stmt->execute([':id' => $id]);

    echo json_encode(['status' => 'success', 'message' => 'Berita berhasil dihapus.']);
}