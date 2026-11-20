<?php
class Database {
    private $conn;

    public function __construct() {
        $config = require __DIR__ . '/../config/config.php';
        try {
            $this->conn = new PDO(
                "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset=utf8mb4",
                $config['db']['user'],
                $config['db']['pass'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            die('DB Error: ' . $e->getMessage());
        }
    }

    public function connect() {
        return $this->conn;
    }
}