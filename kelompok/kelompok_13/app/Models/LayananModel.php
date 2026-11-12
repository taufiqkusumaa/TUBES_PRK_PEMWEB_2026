<?php
/**
 * Query PDO untuk paket layanan servis.
 */
class LayananModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getSemua(): array
    {
        $sql = "SELECT l.id, l.kategori_id, l.nama_paket, l.tarif_dasar,
                       l.estimasi_durasi_menit, l.status_layanan,
                       k.jenis, k.merk, k.tipe
                FROM layanan_servis l
                JOIN kategori_kendaraan k ON k.id = l.kategori_id
                ORDER BY l.nama_paket";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getKategori(): array
    {
        return $this->db->query(
            'SELECT id, jenis, merk, tipe FROM kategori_kendaraan ORDER BY jenis, merk, tipe'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function simpan(array $data, ?int $id = null): int
    {
        if ($id === null) {
            $stmt = $this->db->prepare(
                'INSERT INTO layanan_servis (kategori_id, nama_paket, tarif_dasar, estimasi_durasi_menit)
                 VALUES (:kategori_id, :nama_paket, :tarif_dasar, :estimasi_durasi_menit)'
            );
            $stmt->execute($data);
            return (int) $this->db->lastInsertId();
        }

        $stmt = $this->db->prepare(
            'UPDATE layanan_servis
             SET kategori_id = :kategori_id, nama_paket = :nama_paket, tarif_dasar = :tarif_dasar,
                 estimasi_durasi_menit = :estimasi_durasi_menit
             WHERE id = :id'
        );
        $stmt->execute($data + [':id' => $id]);
        return $stmt->rowCount();
    }

    public function hapus(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM layanan_servis WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function digunakan(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM transaksi_servis WHERE layanan_id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        return (bool) $stmt->fetchColumn();
    }

    public function ada(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM layanan_servis WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return (bool) $stmt->fetchColumn();
    }

    public function kategoriAda(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM kategori_kendaraan WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return (bool) $stmt->fetchColumn();
    }
}