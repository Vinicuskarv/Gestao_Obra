<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/Database.php';

$id = $_POST['id'] ?? null;
$ocorrido_at = $_POST['ocorrido_at'] ?? null;
$obra_token = $_POST['obra_token'] ?? null;


if (!$id || !$ocorrido_at || !$obra_token) {
    die("Dados inválidos.");
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("UPDATE pontos SET ocorrido_at = :t WHERE id = :id LIMIT 1");
    $stmt->execute([
        ':t' => $ocorrido_at,
        ':id' => $id
    ]);

    header("Location: project_hours.php?obra_token=" . $obra_token);
    exit;

} catch (Exception $e) {
    die("Erro ao atualizar.");
}