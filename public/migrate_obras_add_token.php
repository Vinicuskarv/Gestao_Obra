<?php
require_once __DIR__ . '../../src/Database.php';

function generateToken(int $len = 20): string {
    $bytes = random_bytes((int)ceil($len / 2));
    return substr(bin2hex($bytes), 0, $len);
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    // verifica se coluna 'token' existe
    $colExists = (int)$conn->query("SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'obras' AND COLUMN_NAME = 'token'")->fetchColumn();

    if (!$colExists) {
        $conn->exec("ALTER TABLE obras ADD COLUMN token VARCHAR(20) DEFAULT NULL");
        echo "Coluna 'token' adicionada.\n";
    } else {
        echo "Coluna 'token' já existe.\n";
    }

    // verifica se índice único existe
    $idxExists = (int)$conn->query("SELECT COUNT(*) FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'obras' AND INDEX_NAME = 'ux_obras_token'")->fetchColumn();

    if (!$idxExists) {
        try {
            $conn->exec("ALTER TABLE obras ADD UNIQUE INDEX ux_obras_token (token)");
            echo "Índice ux_obras_token criado.\n";
        } catch (Exception $e) {
            // pode falhar se houver valores duplicados; informar e continuar
            echo "Falha ao criar índice ux_obras_token: " . $e->getMessage() . "\n";
        }
    } else {
        echo "Índice ux_obras_token já existe.\n";
    }

    // popular tokens vazios
    $stmt = $conn->query("SELECT id FROM obras WHERE token IS NULL OR token = ''");
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($rows as $id) {
        $tries = 0;
        do {
            $token = generateToken(20);
            $check = $conn->prepare('SELECT COUNT(*) FROM obras WHERE token = :token');
            $check->execute([':token' => $token]);
            $exists = (int)$check->fetchColumn() > 0;
            $tries++;
            if ($tries > 50) throw new Exception('Falha ao gerar token único para id ' . $id);
        } while ($exists);
        $up = $conn->prepare('UPDATE obras SET token = :token WHERE id = :id');
        $up->execute([':token' => $token, ':id' => $id]);
        echo "Updated obra id {$id} -> token {$token}\n";
    }
    echo "Migração concluída.\n";
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}