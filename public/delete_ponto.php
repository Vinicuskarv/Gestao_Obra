<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/Database.php';

$id = $_GET['id'] ?? null;
$obraToken = $_GET['obra_token'] ?? '';

if (!$id || !$obraToken) {
    die("Dados inválidos.");
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("DELETE FROM pontos WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);

    header("Location: project_hours.php?obra_token=" . $obraToken);
    exit;

} catch (Exception $e) {
    die("Erro ao deletar.");
}
