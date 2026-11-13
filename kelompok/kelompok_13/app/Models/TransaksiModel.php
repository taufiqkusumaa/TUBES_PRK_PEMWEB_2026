<?php
// File: kelompok/kelompok_13/app/Models/TransaksiModel.php

require_once __DIR__ . '/../Config/Database.php';

class TransaksiModel {
    private $db;
    private $table = 'transaksi_servis';

    public function __construct($db = null) {
        $this->db = $db ?: Database::getConnection();
    }

    /**
     * Ambil seluruh data transaksi servis lengkap dengan relasi customer, kendaraan, layanan, teknisi, dan total sparepart
     */
    public function getAll($filters = []) {
        $sql = "SELECT t.*,
                       u.nama AS nama_customer, u.email AS email_customer, u.no_telp AS telp_customer,
                       k.no_polisi, k.nama_model, k.tahun_pembuatan, k.warna,
                       kat.jenis AS jenis_kendaraan, kat.merk AS merk_kendaraan, kat.tipe AS tipe_kendaraan,
                       l.nama_paket, l.tarif_dasar, l.estimasi_durasi_menit,
                       tek.nama AS nama_teknisi, tek.no_telp AS telp_teknisi,
                       COALESCE((SELECT SUM(w.jumlah * w.harga_satuan) FROM work_order_komponen w WHERE w.transaksi_id = t.id), 0) AS biaya_sparepart
                FROM {$this->table} t
                JOIN users u ON t.user_id = u.id
                JOIN kendaraan k ON t.kendaraan_id = k.id
                JOIN kategori_kendaraan kat ON k.kategori_id = kat.id
                JOIN layanan_servis l ON t.layanan_id = l.id
                LEFT JOIN users tek ON t.teknisi_id = tek.id
                WHERE 1=1";

        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND t.status_servis = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['teknisi_id'])) {
            $sql .= " AND t.teknisi_id = :teknisi_id";
            $params[':teknisi_id'] = (int) $filters['teknisi_id'];
        }

        if (!empty($filters['user_id'])) {
            $sql .= " AND t.user_id = :user_id";
            $params[':user_id'] = (int) $filters['user_id'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (t.kode_transaksi LIKE :search OR k.no_polisi LIKE :search OR u.nama LIKE :search OR k.nama_model LIKE :search)";
            $params[':search'] = '%' . trim($filters['search']) . '%';
        }

        $sql .= " ORDER BY t.tanggal_pesan DESC, t.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Tambahkan alias kunci ini di dalam perulangan fetchAll pada TransaksiModel.php
        foreach ($results as &$row) {
            // Berikan alias ganda untuk semua kemungkinan nama key di frontend
            $row['kode']          = $row['kode_transaksi'] ?? '-';
            $row['no_pkb']        = $row['kode_transaksi'] ?? '-';
            $row['status']        = $row['status_servis'] ?? 'Antre';
            $row['pelanggan']     = $row['nama_customer'] ?? '-';
            $row['no_polisi']     = $row['no_polisi'] ?? '-';
            $row['model']         = $row['nama_model'] ?? '-';
            $row['paket']         = $row['nama_paket'] ?? '-';
            $row['teknisi']       = $row['nama_teknisi'] ?? '-';
            $row['total_biaya']   = (float)($row['tarif_dasar'] ?? 0) + (float)($row['biaya_sparepart'] ?? 0);
        }
        unset($row);

        if ($results) {
            $ids = array_column($results, 'id');
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmtParts = $this->db->prepare(
                "SELECT transaksi_id, nama_suku_cadang AS nama, jumlah AS qty,
                        harga_satuan AS harga
                 FROM work_order_komponen
                 WHERE transaksi_id IN ($placeholders)
                 ORDER BY id ASC"
            );
            $stmtParts->execute($ids);
            $partsByTransaction = [];
            foreach ($stmtParts->fetchAll(PDO::FETCH_ASSOC) as $part) {
                $partsByTransaction[$part['transaksi_id']][] = $part;
            }
            foreach ($results as &$row) {
                $row['sparepart'] = $partsByTransaction[$row['id']] ?? [];
            }
            unset($row);
        }

        return $results;
    }

    /**
     * Ambil satu detail transaksi berdasarkan ID beserta rincian komponen suku cadang (Work Order)
     */
    public function getById($id) {
        $sql = "SELECT t.*,
                       u.nama AS nama_customer, u.email AS email_customer, u.no_telp AS telp_customer,
                       k.no_polisi, k.nama_model, k.tahun_pembuatan, k.warna,
                       kat.jenis AS jenis_kendaraan, kat.merk AS merk_kendaraan, kat.tipe AS tipe_kendaraan,
                       l.nama_paket, l.deskripsi AS deskripsi_layanan, l.tarif_dasar, l.estimasi_durasi_menit,
                       tek.nama AS nama_teknisi, tek.no_telp AS telp_teknisi
                FROM {$this->table} t
                JOIN users u ON t.user_id = u.id
                JOIN kendaraan k ON t.kendaraan_id = k.id
                JOIN kategori_kendaraan kat ON k.kategori_id = kat.id
                JOIN layanan_servis l ON t.layanan_id = l.id
                LEFT JOIN users tek ON t.teknisi_id = tek.id
                WHERE t.id = :id
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => (int) $id]);
        $transaksi = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$transaksi) {
            return null;
        }

        // Ambil daftar komponen/suku cadang dari work_order_komponen
        $stmtParts = $this->db->prepare("
            SELECT w.*, u.nama AS nama_teknisi
            FROM work_order_komponen w
            LEFT JOIN users u ON w.teknisi_id = u.id
            WHERE w.transaksi_id = :id
            ORDER BY w.id ASC
        ");
        $stmtParts->execute([':id' => (int) $id]);
        $komponen = $stmtParts->fetchAll(PDO::FETCH_ASSOC);

        $biayaSparepart = 0;
        foreach ($komponen as $part) {
            $biayaSparepart += ((int) $part['jumlah'] * (float) $part['harga_satuan']);
        }

        $transaksi['komponen'] = $komponen;
        $transaksi['biaya_sparepart'] = $biayaSparepart;
        $transaksi['grand_total'] = (float) $transaksi['tarif_dasar'] + $biayaSparepart;

        return $transaksi;
    }

    /**
     * Ambil transaksi berdasarkan Kode Transaksi (untuk fitur Tracking Pelanggan)
     */
    public function getByKode($kodeTransaksi) {
        $stmt = $this->db->prepare("SELECT id FROM {$this->table} WHERE kode_transaksi = :kode LIMIT 1");
        $stmt->execute([':kode' => trim($kodeTransaksi)]);
        $id = $stmt->fetchColumn();
        return $id ? $this->getById($id) : null;
    }

    /**
     * Generate Kode Transaksi Unik Otomatis: SRV-YYYYMMDD-XXXX
     */
    public function generateKodeTransaksi() {
        $today = date('Ymd');
        $prefix = "SRV-{$today}-";
        $stmt = $this->db->prepare("SELECT kode_transaksi FROM {$this->table} WHERE kode_transaksi LIKE :prefix ORDER BY id DESC LIMIT 1");
        $stmt->execute([':prefix' => $prefix . '%']);
        $lastKode = $stmt->fetchColumn();

        if ($lastKode) {
            $lastNumber = (int) substr($lastKode, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf("%s%04d", $prefix, $nextNumber);
    }

    /**
     * Buat transaksi booking servis baru
     */
    public function create($data) {
        $kodeTransaksi = !empty($data['kode_transaksi']) ? $data['kode_transaksi'] : $this->generateKodeTransaksi();

        // Ambil tarif dasar layanan
        $stmtLayanan = $this->db->prepare("SELECT tarif_dasar FROM layanan_servis WHERE id = :id");
        $stmtLayanan->execute([':id' => (int) $data['layanan_id']]);
        $tarifDasar = (float) $stmtLayanan->fetchColumn();

        // Ambil user_id pemilik kendaraan jika tidak dikirim langsung
        $userId = !empty($data['user_id']) ? (int) $data['user_id'] : null;
        if (!$userId && !empty($data['kendaraan_id'])) {
            $stmtKend = $this->db->prepare("SELECT user_id FROM kendaraan WHERE id = :id");
            $stmtKend->execute([':id' => (int) $data['kendaraan_id']]);
            $userId = (int) $stmtKend->fetchColumn();
        }

        $sql = "INSERT INTO {$this->table}
                (kode_transaksi, user_id, kendaraan_id, layanan_id, teknisi_id, keluhan_mesin, foto_kendala, status_servis, total_biaya, catatan_admin, tanggal_pesan)
                VALUES
                (:kode_transaksi, :user_id, :kendaraan_id, :layanan_id, :teknisi_id, :keluhan_mesin, :foto_kendala, :status_servis, :total_biaya, :catatan_admin, NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':kode_transaksi' => $kodeTransaksi,
            ':user_id'        => $userId,
            ':kendaraan_id'   => (int) $data['kendaraan_id'],
            ':layanan_id'     => (int) $data['layanan_id'],
            ':teknisi_id'     => !empty($data['teknisi_id']) ? (int) $data['teknisi_id'] : null,
            ':keluhan_mesin'  => trim($data['keluhan_mesin']),
            ':foto_kendala'   => $data['foto_kendala'] ?? null,
            ':status_servis'  => $data['status_servis'] ?? 'Antre',
            ':total_biaya'    => $tarifDasar,
            ':catatan_admin'  => !empty($data['catatan_admin']) ? trim($data['catatan_admin']) : null
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * Update status servis, penugasan teknisi, catatan admin, dan sinkronisasi total biaya otomatis
     */
    public function updateStatus($id, $data) {
        $totalBiaya = $this->recalculateTotalBiaya($id);
        $statusBaru = $data['status_servis'] ?? 'Antre';
        $tanggalSelesai = in_array($statusBaru, ['Siap Diambil', 'Selesai']) ? date('Y-m-d H:i:s') : null;

        $sql = "UPDATE {$this->table}
                SET status_servis = :status_servis,
                    teknisi_id = COALESCE(:teknisi_id, teknisi_id),
                    catatan_admin = :catatan_admin,
                    total_biaya = :total_biaya,
                    tanggal_selesai = COALESCE(:tanggal_selesai, tanggal_selesai)
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':status_servis'   => $statusBaru,
            ':teknisi_id'      => !empty($data['teknisi_id']) ? (int) $data['teknisi_id'] : null,
            ':catatan_admin'   => isset($data['catatan_admin']) ? trim($data['catatan_admin']) : null,
            ':total_biaya'     => $totalBiaya,
            ':tanggal_selesai' => $tanggalSelesai,
            ':id'              => (int) $id
        ]);
    }

    /**
     * Hitung ulang dan simpan total_biaya (Tarif Dasar Paket + Total Sparepart Work Order)
     */
    public function recalculateTotalBiaya($id) {
        $stmt = $this->db->prepare("
            SELECT l.tarif_dasar, COALESCE(SUM(w.jumlah * w.harga_satuan), 0) AS total_part
            FROM {$this->table} t
            JOIN layanan_servis l ON t.layanan_id = l.id
            LEFT JOIN work_order_komponen w ON w.transaksi_id = t.id
            WHERE t.id = :id
            GROUP BY t.id, l.tarif_dasar
        ");
        $stmt->execute([':id' => (int) $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $grandTotal = (float) ($row['tarif_dasar'] ?? 0) + (float) ($row['total_part'] ?? 0);

        $updateStmt = $this->db->prepare("UPDATE {$this->table} SET total_biaya = :total WHERE id = :id");
        $updateStmt->execute([':total' => $grandTotal, ':id' => (int) $id]);

        return $grandTotal;
    }

    /**
     * Hapus transaksi servis
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id");
        return $stmt->execute([':id' => (int) $id]);
    }

    /**
     * Statistik Ringkasan untuk Dashboard Operasional
     */
    public function getDashboardStats(?int $userId = null) {
        $sql = "SELECT
                    COUNT(*) AS total_transaksi,
                    SUM(CASE WHEN status_servis IN ('Pending', 'Antre') THEN 1 ELSE 0 END) AS total_antre,
                    SUM(CASE WHEN status_servis = 'Dikerjakan' THEN 1 ELSE 0 END) AS total_dikerjakan,
                    SUM(CASE WHEN status_servis IN ('Siap Diambil', 'Selesai') THEN 1 ELSE 0 END) AS total_selesai,
                    COALESCE(SUM(CASE WHEN status_servis != 'Dibatalkan' THEN total_biaya ELSE 0 END), 0) AS total_pendapatan
                FROM {$this->table}";
        if ($userId !== null) {
            $sql .= ' WHERE user_id = :user_id';
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return $this->db->query($sql)->fetch(PDO::FETCH_ASSOC);
    }
}