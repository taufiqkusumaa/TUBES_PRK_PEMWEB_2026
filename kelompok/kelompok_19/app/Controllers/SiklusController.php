<?php
// app/Controllers/SiklusController.php
// Tugas: Menerima request, memanggil Model Siklus, mengembalikan JSON.

require_once __DIR__ . "/../Config/Database.php";
require_once __DIR__ . "/../Models/SiklusModel.php";

class SiklusController
{
    private $model;

    public function __construct()
    {
        $database = new \App\Config\Database();
        $db = $database->getConnection();
        $this->model = new SiklusModel($db);
    }

    public function index()
    {
        try {
            $filters = [
                'q' => $this->param('q'),
                'status' => $this->param('status'),
                'lahan_id' => $this->param('lahan_id'),
                'dari' => $this->param('dari'),
                'sampai' => $this->param('sampai'),
            ];

            if ($filters['dari'] !== null && !$this->tanggalValid($filters['dari'])) {
                $this->sendResponse(422, [
                    'status' => 'error',
                    'message' => 'Tanggal "dari" tidak valid (format YYYY-MM-DD).',
                    'data' => null,
                ]);
                return;
            }
            if ($filters['sampai'] !== null && !$this->tanggalValid($filters['sampai'])) {
                $this->sendResponse(422, [
                    'status' => 'error',
                    'message' => 'Tanggal "sampai" tidak valid (format YYYY-MM-DD).',
                    'data' => null,
                ]);
                return;
            }

            $data = $this->model->read($filters);
            $this->sendResponse(200, [
                'status' => 'success',
                'message' => 'Data siklus berhasil diambil.',
                'data' => $data,
            ]);
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $this->sendResponse(500, [
                'status' => 'error',
                'message' => 'Gagal mengambil data siklus.',
                'data' => null,
            ]);
        }
        return;
    }

    public function show($id)
    {
        try {
            if (!filter_var($id, FILTER_VALIDATE_INT) || $id <= 0) {
                $this->sendResponse(400, [
                    'status' => 'error',
                    'message' => 'ID siklus tidak valid.',
                    'data' => null,
                ]);
                return;
            }

            $data = $this->model->readSingle($id);

            if (!$data) {
                $this->sendResponse(404, [
                    'status' => 'error',
                    'message' => 'Data siklus tidak ditemukan.',
                    'data' => null,
                ]);
                return;
            }

            $this->sendResponse(200, [
                'status' => 'success',
                'message' => 'Data siklus berhasil ditemukan.',
                'data' => $data,
            ]);
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $this->sendResponse(500, [
                'status' => 'error',
                'message' => 'Gagal mengambil detail siklus.',
                'data' => null,
            ]);
        }
        return;
    }

