<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método não permitido.']);
    exit;
}

session_start();
$funcionario_id = $_SESSION['funcionario_id'] ?? null;

if (!$funcionario_id) {
    http_response_code(403);
    exit("Funcionário não autenticado");
}

$token = trim($_POST['token'] ?? '');
$obraId = intval($_POST['obra'] ?? 0);
$type = trim($_POST['type'] ?? '');
$hora = trim($_POST['hora'] ?? '');

if (!$token || $token !== COMPANY_TOKEN || !$obraId || !$type || !$hora) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Parâmetros inválidos.']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    // mapping
    $tipoMap = [
        'entrada' => 'entrada',
        'pausa_inicio' => 'pausa_inicio',
        'pausa_fim' => 'pausa_fim',
        'saida' => 'saida'
    ];
    $tipoDb = $tipoMap[$type] ?? $type;

    // monta datetime completo
    $dataHora = date('Y-m-d') . ' ' . $hora;

    // 1️⃣ Busca o registro mais recente (para pegar o ID)
    $stmt = $conn->prepare("
        SELECT id FROM pontos
        WHERE obra_id = :obra
          AND tipo = :tipo
          AND DATE(ocorrido_at) = CURDATE()
          AND funcionario_id = :funcionario_id
        ORDER BY ocorrido_at DESC
        LIMIT 1
    ");
    $stmt->execute([
        ':obra' => $obraId,
        ':tipo' => $tipoDb,
        ':funcionario_id' => $funcionario_id
    ]);

    $ponto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ponto) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Nenhum ponto encontrado para atualizar']);
        exit;
    }

    // 2️⃣ Atualiza pelo ID
    $stmt = $conn->prepare("
        UPDATE pontos
        SET ocorrido_at = :dataHora
        WHERE id = :id
    ");
    $stmt->execute([
        ':dataHora' => $dataHora,
        ':id' => $ponto['id']
    ]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
