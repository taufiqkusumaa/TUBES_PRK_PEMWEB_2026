
<?php
// app/Models/SiklusModel.php
// Tugas: Query SQL murni (PDO) untuk tabel siklus.
// Diisi oleh: Hengky.

class SiklusModel
{
    private $conn;
    private $table_name = "siklus";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // TODO: method read() -> SELECT * FROM siklus
    public function read($filters = [])
    {
        $query = "SELECT s.*, 
                         l.nama AS nama_lahan,
                         l.lokasi AS lokasi_lahan
                  FROM " . $this->table_name . " s
                  INNER JOIN lahan l ON s.lahan_id = l.id
                  WHERE 1 = 1";

        $params = [];

        if (!empty($filters['q'])) {
            $query .= " AND (s.nama_tanaman LIKE :q OR l.nama LIKE :q OR l.lokasi LIKE :q)";
            $params[':q'] = '%' . $filters['q'] . '%';
        }

        if (!empty($filters['status'])) {
            $query .= " AND s.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['lahan_id'])) {
            $query .= " AND s.lahan_id = :lahan_id";
            $params[':lahan_id'] = $filters['lahan_id'];
        }

        if (!empty($filters['dari'])) {
            $query .= " AND s.tanggal_tanam >= :dari";
            $params[':dari'] = $filters['dari'];
        }

        if (!empty($filters['sampai'])) {
            $query .= " AND s.tanggal_tanam <= :sampai";
            $params[':sampai'] = $filters['sampai'];
        }

        $query .= " ORDER BY s.id DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // TODO: method readSingle($id) -> SELECT * FROM siklus WHERE id = ?
    public function readSingle($id)
    {
        $query = "SELECT s.*, 
                         l.nama AS nama_lahan,
                         l.lokasi AS lokasi_lahan
                  FROM " . $this->table_name . " s
                  INNER JOIN lahan l ON s.lahan_id = l.id
                  WHERE s.id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // TODO: method create($data) -> INSERT INTO siklus (...)
    public function create($data)
    {
        $query = "INSERT INTO " . $this->table_name . "
                  (lahan_id, nama_tanaman, tanggal_tanam,
                   tanggal_panen_estimasi, status)
                  VALUES (?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($query);

        return $stmt->execute([
            $data['lahan_id'],
            $data['nama_tanaman'],
            $data['tanggal_tanam'],
            $data['tanggal_panen_estimasi'] ?? null,
            $data['status'] ?? 'Aktif'
        ]);
    }

    // TODO: method update($id, $data) -> UPDATE siklus SET ... WHERE id = ?
    public function update($id, $data)
    {
        $query = "UPDATE " . $this->table_name . "
                  SET lahan_id = ?,
                      nama_tanaman = ?,
                      tanggal_tanam = ?,
                      tanggal_panen_estimasi = ?,
                      status = ?
                  WHERE id = ?";

        $stmt = $this->conn->prepare($query);

        return $stmt->execute([
            $data['lahan_id'],
            $data['nama_tanaman'],
            $data['tanggal_tanam'],
            $data['tanggal_panen_estimasi'] ?? null,
            $data['status'],
            $id
        ]);
    }

    // TODO: method delete($id) -> DELETE FROM siklus WHERE id = ?
    public function delete($id)
    {
        $query = "DELETE FROM " . $this->table_name . "
                  WHERE id = ?";

        $stmt = $this->conn->prepare($query);

        return $stmt->execute([$id]);
    }
}
