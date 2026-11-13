<?php
// app/Models/InventarisModel.php
// Tugas: Query SQL murni (PDO) untuk tabel inventaris.
// Diisi oleh: Valerie.

class InventarisModel
{
    private $conn;
    private $table_name = "inventaris";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // method read() -> SELECT * FROM inventaris
    public function read()
    {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
    // method readSingle($id) -> SELECT * FROM inventaris WHERE id = ?
    public function readSingle($id)
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    // method create($data) -> INSERT INTO inventaris (...)
    public function create($data)
    {
        $query = "INSERT INTO " . $this->table_name . " 
                  (nama, kategori, stok, satuan, harga_satuan) 
                  VALUES (:nama, :kategori, :stok, :satuan, :harga_satuan)";
        
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':nama', $data['nama']);
        $stmt->bindParam(':kategori', $data['kategori']);
        $stmt->bindParam(':stok', $data['stok']);
        $stmt->bindParam(':satuan', $data['satuan']);
        $stmt->bindParam(':harga_satuan', $data['harga_satuan']);

        return $stmt->execute();
    }
    // method update($id, $data) -> UPDATE inventaris SET ... WHERE id = ?
    public function update($id, $data)
    {
        $query = "UPDATE " . $this->table_name . " 
                  SET nama = :nama, kategori = :kategori, stok = :stok, satuan = :satuan, harga_satuan = :harga_satuan 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':nama', $data['nama']);
        $stmt->bindParam(':kategori', $data['kategori']);
        $stmt->bindParam(':stok', $data['stok']);
        $stmt->bindParam(':satuan', $data['satuan']);
        $stmt->bindParam(':harga_satuan', $data['harga_satuan']);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }
    // method delete($id) -> DELETE FROM inventaris WHERE id = ?
    public function delete($id)
    {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    // ==========================================
    // METHOD UNTUK TABEL TRANSAKSI INVENTARIS
    // ==========================================

    // method readTransaksi() -> SELECT JOIN transaksi dan master barang
    public function readTransaksi()
    {
        $query = "SELECT t.*, i.nama as nama_barang 
                  FROM transaksi_inventaris t 
                  JOIN inventaris i ON t.inventaris_id = i.id 
                  ORDER BY t.tanggal DESC, t.id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // method createTransaksi($data) -> catat transaksi lalu sesuaikan stok master
    public function createTransaksi($data)
    {
        $jenis = $data['jenis'] ?? '';
        $jumlah = (float) ($data['jumlah'] ?? 0);
        $inventarisId = (int) ($data['inventaris_id'] ?? 0);

        if (!in_array($jenis, ['Masuk', 'Keluar'], true) || $jumlah <= 0 || $inventarisId < 1) {
            return false;
        }

        try {
            $this->conn->beginTransaction();

            $query = "INSERT INTO transaksi_inventaris 
                      (inventaris_id, siklus_id, jenis, jumlah, tanggal) 
                      VALUES (:inventaris_id, :siklus_id, :jenis, :jumlah, :tanggal)";

            $stmt = $this->conn->prepare($query);

            $stmt->bindParam(':inventaris_id', $inventarisId, PDO::PARAM_INT);
            $stmt->bindParam(':jenis', $jenis);
            $stmt->bindParam(':jumlah', $jumlah);
            $tanggal = htmlspecialchars(strip_tags($data['tanggal'] ?? ''));
            $stmt->bindParam(':tanggal', $tanggal);

            // Logika khusus untuk membiarkan siklus_id berisi NULL saat barang "Masuk" (Kulakan)
            $siklus_id = !empty($data['siklus_id']) ? htmlspecialchars(strip_tags($data['siklus_id'])) : null;
            $stmt->bindParam(':siklus_id', $siklus_id, $siklus_id ? PDO::PARAM_INT : PDO::PARAM_NULL);

            if (!$stmt->execute()) {
                $this->conn->rollBack();
                return false;
            }

            $delta = $jenis === 'Masuk' ? $jumlah : -$jumlah;
            $update = "UPDATE inventaris SET stok = stok + :delta WHERE id = :id AND stok + :delta >= 0";
            $stmtStok = $this->conn->prepare($update);
            $stmtStok->bindParam(':delta', $delta);
            $stmtStok->bindParam(':id', $inventarisId, PDO::PARAM_INT);
            $stmtStok->execute();

            if ($stmtStok->rowCount() === 0) {
                $this->conn->rollBack();
                return false;
            }

            $this->conn->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return false;
        }
    }
}