<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/Database.php';


$obraToken = trim($_GET['obra_token'] ?? '');

$mes = $_GET['mes'] ?? date('Y-m'); // formato YYYY-MM — padrão = mês atual

// Início e fim do mês selecionado
$inicioMes = $mes . '-01 00:00:00';
$fimMes = date('Y-m-t 23:59:59', strtotime($inicioMes));

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
        SELECT p.funcionario_id, f.name, p.tipo, p.ocorrido_at
        FROM pontos p
        JOIN funcionarios f ON f.id = p.funcionario_id
        WHERE p.obra_id = :obra
        AND p.ocorrido_at BETWEEN :ini AND :fim
        ORDER BY f.name ASC, p.ocorrido_at ASC
    ');
    $stmt->execute([
        ':obra' => (int)$obra['id'],
        ':ini' => $inicioMes,
        ':fim' => $fimMes
    ]);
    $funcRows = $stmt->fetchAll(PDO::FETCH_ASSOC);


    $stmt = $conn->prepare('
        SELECT tipo, ocorrido_at
        FROM pontos
        WHERE obra_id = :obra
        AND ocorrido_at BETWEEN :ini AND :fim
        ORDER BY ocorrido_at ASC
    ');
    $stmt->execute([
        ':obra' => (int)$obra['id'],
        ':ini' => $inicioMes,
        ':fim' => $fimMes
    ]);
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

$porFuncionario = [];

$funcEventosDia = [];

foreach ($funcRows as $r) {
    $fid = $r['funcionario_id'];
    $dia = date('Y-m-d', strtotime($r['ocorrido_at']));

    $funcEventosDia[$fid][$dia][] = $r;

    if (!isset($porFuncionario[$fid])) {
        $porFuncionario[$fid] = [
            'name' => $r['name'],
            'work' => 0,
            'break' => 0
        ];
    }
}

    foreach ($funcEventosDia as $fid => $dias) {
        foreach ($dias as $dia => $eventos) {

            $entry = null;
            $breakStart = null;

            foreach ($eventos as $ev) {
                $ts = strtotime($ev['ocorrido_at']);

                if ($ev['tipo'] === 'entrada') {
                    $entry = $ts;

                } elseif ($ev['tipo'] === 'saida' && $entry !== null) {
                    $porFuncionario[$fid]['work'] += max(0, $ts - $entry);
                    $entry = null;

                } elseif ($ev['tipo'] === 'pausa_inicio') {
                    $breakStart = $ts;

                } elseif ($ev['tipo'] === 'pausa_fim' && $breakStart !== null) {
                    $porFuncionario[$fid]['break'] += max(0, $ts - $breakStart);
                    $breakStart = null;
                }
            }
        }
    }

    // calcula líquido
    foreach ($porFuncionario as $fid => &$f) {
        $f['net'] = max(0, $f['work'] - $f['break']);
    }

// Calcular líquido e limpar campos internos
foreach ($porFuncionario as $fid => &$f) {
    $f['net'] = max(0, $f['work'] - $f['break']);
    unset($f['_entry'], $f['_break']);
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
                <?php
        // Cálculo total do mês
            $workMonth = 0;
            $breakMonth = 0;

            foreach ($byDate as $date => $events) {
                $openEntry = null;
                $openBreak = null;

                foreach ($events as $ev) {
                    $ts = strtotime($ev['ocorrido_at']);

                    if ($ev['tipo'] === 'entrada') {
                        $openEntry = $ts;

                    } elseif ($ev['tipo'] === 'saida' && $openEntry !== null) {
                        $workMonth += max(0, $ts - $openEntry);
                        $openEntry = null;

                    } elseif ($ev['tipo'] === 'pausa_inicio') {
                        $openBreak = $ts;

                    } elseif ($ev['tipo'] === 'pausa_fim' && $openBreak !== null) {
                        $breakMonth += max(0, $ts - $openBreak);
                        $openBreak = null;
                    }
                }
            }

            $netMonth = max(0, $workMonth - $breakMonth);
        ?>
        <div class="mb-4 p-3 border rounded bg-light">
            <form method="get">
                <input type="hidden" name="obra_token" value="<?= htmlspecialchars($obraToken) ?>">

                <label class="form-label">Selecionar mês:</label>
                <input type="month" name="mes" value="<?= htmlspecialchars($mes) ?>" class="form-control" onchange="this.form.submit()">

                <div class="mt-3">
                    <strong>Horas do mês:</strong><br>
                    <small class="text-muted">Total bruto: <?= sec2hms($workMonth) ?></small><br>
                    <small class="text-muted">Pausas: <?= sec2hms($breakMonth) ?></small><br>
                    <h4>Líquido: <?= sec2hms($netMonth) ?></h4>
                </div>
            </form>
        </div>
    <div class="mt-4 p-3 border rounded bg-white">
        <h4>Horas totais por funcionário</h4>
        <table class="table table-striped mt-3">
            <thead>
                <tr>
                    <th>Funcionário</th>
                    <th>Bruto</th>
                    <th>Pausas</th>
                    <th>Líquido</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($porFuncionario as $f): ?>
                <tr>
                    <td><?= htmlspecialchars($f['name']) ?></td>
                    <td><?= sec2hms($f['work']) ?></td>
                    <td><?= sec2hms($f['break']) ?></td>
                    <td><strong><?= sec2hms($f['net']) ?></strong></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

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