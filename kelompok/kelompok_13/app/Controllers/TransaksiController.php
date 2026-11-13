<?php
// File: kelompok/kelompok_13/app/Controllers/TransaksiController.php

require_once __DIR__ . '/../Config/Database.php';
require_once __DIR__ . '/../Models/TransaksiModel.php';

class TransaksiController {
    private $model;
    private $db;
    private $uploadDir;

    public function __construct($db = null) {
        $this->db = $db ?: Database::getConnection();
        $this->model = new TransaksiModel($this->db);
        $this->uploadDir = __DIR__ . '/../../public/uploads/';

    }

    /**
     * Method utama untuk merender halaman View Transaksi & memproses aksi form POST
     */
    public function index() {
        $user = $this->currentUser();
        if ($user['id'] <= 0 || !in_array($user['role'], ['admin', 'customer'], true)) {
            http_response_code(403);
            exit('Anda tidak memiliki akses ke halaman transaksi.');
        }
        $canManage = $user['role'] === 'admin';
        $userRole = $user['role'];
        $action = $_POST['action'] ?? $_GET['action'] ?? '';
        $successMsg = '';
        $errorMsg = '';

        // Jika ada request via AJAX / API JSON
        if (isset($_GET['format']) && $_GET['format'] === 'json') {
            $this->handleApiRequest($action);
            return;
        }

        // Proses Form Submit (POST)
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            if ($action === 'create_booking' || $action === 'store') {
                $result = $this->store($_POST, $_FILES);
                if ($result['status']) {
                    $successMsg = $result['message'];
                } else {
                    $errorMsg = $result['message'];
                }
            } elseif ($action === 'update_status' || $action === 'update') {
                $result = $canManage
                    ? $this->updateStatus($_POST)
                    : ['status' => false, 'message' => 'Hanya admin yang dapat mengubah status transaksi.'];
                if ($result['status']) {
                    $successMsg = $result['message'];
                } else {
                    $errorMsg = $result['message'];
                }
            } elseif ($action === 'delete') {
                $result = $canManage
                    ? $this->destroy($_POST['id'] ?? 0)
                    : ['status' => false, 'message' => 'Hanya admin yang dapat menghapus transaksi.'];
                if ($result['status']) {
                    $successMsg = $result['message'];
                } else {
                    $errorMsg = $result['message'];
                }
            }
        }

        // Filter pencarian dari query string
        $filters = [
            'status'     => $_GET['status'] ?? '',
            'teknisi_id' => $canManage ? ($_GET['teknisi_id'] ?? '') : '',
            'search'     => $_GET['search'] ?? ''
        ];
        if (!$canManage) {
            $filters['user_id'] = $user['id'];
        }

        $transaksiList = $this->model->getAll($filters);
        $stats = $this->model->getDashboardStats($canManage ? null : $user['id']);

        $sqlKendaraan = "
            SELECT k.id, k.kategori_id, k.no_polisi, k.nama_model, k.tahun_pembuatan, k.warna, u.nama AS pemilik
            FROM kendaraan k
            JOIN users u ON k.user_id = u.id
        ";
        $kendaraanParams = [];
        if (!$canManage) {
            $sqlKendaraan .= ' WHERE k.user_id = :user_id';
            $kendaraanParams[':user_id'] = $user['id'];
        }
        $stmtKendaraan = $this->db->prepare($sqlKendaraan . ' ORDER BY k.no_polisi ASC');
        $stmtKendaraan->execute($kendaraanParams);
        $kendaraanOptions = $stmtKendaraan->fetchAll(PDO::FETCH_ASSOC);

