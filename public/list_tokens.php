<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/Database.php';

header("Content-Type: application/json");

$db = new Database();
$conn = $db->getConnection();

$stmt = $conn->query("SELECT id, name, token, status, created_at FROM tokens ORDER BY created_at DESC");
$tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'tokens' => $tokens
]);
