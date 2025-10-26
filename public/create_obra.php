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
file_put_contents(__DIR__ . '/debug.log', "POST recebido: " . print_r($_POST, true) . PHP_EOL, FILE_APPEND);

$name = trim($_POST['name'] ?? '');
if ($name === '') {
    echo json_encode(['success' => false, 'error' => 'Nome obrigatório']);
    exit;
}
try {
    file_put_contents(__DIR__ . '/debug.log', "Tentando conectar...\n", FILE_APPEND);


    require_once __DIR__ . '../../src/Database.php';
    $dbClass = new Database();
    $conn = $dbClass->getConnection();

    file_put_contents(__DIR__ . '/debug.log', "Conectou ao banco!\n", FILE_APPEND);

    $stmt = $conn->prepare('INSERT INTO obras (name) VALUES (:name)');
    $ok = $stmt->execute([':name' => $name]);

    file_put_contents(__DIR__ . '/debug.log', "Execução: " . ($ok ? "OK" : "FALHOU") . "\n", FILE_APPEND);

    if ($ok) {
        $id = (int)$conn->lastInsertId();
        file_put_contents(__DIR__ . '/debug.log', "Obra inserida ID=$id Nome=$name\n", FILE_APPEND);
        echo json_encode(['success' => true, 'id' => $id, 'name' => $name]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Falha ao inserir']);
    }

}catch (Exception $e) {
    file_put_contents(__DIR__ . '/debug.log', "ERRO: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'error' => 'Erro: ' . $e->getMessage()]);
}

