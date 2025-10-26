<?php
// src/Database.php
class Database {
    private $host = '127.0.0.1';
    private $db   = 'gestao_ponto';
    private $user = 'root';
    private $pass = '';
    private $charset = 'utf8mb4';
    private $conn;

    public function getConnection() {
        if ($this->conn) return $this->conn;

        $dsn = "mysql:host={$this->host};dbname={$this->db};charset={$this->charset}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $this->conn = new PDO($dsn, $this->user, $this->pass, $options);
        return $this->conn;
    }
}
