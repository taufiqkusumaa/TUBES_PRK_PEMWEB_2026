<?php

require_once __DIR__ . '/../Config/Database.php';

class PelangganModel {
    private $db;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?: Database::getConnection();
    }

    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT id, nama, email, no_telp, role, status_aktif FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function register($nama, $email, $password, $no_telp, $role = 'customer', $statusAktif = 1) {
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("INSERT INTO users (nama, email, password, no_telp, role, status_aktif) 
                                    VALUES (:nama, :email, :password, :no_telp, :role, :status_aktif)");
        return $stmt->execute([
            ':nama' => $nama,
            ':email' => $email,
            ':password' => $hashed,
            ':no_telp' => $no_telp,
            ':role' => $role,
            ':status_aktif' => $statusAktif,
        ]);
    }

    public function updateProfile($id, $nama, $no_telp, $new_password = null) {
        if (!empty($new_password)) {
            $hashed = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt = $this->db->prepare("UPDATE users SET nama = :nama, no_telp = :no_telp, password = :password WHERE id = :id");
            return $stmt->execute([
                ':nama' => $nama,
                ':no_telp' => $no_telp,
                ':password' => $hashed,
                ':id' => $id
            ]);
        } else {
            $stmt = $this->db->prepare("UPDATE users SET nama = :nama, no_telp = :no_telp WHERE id = :id");
            return $stmt->execute([
                ':nama' => $nama,
                ':no_telp' => $no_telp,
                ':id' => $id
            ]);
        }
    }

    public function getAllUsers() {
        $stmt = $this->db->query("SELECT id, nama, email, no_telp, role, status_aktif, created_at FROM users ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function toggleStatus($id, $status) {
        $stmt = $this->db->prepare("UPDATE users SET status_aktif = :status WHERE id = :id");
        return $stmt->execute([
            ':status' => $status,
            ':id' => $id
        ]);
    }
}

