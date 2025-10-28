<?php
require_once __DIR__ . '../../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo "Método não permitido."; exit;
}

$token = isset($_POST['token']) ? trim($_POST['token']) : '';
$obraId = isset($_POST['obra']) ? intval($_POST['obra']) : 0;
$type = isset($_POST['type']) ? $_POST['type'] : '';

if (!$token || $token !== COMPANY_TOKEN || !$obraId || !in_array($type, ['entrada','saida'])) {
    http_response_code(403); echo "Acesso negado."; exit;
}

try {
    $db = getDb();
    // tabela exemplo: pontos(id, obra_id, type, created_at)
    $stmt = $db->prepare('INSERT INTO pontos (obra_id, type, created_at) VALUES (?, ?, NOW())');
    $stmt->execute([$obraId, $type]);
    // redireciona para página de sucesso simples
    header('Location: marca_ponto.php?obra=' . $obraId . '&token=' . urlencode($token) . '&ok=1');
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo "Erro ao registrar ponto.";
    exit;
}