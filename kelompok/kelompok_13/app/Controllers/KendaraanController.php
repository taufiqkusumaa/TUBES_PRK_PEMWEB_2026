<?php
/**
 * REST API kategori dan data kendaraan.
 *
 * Daftarkan endpoint di API Gateway: (new KendaraanController($pdo))->handle().
 */
require_once __DIR__ . '/../Models/KendaraanModel.php';

class KendaraanController
{
    private KendaraanModel $model;

    public function __construct(PDO $db)
    {
        $this->model = new KendaraanModel($db);
    }

    public function handle(): void
    {
        try {
            $user = $this->currentUser();
            if ($user['role'] === '' || ($user['role'] !== 'admin' && $user['id'] <= 0)) {
                $this->json(false, 'Sesi berakhir. Silakan login kembali.', null, 401);
            }
            if (!in_array($user['role'], ['admin', 'customer', 'teknisi'], true)) {
                $this->json(false, 'Anda tidak memiliki akses ke data kendaraan.', null, 403);
            }

            $action = (string) ($_GET['action'] ?? '');
            $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            if ($method === 'GET') {
                if ($action === 'list_vehicles' && $user['role'] !== 'admin') {
                    $this->json(false, 'Hanya admin yang dapat melihat daftar semua kendaraan.', null, 403);
                }
                $this->baca($action);
                return;
            }
            if (in_array($action, ['register_own_vehicle', 'update_own_vehicle'], true)) {
                if ($user['role'] !== 'customer') {
                    $this->json(false, 'Aksi ini hanya tersedia untuk akun customer.', null, 403);
                }
                $this->wajibMethod($method, ['POST']);
                $this->simpanKendaraan($user['id'], $action === 'update_own_vehicle');
                return;
            }
            if ($user['role'] !== 'admin') {
                $this->json(false, 'Hanya admin yang dapat mengubah master kendaraan.', null, 403);
            }

            switch ($action) {
                case 'save_category':
                    $this->wajibMethod($method, ['POST', 'PUT']);
                    $this->simpanKategori();
                    break;
                case 'delete_category':
                    $this->wajibMethod($method, ['DELETE', 'POST']);
                    $this->hapusKategori();
                    break;
                case 'save_vehicle':
                    $this->wajibMethod($method, ['POST', 'PUT']);
                    $this->simpanKendaraan();
                    break;
                case 'delete_vehicle':
                    $this->wajibMethod($method, ['DELETE', 'POST']);
                    $this->hapusKendaraan();
                    break;
                default:
                    $this->json(false, 'Aksi kendaraan tidak dikenal.', null, 404);
            }
        } catch (Throwable $e) {
            error_log('[KendaraanController] ' . $e->getMessage());
            $this->json(false, 'Terjadi kesalahan pada server.', null, 500);
        }
    }

    private function baca(string $action): void
    {
        switch ($action) {
            case 'list_categories':
                $this->json(true, 'OK', ['kategori' => $this->model->getKategori()]);
                break;
            case 'list_vehicles':
                $this->json(true, 'OK', [
                    'kendaraan' => $this->model->getKendaraan(),
                    'kategori' => $this->model->getKategori(),
                    'pelanggan' => $this->model->getPelanggan(),
                ]);
                break;
            default:
                $this->json(false, 'Aksi kendaraan tidak dikenal.', null, 404);
        }
    }

    private function simpanKategori(): void
    {
        $input = $this->input();
        $idInput = $input['id'] ?? null;
        $id = $idInput === null || $idInput === '' ? null : (int) $idInput;
        $jenis = trim((string) ($input['jenis'] ?? ''));
        $merk = trim((string) ($input['merk'] ?? ''));
        $tipe = trim((string) ($input['tipe'] ?? ''));

        if (!in_array($jenis, ['Roda 2', 'Roda 4'], true) || $merk === '' || $tipe === '') {
            $this->json(false, 'Jenis kendaraan, merk, dan tipe wajib diisi dengan benar.', null, 422);
        }
        if ($id !== null && ($id <= 0 || !$this->model->kategoriAda($id))) {
            $this->json(false, 'Kategori kendaraan tidak ditemukan.', null, 404);
        }

        $this->model->simpanKategori([
            ':jenis' => $jenis,
            ':merk' => $merk,
            ':tipe' => $tipe,
        ], $id);
        $this->json(true, $id === null ? 'Kategori berhasil ditambahkan.' : 'Kategori berhasil diperbarui.');
    }

    private function hapusKategori(): void
    {
        $id = (int) ($_GET['id'] ?? $this->input()['id'] ?? 0);
        if ($id <= 0 || !$this->model->kategoriAda($id)) {
            $this->json(false, 'Kategori kendaraan tidak ditemukan.', null, 404);
        }
        if ($this->model->kategoriDigunakan($id)) {
            $this->json(false, 'Kategori tidak dapat dihapus karena masih digunakan kendaraan.', null, 409);
        }
        $this->model->hapusKategori($id);
        $this->json(true, 'Kategori berhasil dihapus.');
    }

