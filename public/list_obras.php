<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '../../src/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    // garante tabela (opcional — pode remover se já criou manualmente)
    $conn->exec("CREATE TABLE IF NOT EXISTS obras (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $stmt = $conn->query('SELECT id, name FROM obras ORDER BY created_at DESC');
    $obras = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'obras' => $obras], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}