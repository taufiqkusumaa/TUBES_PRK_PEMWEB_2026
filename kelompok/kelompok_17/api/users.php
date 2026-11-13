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
        $stmt = $pdo->prepare('SELECT id_user, nama_lengkap, npm, role FROM users WHERE id_user = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch();
        if ($data) {
            echo json_encode(['status' => 'success', 'data' => $data]);
        } else {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'User tidak ditemukan.']);
        }
    } else {
        $stmt = $pdo->query('SELECT id_user, nama_lengkap, npm, role FROM users ORDER BY id_user DESC');
        $data = $stmt->fetchAll();
        echo json_encode(['status' => 'success', 'total' => count($data), 'data' => $data]);
    }
}

function handlePost(PDO $pdo): void {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $nama = trim($input['nama_lengkap'] ?? '');
    $npm = trim($input['npm'] ?? '');
    $password = trim($input['password'] ?? '');
    $role = trim($input['role'] ?? 'mahasiswa');

    if (empty($nama) || empty($npm) || empty($password)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Nama, NPM, dan Password wajib diisi.']);
        return;
    }

    try {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare('INSERT INTO users (nama_lengkap, npm, password, role) VALUES (:nama, :npm, :password, :role)');
        $stmt->execute([
            ':nama' => $nama,
            ':npm' => $npm,
            ':password' => $hashedPassword,
            ':role' => $role
        ]);

        http_response_code(201);
        echo json_encode(['status' => 'success', 'message' => 'User berhasil ditambahkan.', 'id_user' => (int)$pdo->lastInsertId()]);
    } catch (PDOException $e) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Gagal menambah user: ' . $e->getMessage()]);
    }
}

function handlePut(PDO $pdo, ?int $id): void {
    if (!$id) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'ID User wajib disertakan.']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $nama = trim($input['nama_lengkap'] ?? '');
    $role = trim($input['role'] ?? 'mahasiswa');

    $stmt = $pdo->prepare('UPDATE users SET nama_lengkap = :nama, role = :role WHERE id_user = :id');
    $stmt->execute([':id' => $id, ':nama' => $nama, ':role' => $role]);

    echo json_encode(['status' => 'success', 'message' => 'Data user berhasil diperbarui.']);
}

function handleDelete(PDO $pdo, ?int $id): void {
    if (!$id) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'ID User wajib disertakan.']);
        return;
    }

    $stmt = $pdo->prepare('DELETE FROM users WHERE id_user = :id');
    $stmt->execute([':id' => $id]);

    echo json_encode(['status' => 'success', 'message' => 'User berhasil dihapus.']);
}