<?php
// app/Controllers/PekerjaController.php
// Tugas: Menerima request, memanggil Model Pekerja, mengembalikan JSON.
// Diisi oleh : Devi Novita

require_once __DIR__ . "/../Config/Database.php";
require_once __DIR__ . "/../Models/PekerjaModel.php";

class PekerjaController
{
    private $model;

    public function __construct()
    {
        $database = new \App\Config\Database();
        $db = $database->getConnection();
        $this->model = new PekerjaModel($db);
    }

    public function index()
    {
        $data = $this->model->read();
        $totalUpah = $this->model->getTotalUpah();
        \App\Config\Database::response([
            'status' => 'success',
            'message' => 'Data pekerja berhasil diambil.',
            'data' => $data,
            'total_upah' => $totalUpah,
        ]);
        return;
    }

    public function show($id)
    {
        $data = $this->model->readSingle($id);
        if ($data) {
            \App\Config\Database::response([
                'status' => 'success',
                'message' => 'Data pekerja ditemukan.',
                'data' => $data,
            ]);
        } else {
            http_response_code(404);
            \App\Config\Database::response([
                'status' => 'error',
                'message' => 'Data pekerja tidak ditemukan.',
                'data' => null,
            ]);
        }
        return;
    }

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

        if ($this->model->create($data)) {
            \App\Config\Database::response([
                'status' => 'success',
                'message' => 'Data pekerja berhasil ditambahkan.',
                'data' => $data,
            ], 201);
        } else {
            \App\Config\Database::response([
                'status' => 'error',
                'message' => 'Gagal menambahkan data pekerja.',
                'data' => null,
            ], 500);
        }
        return;
    }

    public function update($id)
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

        if (!$this->model->readSingle($id)) {
            \App\Config\Database::response([
                'status' => 'error',
                'message' => 'Data pekerja tidak ditemukan.',
                'data' => null,
            ], 404);
            return;
        }

        if ($this->model->update($id, $data)) {
            \App\Config\Database::response([
                'status' => 'success',
                'message' => 'Data pekerja berhasil diperbarui.',
                'data' => $data,
            ]);
        } else {
            \App\Config\Database::response([
                'status' => 'error',
                'message' => 'Gagal memperbarui data pekerja.',
                'data' => null,
            ], 500);
        }
        return;
    }

    public function delete($id)
    {
        if (!$this->model->readSingle($id)) {
            \App\Config\Database::response([
                'status' => 'error',
                'message' => 'Data pekerja tidak ditemukan.',
                'data' => null,
            ], 404);
            return;
        }

        if ($this->model->delete($id)) {
            \App\Config\Database::response([
                'status' => 'success',
                'message' => 'Data pekerja berhasil dihapus.',
                'data' => null,
            ]);
        } else {
            \App\Config\Database::response([
                'status' => 'error',
                'message' => 'Gagal menghapus data pekerja.',
                'data' => null,
            ], 500);
        }
        return;
    }

    public function indexPresensi()
    {
        $tanggal = $_GET['tanggal'] ?? '';
        $tanggal = is_string($tanggal) && $tanggal !== '' ? $tanggal : null;

        if ($tanggal !== null && !$this->tanggalValid($tanggal)) {
            \App\Config\Database::response([
                'status' => 'error',
                'message' => 'Tanggal filter tidak valid (format YYYY-MM-DD).',
                'data' => null,
            ], 422);
            return;
        }

        try {
            $data = $this->model->readPresensi(
                $this->angkaParam('pekerja_id'),
                $tanggal,
                $this->angkaParam('siklus_id')
            );
            \App\Config\Database::response([
                'status' => 'success',
                'message' => 'Data presensi pekerja berhasil diambil.',
                'data' => $data,
            ]);
        } catch (PDOException $e) {
            error_log($e->getMessage());
            \App\Config\Database::response([
                'status' => 'error',
                'message' => 'Gagal mengambil data presensi pekerja.',
                'data' => null,
            ], 500);
        }
        return;
    }

    public function createPresensi()
    {
        [$data, $errors] = $this->validasiPresensi();

        if (!$errors && $this->model->presensiDuplikat($data['pekerja_id'], $data['siklus_id'], $data['tanggal'])) {
            $errors[] = 'Presensi pekerja ini sudah tercatat pada tanggal tersebut.';
        }

        if ($errors) {
            \App\Config\Database::response([
                'status' => 'error',
                'message' => implode(' ', $errors),
                'data' => null,
            ], 422);
            return;
        }

        try {
            if ($this->model->createPresensi($data)) {
                \App\Config\Database::response([
                    'status' => 'success',
                    'message' => 'Presensi pekerja berhasil dicatat.',
                    'data' => $data,
                ], 201);
            } else {
                \App\Config\Database::response([
                    'status' => 'error',
                    'message' => 'Gagal menyimpan presensi pekerja.',
                    'data' => null,
                ], 500);
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            \App\Config\Database::response([
                'status' => 'error',
                'message' => 'Gagal menyimpan presensi pekerja.',
                'data' => null,
            ], 500);
        }
        return;
    }

    public function updatePresensi($id)
    {
        if (!$this->model->presensiAda($id)) {
            \App\Config\Database::response([
                'status' => 'error',
                'message' => 'Data presensi tidak ditemukan.',
                'data' => null,
            ], 404);
            return;
        }

        [$data, $errors] = $this->validasiPresensi();

        if (!$errors && $this->model->presensiDuplikat($data['pekerja_id'], $data['siklus_id'], $data['tanggal'], $id)) {
            $errors[] = 'Presensi pekerja ini sudah tercatat pada tanggal tersebut.';
        }

        if ($errors) {
            \App\Config\Database::response([
                'status' => 'error',
                'message' => implode(' ', $errors),
                'data' => null,
            ], 422);
            return;
        }

        try {
            if ($this->model->updatePresensi($id, $data)) {
                \App\Config\Database::response([
                    'status' => 'success',
                    'message' => 'Presensi pekerja berhasil diperbarui.',
                    'data' => $data,
                ]);
            } else {
                \App\Config\Database::response([
                    'status' => 'error',
                    'message' => 'Gagal memperbarui presensi pekerja.',
                    'data' => null,
                ], 500);
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            \App\Config\Database::response([
                'status' => 'error',
                'message' => 'Gagal memperbarui presensi pekerja.',
                'data' => null,
            ], 500);
        }
        return;
    }

    public function deletePresensi($id)
    {
        if (!$this->model->presensiAda($id)) {
            \App\Config\Database::response([
                'status' => 'error',
                'message' => 'Data presensi tidak ditemukan.',
                'data' => null,
            ], 404);
            return;
        }

        if ($this->model->deletePresensi($id)) {
            \App\Config\Database::response([
                'status' => 'success',
                'message' => 'Presensi pekerja berhasil dihapus.',
                'data' => null,
            ]);
        } else {
            \App\Config\Database::response([
                'status' => 'error',
                'message' => 'Gagal menghapus presensi pekerja.',
                'data' => null,
            ], 500);
        }
        return;
    }

    private function tanggalValid($tanggal)
    {
        $parsed = DateTime::createFromFormat('Y-m-d', $tanggal);
        return $parsed && $parsed->format('Y-m-d') === $tanggal;
    }

    private function angkaParam($nama)
    {
        $nilai = $_GET[$nama] ?? null;
        return is_numeric($nilai) ? (int) $nilai : null;
    }

    // Validasi + bersihkan input presensi. Mengembalikan [data_bersih, daftar_error]
    private function validasiPresensi()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            $input = [];
        }

        $errors = [];

        $pekerja_id = (int) ($input['pekerja_id'] ?? 0);
        if ($pekerja_id <= 0) {
            $errors[] = 'Pekerja wajib dipilih.';
        } elseif (!$this->model->readSingle($pekerja_id)) {
            $errors[] = 'Data pekerja tidak ditemukan.';
        }

        $siklus_id = (int) ($input['siklus_id'] ?? 0);
        if ($siklus_id <= 0) {
            $errors[] = 'Siklus tanam wajib dipilih.';
        }

        $tanggal = trim((string) ($input['tanggal'] ?? ''));
        $parsed = DateTime::createFromFormat('Y-m-d', $tanggal);
        if (!$parsed || $parsed->format('Y-m-d') !== $tanggal) {
            $errors[] = 'Tanggal presensi tidak valid (format YYYY-MM-DD).';
        }

        $upah_harian = $input['upah_harian'] ?? null;
        if (!is_numeric($upah_harian) || (float) $upah_harian < 0) {
            $errors[] = 'Upah harian harus berupa angka 0 atau lebih.';
        }

        return [[
            'pekerja_id' => $pekerja_id,
            'siklus_id' => $siklus_id,
            'tanggal' => $tanggal,
            'upah_harian' => is_numeric($upah_harian) ? (float) $upah_harian : 0,
        ], $errors];
    }

    // Validasi + bersihkan input. Mengembalikan [data_bersih, daftar_error]
    private function validasi()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            $input = [];
        }

        $errors = [];

        $nama = trim((string) ($input['nama'] ?? ''));
        if ($nama === '') {
            $errors[] = 'Nama pekerja wajib diisi.';
        }

        $no_telepon = trim((string) ($input['no_telepon'] ?? ''));
        if ($no_telepon === '') {
            $errors[] = 'No. telepon wajib diisi.';
        }

        $jabatan = trim((string) ($input['jabatan'] ?? ''));
        if ($jabatan === '') {
            $errors[] = 'Jabatan wajib dipilih.';
        }

        $gaji_pokok = $input['gaji_pokok'] ?? null;
        if (!is_numeric($gaji_pokok) || (float) $gaji_pokok < 0) {
            $errors[] = 'Gaji pokok harus berupa angka 0 atau lebih.';
        }

        $tanggal_bergabung = trim((string) ($input['tanggal_bergabung'] ?? ''));
        $tanggal = DateTime::createFromFormat('Y-m-d', $tanggal_bergabung);
        if (!$tanggal || $tanggal->format('Y-m-d') !== $tanggal_bergabung) {
            $errors[] = 'Tanggal bergabung tidak valid (format YYYY-MM-DD).';
        }

        return [[
            'nama' => $nama,
            'no_telepon' => $no_telepon,
            'jabatan' => $jabatan,
            'gaji_pokok' => is_numeric($gaji_pokok) ? (float) $gaji_pokok : 0,
            'tanggal_bergabung' => $tanggal_bergabung,
        ], $errors];
    }
}
