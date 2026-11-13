<?php
// app/Controllers/PanenController.php
// Tugas: Menerima request, memanggil Model Panen, mengembalikan JSON.

require_once __DIR__ . "/../Config/Database.php";
require_once __DIR__ . "/../Models/PanenModel.php";

class PanenController
{
    private $model;

    public function __construct()
    {
        $database = new \App\Config\Database();
        $db = $database->getConnection();
        $this->model = new PanenModel($db);
    }

    private function tanggalValid($tanggal)
    {
        if (!is_string($tanggal) || $tanggal === '') {
            return false;
        }
        $d = DateTime::createFromFormat('Y-m-d', $tanggal);
        return $d && $d->format('Y-m-d') === $tanggal;
    }

    private function param($nama)
    {
        $nilai = $_GET[$nama] ?? '';
        return $nilai !== '' ? $nilai : null;
    }

    // Validasi + bersihkan input. Mengembalikan [data_bersih, daftar_error]
    private function validasi()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            $input = [];
        }

        $errors = [];

        $siklusId = filter_var($input['siklus_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$siklusId || $siklusId < 1) {
            $errors[] = 'Siklus wajib dipilih.';
        }

        $tanggal = $input['tanggal_panen'] ?? null;
        if (!$this->tanggalValid($tanggal)) {
            $errors[] = 'Tanggal panen tidak valid (format YYYY-MM-DD).';
        }

        $jumlah = $input['jumlah_kg'] ?? null;
        if (!is_numeric($jumlah) || (float) $jumlah <= 0 || (float) $jumlah > 99999999.99) {
            $errors[] = 'Jumlah panen harus berupa angka lebih dari 0.';
        }

        $kualitas = $input['kualitas'] ?? 'A';
        if (!in_array($kualitas, ['A', 'B', 'C', 'Reject'], true)) {
            $errors[] = 'Kualitas harus A, B, C, atau Reject.';
        }

        $catatan = trim((string) ($input['catatan'] ?? ''));

        return [[
            'siklus_id'     => $siklusId,
            'tanggal_panen' => $tanggal,
            'jumlah_kg'     => is_numeric($jumlah) ? (float) $jumlah : 0,
            'kualitas'      => $kualitas,
            'catatan'       => $catatan !== '' ? $catatan : null,
        ], $errors];
    }

    // ---------- Endpoint ----------

    // GET /api/panen?dari=YYYY-MM-DD&sampai=YYYY-MM-DD
    // GET /api/panen?mode=siklus  (isi dropdown form)
    public function index()
    {
        $dari = $this->param('dari');
        $sampai = $this->param('sampai');

        if ($dari !== null && !$this->tanggalValid($dari)) {
            \App\Config\Database::response([
                'status' => 'error',
                'message' => 'Format tanggal "dari" tidak valid.',
                'data' => null,
            ], 400);
            return;
        }
        if ($sampai !== null && !$this->tanggalValid($sampai)) {
            \App\Config\Database::response([
                'status' => 'error',
                'message' => 'Format tanggal "sampai" tidak valid.',
                'data' => null,
            ], 400);
            return;
        }

        try {
            if (($_GET['mode'] ?? '') === 'siklus') {
                \App\Config\Database::response([
                    'status' => 'success',
                    'message' => 'Daftar siklus berhasil diambil.',
                    'data' => $this->model->getSiklusOptions(),
                ]);
                return;
            }

            \App\Config\Database::response([
                'status' => 'success',
                'message' => 'Data panen berhasil diambil.',
                'data'      => $this->model->read($dari, $sampai),
                'ringkasan' => $this->model->getRingkasan($dari, $sampai),
            ]);
        } catch (PDOException $e) {
            error_log('PanenController::index ' . $e->getMessage());
            \App\Config\Database::response([
                'status' => 'error',
                'message' => 'Gagal mengambil data panen.',
                'data' => null,
            ], 500);
        }
        return;
    }

    // GET /api/panen/{id}
    public function show($id)
    {
        $id = (int) $id;
        try {
            $data = $this->model->readSingle($id);
        } catch (PDOException $e) {
            error_log('PanenController::show ' . $e->getMessage());
            \App\Config\Database::response([
                'status' => 'error',
                'message' => 'Gagal mengambil data panen.',
                'data' => null,
            ], 500);
            return;
        }

        if (!$data) {
            \App\Config\Database::response([
                'status' => 'error',
                'message' => 'Data panen tidak ditemukan',
                'data' => null,
            ], 404);
            return;
        }
        \App\Config\Database::response([
            'status' => 'success',
            'message' => 'Data panen ditemukan.',
            'data' => $data,
        ]);
        return;
    }

    // POST /api/panen
    public function create()
    {
        [$data, $errors] = $this->validasi();
        if ($errors) {
            \App\Config\Database::response([
                'status' => 'error',
                'message' => implode(' ', $errors),
                'data' => null,
            ], 422);
            return;
        }

        try {
            $this->model->create($data);
            \App\Config\Database::response([
                'status' => 'success',
                'message' => 'Data panen berhasil ditambahkan.',
                'data' => ['id' => $this->model->getLastId()],
            ], 201);
        } catch (PDOException $e) {
            error_log('PanenController::create ' . $e->getMessage());
            \App\Config\Database::response([
                'status' => 'error',
                'message' => 'Gagal menambahkan data panen. Pastikan siklus yang dipilih valid.',
                'data' => null,
            ], 500);
        }
        return;
    }

    // PUT /api/panen/{id}
    public function update($id)
    {
        $id = (int) $id;
        [$data, $errors] = $this->validasi();
        if ($errors) {
            \App\Config\Database::response([
                'status' => 'error',
                'message' => implode(' ', $errors),
                'data' => null,
            ], 422);
            return;
        }

        try {
            if (!$this->model->readSingle($id)) {
                \App\Config\Database::response([
                    'status' => 'error',
                    'message' => 'Data panen tidak ditemukan',
                    'data' => null,
                ], 404);
                return;
            }
            $this->model->update($id, $data);
            \App\Config\Database::response([
                'status' => 'success',
                'message' => 'Data panen berhasil diperbarui.',
                'data' => $data,
            ]);
        } catch (PDOException $e) {
            error_log('PanenController::update ' . $e->getMessage());
            \App\Config\Database::response([
                'status' => 'error',
                'message' => 'Gagal memperbarui data panen. Pastikan siklus yang dipilih valid.',
                'data' => null,
            ], 500);
        }
        return;
    }

    // DELETE /api/panen/{id}
    public function delete($id)
    {
        $id = (int) $id;
        try {
            if (!$this->model->delete($id)) {
                \App\Config\Database::response([
                    'status' => 'error',
                    'message' => 'Data panen tidak ditemukan',
                    'data' => null,
                ], 404);
                return;
            }
            \App\Config\Database::response([
                'status' => 'success',
                'message' => 'Data panen berhasil dihapus.',
                'data' => null,
            ]);
        } catch (PDOException $e) {
            error_log('PanenController::delete ' . $e->getMessage());
            \App\Config\Database::response([
                'status' => 'error',
                'message' => 'Gagal menghapus data panen.',
                'data' => null,
            ], 500);
        }
        return;
    }
}