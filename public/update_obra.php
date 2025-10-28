<?php
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método inválido']);
    exit;
}

$identifier = trim($_POST['id'] ?? $_POST['token'] ?? '');
$name = trim($_POST['name'] ?? '');

if ($identifier === '' || $name === '') {
    echo json_encode(['success' => false, 'error' => 'ID/token e nome são obrigatórios']);
    exit;
}

require_once __DIR__ . '/../src/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    // resolve id por token se necessário
    if (!is_numeric($identifier)) {
        $stmt = $conn->prepare('SELECT id FROM obras WHERE token = :token');
        $stmt->execute([':token' => $identifier]);
        $id = $stmt->fetchColumn();
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'Obra não encontrada pelo token']);
            exit;
        }
        $id = (int)$id;
    } else {
        $id = (int)$identifier;
    }

    $stmt = $conn->prepare('UPDATE obras SET name = :name WHERE id = :id');
    $ok = $stmt->execute([':name' => $name, ':id' => $id]);

    if ($ok) {
        // opcional: retornar token também
        $stmt2 = $conn->prepare('SELECT token FROM obras WHERE id = :id');
        $stmt2->execute([':id' => $id]);
        $token = $stmt2->fetchColumn();
        echo json_encode(['success' => true, 'id' => $id, 'name' => $name, 'token' => $token]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Falha ao atualizar']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}