<?php
/**
 * MekanikController - REST API Work Order Teknisi & update progres lapangan.
 *
 * Endpoint:
 *   GET  api.php?endpoint=mekanik&action=antrean[&status=Antre,Dikerjakan | semua]
 *   GET  api.php?endpoint=mekanik&action=detail&id=ID
 *   POST api.php?endpoint=mekanik&action=update_status   body JSON {id, status_baru}
 *
 * Hak akses: teknisi (pekerjaannya sendiri + antrean yang belum diambil) dan admin.
 */
require_once __DIR__ . '/../Models/MekanikModel.php';

class MekanikController
{
    /** Transisi status yang diizinkan per role: status_sekarang => [status_berikutnya]. */
    private const TRANSISI = [
        'teknisi' => [
            'Antre'      => ['Dikerjakan'],
            'Dikerjakan' => ['Siap Diambil'],
        ],
        'admin' => [
            'Antre'        => ['Dikerjakan'],
            'Dikerjakan'   => ['Siap Diambil'],
            'Siap Diambil' => ['Selesai'],
        ],
    ];

    private MekanikModel $model;

    public function __construct(PDO $db)
    {
        $this->model = new MekanikModel($db);
    }

    /** Titik masuk: dipanggil api.php. */
    public function handle(): void
    {
        try {
            $user = $this->currentUser();
            // Admin boleh tanpa id (stub sesi awal); teknisi wajib punya id karena
            // pekerjaan difilter per teknisi.
            if ($user['role'] === '' || ($user['role'] === 'teknisi' && $user['id'] <= 0)) {
                $this->json(false, 'Sesi berakhir. Silakan login kembali.', null, 401);
            }
            if (!in_array($user['role'], ['teknisi', 'admin'], true)) {
                $this->json(false, 'Anda tidak memiliki akses ke halaman teknisi.', null, 403);
            }

            $aksi   = $_GET['action'] ?? '';
            $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

            switch ($aksi) {
                case 'antrean':
                    $this->wajibMethod($method, ['GET']);
                    $this->antrean($user);
                    break;
                case 'detail':
                    $this->wajibMethod($method, ['GET']);
                    $this->detail($user);
                    break;
                case 'update_status':
                    $this->wajibMethod($method, ['POST', 'PUT']);
                    $this->updateStatus($user);
                    break;
                default:
                    $this->json(false, 'Aksi tidak dikenal.', null, 404);
            }
        } catch (Throwable $e) {
            error_log('[MekanikController] ' . $e->getMessage());
            $this->json(false, 'Terjadi kesalahan pada server.', null, 500);
        }
    }

    // ------------------------------------------------------------------
    // Aksi
    // ------------------------------------------------------------------

    public function antrean(array $user): void
    {
        $param = trim((string) ($_GET['status'] ?? ''));

        if ($param === '') {
            $statuses = ['Antre', 'Dikerjakan', 'Siap Diambil'];
        } elseif (strtolower($param) === 'semua') {
            $statuses = MekanikModel::STATUS_TAMPIL;
        } else {
            $diminta  = array_map('trim', explode(',', $param));
            $statuses = array_values(array_intersect(MekanikModel::STATUS_TAMPIL, $diminta));
            if (!$statuses) {
                $this->json(false, 'Filter status tidak valid.', null, 422);
            }
        }

        $this->json(true, 'OK', [
            'role'      => $user['role'],
            'ringkasan' => $this->model->getRingkasan($user['role'], $user['id']),
            'antrean'   => $this->model->getAntrean($user['role'], $user['id'], $statuses),
        ]);
    }

    private function detail(array $user): void
    {
        $id  = (int) ($_GET['id'] ?? 0);
        $row = $id > 0 ? $this->model->getDetail($id) : null;

        if (!$row) {
            $this->json(false, 'Work order tidak ditemukan.', null, 404);
        }
        if (!$this->bolehAkses($row, $user)) {
            $this->json(false, 'Work order ini ditugaskan ke teknisi lain.', null, 403);
        }

        $this->json(true, 'OK', $this->denganTransisi($row, $user['role']));
    }

    private function updateStatus(array $user): void
    {
        $in        = $this->input();
        $id        = (int) ($in['id'] ?? 0);
        $statusBaru = trim((string) ($in['status_baru'] ?? ''));

        if ($id <= 0 || $statusBaru === '') {
            $this->json(false, 'Data work order dan status baru wajib diisi.', null, 422);
        }

        $row = $this->model->getDetail($id);
        if (!$row) {
            $this->json(false, 'Work order tidak ditemukan.', null, 404);
        }
        if (!$this->bolehAkses($row, $user)) {
            $this->json(false, 'Work order ini ditugaskan ke teknisi lain.', null, 403);
        }

        $boleh = self::TRANSISI[$user['role']][$row['status_servis']] ?? [];
        if (!in_array($statusBaru, $boleh, true)) {
            $this->json(
                false,
                "Status \"{$row['status_servis']}\" tidak dapat diubah langsung menjadi \"{$statusBaru}\".",
                null,
                422
            );
        }

        $teknisiId = $user['role'] === 'teknisi' ? $user['id'] : null;
        $berhasil  = $this->model->updateStatus($id, $row['status_servis'], $statusBaru, $teknisiId);

        if (!$berhasil) {
            $this->json(false, 'Status sudah berubah oleh pihak lain. Muat ulang halaman.', null, 409);
        }

        $baru = $this->model->getDetail($id);
        $this->json(true, "Status berhasil diubah menjadi {$statusBaru}.", $this->denganTransisi($baru, $user['role']));
    }

    // ------------------------------------------------------------------
    // Helper
    // ------------------------------------------------------------------

    /** Teknisi hanya boleh membuka pekerjaan miliknya atau yang masih berstatus Antre tanpa teknisi. */
    private function bolehAkses(array $row, array $user): bool
    {
        if ($user['role'] === 'admin') {
            return true;
        }
        $pemilik = $row['teknisi_id'] === null ? null : (int) $row['teknisi_id'];
        if ($pemilik === $user['id']) {
            return true;
        }
        return $pemilik === null && $row['status_servis'] === 'Antre';
    }

    private function denganTransisi(array $row, string $role): array
    {
        $row['transisi'] = self::TRANSISI[$role][$row['status_servis']] ?? [];
        return $row;
    }

    private function currentUser(): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $s = $_SESSION ?? [];
        $u = is_array($s['user'] ?? null) ? $s['user'] : [];

        $role = strtolower(trim((string) ($u['role'] ?? ($s['role'] ?? ''))));
        $role = ['mekanik' => 'teknisi', 'pelanggan' => 'customer'][$role] ?? $role;

        return [
            'id'   => (int) ($u['id'] ?? ($u['id_user'] ?? ($s['user_id'] ?? 0))),
            'role' => $role,
        ];
    }

    private function input(): array
    {
        $ct = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($ct, 'application/json') !== false) {
            $data = json_decode((string) file_get_contents('php://input'), true);
            return is_array($data) ? $data : [];
        }
        return $_POST;
    }

    private function wajibMethod(string $method, array $diizinkan): void
    {
        if (!in_array($method, $diizinkan, true)) {
            header('Allow: ' . implode(', ', $diizinkan));
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