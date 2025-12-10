<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/Database.php';

header("Content-Type: application/json");

if (empty($_POST['id'])) {
    echo json_encode(['success' => false, 'error' => 'ID inválido']);
    exit;
}

$id = $_POST['id'];

$db = new Database();
$conn = $db->getConnection();

$stmt = $conn->prepare("DELETE FROM tokens WHERE id = ?");
$stmt->execute([$id]);

echo json_encode(['success' => true]);
