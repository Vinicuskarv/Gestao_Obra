<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método inválido']);
    exit;
}

$name = trim($_POST['name'] ?? '');
if ($name === '') {
    echo json_encode(['success' => false, 'error' => 'Nome obrigatório']);
    exit;
}

require_once __DIR__ . '../../src/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    $conn->beginTransaction();

    // 1️⃣ Buscar token disponível
    $stmtToken = $conn->prepare("
        SELECT id, token 
        FROM tokens 
        WHERE status = 'inativo' 
          AND token NOT IN (SELECT token FROM obras)
        LIMIT 1
    ");
    $stmtToken->execute();
    $tokenData = $stmtToken->fetch(PDO::FETCH_ASSOC);

    if (!$tokenData) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'error' => 'Limite de obras atingido.']);
        exit;
    }

    $tokenId = $tokenData['id'];
    $token = $tokenData['token'];

    // 2️⃣ Marcar token como ativo
    $stmtUpdate = $conn->prepare("UPDATE tokens SET status = 'ativo' WHERE token = :token");
    $stmtUpdate->execute([':token' => $token]);

    // 3️⃣ Criar obra usando token
    $stmtInsert = $conn->prepare("
        INSERT INTO obras (name, token) 
        VALUES (:name, :token)
    ");
    $stmtInsert->execute([
        ':name' => $name,
        ':token' => $token
    ]);

    $obraId = (int) $conn->lastInsertId();
    $conn->commit();

    echo json_encode([
        'success' => true,
        'id' => $obraId,
        'name' => $name,
        'token' => $token
    ]);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

<!-- Toast de notificação (canto superior direito) -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
  <div id="toastNotification" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="toast-header">
      <strong class="me-auto" id="toastTitle">Sucesso</strong>
      <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
    </div>
    <div class="toast-body" id="toastBody">
      Operação realizada com sucesso.
    </div>
  </div>
</div>