        $layananOptions = $this->db->query("
            SELECT * FROM layanan_servis
            WHERE status_layanan = 'Tersedia'
            ORDER BY nama_paket ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $teknisiOptions = $this->db->query("
            SELECT id, nama FROM users
            WHERE role = 'teknisi' AND status_aktif = 1
            ORDER BY nama ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
        $kategoriOptions = $this->db->query("
            SELECT id, jenis, merk, tipe FROM kategori_kendaraan
            ORDER BY jenis, merk, tipe
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Render View
        require_once __DIR__ . '/../Views/transaksi.php';
    }

    /**
     * Validasi & Simpan Pendaftaran Booking Servis Baru
     */
    public function store($postData, $fileData = []) {
        try {
            $user = $this->currentUser();
            if (!in_array($user['role'], ['admin', 'customer'], true)) {
                return ['status' => false, 'message' => 'Anda tidak dapat membuat booking servis.'];
            }
            $kendaraanId = filter_var($postData['kendaraan_id'] ?? null, FILTER_VALIDATE_INT);
            $layananId = filter_var($postData['layanan_id'] ?? null, FILTER_VALIDATE_INT);
            $keluhan = trim((string) ($postData['keluhan_mesin'] ?? ''));
            if ($user['id'] <= 0 || $kendaraanId === false || $kendaraanId <= 0 ||
                $layananId === false || $layananId <= 0 || $keluhan === '' || mb_strlen($keluhan) > 5000) {
                return [
                    'status'  => false,
                    'message' => 'Kendaraan, Paket Layanan, dan Keluhan Mesin wajib diisi!'
                ];
            }

            $stmtKendaraan = $this->db->prepare('SELECT user_id FROM kendaraan WHERE id = :id');
            $stmtKendaraan->execute([':id' => $kendaraanId]);
            $pemilikId = $stmtKendaraan->fetchColumn();
            if (!$pemilikId || ($user['role'] !== 'admin' && (int) $pemilikId !== $user['id'])) {
                return ['status' => false, 'message' => 'Kendaraan tidak ditemukan atau bukan milik Anda.'];
            }

            $stmtLayanan = $this->db->prepare(
                "SELECT l.id
                 FROM layanan_servis l
                 JOIN kendaraan k ON k.kategori_id = l.kategori_id
                 WHERE l.id = :layanan_id AND k.id = :kendaraan_id
                   AND l.status_layanan = 'Tersedia'"
            );
            $stmtLayanan->execute([':layanan_id' => $layananId, ':kendaraan_id' => $kendaraanId]);
            if (!$stmtLayanan->fetchColumn()) {
                return ['status' => false, 'message' => 'Paket layanan tidak tersedia atau tidak cocok dengan kategori kendaraan.'];
            }

            $teknisiId = null;
            $statusAwal = 'Antre';
            $catatanAdmin = null;
            if ($user['role'] === 'admin') {
                $teknisiId = filter_var($postData['teknisi_id'] ?? null, FILTER_VALIDATE_INT);
                $teknisiId = $teknisiId ? (int) $teknisiId : null;
                $statusAwal = (string) ($postData['status_servis'] ?? 'Antre');
                $catatanAdmin = trim((string) ($postData['catatan_admin'] ?? ''));
                if (!in_array($statusAwal, ['Pending', 'Antre', 'Dikerjakan'], true)) {
                    return ['status' => false, 'message' => 'Status awal transaksi tidak valid.'];
                }
                if ($teknisiId !== null) {
                    $stmtTeknisi = $this->db->prepare(
                        "SELECT 1 FROM users WHERE id = :id AND role = 'teknisi' AND status_aktif = 1"
                    );
                    $stmtTeknisi->execute([':id' => $teknisiId]);
                    if (!$stmtTeknisi->fetchColumn()) {
                        return ['status' => false, 'message' => 'Teknisi tidak ditemukan atau tidak aktif.'];
                    }
                }
                if ($statusAwal === 'Dikerjakan' && $teknisiId === null) {
                    return ['status' => false, 'message' => 'Pilih teknisi sebelum memulai pengerjaan.'];
                }
            }

            $fotoPath = null;
            if (isset($fileData['foto_kendala']) &&
                ($fileData['foto_kendala']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $uploadResult = $this->handleUploadFoto($fileData['foto_kendala']);
                if (!$uploadResult['status']) {
                    return $uploadResult;
                }
                $fotoPath = $uploadResult['path'];
            }

            $kodeTransaksi = $this->model->generateKodeTransaksi();
            $insertId = $this->model->create([
                'kode_transaksi' => $kodeTransaksi,
                'kendaraan_id'   => $kendaraanId,
                'layanan_id'     => $layananId,
                'teknisi_id'     => $teknisiId,
                'keluhan_mesin'  => $keluhan,
                'foto_kendala'   => $fotoPath,
                'status_servis'  => $statusAwal,
                'catatan_admin'  => $catatanAdmin
            ]);

            return [
                'status'  => true,
                'id'      => $insertId,
                'kode'    => $kodeTransaksi,
                'message' => "Booking servis berhasil didaftarkan dengan kode: {$kodeTransaksi}"
            ];
        } catch (Throwable $e) {
            error_log('[TransaksiController::store] ' . $e->getMessage());
            return [
                'status'  => false,
                'message' => 'Gagal menyimpan transaksi. Silakan coba kembali.'
            ];
        }
    }

    /**
     * Validasi & Update Status Progress Servis (Visual Stepper)
     */
    public function updateStatus($postData) {
        try {
            if ($this->currentUser()['role'] !== 'admin') {
                return ['status' => false, 'message' => 'Hanya admin yang dapat mengubah status transaksi.'];
            }
            $id = (int) ($postData['id'] ?? 0);
            if ($id <= 0) {
                return ['status' => false, 'message' => 'ID Transaksi tidak valid.'];
            }

            $validStatuses = ['Pending', 'Antre', 'Dikerjakan', 'Siap Diambil', 'Selesai', 'Dibatalkan'];
            $statusBaru = $postData['status_servis'] ?? 'Antre';

            if (!in_array($statusBaru, $validStatuses, true)) {
                return ['status' => false, 'message' => 'Status servis tidak dikenali.'];
            }
            if (!$this->model->getById($id)) {
                return ['status' => false, 'message' => 'Data transaksi tidak ditemukan.'];
            }

            $teknisiId = filter_var($postData['teknisi_id'] ?? null, FILTER_VALIDATE_INT);
            $teknisiId = $teknisiId ? (int) $teknisiId : null;
            if ($teknisiId !== null) {
                $stmtTeknisi = $this->db->prepare(
                    "SELECT 1 FROM users WHERE id = :id AND role = 'teknisi' AND status_aktif = 1"
                );
                $stmtTeknisi->execute([':id' => $teknisiId]);
                if (!$stmtTeknisi->fetchColumn()) {
                    return ['status' => false, 'message' => 'Teknisi tidak ditemukan atau tidak aktif.'];
                }
            }
            if ($statusBaru === 'Dikerjakan' && $teknisiId === null) {
                return ['status' => false, 'message' => 'Pilih teknisi sebelum memulai pengerjaan.'];
            }

            $this->model->updateStatus($id, [
                'status_servis' => $statusBaru,
                'teknisi_id'    => $teknisiId,
                'catatan_admin' => $postData['catatan_admin'] ?? ''
            ]);

            return [
                'status'  => true,
                'message' => "Status transaksi berhasil diperbarui menjadi '{$statusBaru}'!"
            ];
        } catch (Throwable $e) {
            error_log('[TransaksiController::updateStatus] ' . $e->getMessage());
            return [
                'status'  => false,
                'message' => 'Gagal memperbarui status transaksi.'
            ];
        }
    }

    /**
     * Hapus Data Transaksi beserta file foto kendala jika ada
     */
    public function destroy($id) {
        try {
            if ($this->currentUser()['role'] !== 'admin') {
                return ['status' => false, 'message' => 'Hanya admin yang dapat menghapus transaksi.'];
            }
            $existing = $this->model->getById($id);
            if (!$existing) {
                return ['status' => false, 'message' => 'Data transaksi tidak ditemukan.'];
            }

            $this->model->delete($id);
            if (!empty($existing['foto_kendala']) &&
                preg_match('/\Auploads\/[A-Za-z0-9._-]+\z/', $existing['foto_kendala'])) {
                $fullFile = __DIR__ . '/../../public/' . $existing['foto_kendala'];
                if (is_file($fullFile) && !unlink($fullFile)) {
                    error_log('[TransaksiController] Tidak dapat menghapus foto kendala: ' . $fullFile);
                }
            }
            return ['status' => true, 'message' => 'Data transaksi servis berhasil dihapus.'];
        } catch (Throwable $e) {
            error_log('[TransaksiController::destroy] ' . $e->getMessage());
            return ['status' => false, 'message' => 'Gagal menghapus transaksi.'];
        }
    }

    /**
     * Helper Upload Foto Kendala dengan Validasi Ekstensi & Ukuran (Maks 5MB)
     */
    private function handleUploadFoto($file) {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK ||
            (int) ($file['size'] ?? 0) > 5 * 1024 * 1024) {
            return ['status' => false, 'message' => 'Unggahan foto gagal atau ukuran file melebihi 5 MB.'];
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($extensions[$mime])) {
            return ['status' => false, 'message' => 'Format foto harus berupa JPG, JPEG, PNG, atau WEBP.'];
        }
        if (!is_dir($this->uploadDir) && !mkdir($this->uploadDir, 0775, true) && !is_dir($this->uploadDir)) {
            return ['status' => false, 'message' => 'Direktori unggahan tidak dapat dibuat.'];
        }

        $fileName = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
        $targetPath = $this->uploadDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return ['status' => true, 'path' => 'uploads/' . $fileName];
        }

        return ['status' => false, 'message' => 'Gagal mengunggah file foto ke server.'];
    }

    /**
     * Handler khusus untuk dipanggil dari public/api.php (JSON Response)
     */
    public function handleApiRequest($action) {
        header('Content-Type: application/json; charset=utf-8');

        $user = $this->currentUser();
        if ($user['id'] <= 0) {
            http_response_code(401);
            echo json_encode(['status' => false, 'message' => 'Sesi berakhir. Silakan login kembali.']);
            exit;
        }
        $filters = $_GET;
        if ($user['role'] !== 'admin') {
            $filters['user_id'] = $user['id'];
        }
        switch ($action) {
            case 'list':
                echo json_encode(['status' => true, 'data' => $this->model->getAll($filters)]);
                break;
            case 'detail':
                $id = $_GET['id'] ?? 0;
                $data = null;
                $data = $this->model->getById($id);
                if ($data && $user['role'] !== 'admin' && (int) $data['user_id'] !== $user['id']) {
                    $data = null;
                }
                echo json_encode(['status' => (bool) $data, 'data' => $data]);
                break;
            case 'track':
                $kode = $_GET['kode'] ?? '';
                $data = null;
                $data = $this->model->getByKode($kode);
                if ($data && $user['role'] !== 'admin' && (int) $data['user_id'] !== $user['id']) {
                    $data = null;
                }
                echo json_encode(['status' => (bool) $data, 'data' => $data]);
                break;
            case 'store':
                echo json_encode($this->store($_POST, $_FILES));
                break;
            case 'update_status':
                echo json_encode($this->updateStatus($_POST));
                break;
            case 'delete':
                echo json_encode($this->destroy($_POST['id'] ?? $_GET['id'] ?? 0));
                break;
            default:
                echo json_encode(['status' => false, 'message' => 'Aksi API Transaksi tidak valid']);
                break;
        }
        exit;
    }

    private function currentUser(): array {
        $user = is_array($_SESSION['user'] ?? null) ? $_SESSION['user'] : [];
        return [
            'id' => (int) ($user['id'] ?? 0),
            'role' => strtolower((string) ($user['role'] ?? '')),
        ];
    }
}