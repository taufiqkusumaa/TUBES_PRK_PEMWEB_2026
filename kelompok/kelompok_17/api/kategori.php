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
        $stmt = $pdo->prepare('SELECT * FROM kategori WHERE id_kategori = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch();
        if ($data) {
            echo json_encode(['status' => 'success', 'data' => $data]);
        } else {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Kategori tidak ditemukan.']);
        }
    } else {
        $stmt = $pdo->query('SELECT * FROM kategori ORDER BY id_kategori DESC');
        $data = $stmt->fetchAll();
        echo json_encode(['status' => 'success', 'total' => count($data), 'data' => $data]);
    }
}

function handlePost(PDO $pdo): void {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $nama_kategori = trim($input['nama_kategori'] ?? '');

    if (empty($nama_kategori)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Nama kategori wajib diisi.']);
        return;
    }

    $stmt = $pdo->prepare('INSERT INTO kategori (nama_kategori) VALUES (:nama)');
    $stmt->execute([':nama' => $nama_kategori]);

    http_response_code(201);
    echo json_encode(['status' => 'success', 'message' => 'Kategori berhasil ditambahkan.', 'id_kategori' => (int)$pdo->lastInsertId()]);
}

function handlePut(PDO $pdo, ?int $id): void {
    if (!$id) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'ID Kategori wajib disertakan.']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $nama_kategori = trim($input['nama_kategori'] ?? '');

    $stmt = $pdo->prepare('UPDATE kategori SET nama_kategori = :nama WHERE id_kategori = :id');
    $stmt->execute([':id' => $id, ':nama' => $nama_kategori]);

    echo json_encode(['status' => 'success', 'message' => 'Kategori berhasil diperbarui.']);
}

function handleDelete(PDO $pdo, ?int $id): void {
    if (!$id) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'ID Kategori wajib disertakan.']);
        return;
    }

    $stmt = $pdo->prepare('DELETE FROM kategori WHERE id_kategori = :id');
    $stmt->execute([':id' => $id]);

    echo json_encode(['status' => 'success', 'message' => 'Kategori berhasil dihapus.']);
}