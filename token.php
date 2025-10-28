<?php
require_once __DIR__ . '/config.php';

// aceita token e obra via GET (ex: token.php?token=XXX&obra=123)
$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$obraId = isset($_GET['obra']) ? intval($_GET['obra']) : 0;

if (!$token || !$obraId) {
    http_response_code(400);
    echo "Parâmetros inválidos.";
    exit;
}

if ($token !== COMPANY_TOKEN) {
    http_response_code(403);
    echo "Token inválido.";
    exit;
}

// redireciona para a página pública de marcação de ponto para a obra
// (crie marca_ponto.php abaixo)
header('Location: /marca_ponto.php?obra=' . $obraId . '&token=' . urlencode($token));
exit;