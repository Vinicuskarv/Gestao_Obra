<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método não permitido.']);
    exit;
}

$token = isset($_POST['token']) ? trim($_POST['token']) : '';
$obraId = isset($_POST['obra']) ? intval($_POST['obra']) : 0;
$type = isset($_POST['type']) ? trim($_POST['type']) : '';
$hora = isset($_POST['hora']) ? trim($_POST['hora']) : '';

if (!$token || $token !== COMPANY_TOKEN || !$obraId || !$type || !$hora) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Parâmetros inválidos.']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    // mapeia tipos para enum de banco
    $tipoMap = [
        'entrada' => 'entrada',
        'pausa_inicio' => 'pausa_inicio',
        'pausa_fim' => 'pausa_fim',
        'saida' => 'saida'
    ];
    $tipoDb = $tipoMap[$type] ?? $type;

    // concatena data de hoje com a hora enviada
    $dataHora = date('Y-m-d') . ' ' . $hora;

    // atualiza o ponto mais recente do dia deste tipo
    $stmt = $conn->prepare('
        UPDATE pontos 
        SET ocorrido_at = :dataHora
        WHERE obra_id = :obra 
          AND tipo = :tipo 
          AND DATE(ocorrido_at) = CURDATE()
        ORDER BY ocorrido_at DESC
        LIMIT 1
    ');
    $stmt->execute([
        ':obra' => $obraId,
        ':tipo' => $tipoDb,
        ':dataHora' => $dataHora
    ]);

    http_response_code(200);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}