<?php
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método inválido']); exit;
}
require_once __DIR__ . '../../src/Database.php';

$id = $_POST['id'] ?? '';
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');

if (!is_numeric($id) || $name === '') {
    echo json_encode(['success' => false, 'error' => 'ID inválido ou nome vazio']); exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare('UPDATE funcionarios SET name = :name, email = :email, phone = :phone WHERE id = :id');
    $ok = $stmt->execute([':name' => $name, ':email' => $email ?: null, ':phone' => $phone ?: null, ':id' => (int)$id]);

    echo json_encode(['success' => (bool)$ok]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}