<?php
// app/Controllers/LahanController.php
// Tugas: Menerima request, memanggil Model Lahan, mengembalikan JSON.

require_once __DIR__ . "/../Config/Database.php";
require_once __DIR__ . "/../Models/LahanModel.php";

class LahanController
{
    private $model;

    public function __construct()
    {
        $database = new \App\Config\Database();
        $db = $database->getConnection();
        $this->model = new LahanModel($db);
    }

    public function index()
    {
        $stmt = $this->model->read();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        \App\Config\Database::response([
            'status' => 'success',
            'message' => 'Data lahan berhasil diambil.',
            'data' => $data,
        ]);
        return;
    }

    public function show($id)
    {
        $stmt = $this->model->readSingle($id);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($data) {
            \App\Config\Database::response([
                'status' => 'success',
                'message' => 'Data lahan ditemukan.',
                'data' => $data,
            ]);
        } else {
            \App\Config\Database::response([
                'status' => 'error',
                'message' => 'Data lahan tidak ditemukan.',
                'data' => null,
            ], 404);
        }
        return;
    }

    public function create()
    {
        $input = json_decode(file_get_contents("php://input"), true);
        if (!is_array($input)) {
            \App\Config\Database::response([
                'status' => 'error',
                'message' => 'Format JSON tidak valid.',
                'data' => null,
            ], 400);
            return;
        }

        $nama = trim((string) ($input['nama'] ?? ''));
        $lokasi = trim((string) ($input['lokasi'] ?? ''));
        $luas = $input['luas'] ?? null;
        $jenis_tanah = trim((string) ($input['jenis_tanah'] ?? ''));

        if ($nama === '' || $lokasi === '' || $jenis_tanah === '') {
            \App\Config\Database::response([
                'status' => 'error',
                'message' => 'Nama, lokasi, dan jenis tanah wajib diisi.',
                'data' => null,
            ], 422);
            return;
        }
        if (!is_numeric($luas) || (float) $luas < 0) {
            \App\Config\Database::response([
                'status' => 'error',
                'message' => 'Luas harus berupa angka 0 atau lebih.',
                'data' => null,
            ], 422);
            return;
        }

        $data = (object) [
            'nama' => $nama,
            'lokasi' => $lokasi,
            'luas' => (float) $luas,
            'jenis_tanah' => $jenis_tanah,
        ];

        if ($this->model->create($data)) {
            \App\Config\Database::response([
                'status' => 'success',
                'message' => 'Data berhasil ditambahkan.',
                'data' => $data,
            ], 201);
        } else {
            \App\Config\Database::response([
                'status' => 'error',
                'message' => 'Gagal menambahkan data.',
                'data' => null,
            ], 500);
        }
        return;
    }

    public function update($id)
    {
        $input = json_decode(file_get_contents("php://input"), true);
        if (!is_array($input)) {
            \App\Config\Database::response([
                'status' => 'error',
                'message' => 'Format JSON tidak valid.',
                'data' => null,
            ], 400);
            return;
        }

        $nama = trim((string) ($input['nama'] ?? ''));
        $lokasi = trim((string) ($input['lokasi'] ?? ''));
        $luas = $input['luas'] ?? null;
        $jenis_tanah = trim((string) ($input['jenis_tanah'] ?? ''));

        if ($nama === '' || $lokasi === '' || $jenis_tanah === '') {
            \App\Config\Database::response([
                'status' => 'error',
                'message' => 'Nama, lokasi, dan jenis tanah wajib diisi.',
                'data' => null,
            ], 422);
            return;
        }
        if (!is_numeric($luas) || (float) $luas < 0) {
            \App\Config\Database::response([
                'status' => 'error',
                'message' => 'Luas harus berupa angka 0 atau lebih.',
                'data' => null,
            ], 422);
            return;
        }

        $data = (object) [
            'nama' => $nama,
            'lokasi' => $lokasi,
            'luas' => (float) $luas,
            'jenis_tanah' => $jenis_tanah,
        ];

        if ($this->model->update($id, $data)) {
            \App\Config\Database::response([
                'status' => 'success',
                'message' => 'Data berhasil diupdate.',
                'data' => $data,
            ]);
        } else {
            \App\Config\Database::response([
                'status' => 'error',
                'message' => 'Gagal mengupdate data.',
                'data' => null,
            ], 500);
        }
        return;
    }

    public function delete($id)
    {
        if ($this->model->delete($id)) {
            \App\Config\Database::response([
                'status' => 'success',
                'message' => 'Data berhasil dihapus.',
                'data' => null,
            ]);
        } else {
            \App\Config\Database::response([
                'status' => 'error',
                'message' => 'Gagal menghapus data.',
                'data' => null,
            ], 500);
        }
        return;
    }
}
