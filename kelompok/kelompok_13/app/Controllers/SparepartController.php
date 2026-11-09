<?php
/**
 * SparepartController - REST API dokumentasi komponen & catatan teknis suku cadang.
 * KONTRAK (sama dengan MekanikController):
 *   api.php: case 'sparepart': (new SparepartController($pdo))->handle(); break;
 *   Session: $_SESSION['user'] = ['id', 'nama', 'role'] (role dinormalisasi:
 *            mekanik => teknisi, pelanggan => customer).
 *   Sub-aksi: parameter "action". Respons: {"success": bool, "message": string, "data": mixed}
 *
 * Endpoint:
 *   GET    api.php?endpoint=sparepart&action=list&transaksi_id=ID
 *   POST   api.php?endpoint=sparepart&action=tambah   (multipart/form-data)
 *   POST   api.php?endpoint=sparepart&action=ubah     (multipart/form-data, field id)
 *   DELETE api.php?endpoint=sparepart&action=hapus&id=ID   (POST juga diterima)
 *
 * Catatan: PHP tidak mengisi $_POST/$_FILES untuk multipart bermetode PUT, karena itu
 * "ubah" memakai POST.
 *
 * Aturan bisnis:
 *   - list     : admin; teknisi yang ditugaskan; customer pemilik transaksi (hanya baca).
 *   - tambah/ubah/hapus : admin atau teknisi yang ditugaskan, HANYA saat status Dikerjakan.
 *   - Setiap perubahan komponen menghitung ulang transaksi_servis.total_biaya
 *     (tarif dasar + SUM jumlah x harga_satuan).
 */
require_once __DIR__ . '/../Models/SparepartModel.php';

class SparepartController
{
    private const MAKS_FOTO_BYTE = 2097152; // 2 MB
    private const TIPE_FOTO = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    private SparepartModel $model;

    public function __construct(PDO $db)
    {
        $this->model = new SparepartModel($db);
    }

    public function handle(): void
    {
        try {
            $user = $this->currentUser();
            // Admin boleh tanpa id (stub sesi awal). Teknisi & customer difilter
            // berdasarkan id, jadi wajib ada.
            if ($user['role'] === '' || ($user['role'] !== 'admin' && $user['id'] <= 0)) {
                $this->json(false, 'Sesi berakhir. Silakan login kembali.', null, 401);
            }

            $aksi   = $_GET['action'] ?? '';
            $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

            switch ($aksi) {
                case 'list':
                    $this->wajibMethod($method, ['GET']);
                    $this->daftar($user);
                    break;
                case 'tambah':
                    $this->wajibMethod($method, ['POST']);
                    $this->tambah($user);
                    break;
                case 'ubah':
                    $this->wajibMethod($method, ['POST']);
                    $this->ubah($user);
                    break;
                case 'hapus':
                    $this->wajibMethod($method, ['DELETE', 'POST']);
                    $this->hapus($user);
                    break;
                default:
                    $this->json(false, 'Aksi tidak dikenal.', null, 404);
            }
        } catch (Throwable $e) {
            error_log('[SparepartController] ' . $e->getMessage());
            $this->json(false, 'Terjadi kesalahan pada server.', null, 500);
        }
    }

    // ------------------------------------------------------------------
    // Aksi
    // ------------------------------------------------------------------

    private function daftar(array $user): void
    {
        $transaksiId = (int) ($_GET['transaksi_id'] ?? 0);
        $info = $transaksiId > 0 ? $this->model->getTransaksiInfo($transaksiId) : null;
        if (!$info) {
            $this->json(false, 'Work order tidak ditemukan.', null, 404);
        }
        if (!$this->bolehBaca($info, $user)) {
            $this->json(false, 'Anda tidak memiliki akses ke work order ini.', null, 403);
        }

        $info['bisa_ubah'] = $this->bolehUbah($info, $user);
        unset($info['user_id'], $info['teknisi_id']);

        $this->json(true, 'OK', [
            'transaksi' => $info,
            'komponen'  => $this->model->getByTransaksi($transaksiId),
            'ringkasan' => $this->model->getRingkasan($transaksiId),
        ]);
    }

