<?php
// Defina o token público da empresa (troque pelo valor real)
define('COMPANY_TOKEN', 'p895ssdsf8f7s6d5f4s6d4f5s4d6f54s6d54f6s4d');

// Exemplo de configuração PDO (ajuste conforme seu ambiente)
define('DB_DSN', 'mysql:host=127.0.0.1;dbname=gestao_ponto;charset=utf8mb4');
define('DB_USER', 'root');
define('DB_PASS', '');
function getDb() {
    static $pdo;
    if ($pdo) return $pdo;
    $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    return $pdo;
}