<?php
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método inválido']); exit;
}
require_once __DIR__ . '../../src/Database.php';

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$obra_id = $_POST['obra_id'] ?? null;

if ($name === '') {
    echo json_encode(['success' => false, 'error' => 'Nome obrigatório']); exit;
}


try {
    $db = new Database();
    $conn = $db->getConnection();

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
        echo json_encode(['success' => false, 'error' => 'Limite de atingido.']);
        exit;
    }
    $code = '';
    if ($name ){
        $stmtName = $conn->prepare("
            SELECT id, name 
            FROM funcionarios 
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmtName->execute();
        $userName = $stmtName->fetch(PDO::FETCH_ASSOC);

        $id = $userName['id'] + 1;
        $newId = str_pad($id, 2, '0', STR_PAD_LEFT);
        $code = mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8') . '' . $newId;
    }


    $token = $tokenData['token'];

    $stmtUpdate = $conn->prepare("UPDATE tokens SET status = 'ativo' WHERE token = :token");
    $stmtUpdate->execute([':token' => $token]);


    $stmt = $conn->prepare('INSERT INTO funcionarios (name, email, phone , token, code) VALUES (:name, :email, :phone, :token, :code)');
    $ok = $stmt->execute([':name' => $name, ':email' => $email ?: null, ':phone' => $phone ?: null , ':token' => $token ?: null, ':code' => $code ?: null]);

    if (!$ok) {
        echo json_encode(['success' => false, 'error' => 'Falha ao inserir funcionário']); exit;
    }

    $funcId = (int)$conn->lastInsertId();

    if ($obra_id && is_numeric($obra_id)) {
        $stmt2 = $conn->prepare('INSERT IGNORE INTO obra_funcionario (obra_id, funcionario_id, status) VALUES (:obra, :func, :status)');
        $stmt2->execute([':obra' => (int)$obra_id, ':func' => $funcId, ':status' => $status ?: 'ativo']);
    }

    echo json_encode(['success' => true, 'id' => $funcId, 'name' => $name]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}