    private function tambah(array $user): void
    {
        $this->cekBodyTerlalu();
        $this->wajibRoleTulis($user);

        $transaksiId = (int) ($_POST['transaksi_id'] ?? 0);
        $info = $transaksiId > 0 ? $this->model->getTransaksiInfo($transaksiId) : null;
        if (!$info) {
            $this->json(false, 'Work order tidak ditemukan.', null, 404);
        }
        $this->wajibBolehUbah($info, $user);

        $data = $this->validasiForm();

        // Komponen dicatat atas nama teknisi penanggung jawab work order.
        $teknisiId = $info['teknisi_id'] !== null ? (int) $info['teknisi_id'] : $user['id'];
        if ($teknisiId <= 0) {
            $this->json(false, 'Work order belum memiliki teknisi penanggung jawab.', null, 422);
        }

        $fotoBaru = null;
        try {
            $fotoBaru = $this->simpanFotoJikaAda();
            $id = $this->model->tambah([
                'transaksi_id'      => $transaksiId,
                'teknisi_id'        => $teknisiId,
                'nama_suku_cadang'  => $data['nama_suku_cadang'],
                'jumlah'            => $data['jumlah'],
                'harga_satuan'      => $data['harga_satuan'],
                'foto_komponen'     => $fotoBaru,
                'catatan_perbaikan' => $data['catatan_perbaikan'],
            ]);
        } catch (InvalidArgumentException $e) {
            $this->json(false, $e->getMessage(), null, 422);
        } catch (Throwable $e) {
            $this->hapusFile($fotoBaru);
            throw $e;
        }

        $this->json(true, 'Komponen berhasil ditambahkan.', ['id' => $id], 201);
    }

    private function ubah(array $user): void
    {
        $this->cekBodyTerlalu();
        $this->wajibRoleTulis($user);

        $id  = (int) ($_POST['id'] ?? 0);
        $row = $id > 0 ? $this->model->getById($id) : null;
        if (!$row) {
            $this->json(false, 'Data komponen tidak ditemukan.', null, 404);
        }
        $info = $this->model->getTransaksiInfo((int) $row['transaksi_id']);
        $this->wajibBolehUbah($info, $user);

        $data = $this->validasiForm();

        $fotoBaru = null;
        try {
            $fotoBaru = $this->simpanFotoJikaAda();
            if ($fotoBaru !== null) {
                $data['foto_komponen'] = $fotoBaru; // kunci hanya ada bila ganti foto
            }
            $this->model->ubah($id, (int) $row['transaksi_id'], $data);
        } catch (InvalidArgumentException $e) {
            $this->json(false, $e->getMessage(), null, 422);
        } catch (Throwable $e) {
            $this->hapusFile($fotoBaru);
            throw $e;
        }

        if ($fotoBaru !== null) {
            $this->hapusFile($row['foto_komponen']);
        }
        $this->json(true, 'Komponen berhasil diperbarui.', ['id' => $id]);
    }

    private function hapus(array $user): void
    {
        $this->wajibRoleTulis($user);

        $id = (int) ($_GET['id'] ?? ($_POST['id'] ?? 0));
        $row = $id > 0 ? $this->model->getById($id) : null;
        if (!$row) {
            $this->json(false, 'Data komponen tidak ditemukan.', null, 404);
        }
        $info = $this->model->getTransaksiInfo((int) $row['transaksi_id']);
        $this->wajibBolehUbah($info, $user);

        $this->model->hapus($id, (int) $row['transaksi_id']);
        $this->hapusFile($row['foto_komponen']);

        $this->json(true, 'Komponen berhasil dihapus.');
    }

    // ------------------------------------------------------------------
    // Otorisasi
    // ------------------------------------------------------------------

    private function wajibRoleTulis(array $user): void
    {
        if (!in_array($user['role'], ['teknisi', 'admin'], true)) {
            $this->json(false, 'Anda tidak memiliki akses untuk mengubah komponen.', null, 403);
        }
    }

    private function bolehBaca(array $info, array $user): bool
    {
        switch ($user['role']) {
            case 'admin':
                return true;
            case 'teknisi':
                return $info['teknisi_id'] !== null && (int) $info['teknisi_id'] === $user['id'];
            case 'customer':
                return (int) $info['user_id'] === $user['id'];
            default:
                return false;
        }
    }

    /** Boleh tulis: admin / teknisi yang ditugaskan, dan status harus Dikerjakan. */
    private function bolehUbah(array $info, array $user): bool
    {
        if (!in_array($user['role'], ['teknisi', 'admin'], true)) {
            return false;
        }
        if ($user['role'] === 'teknisi'
            && ($info['teknisi_id'] === null || (int) $info['teknisi_id'] !== $user['id'])) {
            return false;
        }
        return $info['status_servis'] === 'Dikerjakan';
    }

