<?php
// kelompok/kelompok_13/app/Controllers/PelangganController.php

require_once __DIR__ . '/../Models/PelangganModel.php';

class PelangganController {
    private $model;

    public function __construct(?PDO $db = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->model = new PelangganModel($db);
    }

    public function index() {
        require_once __DIR__ . '/../Views/pelanggan.php';
        exit;
    }

    private function jsonResponse($success, $message, $data = null, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data'    => $data
        ]);
        exit;
    }

    public function handle(): void {
        $this->handleApi();
    }

    public function handleApi() {
        $action = $_GET['action'] ?? '';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        $rawInput = file_get_contents('php://input');
        $jsonInput = json_decode($rawInput, true);
        $input = !empty($jsonInput) ? $jsonInput : $_POST;

        switch ($action) {
            case 'register':
                if ($method !== 'POST') $this->jsonResponse(false, 'Metode HTTP tidak valid', null, 405);
                $this->processRegister($input);
                break;

            case 'login':
                if ($method !== 'POST') $this->jsonResponse(false, 'Metode HTTP tidak valid', null, 405);
                $this->processLogin($input);
                break;

            case 'logout':
                if ($method !== 'POST') {
                    $this->jsonResponse(false, 'Metode HTTP tidak valid', null, 405);
                }
                $this->processLogout();
                break;

            case 'profile':
                if ($method === 'GET') {
                    $this->getProfile();
                } elseif ($method === 'POST') {
                    $this->updateProfile($input);
                } else {
                    $this->jsonResponse(false, 'Metode HTTP tidak valid', null, 405);
                }
                break;

            case 'users':
                $this->requireRole(['admin']);
                if ($method === 'GET') {
                    $this->listUsers();
                } else {
                    $this->jsonResponse(false, 'Metode HTTP tidak valid', null, 405);
                }
                break;

            case 'toggle-status':
                $this->requireRole(['admin']);
                if ($method === 'POST') {
                    $this->toggleStatusUser($input);
                } else {
                    $this->jsonResponse(false, 'Metode HTTP tidak valid', null, 405);
                }
                break;

            default:
                $this->jsonResponse(false, 'Aksi tidak ditemukan', null, 404);
        }
    }

    // Tambahkan method alias jika sebelumnya terpanggil handleRequest
    public function handleRequest() {
        $this->handleApi();
    }

    private function processRegister($input) {
        $nama     = trim($input['nama'] ?? '');
        $email    = trim($input['email'] ?? '');
        $password = trim($input['password'] ?? '');
        $no_telp  = trim($input['no_telp'] ?? '');
        $role     = $input['role'] ?? 'customer';

        if (empty($nama) || empty($email) || empty($password)) {
            $this->jsonResponse(false, 'Nama, email, dan password wajib diisi', null, 400);
        }

        if ($this->model->findByEmail($email)) {
            $this->jsonResponse(false, 'Email sudah terdaftar', null, 409);
        }

        if (!in_array($role, ['customer', 'teknisi'])) {
            $role = 'customer';
        }

        $statusAktif = $role === 'teknisi' ? 0 : 1;
        $res = $this->model->register($nama, $email, $password, $no_telp, $role, $statusAktif);
        if ($res) {
            $message = $statusAktif === 0
                ? 'Pendaftaran teknisi berhasil. Akun menunggu aktivasi admin.'
                : 'Registrasi berhasil. Silakan login.';
            $this->jsonResponse(true, $message);
        } else {
            $this->jsonResponse(false, 'Gagal melakukan registrasi', null, 500);
        }
    }

    private function processLogin($input) {
        $email    = trim($input['email'] ?? '');
        $password = trim($input['password'] ?? '');

        if (empty($email) || empty($password)) {
            $this->jsonResponse(false, 'Email dan password tidak boleh kosong', null, 400);
        }

        $user = $this->model->findByEmail($email);
        if (!$user || !password_verify($password, $user['password'])) {
            $this->jsonResponse(false, 'Email atau password salah', null, 401);
        }

        if ((int)$user['status_aktif'] !== 1) {
            $this->jsonResponse(false, 'Akun Anda dinonaktifkan oleh administrator', null, 403);
        }

        $_SESSION['user'] = [
            'id'    => $user['id'],
            'nama'  => $user['nama'],
            'email' => $user['email'],
            'role'  => $user['role']
        ];
        session_regenerate_id(true);

        $this->jsonResponse(true, 'Login berhasil', $_SESSION['user']);
    }

    private function processLogout() {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        $this->jsonResponse(true, 'Logout berhasil');
    }

    private function getProfile() {
        if (!isset($_SESSION['user']['id'])) {
            $this->jsonResponse(false, 'Belum login', null, 401);
        }
        $data = $this->model->findById($_SESSION['user']['id']);
        $this->jsonResponse(true, 'Data profil', $data);
    }

    private function updateProfile($input) {
        if (!isset($_SESSION['user']['id'])) {
            $this->jsonResponse(false, 'Belum login', null, 401);
        }
        $userId   = $_SESSION['user']['id'];
        $nama     = trim($input['nama'] ?? '');
        $no_telp  = trim($input['no_telp'] ?? '');
        $new_pass = !empty($input['password']) ? trim($input['password']) : null;

        if (empty($nama)) {
            $this->jsonResponse(false, 'Nama tidak boleh kosong', null, 400);
        }

        $res = $this->model->updateProfile($userId, $nama, $no_telp, $new_pass);
        if ($res) {
            $_SESSION['user']['nama'] = $nama;
            $this->jsonResponse(true, 'Profil berhasil diperbarui');
        } else {
            $this->jsonResponse(false, 'Gagal memperbarui profil', null, 500);
        }
    }

    private function listUsers() {
        $users = $this->model->getAllUsers();
        $this->jsonResponse(true, 'Daftar semua pengguna', $users);
    }

    private function toggleStatusUser($input) {
        $userId = $input['user_id'] ?? null;
        $status = isset($input['status']) ? (int)$input['status'] : null;

        if (!$userId || !in_array($status, [0, 1])) {
            $this->jsonResponse(false, 'Parameter tidak valid', null, 400);
        }

        $res = $this->model->toggleStatus($userId, $status);
        if ($res) {
            $this->jsonResponse(true, 'Status akun berhasil diubah');
        } else {
            $this->jsonResponse(false, 'Gagal mengubah status akun', null, 500);
        }
    }

    public function requireRole(array $allowedRoles) {
        if (!isset($_SESSION['user'])) {
            $this->jsonResponse(false, 'Akses ditolak: Silakan login terlebih dahulu', null, 401);
        }
        if (!in_array($_SESSION['user']['role'], $allowedRoles)) {
            $this->jsonResponse(false, 'Akses ditolak: Hak akses tidak sesuai', null, 403);
        }
    }
}

