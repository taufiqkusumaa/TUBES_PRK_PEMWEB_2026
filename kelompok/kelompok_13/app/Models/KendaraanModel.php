<?php
/**
 * Query PDO untuk kategori kendaraan dan data kendaraan bengkel.
 */
class KendaraanModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getKategori(): array
    {
        $sql = "SELECT k.id, k.jenis, k.merk, k.tipe,
                       COUNT(v.id) AS jumlah_kendaraan
                FROM kategori_kendaraan k
                LEFT JOIN kendaraan v ON v.kategori_id = k.id
                GROUP BY k.id, k.jenis, k.merk, k.tipe
                ORDER BY k.jenis, k.merk, k.tipe";

        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function simpanKategori(array $data, ?int $id = null): int
    {
        if ($id === null) {
            $stmt = $this->db->prepare(
                'INSERT INTO kategori_kendaraan (jenis, merk, tipe) VALUES (:jenis, :merk, :tipe)'
            );
            $stmt->execute($data);
            return (int) $this->db->lastInsertId();
        }

        $stmt = $this->db->prepare(
            'UPDATE kategori_kendaraan SET jenis = :jenis, merk = :merk, tipe = :tipe WHERE id = :id'
        );
        $stmt->execute($data + [':id' => $id]);
        return $stmt->rowCount();
    }

    public function hapusKategori(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM kategori_kendaraan WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function kategoriDigunakan(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM kendaraan WHERE kategori_id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        return (bool) $stmt->fetchColumn();
    }

    public function getKendaraan(): array
{
    $sql = "SELECT v.id, v.user_id, v.kategori_id, v.no_polisi, v.nama_model, v.tahun_pembuatan, v.warna,
                   k.jenis, k.merk, k.tipe,
                   u.nama AS nama_pemilik, u.no_telp
            FROM kendaraan v
            JOIN kategori_kendaraan k ON k.id = v.kategori_id
            LEFT JOIN users u ON u.id = v.user_id
            ORDER BY v.id DESC";

    return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}
    public function getPelanggan(): array
    {
        return $this->db->query(
            "SELECT id, nama, email FROM users
             WHERE role = 'customer' AND status_aktif = 1
             ORDER BY nama"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function pelangganAda(int $id): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM users WHERE id = :id AND role = 'customer' AND status_aktif = 1"
        );
        $stmt->execute([':id' => $id]);
        return (bool) $stmt->fetchColumn();
    }

    public function simpanKendaraan(array $data, ?int $id = null): int
    {
        if ($id === null) {
            $stmt = $this->db->prepare(
                'INSERT INTO kendaraan (user_id, kategori_id, no_polisi, nama_model, tahun_pembuatan, warna)
                 VALUES (:user_id, :kategori_id, :no_polisi, :nama_model, :tahun_pembuatan, :warna)'
            );
            $stmt->execute($data);
            return (int) $this->db->lastInsertId();
        }

        $stmt = $this->db->prepare(
            'UPDATE kendaraan
             SET user_id = :user_id, kategori_id = :kategori_id, no_polisi = :no_polisi,
                 nama_model = :nama_model, tahun_pembuatan = :tahun_pembuatan, warna = :warna
             WHERE id = :id'
        );
        $stmt->execute($data + [':id' => $id]);
        return $stmt->rowCount();
    }

    public function hapusKendaraan(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM kendaraan WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function kendaraanAda(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM kendaraan WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return (bool) $stmt->fetchColumn();
    }

    public function kendaraanDimiliki(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1 FROM kendaraan WHERE id = :id AND user_id = :user_id'
        );
        $stmt->execute([':id' => $id, ':user_id' => $userId]);
        return (bool) $stmt->fetchColumn();
    }

    public function kendaraanDigunakan(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM transaksi_servis WHERE kendaraan_id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        return (bool) $stmt->fetchColumn();
    }

    public function nomorPolisiTerpakai(string $nomor, ?int $kecualiId = null): bool
    {
        $sql = 'SELECT 1 FROM kendaraan WHERE no_polisi = :nomor';
        $params = [':nomor' => $nomor];
        if ($kecualiId !== null) {
            $sql .= ' AND id <> :id';
            $params[':id'] = $kecualiId;
        }
        $stmt = $this->db->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }

    public function kategoriAda(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM kategori_kendaraan WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return (bool) $stmt->fetchColumn();
    }
}