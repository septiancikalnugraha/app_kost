<?php
// config/database.php
class Database {
    private $host = 'localhost';
    private $db_name = 'app_kost';
    private $username = 'root';
    private $password = '';
    private $conn;

    public function connect() {
        $this->conn = null;
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4";
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            die("Connection Error: " . $e->getMessage());
        }
        return $this->conn;
    }
}

// Helper function untuk mendapatkan koneksi database
if (!function_exists('getConnection')) {
    function getConnection() {
        $database = new Database();
        return $database->connect();
    }
} 