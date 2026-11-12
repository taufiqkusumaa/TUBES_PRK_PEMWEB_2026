<?php
/**
 * File: app/Config/Database.php
 * Deskripsi: Kelas Koneksi Database menggunakan PDO (Singleton Pattern)
 * untuk keamanan dari SQL Injection dan manajemen koneksi yang efisien.
 */

namespace App\Config;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    // Konfigurasi kredensial database MySQL
    private string $host = 'localhost';
    private string $db_name = 'db_bengkel_servis';
    private string $username = 'root';
    private string $password = '';
    private string $charset = 'utf8mb4';

    /**
     * Constructor dibuat private untuk mencegah instansiasi berulang
     */
    private function __construct()
    {
        $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            self::$instance = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            // Tangani error koneksi dengan rapi
            http_response_code(500);
            die(json_encode([
                'status'  => 'error',
                'message' => 'Koneksi Database Gagal: ' . $e->getMessage()
            ]));
        }
    }

    /**
     * Mendapatkan instance koneksi PDO aktif
     * @return PDO
     */
    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            new self();
        }
        return self::$instance;
    }
}