<?php

class SparepartModel {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function getByTransaksi(int $transaksiId): array {
        $stmt = $this->db->prepare("
            SELECT w.*, u.nama AS nama_teknisi,
                   w.jumlah * w.harga_satuan AS subtotal
            FROM work_order_komponen w
            LEFT JOIN users u ON u.id = w.teknisi_id
            WHERE w.transaksi_id = :transaksi_id
            ORDER BY w.id ASC
        ");
        $stmt->execute([':transaksi_id' => $transaksiId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare('SELECT * FROM work_order_komponen WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function simpan(array $data, ?int $id = null): int {
        if ($id === null) {
            $stmt = $this->db->prepare("
                INSERT INTO work_order_komponen
                    (transaksi_id, teknisi_id, nama_suku_cadang, jumlah, harga_satuan, foto_komponen, catatan_perbaikan)
                VALUES
                    (:transaksi_id, :teknisi_id, :nama_suku_cadang, :jumlah, :harga_satuan, :foto_komponen, :catatan_perbaikan)
            ");
            $stmt->execute($data);
            return (int) $this->db->lastInsertId();
        }

        $stmt = $this->db->prepare("
            UPDATE work_order_komponen
            SET nama_suku_cadang = :nama_suku_cadang,
                jumlah = :jumlah,
                harga_satuan = :harga_satuan,
                foto_komponen = :foto_komponen,
                catatan_perbaikan = :catatan_perbaikan
            WHERE id = :id AND transaksi_id = :transaksi_id
        ");
        $stmt->execute($data + [':id' => $id]);
        return $stmt->rowCount();
    }

    public function hapus(int $id, int $transaksiId): bool {
        $stmt = $this->db->prepare(
            'DELETE FROM work_order_komponen WHERE id = :id AND transaksi_id = :transaksi_id'
        );
        $stmt->execute([':id' => $id, ':transaksi_id' => $transaksiId]);
        return $stmt->rowCount() > 0;
    }
}