    private function wajibBolehUbah(?array $info, array $user): void
    {
        if (!$info) {
            $this->json(false, 'Work order tidak ditemukan.', null, 404);
        }
        if ($user['role'] === 'teknisi'
            && ($info['teknisi_id'] === null || (int) $info['teknisi_id'] !== $user['id'])) {
            $this->json(false, 'Work order ini bukan tugas Anda.', null, 403);
        }
        if ($info['status_servis'] !== 'Dikerjakan') {
            $this->json(false, 'Komponen hanya dapat diubah saat status work order "Dikerjakan".', null, 409);
        }
    }

    // ------------------------------------------------------------------
    // Validasi & upload
    // ------------------------------------------------------------------

    private function validasiForm(): array
    {
        $nama    = trim((string) ($_POST['nama_suku_cadang'] ?? ''));
        $jumlah  = $_POST['jumlah'] ?? '';
        $harga   = $_POST['harga_satuan'] ?? '';
        $catatan = trim((string) ($_POST['catatan_perbaikan'] ?? ''));

        if ($nama === '' || mb_strlen($nama) > 100) {
            $this->json(false, 'Nama suku cadang wajib diisi (maksimal 100 karakter).', null, 422);
        }
        if (filter_var($jumlah, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 999]]) === false) {
            $this->json(false, 'Jumlah harus bilangan bulat antara 1 dan 999.', null, 422);
        }
        if (!is_numeric($harga) || (float) $harga < 0 || (float) $harga > 99999999.99) {
            $this->json(false, 'Harga satuan harus berupa angka 0 sampai 99.999.999.', null, 422);
        }
        if (mb_strlen($catatan) > 1000) {
            $this->json(false, 'Catatan perbaikan maksimal 1000 karakter.', null, 422);
        }

        return [
            'nama_suku_cadang'  => $nama,
            'jumlah'            => (int) $jumlah,
            'harga_satuan'      => round((float) $harga, 2),
            'catatan_perbaikan' => $catatan === '' ? null : $catatan,
        ];
    }

    /** Jika post_max_size terlewati, PHP mengosongkan $_POST dan $_FILES. */
    private function cekBodyTerlalu(): void
    {
        if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0 && empty($_POST) && empty($_FILES)) {
            $this->json(false, 'Ukuran data yang dikirim terlalu besar.', null, 413);
        }
    }

    /**
     * Simpan foto ke public/uploads/. Mengembalikan path relatif ("uploads/xxx.jpg")
     * atau null jika tidak ada file. Melempar InvalidArgumentException bila tidak valid.
     */
    private function simpanFotoJikaAda(): ?string
    {
        $f = $_FILES['foto_komponen'] ?? null;
        if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE) {
            throw new InvalidArgumentException('Ukuran foto melebihi batas 2 MB.');
        }
        if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
            throw new InvalidArgumentException('Upload foto gagal. Silakan coba lagi.');
        }
        if ($f['size'] > self::MAKS_FOTO_BYTE) {
            throw new InvalidArgumentException('Ukuran foto melebihi batas 2 MB.');
        }

        // getimagesize() membaca isi file sungguhan, bukan sekadar ekstensi/MIME kiriman browser.
        $info = @getimagesize($f['tmp_name']);
        $ext  = $info ? (self::TIPE_FOTO[$info['mime']] ?? null) : null;
        if ($ext === null) {
            throw new InvalidArgumentException('Format foto harus JPG, PNG, atau WebP.');
        }

        $dir = $this->uploadDir();
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('Folder uploads tidak dapat dibuat.');
        }

        $nama = 'part_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        if (!move_uploaded_file($f['tmp_name'], $dir . DIRECTORY_SEPARATOR . $nama)) {
            throw new RuntimeException('Gagal menyimpan file upload.');
        }
        return 'uploads/' . $nama;
    }

    /** Hapus file hanya jika berada di uploads/ (basename mencegah path traversal). */
    private function hapusFile(?string $path): void
    {
        if (!$path || strpos($path, 'uploads/') !== 0) {
            return;
        }
        $full = $this->uploadDir() . DIRECTORY_SEPARATOR . basename($path);
        if (is_file($full)) {
            @unlink($full);
        }
    }

    /** app/Controllers -> kelompok_13/public/uploads */
    private function uploadDir(): string
    {
        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads';
    }

    // ------------------------------------------------------------------
    // Helper umum
    // ------------------------------------------------------------------

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