<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método não permitido.']);
    exit;
}

session_start();

// ID real do funcionário (único válido)
$funcionario_id = $_SESSION['funcionario_id'] ?? null;

if (!$funcionario_id) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Funcionário não autenticado']);
    exit;
}

$token = trim($_POST['token'] ?? '');
$type = trim($_POST['type'] ?? '');
$horaClient = trim($_POST['hora'] ?? '');

// valida
if (!$token || $token !== COMPANY_TOKEN || !$type) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Parâmetros inválidos.']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    $tipoMap = [
        'entrada'        => 'entrada',
        'entradaAlmoco'  => 'pausa_inicio',
        'saidaAlmoco'    => 'pausa_fim',
        'saida'          => 'saida',
    ];
    $tipoDb = $tipoMap[$type] ?? $type;

    // Query corrigida
    $stmt = $conn->prepare('
        INSERT INTO pontos (obra_id, tipo, ocorrido_at, funcionario_id)
        VALUES (:obra, :tipo, UTC_TIMESTAMP(6), :funcionario)
    ');

    $stmt->execute([
        ':tipo'        => $tipoDb,
        ':funcionario' => $funcionario_id
    ]);

    echo json_encode(['success' => true, 'hora' => $horaClient]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
