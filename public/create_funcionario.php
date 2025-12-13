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

    // // garante tabelas
    // $conn->exec("CREATE TABLE IF NOT EXISTS funcionarios (
    //     id INT AUTO_INCREMENT PRIMARY KEY,
    //     name VARCHAR(255) NOT NULL,
    //     email VARCHAR(150),
    //     phone VARCHAR(50),
    //     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    // ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // $conn->exec("CREATE TABLE IF NOT EXISTS obra_funcionario (
    //     obra_id INT NOT NULL,
    //     funcionario_id INT NOT NULL,
    //     role VARCHAR(100) DEFAULT NULL,
    //     status VARCHAR(50) DEFAULT 'ativo',
    //     assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    //     PRIMARY KEY (obra_id, funcionario_id)
    // ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

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
        echo json_encode(['success' => false, 'error' => 'Limite de atingido.']);
        exit;
    }

    $token = $tokenData['token'];

    $stmtUpdate = $conn->prepare("UPDATE tokens SET status = 'ativo' WHERE token = :token");
    $stmtUpdate->execute([':token' => $token]);


    $stmt = $conn->prepare('INSERT INTO funcionarios (name, email, phone , token) VALUES (:name, :email, :phone, :token)');
    $ok = $stmt->execute([':name' => $name, ':email' => $email ?: null, ':phone' => $phone ?: null , ':token' => $token ?: null]);

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