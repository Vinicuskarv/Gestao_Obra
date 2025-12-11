<?php
require_once __DIR__ . '/Database.php';
class Auth {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function login($username, $password) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $user['password'])) {
                session_start();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['admin'] = (int)$user['admin'];
                return true;
            }
        }
        return false;
    }

    public function logout() {
        session_start();
        session_unset();
        session_destroy();
        header("Location: /login-app/public/login.php");
        exit();
    }

    public function isLoggedIn() {
        session_start();
        return isset($_SESSION['user_id']);
    }
}