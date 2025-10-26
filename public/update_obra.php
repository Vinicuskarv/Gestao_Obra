<?php
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método inválido']);
    exit;
}

$id = $_POST['id'] ?? '';
$name = trim($_POST['name'] ?? '');

if ($id === '' || !is_numeric($id)) {
    echo json_encode(['success' => false, 'error' => 'ID inválido']);
    exit;
}
if ($name === '') {
    echo json_encode(['success' => false, 'error' => 'Nome obrigatório']);
    exit;
}

require_once __DIR__ . '../../src/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare('UPDATE obras SET name = :name WHERE id = :id');
    $ok = $stmt->execute([':name' => $name, ':id' => (int)$id]);

    if ($ok) {
        echo json_encode(['success' => true, 'id' => (int)$id, 'name' => $name]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Falha ao atualizar']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Erro: ' . $e->getMessage()]);
}