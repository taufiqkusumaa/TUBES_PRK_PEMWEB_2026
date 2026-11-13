<?php
// app/Config/Database.php
// Tugas: Koneksi PDO ke database MySQL + helper kirim response JSON.
// Diisi oleh: Faqih.

namespace App\Config;

use PDO;
use PDOException;

class Database
{
    private $host = "localhost";
    private $db_name = "kebunku";
    private $username = "root";
    private $password = "";
    private $conn = null;

    public function getConnection()
    {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $exception) {
            error_log("Koneksi database gagal: " . $exception->getMessage());
            throw $exception;
        }

        return $this->conn;
    }

    public static function response($data, $status = 200)
    {
        http_response_code($status);
        header("Content-Type: application/json; charset=UTF-8");
        echo json_encode($data);
    }
}
