<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

// endpoint para criar obra via AJAX
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

function generateToken(int $len = 20): string {
    // gera token hex de tamanho $len (até 20). Usa random_bytes para segurança.
    $bytes = random_bytes((int)ceil($len / 2));
    return substr(bin2hex($bytes), 0, $len);
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    // garante tabela com coluna token (única)
    $conn->exec("CREATE TABLE IF NOT EXISTS obras (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        token VARCHAR(20) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $conn->exec("ALTER TABLE obras ADD UNIQUE INDEX ux_obras_token (token);");

    // gera token único
    $tries = 0;
    do {
        $token = generateToken(20);
        $stmtCheck = $conn->prepare('SELECT COUNT(*) FROM obras WHERE token = :token');
        $stmtCheck->execute([':token' => $token]);
        $exists = (int)$stmtCheck->fetchColumn() > 0;
        $tries++;
        if ($tries > 10) {
            // proteção extra — pouco provável
            throw new Exception('Não foi possível gerar token único');
        }
    } while ($exists);

    $stmt = $conn->prepare('INSERT INTO obras (name, token) VALUES (:name, :token)');
    $ok = $stmt->execute([':name' => $name, ':token' => $token]);

    if ($ok) {
        $id = (int)$conn->lastInsertId();
        echo json_encode(['success' => true, 'id' => $id, 'name' => $name, 'token' => $token]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Falha ao inserir']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Erro: ' . $e->getMessage()]);
}

