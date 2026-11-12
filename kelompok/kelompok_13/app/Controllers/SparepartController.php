<?php

require_once __DIR__ . '/../Models/SparepartModel.php';
require_once __DIR__ . '/../Models/MekanikModel.php';
require_once __DIR__ . '/../Models/TransaksiModel.php';

class SparepartController {
    private SparepartModel $model;
    private MekanikModel $mekanikModel;
    private TransaksiModel $transaksiModel;
    private PDO $db;
    private string $uploadDir;

    public function __construct(PDO $db) {
        $this->db = $db;
        $this->model = new SparepartModel($db);
        $this->mekanikModel = new MekanikModel($db);
        $this->transaksiModel = new TransaksiModel($db);
        $this->uploadDir = __DIR__ . '/../../public/uploads/';
    }

    public function handle(): void {
        try {
            $user = $this->currentUser();
            if ($user['id'] <= 0 || !in_array($user['role'], ['admin', 'teknisi'], true)) {
                $this->json(false, 'Anda tidak memiliki akses ke dokumentasi suku cadang.', null, 403);
            }

            $action = (string) ($_GET['action'] ?? '');
            $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            switch ($action) {
                case 'list':
                    $this->wajibMethod($method, ['GET']);
                    $this->list($user);
                    break;
                case 'tambah':
                    $this->wajibMethod($method, ['POST']);
                    $this->simpan($user, null);
                    break;
                case 'ubah':
                    $this->wajibMethod($method, ['POST', 'PUT']);
                    $this->simpan($user, (int) ($this->input()['id'] ?? 0));
                    break;
                case 'hapus':
                    $this->wajibMethod($method, ['DELETE', 'POST']);
                    $this->hapus($user);
                    break;
                default:
                    $this->json(false, 'Aksi suku cadang tidak dikenal.', null, 404);
            }
        } catch (Throwable $e) {
            error_log('[SparepartController] ' . $e->getMessage());
            $this->json(false, 'Terjadi kesalahan pada server.', null, 500);
        }
    }

    private function list(array $user): void {
        $transaksiId = (int) ($_GET['transaksi_id'] ?? 0);
        $transaksi = $this->workOrder($transaksiId, $user, false);
        $items = $this->model->getByTransaksi($transaksiId);
        $totalKomponen = array_sum(array_map(
            static fn (array $item): float => (float) $item['subtotal'],
            $items
        ));
        $transaksi['bisa_ubah'] = $this->bisaUbah($transaksi, $user);
        $detail = $this->transaksiModel->getById($transaksiId);
        $transaksi['total_biaya'] = (float) ($detail['grand_total'] ?? $transaksi['total_biaya']);

        $this->json(true, 'OK', [
            'transaksi' => $transaksi,
            'komponen' => $items,
            'ringkasan' => [
                'jumlah_item' => count($items),
                'total_komponen' => $totalKomponen,
            ],
        ]);
    }

    private function simpan(array $user, ?int $id): void {
        $input = $this->input();
        $transaksiId = (int) ($input['transaksi_id'] ?? 0);
        if ($id !== null && $id <= 0) {
            $this->json(false, 'ID komponen tidak valid.', null, 422);
        }
        $transaksi = $this->workOrder($transaksiId, $user, true);
        if (!$this->bisaUbah($transaksi, $user)) {
            $this->json(false, 'Komponen hanya dapat diubah pada work order yang sedang dikerjakan.', null, 409);
        }

        $existing = $id === null ? null : $this->model->getById($id);
        if ($id !== null && (!$existing || (int) $existing['transaksi_id'] !== $transaksiId)) {
            $this->json(false, 'Komponen tidak ditemukan pada work order ini.', null, 404);
        }

        $nama = trim((string) ($input['nama_suku_cadang'] ?? ''));
        $jumlah = filter_var($input['jumlah'] ?? null, FILTER_VALIDATE_INT);
        $harga = filter_var($input['harga_satuan'] ?? null, FILTER_VALIDATE_FLOAT);
        $catatan = trim((string) ($input['catatan_perbaikan'] ?? ''));
        if ($nama === '' || mb_strlen($nama) > 100 ||
            $jumlah === false || $jumlah < 1 || $jumlah > 999 ||
            $harga === false || $harga < 0 || $harga > 99999999 ||
            mb_strlen($catatan) > 1000) {
            $this->json(false, 'Nama, jumlah, harga, atau catatan komponen tidak valid.', null, 422);
        }

        $foto = $existing['foto_komponen'] ?? null;
        $uploadedFoto = $this->simpanFoto($_FILES['foto_komponen'] ?? null);
        if ($uploadedFoto !== null) {
            $foto = $uploadedFoto;
        }

        $this->model->simpan([
            ':transaksi_id' => $transaksiId,
            ':teknisi_id' => $user['role'] === 'teknisi' ? $user['id'] : (int) $transaksi['teknisi_id'],
            ':nama_suku_cadang' => $nama,
            ':jumlah' => $jumlah,
            ':harga_satuan' => $harga,
            ':foto_komponen' => $foto,
            ':catatan_perbaikan' => $catatan !== '' ? $catatan : null,
        ], $id);

        if ($uploadedFoto !== null && !empty($existing['foto_komponen'])) {
            $this->hapusFoto($existing['foto_komponen']);
        }
        $this->transaksiModel->recalculateTotalBiaya($transaksiId);
        $this->json(true, $id === null ? 'Komponen berhasil ditambahkan.' : 'Komponen berhasil diperbarui.');
    }

