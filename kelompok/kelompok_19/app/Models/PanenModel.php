<?php
// app/Models/PanenModel.php
// Tugas: Query SQL murni (PDO) untuk tabel panen.
// Diisi oleh: Putri.

class PanenModel
{
    private $conn;
    private $table_name = "panen";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Susun kondisi filter tanggal (prepared statement, bukan string langsung)
    private function filterTanggal($dari, $sampai, &$params)
    {
        $where = "";
        if ($dari) {
            $where .= " AND p.tanggal_panen >= :dari";
            $params[':dari'] = $dari;
        }
        if ($sampai) {
            $where .= " AND p.tanggal_panen <= :sampai";
            $params[':sampai'] = $sampai;
        }
        return $where;
    }

    // SELECT semua panen + JOIN ke siklus dan lahan, bisa difilter tanggal
    public function read($dari = null, $sampai = null)
    {
        $params = [];
        $query = "SELECT p.id, p.siklus_id, p.tanggal_panen, p.jumlah_kg, p.kualitas, p.catatan,
                         s.nama_tanaman, l.nama AS nama_lahan
                  FROM " . $this->table_name . " p
                  JOIN siklus s ON s.id = p.siklus_id
                  JOIN lahan l ON l.id = s.lahan_id
                  WHERE 1=1" . $this->filterTanggal($dari, $sampai, $params) . "
                  ORDER BY p.tanggal_panen DESC, p.id DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // SELECT satu panen berdasarkan id
    public function readSingle($id)
    {
        $query = "SELECT p.id, p.siklus_id, p.tanggal_panen, p.jumlah_kg, p.kualitas, p.catatan,
                         s.nama_tanaman, l.nama AS nama_lahan
                  FROM " . $this->table_name . " p
                  JOIN siklus s ON s.id = p.siklus_id
                  JOIN lahan l ON l.id = s.lahan_id
                  WHERE p.id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Rekapitulasi: agregasi SUM/COUNT total dan per tanaman
    public function getRingkasan($dari = null, $sampai = null)
    {
        $params = [];
        $filter = $this->filterTanggal($dari, $sampai, $params);

        $queryTotal = "SELECT COUNT(*) AS jumlah_catatan,
                              COALESCE(SUM(p.jumlah_kg), 0) AS total_kg
                       FROM " . $this->table_name . " p
                       JOIN siklus s ON s.id = p.siklus_id
                       JOIN lahan l ON l.id = s.lahan_id
                       WHERE 1=1" . $filter;
        $stmt = $this->conn->prepare($queryTotal);
        $stmt->execute($params);
        $total = $stmt->fetch(PDO::FETCH_ASSOC);

        $queryTanaman = "SELECT s.nama_tanaman,
                                COUNT(*) AS jumlah_catatan,
                                SUM(p.jumlah_kg) AS total_kg
                         FROM " . $this->table_name . " p
                         JOIN siklus s ON s.id = p.siklus_id
                         JOIN lahan l ON l.id = s.lahan_id
                         WHERE 1=1" . $filter . "
                         GROUP BY s.nama_tanaman
                         ORDER BY total_kg DESC";
        $stmt = $this->conn->prepare($queryTanaman);
        $stmt->execute($params);

        return [
            'jumlah_catatan' => (int) $total['jumlah_catatan'],
            'total_kg'       => (float) $total['total_kg'],
            'per_tanaman'    => $stmt->fetchAll(PDO::FETCH_ASSOC),
        ];
    }

    // Untuk dropdown form: daftar siklus beserta nama lahannya
    public function getSiklusOptions()
    {
        $query = "SELECT s.id, s.nama_tanaman, s.status, l.nama AS nama_lahan
                  FROM siklus s
                  JOIN lahan l ON l.id = s.lahan_id
                  ORDER BY s.id DESC";
        return $this->conn->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $query = "INSERT INTO " . $this->table_name . "
                  (siklus_id, tanggal_panen, jumlah_kg, kualitas, catatan)
                  VALUES (:siklus_id, :tanggal_panen, :jumlah_kg, :kualitas, :catatan)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':siklus_id'     => $data['siklus_id'],
            ':tanggal_panen' => $data['tanggal_panen'],
            ':jumlah_kg'     => $data['jumlah_kg'],
            ':kualitas'      => $data['kualitas'],
            ':catatan'       => $data['catatan'],
        ]);
    }

    public function getLastId()
    {
        return (int) $this->conn->lastInsertId();
    }

    public function update($id, $data)
    {
        $query = "UPDATE " . $this->table_name . "
                  SET siklus_id = :siklus_id,
                      tanggal_panen = :tanggal_panen,
                      jumlah_kg = :jumlah_kg,
                      kualitas = :kualitas,
                      catatan = :catatan
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':siklus_id'     => $data['siklus_id'],
            ':tanggal_panen' => $data['tanggal_panen'],
            ':jumlah_kg'     => $data['jumlah_kg'],
            ':kualitas'      => $data['kualitas'],
            ':catatan'       => $data['catatan'],
            ':id'            => $id,
        ]);
    }

    public function delete($id)
    {
        $stmt = $this->conn->prepare("DELETE FROM " . $this->table_name . " WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }
}