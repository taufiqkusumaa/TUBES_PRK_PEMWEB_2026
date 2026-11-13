<?php
// app/Controllers/InventarisController.php
// Tugas: Menerima request, memanggil Model Inventaris, mengembalikan JSON.

require_once __DIR__ . "/../Config/Database.php";
require_once __DIR__ . "/../Models/InventarisModel.php";

class InventarisController
{
    private $model;

    public function __construct()
    {
        $database = new \App\Config\Database();
        $db = $database->getConnection();
        $this->model = new InventarisModel($db);
    }

    public function index()
    {
        $stmt = $this->model->read();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        \App\Config\Database::response([
            'status' => 'success',
            'message' => 'Data inventaris berhasil diambil.',
            'data' => $data,
        ]);
        return;
    }

    public function show($id)
    {
        $data = $this->model->readSingle($id);

        if ($data) {
            \App\Config\Database::response([
                'status' => 'success',
                'message' => 'Data inventaris ditemukan.',
                'data' => $data,
            ]);
        } else {
            \App\Config\Database::response([
                'status' => 'error',
                'message' => 'Data inventaris tidak ditemukan.',
                'data' => null,
            ], 404);
        }
        return;
    }

    public function create()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            \App\Config\Database::response([
                'status' => 'error',
                'message' => 'Format JSON tidak valid.',
                'data' => null,
            ], 400);
            return;
        }

        $nama = trim((string) ($input['nama'] ?? ''));
        $kategori = trim((string) ($input['kategori'] ?? ''));
        $stok = $input['stok'] ?? null;
        $satuan = trim((string) ($input['satuan'] ?? ''));
        $harga_satuan = $input['harga_satuan'] ?? null;

        if ($nama === '' || $kategori === '' || $satuan === '') {
            \App\Config\Database::response([
                'status' => 'error',
                'message' => 'Nama, kategori, dan satuan wajib diisi.',
                'data' => null,
            ], 422);
            return;
        }
        if (!is_numeric($stok) || (float) $stok < 0) {
            \App\Config\Database::response([
                'status' => 'error',
                'message' => 'Stok harus berupa angka 0 atau lebih.',
                'data' => null,
            ], 422);
            return;
        }
        if (!is_numeric($harga_satuan) || (float) $harga_satuan < 0) {
            \App\Config\Database::response([
                'status' => 'error',
                'message' => 'Harga satuan harus berupa angka 0 atau lebih.',
                'data' => null,
            ], 422);
            return;
        }

        $data = [
            'nama' => $nama,
            'kategori' => $kategori,
            'stok' => (float) $stok,
            'satuan' => $satuan,
            'harga_satuan' => (float) $harga_satuan,
        ];

        if ($this->model->create($data)) {
            \App\Config\Database::response([
                'status' => 'success',
                'message' => 'Data barang berhasil ditambahkan.',
                'data' => $data,
            ], 201);
        } else {
            \App\Config\Database::response([
                'status' => 'error',
                'message' => 'Gagal menambah data barang.',
                'data' => null,
            ], 500);
        }
        return;
    }

    public function update($id)
    {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            \App\Config\Database::response([
                'status' => 'error',
                'message' => 'Format JSON tidak valid.',
                'data' => null,
            ], 400);
            return;
        }

        $nama = trim((string) ($input['nama'] ?? ''));
        $kategori = trim((string) ($input['kategori'] ?? ''));
        $stok = $input['stok'] ?? null;
        $satuan = trim((string) ($input['satuan'] ?? ''));
        $harga_satuan = $input['harga_satuan'] ?? null;

        if ($nama === '' || $kategori === '' || $satuan === '') {
            \App\Config\Database::response([
                'status' => 'error',
                'message' => 'Nama, kategori, dan satuan wajib diisi.',
                'data' => null,
            ], 422);
            return;
        }
        if (!is_numeric($stok) || (float) $stok < 0) {
            \App\Config\Database::response([
                'status' => 'error',
                'message' => 'Stok harus berupa angka 0 atau lebih.',
                'data' => null,
            ], 422);
            return;
        }
        if (!is_numeric($harga_satuan) || (float) $harga_satuan < 0) {
            \App\Config\Database::response([
                'status' => 'error',
                'message' => 'Harga satuan harus berupa angka 0 atau lebih.',
                'data' => null,
            ], 422);
            return;
        }

        $data = [
            'nama' => $nama,
            'kategori' => $kategori,
            'stok' => (float) $stok,
            'satuan' => $satuan,
            'harga_satuan' => (float) $harga_satuan,
        ];

        if ($this->model->update($id, $data)) {
            \App\Config\Database::response([
                'status' => 'success',
                'message' => 'Data barang berhasil diperbarui.',
                'data' => $data,
            ]);
        } else {
            \App\Config\Database::response([
                'status' => 'error',
                'message' => 'Gagal memperbarui data barang.',
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
                'message' => 'Data barang berhasil dihapus.',
                'data' => null,
            ]);
        } else {
            \App\Config\Database::response([
                'status' => 'error',
                'message' => 'Gagal menghapus data barang.',
                'data' => null,
            ], 500);
        }
        return;
    }

    public function indexTransaksi()
    {
        $stmt = $this->model->readTransaksi();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        \App\Config\Database::response([
            'status' => 'success',
            'message' => 'Riwayat transaksi berhasil diambil.',
            'data' => $data,
        ]);
        return;
    }

    public function createTransaksi()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            \App\Config\Database::response([
                'status' => 'error',
                'message' => 'Format JSON tidak valid.',
                'data' => null,
            ], 400);
            return;
        }

        if ($this->model->createTransaksi($input)) {
            \App\Config\Database::response([
                'status' => 'success',
                'message' => 'Transaksi berhasil dicatat.',
                'data' => null,
            ]);
        } else {
            \App\Config\Database::response([
                'status' => 'error',
                'message' => 'Gagal mencatat transaksi.',
                'data' => null,
            ], 500);
        }
        return;
    }
}
