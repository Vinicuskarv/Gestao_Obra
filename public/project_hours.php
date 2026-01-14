<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/Database.php';

$obraToken = trim($_GET['obra_token'] ?? '');

$mes = $_GET['mes'] ?? date('Y-m'); // formato YYYY-MM

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

    // Obra
    $stmt = $conn->prepare('SELECT id, name, token FROM obras WHERE token = :token LIMIT 1');
    $stmt->execute([':token' => $obraToken]);
    $obra = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$obra) {
        http_response_code(404);
        echo "Obra não encontrada.";
        exit;
    }
    

    // Relacionamento funcionário X pontos
    $stmt = $conn->prepare('
        SELECT p.funcionario_id, f.name, p.ocorrido_at
        FROM pontos p
        JOIN funcionarios f ON f.id = p.funcionario_id
        WHERE p.obra_id = :obra
        AND p.ocorrido_at BETWEEN :ini AND :fim
        ORDER BY f.name ASC, p.ocorrido_at ASC
    ');
    $stmt->execute([
        ':obra' => $obra['token'],
        ':ini' => $inicioMes,
        ':fim' => $fimMes
    ]);
    $funcRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Listagem por data
    $stmt = $conn->prepare('
        SELECT p.id, p.ocorrido_at, f.name AS funcionario
        FROM pontos p
        JOIN funcionarios f ON f.id = p.funcionario_id
        WHERE p.obra_id = :obra
        AND p.ocorrido_at BETWEEN :ini AND :fim
        ORDER BY p.ocorrido_at ASC
    ');

    $stmt->execute([
        ':obra' => $obra['token'],
        ':ini' => $inicioMes,
        ':fim' => $fimMes
    ]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Agrupa por data
    $byDate = [];
    foreach ($rows as $r) {
        $d = date('Y-m-d', strtotime($r['ocorrido_at']));
        $func = $r['funcionario'];

        if (!isset($byDate[$d][$func])) {
            $byDate[$d][$func] = [];
        }

        $byDate[$d][$func][] = $r;  // adiciona evento do funcionário no dia
    }

    $totalHorasTrabalhadas = [];

    foreach ($byDate as $date => $funcionarios) {
        foreach ($funcionarios as $funcName => $eventos) {

            $count = count($eventos);

            // Ignorar dias inválidos
            if (!($count == 2 || $count == 4)) {
                continue;
            }

            // Ordenar horários só por segurança
            usort($eventos, function($a, $b) {
                return strtotime($a['ocorrido_at']) - strtotime($b['ocorrido_at']);
            });

            $horasDia = 0;

            // Caso 2 pontos (entrada / saída)
            if ($count == 2) {
                $entrada = strtotime($eventos[0]['ocorrido_at']);
                $saida   = strtotime($eventos[1]['ocorrido_at']);

                $horasDia = ($saida - $entrada) / 3600;
            }

            // Caso 4 pontos (entrada / saída + entrada / saída)
            if ($count == 4) {
                $entrada1 = strtotime($eventos[0]['ocorrido_at']);
                $saida1   = strtotime($eventos[1]['ocorrido_at']);
                $entrada2 = strtotime($eventos[2]['ocorrido_at']);
                $saida2   = strtotime($eventos[3]['ocorrido_at']);

                $horasDia =
                    (($saida1 - $entrada1) + ($saida2 - $entrada2)) / 3600;
            }

            // Somar por funcionário
            if (!isset($totalHorasTrabalhadas[$funcName])) {
                $totalHorasTrabalhadas[$funcName] = 0;
            }

            $totalHorasTrabalhadas[$funcName] += $horasDia;
        }
    }



} catch (Exception $e) {
    http_response_code(500);
    echo "Erro interno.";
    exit;
}

// Agrupar por funcionário
$porFuncionario = [];
foreach ($funcRows as $r) {
    $fid = $r['funcionario_id'];
    if (!isset($porFuncionario[$fid])) {
        $porFuncionario[$fid] = [
            'name' => $r['name'],
            'total' => 0
        ];
    }
    $porFuncionario[$fid]['total']++;
}

?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Pontos — <?= htmlspecialchars($obra['name'], ENT_QUOTES, 'UTF-8') ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link href="css/styles.css" rel="stylesheet">
    <style>
        .nav-link{
            cursor: pointer;
            color: #ffffff;
        }
        .nav-link:hover{
            cursor: pointer;
            color: #ffffff;
        }
    </style>

</head>

<body class="p-4">
    <ul class="nav nav-tabs mb-4" id="tabsRelatorios" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active"
                    id="tab-pontos"
                    data-bs-toggle="tab"
                    data-bs-target="#pontos"
                    type="button"
                    role="tab">
                Pontos
            </button>
        </li>

        <li class="nav-item" role="presentation">
            <button class="nav-link"
                    id="tab-relatorio"
                    data-bs-toggle="tab"
                    data-bs-target="#relatorio"
                    type="button"
                    role="tab">
                Relatório Mensal
            </button>
        </li>
    </ul>

<div class="tab-content">
    <div class="tab-pane fade" id="relatorio" role="tabpanel">
        <div class="container">

        <div class="card p-4">
            <h4 class="mb-3">Solicitar Relatório Mensal</h4>

            <form method="get" action="relatorio_mensal.php">

                <input type="hidden" name="obra_token" value="<?= htmlspecialchars($obraToken) ?>">

                <!-- Mês -->
                <div class="mb-3">
                    <label class="form-label">Mês</label>
                    <input type="month"
                        name="mes"
                        value="<?= htmlspecialchars($mes) ?>"
                        class="form-control"
                        required>
                </div>

                <!-- Funcionário -->
                <div class="mb-3">
                    <label class="form-label">Funcionário</label>
                    <select name="funcionario_id" class="form-select">
                        <option value="">Todos os funcionários</option>

                        <?php
                        $stmt = $conn->prepare('
                            SELECT DISTINCT f.id, f.name
                            FROM funcionarios f
                            JOIN pontos p ON p.funcionario_id = f.id
                            WHERE p.obra_id = :obra
                            ORDER BY f.name
                        ');
                        $stmt->execute([':obra' => $obra['token']]);
                        $funcs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>

                        <?php foreach ($funcs as $f): ?>
                            <option value="<?= $f['id'] ?>">
                                <?= htmlspecialchars($f['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Botão -->
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-file-earmark-text"></i>
                    Gerar Relatório
                </button>

            </form>
        </div>
    </div>
</div>


<div class="tab-pane fade show active" id="pontos" role="tabpanel">

<div class="container">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Obra: <?= htmlspecialchars($obra['name'], ENT_QUOTES, 'UTF-8') ?></h3>
        <a href="/dashboard.php" class="btn btn-sm btn-outline-secondary">Voltar</a>
    </div>
    <div class="mb-4 p-3 border rounded">
        <form method="get" style="padding: 13px;">
            <input type="hidden" name="obra_token" value="<?= htmlspecialchars($obraToken) ?>">

            <label class="form-label">Selecionar mês:</label>
            <input type="month" name="mes" value="<?= htmlspecialchars($mes) ?>" class="form-control" onchange="this.form.submit()">

            <div class="mt-3">
                <strong>Total geral no mês:</strong>
                <?php
                    $totalGeralHoras = array_sum($totalHorasTrabalhadas);
                ?>
                <h4><?= number_format($totalGeralHoras, 2, ',', '.') . " h" ?> Horas</h4>
            </div>
        </form>
    </div>
<div class="mt-4 p-3 border rounded">
    <h4>Total de horas trabalhadas (somente dias válidos)</h4>
    <table class="table table-dark table-striped mt-3">
        <thead>
            <tr>
                <th>Funcionário</th>
                <th>Horas Trabalhadas</th>
            </tr>
        </thead>
        <tbody>
    
        <?php foreach ($totalHorasTrabalhadas as $func => $horas): ?>
            <tr>
                <td><?= htmlspecialchars($func) ?></td>
                <td><?= number_format($horas, 2, ',', '.') ?> h</td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

    <br>
    <?php foreach ($byDate as $date => $funcionarios): ?>

    <?php
        $horasPorFuncionarioDia = [];

        foreach ($funcionarios as $funcName => $eventosDoFunc) {

            $count = count($eventosDoFunc);

            // Ignora dias inválidos para aquele funcionário
            if (!($count == 2 || $count == 4)) {
                $horasPorFuncionarioDia[$funcName] = null; // inválido
                continue;
            }

            // Ordenação (garantia)
            usort($eventosDoFunc, function($a, $b){
                return strtotime($a['ocorrido_at']) - strtotime($b['ocorrido_at']);
            });

            $horas = 0;

            if ($count == 2) {
                $horas = (strtotime($eventosDoFunc[1]['ocorrido_at']) - strtotime($eventosDoFunc[0]['ocorrido_at'])) / 3600;
            }

            if ($count == 4) {
                $horas =
                    ((strtotime($eventosDoFunc[1]['ocorrido_at']) - strtotime($eventosDoFunc[0]['ocorrido_at'])) +
                    (strtotime($eventosDoFunc[3]['ocorrido_at']) - strtotime($eventosDoFunc[2]['ocorrido_at'])))
                    / 3600;
            }

            $horasPorFuncionarioDia[$funcName] = $horas;
        }
        ?>


    <?php
        $erroDiaFuncionarios = false;
        $totalDia = 0;

        foreach ($funcionarios as $fnName => $evsDoFunc) {
            $count = count($evsDoFunc);
            $totalDia += $count;

            // Se algum funcionário tiver quantidade diferente de 2 ou 4, marca erro no dia
            if (!($count == 2 || $count == 4)) {
                $erroDiaFuncionarios = true;
                // não precisa break — queremos contar totalDia mesmo que haja erro
            }
        }
    ?>

    <div class="card mb-3">

        <a data-bs-toggle="collapse"
           href="#d<?= $date ?>"
           role="button"
           style="text-decoration: none; color: inherit;">
            <div class="card-header d-flex justify-content-between">
                <strong><?= date('d/m/Y', strtotime($date)) ?></strong>
                <span class="<?= $erroDiaFuncionarios ? 'text-danger fw-bold' : 'text-muted' ?>">
                    <?= $totalDia ?> registros

                    <?php if ($erroDiaFuncionarios): ?>
                        <i class="bi bi-exclamation-triangle-fill text-danger ms-2"
                        title="Quantidade incorreta no dia!"></i>
                    <?php endif; ?>
                </span>

            </div>
        </a>

        <div class="collapse" id="d<?= $date ?>">
            <div class="card-body">

                <?php foreach ($funcionarios as $funcName => $eventosDoFunc): ?>

                    <div class="card mb-2 ">
                        <a data-bs-toggle="collapse"
                           href="#f<?= md5($date . $funcName) ?>"
                           role="button"
                           style="text-decoration: none; color: inherit;">
                            <div class="card-header d-flex justify-content-between">
                                <strong><?= htmlspecialchars($funcName) ?></strong>
                                <?php if ($horasPorFuncionarioDia[$funcName] !== null): ?>
                                    <span class="text-success ms-3">
                                        <?= number_format($horasPorFuncionarioDia[$funcName], 2, ',', '.') ?> h
                                    </span>
                                <?php else: ?>
                                    <span class="text-danger ms-3">
                                        Dia inválido
                                    </span>
                                <?php endif; ?>

                                <?php
                                    $count = count($eventosDoFunc);

                                    $esperadoMin = 2;
                                    $esperadoMax = 4;

                                    $temErro = !($count == $esperadoMin || $count == $esperadoMax);
                                ?>
                                <span class="<?= $temErro ? 'text-danger fw-bold' : 'text-muted' ?>">
                                    <?= $count ?> registros

                                    <?php if ($temErro): ?>
                                        <i class="bi bi-exclamation-triangle-fill text-danger ms-2" title="Quantidade incorreta!"></i>
                                    <?php endif; ?>
                                </span>
                                <?php
                                    $temErro = false;
                                ?>
                            </div>
                        </a>

                        <div class="collapse" id="f<?= md5($date . $funcName) ?>">
                            <ul class="list-group list-group-flush">

                                <?php foreach ($eventosDoFunc as $ev): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">

                                    <span>
                                        <?= date('H:i:s', strtotime($ev['ocorrido_at'])) ?>
                                    </span>

                                    <div>
                                        <button class="btn btn-sm btn-outline-primary"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editModal"
                                                data-id="<?= $ev['id'] ?>"
                                                data-time="<?= date('Y-m-d\TH:i', strtotime($ev['ocorrido_at'])) ?>">
                                            Editar
                                        </button>

                                        <a href="delete_ponto.php?id=<?= $ev['id'] ?>&obra_token=<?= $obraToken ?>"
                                        class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('Deseja apagar este registro?')">
                                            Excluir
                                        </a>
                                    </div>

                                </li>

                                <?php endforeach; ?>

                            </ul>
                        </div>
                    </div>

                <?php endforeach; ?>

            </div>
        </div>

    </div>
<?php endforeach; ?>

</div>
</div>
<!-- Modal Editar -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" action="update_ponto.php" class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Editar Horário</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">

        <input type="hidden" name="id" id="edit-id">
        <input type="hidden" name="obra_token" id="edit-obra-token" value="<?= htmlspecialchars($obraToken) ?>">

        <label class="form-label">Novo horário:</label>
        <input type="datetime-local" class="form-control" id="edit-time" name="ocorrido_at" required>

      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
        <button type="submit" class="btn btn-primary">Salvar alterações</button>
      </div>

    </form>
  </div>
</div>

<script>
// Preenche modal com dados do ponto selecionado
const editModal = document.getElementById('editModal');
editModal.addEventListener('show.bs.modal', event => {
    const button = event.relatedTarget;
    document.getElementById('edit-id').value = button.getAttribute('data-id');
    document.getElementById('edit-time').value = button.getAttribute('data-time');
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
