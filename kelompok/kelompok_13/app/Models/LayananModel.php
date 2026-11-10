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
        $sql = "SELECT id, nama_paket, tarif_dasar, estimasi_durasi_menit
                FROM layanan_servis
                ORDER BY nama_paket";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function simpan(array $data, ?int $id = null): int
    {
        if ($id === null) {
            $stmt = $this->db->prepare(
                'INSERT INTO layanan_servis (nama_paket, tarif_dasar, estimasi_durasi_menit)
                 VALUES (:nama_paket, :tarif_dasar, :estimasi_durasi_menit)'
            );
            $stmt->execute($data);
            return (int) $this->db->lastInsertId();
        }

        $stmt = $this->db->prepare(
            'UPDATE layanan_servis
             SET nama_paket = :nama_paket, tarif_dasar = :tarif_dasar,
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
}