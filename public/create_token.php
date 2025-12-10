<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/Database.php';


if (!isset($_POST['name']) || trim($_POST['name']) === '') {
    echo json_encode(["success" => false, "error" => "Nome obrigatório"]);
    exit;
}

$name = trim($_POST['name']);
$token = bin2hex(random_bytes(16)); 
$status = "ativo";

try {

    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("INSERT INTO tokens (name, token, status) VALUES (?, ?, ?)");
    $stmt->execute([$name, $token, $status]);

    echo json_encode([
        "success" => true,
        "token" => $token
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
