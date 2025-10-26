<?php
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método inválido']); exit;
}
require_once __DIR__ . '../../src/Database.php';

$obra_id = $_POST['obra_id'] ?? '';
$func_id = $_POST['funcionario_id'] ?? '';
$role = trim($_POST['role'] ?? null);
$status = trim($_POST['status'] ?? null); // novo

if (!is_numeric($obra_id) || !is_numeric($func_id)) {
    echo json_encode(['success' => false, 'error' => 'IDs inválidos']); exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    // garante pivot com campo status
    $conn->exec("CREATE TABLE IF NOT EXISTS obra_funcionario (
        obra_id INT NOT NULL,
        funcionario_id INT NOT NULL,
        role VARCHAR(100) DEFAULT NULL,
        status VARCHAR(50) DEFAULT 'ativo',
        assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (obra_id, funcionario_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $stmt = $conn->prepare(
        'INSERT INTO obra_funcionario (obra_id, funcionario_id, role, status) VALUES (:obra, :func, :role, :status)
         ON DUPLICATE KEY UPDATE role = VALUES(role), status = VALUES(status), assigned_at = CURRENT_TIMESTAMP'
    );
    $ok = $stmt->execute([':obra' => (int)$obra_id, ':func' => (int)$func_id, ':role' => $role ?: null, ':status' => $status ?: 'ativo']);

    echo json_encode(['success' => (bool)$ok]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}