<?php
// app/Models/LahanModel.php
// Tugas: Query SQL murni (PDO) untuk tabel lahan.
// Diisi oleh: Faqih.

class LahanModel
{
    private $conn;
    private $table_name = "lahan";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // TODO: method read() -> SELECT * FROM lahan
    public function read()
    {
        $query = "SELECT * FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // TODO: method readSingle($id) -> SELECT * FROM lahan WHERE id = ?
    public function readSingle($id)
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        return $stmt;
    }

    // TODO: method create($data) -> INSERT INTO lahan (...)
    public function create($data)
    {
        $query = "INSERT INTO " . $this->table_name . " SET nama = :nama, lokasi = :lokasi, luas = :luas, jenis_tanah = :jenis_tanah";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":nama", $data->nama);
        $stmt->bindParam(":lokasi", $data->lokasi);
        $stmt->bindParam(":luas", $data->luas);
        $stmt->bindParam(":jenis_tanah", $data->jenis_tanah);
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // TODO: method update($id, $data) -> UPDATE lahan SET ... WHERE id = ?
    public function update($id, $data)
    {
        $query = "UPDATE " . $this->table_name . " SET nama = :nama, lokasi = :lokasi, luas = :luas, jenis_tanah = :jenis_tanah WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":nama", $data->nama);
        $stmt->bindParam(":lokasi", $data->lokasi);
        $stmt->bindParam(":luas", $data->luas);
        $stmt->bindParam(":jenis_tanah", $data->jenis_tanah);
        $stmt->bindParam(":id", $id);
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // TODO: method delete($id) -> DELETE FROM lahan WHERE id = ?
    public function delete($id)
    {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }
}
