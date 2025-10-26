<?php

class Database {
    private $host;
    private $database_name;
    private $username;
    private $password;
    private $conn;
    
    public function __construct() {
        // Configuración para el servidor de producción
        $this->host = $_ENV['DB_HOST'] ?? 'localhost';
        $this->database_name = $_ENV['DB_NAME'] ?? 'u244254974_terpenitos';
        $this->username = $_ENV['DB_USER'] ?? 'u244254974_terpenitos';
        $this->password = $_ENV['DB_PASS'] ?? 'Terpenitos1';
    }

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->database_name,
                $this->username,
                $this->password
            );
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }

    public function createDatabase() {
        try {
            $conn = new PDO("mysql:host=" . $this->host, $this->username, $this->password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $sql = "CREATE DATABASE IF NOT EXISTS " . $this->database_name . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
            $conn->exec($sql);
            
            return true;
        } catch(PDOException $e) {
            return false;
        }
    }
}
?>
