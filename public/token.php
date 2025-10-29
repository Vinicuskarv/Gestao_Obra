<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/Database.php';

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// exemplo esperado: /token/{companyToken}/obra/{obraToken}
if (!preg_match('#^/token/([^/]+)/obra/([^/]+)/?$#', $path, $m)) {
    http_response_code(404);
    echo "Not found.";
    exit;
}
$companyToken = $m[1];
$obraToken = $m[2];

// valida token da empresa
if (!defined('COMPANY_TOKEN') || $companyToken !== COMPANY_TOKEN) {
    http_response_code(403);
    echo "Acesso negado (empresa).";
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare('SELECT id, name, token FROM obras WHERE token = :token LIMIT 1');
    $stmt->execute([':token' => $obraToken]);
    $obra = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$obra) {
        http_response_code(404);
        echo "Obra não encontrada.";
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo "Erro interno.";
    exit;
}

// mostra página simples com botões de marcar ponto (POST para /mark_ponto.php)
?><!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Marcar Ponto — <?= htmlspecialchars($obra['name'], ENT_QUOTES, 'UTF-8') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/public/css/styles.css" rel="stylesheet">
    <style>
        .marcar-hora-container{
            max-width: 500px;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
    </style>
</head>
<body class="p-4 marcar-hora-container">
    <h2>Obra: <?= htmlspecialchars($obra['name'], ENT_QUOTES, 'UTF-8') ?></h2>

    <form method="post" action="/mark_ponto.php" style="display:inline-block; margin-right:8px;">
        <input type="hidden" name="token" value="<?= htmlspecialchars(COMPANY_TOKEN, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="obra" value="<?= $obra['token'] ?>">
        <input type="hidden" name="type" value="entrada">
        <button class="btn btn-success" type="submit">Marcar Entrada</button>
    </form>

    <form method="post" action="/mark_ponto.php" style="display:inline-block; margin-right:8px;">
        <input type="hidden" name="token" value="<?= htmlspecialchars(COMPANY_TOKEN, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="obra" value="<?= $obra['token'] ?>">
        <input type="hidden" name="type" value="saida">
        <button class="btn btn-danger" type="submit">Marcar Saída</button>
    </form>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>