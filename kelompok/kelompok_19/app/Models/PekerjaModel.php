<?php
// app/Models/PekerjaModel.php
// Tugas: Query SQL murni (PDO) untuk tabel pekerja.
// Diisi oleh: Devi.

class PekerjaModel
{
    private $conn;
    private $table_name = "pekerja";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // TODO: method read() -> SELECT * FROM pekerja
    public function read()
    {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // TODO: method readSingle($id) -> SELECT * FROM pekerja WHERE id = ?
    public function readSingle($id)
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // TODO: method create($data) -> INSERT INTO pekerja (...)
    public function create($data)
    {
        $query = "INSERT INTO " . $this->table_name . " (nama, no_telepon, jabatan, gaji_pokok, tanggal_bergabung) 
                  VALUES (:nama, :no_telepon, :jabatan, :gaji_pokok, :tanggal_bergabung)";
        $stmt = $this->conn->prepare($query);

        $nama = $data['nama'] ?? '';
        $no_telepon = $data['no_telepon'] ?? null;
        $jabatan = $data['jabatan'] ?? null;
        $gaji_pokok = $data['gaji_pokok'] ?? 0;
        $tanggal_bergabung = $data['tanggal_bergabung'] ?? null;

        $stmt->bindParam(':nama', $nama);
        $stmt->bindParam(':no_telepon', $no_telepon);
        $stmt->bindParam(':jabatan', $jabatan);
        $stmt->bindParam(':gaji_pokok', $gaji_pokok);
        $stmt->bindParam(':tanggal_bergabung', $tanggal_bergabung);

        return $stmt->execute();
    }

    // TODO: method update($id, $data) -> UPDATE pekerja SET ... WHERE id = ?
    public function update($id, $data)
    {
        $query = "UPDATE " . $this->table_name . " 
                  SET nama = :nama, no_telepon = :no_telepon, jabatan = :jabatan, 
                      gaji_pokok = :gaji_pokok, tanggal_bergabung = :tanggal_bergabung 
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        $nama = $data['nama'] ?? '';
        $no_telepon = $data['no_telepon'] ?? null;
        $jabatan = $data['jabatan'] ?? null;
        $gaji_pokok = $data['gaji_pokok'] ?? 0;
        $tanggal_bergabung = $data['tanggal_bergabung'] ?? null;

        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':nama', $nama);
        $stmt->bindParam(':no_telepon', $no_telepon);
        $stmt->bindParam(':jabatan', $jabatan);
        $stmt->bindParam(':gaji_pokok', $gaji_pokok);
        $stmt->bindParam(':tanggal_bergabung', $tanggal_bergabung);

        return $stmt->execute();
    }

    // TODO: method delete($id) -> DELETE FROM pekerja WHERE id = ?
    public function delete($id)
    {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function getTotalUpah()
    {
        $query = "SELECT SUM(upah_harian) AS total_upah FROM presensi_pekerja";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total_upah'] ?? 0;
    }

    // Presensi pekerja: satu tabel, filter tanggal/pekerja/siklus opsional.
    public function readPresensi($pekerja_id = null, $tanggal = null, $siklus_id = null)
    {
        $query = "SELECT pr.id, pr.pekerja_id, pr.siklus_id, pr.tanggal, pr.upah_harian,
                         p.nama AS nama_pekerja, p.jabatan,
                         s.nama_tanaman, l.nama AS nama_lahan
                  FROM presensi_pekerja pr
                  INNER JOIN pekerja p ON p.id = pr.pekerja_id
                  LEFT JOIN siklus s ON s.id = pr.siklus_id
                  LEFT JOIN lahan l ON l.id = s.lahan_id
                  WHERE 1 = 1";

        $params = [];
        if ($pekerja_id) {
            $query .= " AND pr.pekerja_id = :pekerja_id";
            $params[':pekerja_id'] = $pekerja_id;
        }
        if ($siklus_id) {
            $query .= " AND pr.siklus_id = :siklus_id";
            $params[':siklus_id'] = $siklus_id;
        }
        if ($tanggal) {
            $query .= " AND pr.tanggal = :tanggal";
            $params[':tanggal'] = $tanggal;
        }

        $query .= " ORDER BY pr.tanggal DESC, pr.id DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function presensiAda($id)
    {
        $query = "SELECT COUNT(*) FROM presensi_pekerja WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return (int) $stmt->fetchColumn() > 0;
    }

    // Tabel tidak punya UNIQUE (pekerja_id, siklus_id, tanggal), jadi duplikat dicek di sini.
    public function presensiDuplikat($pekerja_id, $siklus_id, $tanggal, $kecuali_id = null)
    {
        $query = "SELECT COUNT(*) FROM presensi_pekerja
                  WHERE pekerja_id = :pekerja_id AND siklus_id = :siklus_id AND tanggal = :tanggal";
        if ($kecuali_id) {
            $query .= " AND id <> :kecuali_id";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':pekerja_id', $pekerja_id);
        $stmt->bindParam(':siklus_id', $siklus_id);
        $stmt->bindParam(':tanggal', $tanggal);
        if ($kecuali_id) {
            $stmt->bindParam(':kecuali_id', $kecuali_id);
        }
        $stmt->execute();
        return (int) $stmt->fetchColumn() > 0;
    }

    public function createPresensi($data)
    {
        $query = "INSERT INTO presensi_pekerja (pekerja_id, siklus_id, tanggal, upah_harian)
                  VALUES (:pekerja_id, :siklus_id, :tanggal, :upah_harian)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':pekerja_id', $data['pekerja_id']);
        $stmt->bindParam(':siklus_id', $data['siklus_id']);
        $stmt->bindParam(':tanggal', $data['tanggal']);
        $stmt->bindParam(':upah_harian', $data['upah_harian']);

        return $stmt->execute();
    }

    public function updatePresensi($id, $data)
    {
        $query = "UPDATE presensi_pekerja
                  SET pekerja_id = :pekerja_id, siklus_id = :siklus_id,
                      tanggal = :tanggal, upah_harian = :upah_harian
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':pekerja_id', $data['pekerja_id']);
        $stmt->bindParam(':siklus_id', $data['siklus_id']);
        $stmt->bindParam(':tanggal', $data['tanggal']);
        $stmt->bindParam(':upah_harian', $data['upah_harian']);

        return $stmt->execute();
    }

    public function deletePresensi($id)
    {
        $query = "DELETE FROM presensi_pekerja WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}