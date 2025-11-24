<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/Database.php';

$obraToken = trim($_GET['obra_token'] ?? '');
if ($obraToken === '') {
    http_response_code(400);
    echo "Token da obra não informado.";
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare('SELECT id, name FROM obras WHERE token = :token LIMIT 1');
    $stmt->execute([':token' => $obraToken]);
    $obra = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$obra) {
        http_response_code(404);
        echo "Obra não encontrada.";
        exit;
    }

    $stmt = $conn->prepare('
        SELECT tipo, ocorrido_at
        FROM pontos
        WHERE obra_id = :obra
        ORDER BY ocorrido_at ASC
    ');
    $stmt->execute([':obra' => (int)$obra['id']]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Agrupa por data
    $byDate = [];
    foreach ($rows as $r) {
        $d = date('Y-m-d', strtotime($r['ocorrido_at']));
        $byDate[$d][] = $r;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo "Erro interno.";
    exit;
}

function tipoLabel($t) {
    switch ($t) {
        case 'entrada': return 'Entrada';
        case 'pausa_inicio': return 'Início pausa';
        case 'pausa_fim': return 'Fim pausa';
        case 'saida': return 'Saída';
        default: return $t;
    }
}
function sec2hms($sec) {
    if ($sec <= 0) return '00:00:00';
    $h = floor($sec / 3600); $m = floor(($sec % 3600) / 60); $s = $sec % 60;
    return sprintf('%02d:%02d:%02d', $h, $m, $s);
}
?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Horários — <?= htmlspecialchars($obra['name'], ENT_QUOTES, 'UTF-8') ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

</head>
<body class="p-4">
    <div class="container">
    <div class="d-flex justify-content-between align-items-center ">
        <h3>Obra: <?= htmlspecialchars($obra['name'], ENT_QUOTES, 'UTF-8') ?></h3>
        <a href="/dashboard.php" class="btn btn-sm btn-secondary">Voltar</a>
    </div>
    <?php foreach ($byDate as $date => $events): ?>
    <div class="card">
        <a data-bs-toggle="collapse" href="#collapseExample<?= date('d/m/Y', strtotime($date)) ?>" role="button" aria-expanded="false" aria-controls="collapseExample" style="text-decoration: none; color: inherit;">
            <div class="card-header d-flex justify-content-between">
            <div>
                <strong><?= date('d/m/Y', strtotime($date)) ?></strong>
            </div>

            <?php
                // calcula resumo diário
                $workSeconds = 0;
                $breakSeconds = 0;
                $openEntry = null;
                $openBreak = null;

                foreach ($events as $ev) {
                    $ts = strtotime($ev['ocorrido_at']);

                    if ($ev['tipo'] === 'entrada') {
                        $openEntry = $ts;

                    } elseif ($ev['tipo'] === 'saida' && $openEntry !== null) {
                        $workSeconds += max(0, $ts - $openEntry);
                        $openEntry = null;

                    } elseif ($ev['tipo'] === 'pausa_inicio') {
                        $openBreak = $ts;

                    } elseif ($ev['tipo'] === 'pausa_fim' && $openBreak !== null) {
                        $breakSeconds += max(0, $ts - $openBreak);
                        $openBreak = null;
                    }
                }

                $netSeconds = max(0, $workSeconds - $breakSeconds);
            ?>

            <div class="text-end">
                <small class="text-muted">Total bruto: <?= sec2hms($workSeconds) ?></small><br>
                <small class="text-muted">Pausa: <?= sec2hms($breakSeconds) ?></small><br>
                <strong>Liquido: <?= sec2hms($netSeconds) ?></strong>
            </div>
            </div>
        </a>
        <div class="collapse" id="collapseExample<?= date('d/m/Y', strtotime($date)) ?>">
            <div class="card-body p-0">
            <ul class="list-group list-group-flush">
                <?php foreach ($events as $ev): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                    <span class="badge bg-secondary me-2"><?= tipoLabel($ev['tipo']) ?></span>
                    <span class="text-muted"><?= date('H:i:s', strtotime($ev['ocorrido_at'])) ?></span>
                    </div>
                    <div class="text-muted small"><?= date('d/m/Y H:i:s', strtotime($ev['ocorrido_at'])) ?></div>
                </li>
                <?php endforeach; ?>
            </ul>
            </div>
        </div>
    <?php endforeach; ?>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>