    private function simpanKendaraan(?int $ownerId = null, bool $allowOwnerUpdate = false): void
    {
        $input = $this->input();
        $idInput = $input['id'] ?? null;
        $id = ($ownerId === null || $allowOwnerUpdate) && $idInput !== null && $idInput !== ''
            ? (int) $idInput
            : null;
        if ($allowOwnerUpdate && $id === null) {
            $this->json(false, 'Kendaraan yang akan diubah wajib dipilih.', null, 422);
        }
        $kategoriId = (int) ($input['kategori_id'] ?? 0);
        $userId = $ownerId ?? (int) ($input['user_id'] ?? 0);
        $nomor = strtoupper(trim((string) ($input['no_polisi'] ?? '')));
        $nomor = preg_replace('/\s+/', ' ', $nomor) ?? $nomor;
        $model = trim((string) ($input['nama_model'] ?? ''));
        $warna = trim((string) ($input['warna'] ?? ''));
        $tahun = trim((string) ($input['tahun_pembuatan'] ?? ''));

        if ($kategoriId <= 0 || $userId <= 0 || $nomor === '' || mb_strlen($nomor) > 15 ||
            $model === '' || mb_strlen($model) > 100 || mb_strlen($warna) > 30) {
            $this->json(false, 'Pemilik, kategori, nomor polisi, dan model kendaraan wajib diisi.', null, 422);
        }
        if (!preg_match('/^[A-Z]{1,2}\s*[0-9]{1,4}\s*[A-Z]{0,3}$/', $nomor)) {
            $this->json(false, 'Format nomor polisi tidak valid.', null, 422);
        }
        if (!$this->model->kategoriAda($kategoriId)) {
            $this->json(false, 'Kategori kendaraan tidak ditemukan.', null, 422);
        }
        if (!$this->model->pelangganAda($userId)) {
            $this->json(false, 'Pelanggan kendaraan tidak ditemukan atau tidak aktif.', null, 422);
        }
        $tahunInt = $tahun === '' ? null : filter_var($tahun, FILTER_VALIDATE_INT);
        if ($tahun !== '' && ($tahunInt === false || $tahunInt < 1901 || $tahunInt > ((int) date('Y') + 1))) {
            $this->json(false, 'Tahun pembuatan kendaraan tidak valid.', null, 422);
        }
        if ($id !== null && ($id <= 0 || !$this->model->kendaraanAda($id))) {
            $this->json(false, 'Data kendaraan tidak ditemukan.', null, 404);
        }
        if ($ownerId !== null && $id !== null && !$this->model->kendaraanDimiliki($id, $ownerId)) {
            $this->json(false, 'Kendaraan tidak ditemukan atau bukan milik Anda.', null, 404);
        }
        if ($this->model->nomorPolisiTerpakai($nomor, $id)) {
            $this->json(false, 'Nomor polisi sudah terdaftar.', null, 409);
        }

        $kendaraanId = $this->model->simpanKendaraan([
            ':user_id' => $userId,
            ':kategori_id' => $kategoriId,
            ':no_polisi' => $nomor,
            ':nama_model' => $model,
            ':tahun_pembuatan' => $tahunInt,
            ':warna' => $warna !== '' ? $warna : null,
        ], $id);
        if ($id !== null) {
            $kendaraanId = $id;
        }
        $this->json(
            true,
            $id === null ? 'Kendaraan berhasil ditambahkan.' : 'Kendaraan berhasil diperbarui.',
            ['kendaraan' => [
                'id' => $kendaraanId,
                'no_polisi' => $nomor,
                'nama_model' => $model,
                'kategori_id' => $kategoriId,
                'tahun_pembuatan' => $tahunInt,
                'warna' => $warna,
            ]]
        );
    }

    private function hapusKendaraan(): void
    {
        $id = (int) ($_GET['id'] ?? $this->input()['id'] ?? 0);
        if ($id <= 0) {
            $this->json(false, 'ID kendaraan tidak valid.', null, 422);
        }
        if ($this->model->kendaraanDigunakan($id)) {
            $this->json(false, 'Kendaraan tidak dapat dihapus karena memiliki riwayat servis.', null, 409);
        }
        if (!$this->model->hapusKendaraan($id)) {
            $this->json(false, 'Kendaraan tidak ditemukan.', null, 404);
        }
        $this->json(true, 'Kendaraan berhasil dihapus.');
    }

    private function input(): array
    {
        $decoded = json_decode((string) file_get_contents('php://input'), true);
        return is_array($decoded) ? $decoded : $_POST;
    }

    private function currentUser(): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $user = is_array($_SESSION['user'] ?? null) ? $_SESSION['user'] : [];
        $role = strtolower(trim((string) ($user['role'] ?? '')));
        $role = ['mekanik' => 'teknisi', 'pelanggan' => 'customer'][$role] ?? $role;

        return ['id' => (int) ($user['id'] ?? 0), 'role' => $role];
    }

    private function wajibMethod(string $method, array $allowed): void
    {
        if (!in_array($method, $allowed, true)) {
            header('Allow: ' . implode(', ', $allowed));
            $this->json(false, 'Method tidak diizinkan.', null, 405);
        }
    }

    private function json(bool $success, string $message, $data = null, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(
            ['success' => $success, 'message' => $message, 'data' => $data],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        exit;
    }
}