    private function hapus(array $user): void {
        $input = $this->input();
        $id = (int) ($_GET['id'] ?? $input['id'] ?? 0);
        $transaksiId = (int) ($_GET['transaksi_id'] ?? $input['transaksi_id'] ?? 0);
        $transaksi = $this->workOrder($transaksiId, $user, true);
        if (!$this->bisaUbah($transaksi, $user)) {
            $this->json(false, 'Komponen hanya dapat diubah pada work order yang sedang dikerjakan.', null, 409);
        }
        $item = $id > 0 ? $this->model->getById($id) : null;
        if (!$item || (int) $item['transaksi_id'] !== $transaksiId) {
            $this->json(false, 'Komponen tidak ditemukan pada work order ini.', null, 404);
        }
        if (!$this->model->hapus($id, $transaksiId)) {
            $this->json(false, 'Komponen tidak berhasil dihapus.', null, 409);
        }

        $this->hapusFoto($item['foto_komponen'] ?? null);
        $this->transaksiModel->recalculateTotalBiaya($transaksiId);
        $this->json(true, 'Komponen berhasil dihapus.');
    }

    private function workOrder(int $id, array $user, bool $forEdit): array {
        $row = $id > 0 ? $this->mekanikModel->getDetail($id) : null;
        if (!$row) {
            $this->json(false, 'Work order tidak ditemukan.', null, 404);
        }
        if ($user['role'] === 'teknisi' && (int) $row['teknisi_id'] !== $user['id']) {
            $this->json(false, 'Work order ini ditugaskan ke teknisi lain.', null, 403);
        }
        if ($forEdit && $row['status_servis'] !== 'Dikerjakan') {
            $this->json(false, 'Komponen hanya dapat diubah saat work order berstatus Dikerjakan.', null, 409);
        }
        $row['tarif_dasar'] = (float) $row['tarif_dasar'];
        $row['total_biaya'] = $this->transaksiModel->recalculateTotalBiaya($id);
        return $row;
    }

    private function bisaUbah(array $transaksi, array $user): bool {
        return $transaksi['status_servis'] === 'Dikerjakan' && (int) $transaksi['teknisi_id'] > 0 &&
            ($user['role'] === 'admin' || (int) $transaksi['teknisi_id'] === $user['id']);
    }

    private function simpanFoto(?array $file): ?string {
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || (int) $file['size'] > 2 * 1024 * 1024) {
            $this->json(false, 'Unggahan foto gagal atau melebihi batas 2 MB.', null, 422);
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($extensions[$mime])) {
            $this->json(false, 'Foto harus berformat JPG, PNG, atau WebP.', null, 422);
        }
        if (!is_dir($this->uploadDir) && !mkdir($this->uploadDir, 0775, true) && !is_dir($this->uploadDir)) {
            throw new RuntimeException('Direktori unggahan tidak dapat dibuat.');
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
        if (!move_uploaded_file($file['tmp_name'], $this->uploadDir . $filename)) {
            throw new RuntimeException('Foto komponen gagal disimpan.');
        }
        return 'uploads/' . $filename;
    }

    private function hapusFoto(?string $path): void {
        if (!$path || !preg_match('/\Auploads\/[a-f0-9]{32}\.(jpg|png|webp)\z/', $path)) {
            return;
        }
        $file = $this->uploadDir . basename($path);
        if (is_file($file) && !unlink($file)) {
            error_log('[SparepartController] Tidak dapat menghapus foto: ' . $file);
        }
    }

    private function currentUser(): array {
        $user = is_array($_SESSION['user'] ?? null) ? $_SESSION['user'] : [];
        $role = strtolower(trim((string) ($user['role'] ?? '')));
        $role = ['mekanik' => 'teknisi'][$role] ?? $role;
        return ['id' => (int) ($user['id'] ?? 0), 'role' => $role];
    }

    private function input(): array {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') !== false) {
            $decoded = json_decode((string) file_get_contents('php://input'), true);
            return is_array($decoded) ? $decoded : [];
        }
        return $_POST;
    }

    private function wajibMethod(string $method, array $allowed): void {
        if (!in_array($method, $allowed, true)) {
            header('Allow: ' . implode(', ', $allowed));
            $this->json(false, 'Method tidak diizinkan.', null, 405);
        }
    }

    private function json(bool $success, string $message, $data = null, int $code = 200): void {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(
            ['success' => $success, 'message' => $message, 'data' => $data],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        exit;
    }
}
