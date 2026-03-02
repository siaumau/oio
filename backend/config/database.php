<?php
// ========================================
// 数据库连接配置
// ========================================

class Database {
    private $host = 'localhost';
    private $port = 3306;
    private $db = 'oio';
    private $user = 'root';
    private $password = 'password';
    private $conn;

    public function connect() {
        try {
            $this->conn = new mysqli(
                $this->host . ':' . $this->port,
                $this->user,
                $this->password,
                $this->db
            );

            if ($this->conn->connect_error) {
                throw new Exception('数据库连接失败: ' . $this->conn->connect_error);
            }

            // 设置字符集
            $this->conn->set_charset('utf8mb4');

            // 设置时区为 Asia/Taipei（台北时区 UTC+8）
            $this->conn->query("SET time_zone = '+08:00'");

            return $this->conn;
        } catch (Exception $e) {
            die(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));
        }
    }

    public static function getInstance() {
        static $db = null;
        if ($db === null) {
            $database = new self();
            $db = $database->connect();
        }
        return $db;
    }
}
