<?php
require_once __DIR__ . '../../config.php';

require_once __DIR__ . '../../src/Database.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo "Método não permitido."; exit;
}

$token = isset($_POST['token']) ? trim($_POST['token']) : '';
$obraId = isset($_POST['obra']) ? trim($_POST['obra']) : 0;
$type = isset($_POST['type']) ? $_POST['type'] : '';

if (!$token || $token !== COMPANY_TOKEN || !$obraId || !in_array($type, ['entrada','saida'])) {
    if (!defined('COMPANY_TOKEN') || $token !== COMPANY_TOKEN) {
        http_response_code(403); echo "Acesso negado (empresa)."; exit;

    }
    if (!$obraId) {
        http_response_code(400); echo "Obra inválida.".$obraId; exit;
    }   
    if (!in_array($type, ['entrada','saida'])) {
        http_response_code(400); echo "Tipo de ponto inválido."; exit;
    }

    http_response_code(403); echo "Acesso negado."; exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    // Insere evento de ponto: obra_id, tipo, ocorrido_at (UTC)
    // Ajuste os nomes das colunas se sua tabela for diferente (ex: created_at em vez de ocorrido_at)
    $stmt = $conn->prepare('INSERT INTO pontos (obra_id, tipo, ocorrido_at) VALUES (:obra, :tipo, UTC_TIMESTAMP(6))');
    $stmt->execute([':obra' => $obraId, ':tipo' => $type]);

    // redireciona para página de sucesso simples
    header('Location: marca_ponto.php?obra=' . $obraId . '&token=' . urlencode($token) . '&ok=1');
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo "Erro ao registrar ponto.".$e->getMessage(). $obraId, $type;
    exit;
}