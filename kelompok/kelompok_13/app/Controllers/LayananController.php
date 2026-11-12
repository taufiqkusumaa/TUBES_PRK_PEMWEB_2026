<?php
/**
 * REST API paket layanan servis.
 *
 * Daftarkan endpoint di API Gateway: (new LayananController($pdo))->handle().
 */
require_once __DIR__ . '/../Models/LayananModel.php';

class LayananController
{
    private LayananModel $model;

    public function __construct(PDO $db)
    {
        $this->model = new LayananModel($db);
    }

    public function handle(): void
    {
        try {
            $user = $this->currentUser();
            if ($user['role'] === '' || ($user['role'] !== 'admin' && $user['id'] <= 0)) {
                $this->json(false, 'Sesi berakhir. Silakan login kembali.', null, 401);
            }
            if (!in_array($user['role'], ['admin', 'customer', 'teknisi'], true)) {
                $this->json(false, 'Anda tidak memiliki akses ke paket layanan.', null, 403);
            }

            $action = (string) ($_GET['action'] ?? '');
            $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            if ($action === 'list' && $method === 'GET') {
                $this->json(true, 'OK', [
                    'layanan' => $this->model->getSemua(),
                    'kategori' => $this->model->getKategori(),
                ]);
            }
            if ($user['role'] !== 'admin') {
                $this->json(false, 'Hanya admin yang dapat mengubah paket layanan.', null, 403);
            }

            switch ($action) {
                case 'save':
                    $this->wajibMethod($method, ['POST', 'PUT']);
                    $this->simpan();
                    break;
                case 'delete':
                    $this->wajibMethod($method, ['DELETE', 'POST']);
                    $this->hapus();
                    break;
                default:
                    $this->json(false, 'Aksi layanan tidak dikenal.', null, 404);
            }
        } catch (Throwable $e) {
            error_log('[LayananController] ' . $e->getMessage());
            $this->json(false, 'Terjadi kesalahan pada server.', null, 500);
        }
    }

    private function simpan(): void
    {
        $input = $this->input();
        $idInput = $input['id'] ?? null;
        $id = $idInput === null || $idInput === '' ? null : (int) $idInput;
        $kategoriId = (int) ($input['kategori_id'] ?? 0);
        $nama = trim((string) ($input['nama_paket'] ?? ''));
        $tarif = filter_var($input['tarif_dasar'] ?? null, FILTER_VALIDATE_FLOAT);
        $durasi = filter_var($input['estimasi_durasi_menit'] ?? null, FILTER_VALIDATE_INT);

        if ($kategoriId <= 0 || !$this->model->kategoriAda($kategoriId) ||
            $nama === '' || preg_match('/\A.{1,120}\z/u', $nama) !== 1 ||
            $tarif === false || $tarif < 0 || $tarif > 9999999999 ||
            $durasi === false || $durasi < 1 || $durasi > 10080) {
            $this->json(false, 'Kategori, nama paket, tarif (minimal Rp0), dan durasi (1–10.080 menit) wajib valid.', null, 422);
        }
        if ($id !== null && ($id <= 0 || !$this->model->ada($id))) {
            $this->json(false, 'Paket layanan tidak ditemukan.', null, 404);
        }

        $this->model->simpan([
            ':kategori_id' => $kategoriId,
            ':nama_paket' => $nama,
            ':tarif_dasar' => $tarif,
            ':estimasi_durasi_menit' => $durasi,
        ], $id);
        $this->json(true, $id === null ? 'Paket layanan berhasil ditambahkan.' : 'Paket layanan berhasil diperbarui.');
    }

    private function hapus(): void
    {
        $input = $this->input();
        $id = (int) ($_GET['id'] ?? $input['id'] ?? 0);
        if ($id <= 0 || !$this->model->ada($id)) {
            $this->json(false, 'Paket layanan tidak ditemukan.', null, 404);
        }
        if ($this->model->digunakan($id)) {
            $this->json(false, 'Paket tidak dapat dihapus karena sudah digunakan pada transaksi.', null, 409);
        }
        $this->model->hapus($id);
        $this->json(true, 'Paket layanan berhasil dihapus.');
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