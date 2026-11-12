<?php
/**
 * File: app/Models/TransaksiModel.php
 * Deskripsi: Model untuk mengelola query database terkait Paket Layanan,
 * Pemesanan Servis Baru, Riwayat Servis Pelanggan, dan Update Status Stepper.
 */

namespace App\Models;

use App\Config\Database;
use PDO;

class TransaksiModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Mengambil seluruh daftar paket layanan servis bengkel
     * @return array
     */
    public function getAllPaketLayanan(): array
    {
        $sql = "SELECT id, kode_paket, nama_paket, deskripsi, harga, estimasi_menit 
                FROM paket_layanan 
                ORDER BY harga ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Mengambil daftar riwayat servis milik akun pelanggan yang sedang login
     * @param int $userId ID pelanggan yang sedang login
     * @return array
     */
    public function getRiwayatByUser(int $userId): array
    {
        $sql = "SELECT 
                    t.id,
                    t.kode_wo,
                    t.pelat_nomor,
                    t.merk_tipe_kendaraan,
                    t.keluhan_mesin,
                    t.foto_kendala,
                    t.status_servis,
                    t.catatan_mekanik,
                    t.tanggal_booking,
                    t.updated_at,
                    p.kode_paket,
                    p.nama_paket,
                    p.harga,
                    p.estimasi_menit
                FROM transaksi_servis t
                INNER JOIN paket_layanan p ON t.paket_id = p.id
                WHERE t.user_id = :user_id
                ORDER BY t.tanggal_booking DESC, t.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Mengambil detail 1 transaksi Work Order berdasarkan ID atau Kode WO
     * @param int $id
     * @param int $userId
     * @return array|false
     */
    public function getDetailTransaksi(int $id, int $userId)
    {
        $sql = "SELECT 
                    t.*,
                    p.kode_paket,
                    p.nama_paket,
                    p.deskripsi AS deskripsi_paket,
                    p.harga,
                    p.estimasi_menit,
                    u.nama_lengkap AS nama_pelanggan,
                    u.no_telepon
                FROM transaksi_servis t
                INNER JOIN paket_layanan p ON t.paket_id = p.id
                INNER JOIN users u ON t.user_id = u.id
                WHERE t.id = :id AND t.user_id = :user_id
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id'      => $id,
            ':user_id' => $userId
        ]);
        return $stmt->fetch();
    }

    /**
     * Membuat nomor Work Order otomatis (Format: WO-YYYYMMDD-XXX)
     * @return string
     */
    public function generateKodeWorkOrder(): string
    {
        $today = date('Ymd');
        $prefix = "WO-{$today}-";

        $sql = "SELECT COUNT(*) as total FROM transaksi_servis WHERE kode_wo LIKE :prefix";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':prefix' => $prefix . '%']);
        $row = $stmt->fetch();

        $nextNumber = ((int)($row['total'] ?? 0)) + 1;
        return $prefix . str_pad((string)$nextNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Menyimpan pemesanan servis kendaraan baru ke database
     * @param array $data Data input pemesanan
     * @return int ID transaksi yang baru dibuat
     */
    public function createBookingServis(array $data): int
    {
        $kodeWo = $this->generateKodeWorkOrder();

        $sql = "INSERT INTO transaksi_servis 
                (kode_wo, user_id, paket_id, pelat_nomor, merk_tipe_kendaraan, keluhan_mesin, foto_kendala, status_servis, catatan_mekanik) 
                VALUES 
                (:kode_wo, :user_id, :paket_id, :pelat_nomor, :merk_tipe_kendaraan, :keluhan_mesin, :foto_kendala, 'Pending', :catatan_mekanik)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':kode_wo'             => $kodeWo,
            ':user_id'             => $data['user_id'],
            ':paket_id'            => $data['paket_id'],
            ':pelat_nomor'         => strtoupper(trim($data['pelat_nomor'])),
            ':merk_tipe_kendaraan' => trim($data['merk_tipe_kendaraan']),
            ':keluhan_mesin'       => trim($data['keluhan_mesin']),
            ':foto_kendala'        => $data['foto_kendala'] ?? null,
            ':catatan_mekanik'     => 'Pesanan diterima sistem. Menunggu verifikasi Service Advisor.'
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Memperbarui status progres kendaraan untuk simulasi / update mekanik
     * Status valid: 'Pending', 'Antre', 'Dikerjakan', 'Siap Diambil'
     * @param int $id
     * @param string $statusBaru
     * @param string|null $catatan
     * @return bool
     */
    public function updateStatusProgres(int $id, string $statusBaru, ?string $catatan = null): bool
    {
        $validStatuses = ['Pending', 'Antre', 'Dikerjakan', 'Siap Diambil'];
        if (!in_array($statusBaru, $validStatuses, true)) {
            return false;
        }

        $sql = "UPDATE transaksi_servis 
                SET status_servis = :status, 
                    catatan_mekanik = COALESCE(:catatan, catatan_mekanik)
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':status'  => $statusBaru,
            ':catatan' => $catatan,
            ':id'      => $id
        ]);
    }
}