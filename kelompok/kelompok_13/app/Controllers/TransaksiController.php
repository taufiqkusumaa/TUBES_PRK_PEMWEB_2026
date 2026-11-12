<?php
/**
 * File: app/Controllers/TransaksiController.php
 * Deskripsi: Controller untuk menangani tampilan halaman transaksi servis,
 * pemrosesan form pemesanan baru, validasi upload foto ke public/uploads/,
 * dan penyediaan data JSON untuk API.
 */

namespace App\Controllers;

use App\Models\TransaksiModel;
use Exception;

class TransaksiController
{
    private TransaksiModel $transaksiModel;
    private string $uploadDir;

    public function __construct()
    {
        $this->transaksiModel = new TransaksiModel();
        // Path absolut menuju folder public/uploads/
        $this->uploadDir = dirname(__DIR__, 2) . '/public/uploads/';

        // Pastikan direktori public/uploads/ tersedia
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Mendapatkan ID user yang sedang login dari Session
     * (Dilengkapi fallback otomatis ke User ID 1 untuk kemudahan pengujian)
     */
    private function getLoggedInUserId(): int
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['user_id'] = 1;
            $_SESSION['user_nama'] = 'Budi Santoso';
            $_SESSION['user_email'] = 'budi.santoso@email.com';
        }
        return (int)$_SESSION['user_id'];
    }

    /**
     * Menampilkan halaman utama Transaksi & Work Order (app/Views/transaksi.php)
     */
    public function index(): void
    {
        $userId = $this->getLoggedInUserId();
        $paketLayanan = $this->transaksiModel->getAllPaketLayanan();
        $riwayatServis = $this->transaksiModel->getRiwayatByUser($userId);

        // Ambil transaksi terbaru untuk ditampilkan otomatis di Stepper Tracking
        $transaksiAktif = !empty($riwayatServis) ? $riwayatServis[0] : null;

        require_once dirname(__DIR__) . '/Views/transaksi.php';
    }

    /**
     * Memproses penyimpanan pemesanan servis baru beserta upload foto kendala
     * ke direktori public/uploads/
     * @return array Response terstruktur untuk JSON API
     */
    public function storeBooking(): array
    {
        try {
            $userId          = $this->getLoggedInUserId();
            $pelatNomor      = trim($_POST['pelat_nomor'] ?? '');
            $merkKendaraan   = trim($_POST['merk_tipe_kendaraan'] ?? 'Kendaraan Pelanggan');
            $paketId         = (int)($_POST['paket_id'] ?? 0);
            $keluhanMesin    = trim($_POST['keluhan_mesin'] ?? '');

            // Validasi input wajib
            if (empty($pelatNomor) || $paketId <= 0 || empty($keluhanMesin)) {
                return [
                    'status'  => 'error',
                    'message' => 'Mohon lengkapi Nomor Pelat Kendaraan, Paket Layanan, dan Keluhan Mesin.'
                ];
            }

            // Validasi format dasar pelat nomor Indonesia (contoh: BE 1234 XY)
            if (!preg_match('/^[A-Z]{1,2}\s?\d{1,4}\s?[A-Z]{0,3}$/i', $pelatNomor)) {
                return [
                    'status'  => 'error',
                    'message' => 'Format pelat nomor tidak valid (Contoh yang benar: BE 1845 YX).'
                ];
            }

            // Proses Upload Foto Kendala jika ada file yang diunggah
            $namaFileFoto = null;
            if (isset($_FILES['foto_kendala']) && $_FILES['foto_kendala']['error'] !== UPLOAD_ERR_NO_FILE) {
                $file = $_FILES['foto_kendala'];

                if ($file['error'] !== UPLOAD_ERR_OK) {
                    return [
                        'status'  => 'error',
                        'message' => 'Terjadi kesalahan saat mengunggah file foto kendala.'
                    ];
                }

                // Validasi ukuran maksimal 5 MB
                $maxSize = 5 * 1024 * 1024;
                if ($file['size'] > $maxSize) {
                    return [
                        'status'  => 'error',
                        'message' => 'Ukuran file foto terlalu besar. Maksimal 5 MB.'
                    ];
                }

                // Validasi MIME Type & Ekstensi Gambar
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->file($file['tmp_name']);
                $allowedMimes = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp'
                ];

                if (!array_key_exists($mimeType, $allowedMimes)) {
                    return [
                        'status'  => 'error',
                        'message' => 'Format foto tidak didukung. Gunakan file JPG, PNG, atau WEBP.'
                    ];
                }

                // Buat nama file unik agar tidak bentrok di public/uploads/
                $ext = $allowedMimes[$mimeType];
                $cleanPlate = preg_replace('/[^A-Z0-9]/i', '', $pelatNomor);
                $namaFileFoto = 'kendala_' . strtolower($cleanPlate) . '_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
                $targetPath = $this->uploadDir . $namaFileFoto;

                if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                    return [
                        'status'  => 'error',
                        'message' => 'Gagal menyimpan file foto ke direktori public/uploads/.'
                    ];
                }
            }

            // Simpan ke database
            $newId = $this->transaksiModel->createBookingServis([
                'user_id'             => $userId,
                'paket_id'            => $paketId,
                'pelat_nomor'         => $pelatNomor,
                'merk_tipe_kendaraan' => $merkKendaraan,
                'keluhan_mesin'       => $keluhanMesin,
                'foto_kendala'        => $namaFileFoto
            ]);

            $detailBaru = $this->transaksiModel->getDetailTransaksi($newId, $userId);

            return [
                'status'  => 'success',
                'message' => 'Work Order servis berhasil dibuat!',
                'data'    => $detailBaru
            ];
        } catch (Exception $e) {
            return [
                'status'  => 'error',
                'message' => 'Kesalahan sistem: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Mengambil data riwayat dan detail transaksi untuk kebutuhan AJAX / Live Tracking
     */
    public function getRiwayatJson(): array
    {
        $userId = $this->getLoggedInUserId();
        $riwayat = $this->transaksiModel->getRiwayatByUser($userId);

        return [
            'status' => 'success',
            'data'   => $riwayat
        ];
    }

    /**
     * Mengubah tahapan progres status servis (Pending -> Antre -> Dikerjakan -> Siap Diambil)
     */
    public function updateStatusJson(): array
    {
        $userId     = $this->getLoggedInUserId();
        $id         = (int)($_POST['transaksi_id'] ?? 0);
        $statusBaru = trim($_POST['status_servis'] ?? '');

        $catatanDefault = [
            'Pending'      => 'Pesanan diterima sistem. Menunggu verifikasi Service Advisor.',
            'Antre'        => 'Kendaraan berada dalam antrean pit bengkel. Menunggu giliran masuk stall.',
            'Dikerjakan'   => 'Mekanik sedang melakukan pengerjaan servis dan pengecekan komponen.',
            'Siap Diambil' => 'Servis selesai dan telah melewati Quality Control. Kendaraan siap diambil.'
        ];

        $catatan = trim($_POST['catatan_mekanik'] ?? ($catatanDefault[$statusBaru] ?? ''));

        $berhasil = $this->transaksiModel->updateStatusProgres($id, $statusBaru, $catatan);
        if (!$berhasil) {
            return [
                'status'  => 'error',
                'message' => 'Gagal memperbarui status Work Order.'
            ];
        }

        $detail = $this->transaksiModel->getDetailTransaksi($id, $userId);
        return [
            'status'  => 'success',
            'message' => "Status Work Order diperbarui menjadi: {$statusBaru}",
            'data'    => $detail
        ];
    }
}