    public function create()
    {
        try {
            $data = $this->getRequestData();

            if (
                !isset($data['lahan_id']) ||
                !filter_var($data['lahan_id'], FILTER_VALIDATE_INT) ||
                $data['lahan_id'] <= 0 ||
                empty($data['nama_tanaman']) ||
                empty($data['tanggal_tanam'])
            ) {
                $this->sendResponse(400, [
                    'status' => 'error',
                    'message' => 'Lahan, nama tanaman, dan tanggal tanam wajib diisi.',
                    'data' => null,
                ]);
                return;
            }

            $data['nama_tanaman'] = trim($data['nama_tanaman']);
            $data['status'] = $data['status'] ?? 'Aktif';

            $statusValid = ['Aktif', 'Panen', 'Selesai', 'Gagal'];

            if (!in_array($data['status'], $statusValid, true)) {
                $this->sendResponse(400, [
                    'status' => 'error',
                    'message' => 'Status siklus tidak valid.',
                    'data' => null,
                ]);
                return;
            }

            $result = $this->model->create($data);

            if ($result) {
                $this->sendResponse(201, [
                    'status' => 'success',
                    'message' => 'Siklus tanam berhasil ditambahkan.',
                    'data' => ['id' => $result],
                ]);
            } else {
                $this->sendResponse(500, [
                    'status' => 'error',
                    'message' => 'Gagal menambahkan siklus tanam.',
                    'data' => null,
                ]);
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $this->sendResponse(500, [
                'status' => 'error',
                'message' => 'Terjadi kesalahan database saat menambahkan siklus.',
                'data' => null,
            ]);
        }
        return;
    }

    public function update($id)
    {
        try {
            if (!filter_var($id, FILTER_VALIDATE_INT) || $id <= 0) {
                $this->sendResponse(400, [
                    'status' => 'error',
                    'message' => 'ID siklus tidak valid.',
                    'data' => null,
                ]);
                return;
            }

            $existingData = $this->model->readSingle($id);

            if (!$existingData) {
                $this->sendResponse(404, [
                    'status' => 'error',
                    'message' => 'Data siklus tidak ditemukan.',
                    'data' => null,
                ]);
                return;
            }

            $data = $this->getRequestData();

            if (
                !isset($data['lahan_id']) ||
                !filter_var($data['lahan_id'], FILTER_VALIDATE_INT) ||
                $data['lahan_id'] <= 0 ||
                empty($data['nama_tanaman']) ||
                empty($data['tanggal_tanam']) ||
                !isset($data['status'])
            ) {
                $this->sendResponse(400, [
                    'status' => 'error',
                    'message' => 'Data siklus belum lengkap.',
                    'data' => null,
                ]);
                return;
            }

            $data['nama_tanaman'] = trim($data['nama_tanaman']);

            $statusValid = ['Aktif', 'Panen', 'Selesai', 'Gagal'];

            if (!in_array($data['status'], $statusValid, true)) {
                $this->sendResponse(400, [
                    'status' => 'error',
                    'message' => 'Status siklus tidak valid.',
                    'data' => null,
                ]);
                return;
            }

            $result = $this->model->update($id, $data);

            if ($result) {
                $this->sendResponse(200, [
                    'status' => 'success',
                    'message' => 'Data siklus berhasil diperbarui.',
                    'data' => $data,
                ]);
            } else {
                $this->sendResponse(500, [
                    'status' => 'error',
                    'message' => 'Gagal memperbarui data siklus.',
                    'data' => null,
                ]);
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $this->sendResponse(500, [
                'status' => 'error',
                'message' => 'Terjadi kesalahan database saat memperbarui siklus.',
                'data' => null,
            ]);
        }
        return;
    }

    public function delete($id)
    {
        try {
            if (!filter_var($id, FILTER_VALIDATE_INT) || $id <= 0) {
                $this->sendResponse(400, [
                    'status' => 'error',
                    'message' => 'ID siklus tidak valid.',
                    'data' => null,
                ]);
                return;
            }

            $existingData = $this->model->readSingle($id);

            if (!$existingData) {
                $this->sendResponse(404, [
                    'status' => 'error',
                    'message' => 'Data siklus tidak ditemukan.',
                    'data' => null,
                ]);
                return;
            }

            $result = $this->model->delete($id);

            if ($result) {
                $this->sendResponse(200, [
                    'status' => 'success',
                    'message' => 'Data siklus berhasil dihapus.',
                    'data' => null,
                ]);
            } else {
                $this->sendResponse(500, [
                    'status' => 'error',
                    'message' => 'Gagal menghapus data siklus.',
                    'data' => null,
                ]);
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $this->sendResponse(500, [
                'status' => 'error',
                'message' => 'Terjadi kesalahan database saat menghapus siklus.',
                'data' => null,
            ]);
        }
        return;
    }

    private function getRequestData()
    {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            $this->sendResponse(400, [
                'status' => 'error',
                'message' => 'Format JSON tidak valid.',
                'data' => null,
            ]);
            exit;
        }

        return $data;
    }

    private function param($nama)
    {
        $nilai = $_GET[$nama] ?? null;
        return $nilai !== null && $nilai !== '' ? $nilai : null;
    }

    private function tanggalValid($tanggal)
    {
        $d = DateTime::createFromFormat('Y-m-d', $tanggal);
        return $d && $d->format('Y-m-d') === $tanggal;
    }

    private function sendResponse($statusCode, $data)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');

        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}
