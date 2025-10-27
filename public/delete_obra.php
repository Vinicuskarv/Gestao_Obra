<?php
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método inválido']); exit;
}
$id = $_POST['id'] ?? '';
if (!is_numeric($id)) {
    echo json_encode(['success' => false, 'error' => 'ID inválido']); exit;
}
require_once __DIR__ . '../../src/Database.php';
try {
    $db = new Database();
    $conn = $db->getConnection();
    $conn->beginTransaction();
    // remover associações (se existirem)
    $stmt = $conn->prepare('DELETE FROM obra_funcionario WHERE obra_id = :id');
    $stmt->execute([':id' => (int)$id]);
    // remover obra
    $stmt2 = $conn->prepare('DELETE FROM obras WHERE id = :id');
    $stmt2->execute([':id' => (int)$id]);
    $conn->commit();
    echo json_encode(['success' => true, 'id' => (int)$id]);
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) $conn->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}