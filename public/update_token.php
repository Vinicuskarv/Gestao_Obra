<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/Database.php';

header("Content-Type: application/json");

if (empty($_POST['id']) || empty($_POST['name']) || empty($_POST['status'])) {
    echo json_encode(['success' => false, 'error' => 'Campos obrigatórios']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();


$id = $_POST['id'];
$name = trim($_POST['name']);
$status = trim($_POST['status']);

$stmt = $conn->prepare("UPDATE tokens SET name = ?, status = ? WHERE id = ?");
$stmt->execute([$name, $status, $id]);

echo json_encode(['success' => true]);
