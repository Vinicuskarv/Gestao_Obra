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
</head>

<body class="p-4">
<div class="container">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Obra: <?= htmlspecialchars($obra['name'], ENT_QUOTES, 'UTF-8') ?></h3>
        <a href="/dashboard.php" class="btn btn-sm btn-secondary">Voltar</a>
    </div>
    <div class="mb-4 p-3 border rounded bg-light">
        <form method="get">
            <input type="hidden" name="obra_token" value="<?= htmlspecialchars($obraToken) ?>">

            <label class="form-label">Selecionar mês:</label>
            <input type="month" name="mes" value="<?= htmlspecialchars($mes) ?>" class="form-control" onchange="this.form.submit()">

            <div class="mt-3">
                <strong>Total de registros no mês:</strong>
                <h4><?= array_sum(array_column($porFuncionario, 'total')) ?> pontos</h4>
            </div>
        </form>
    </div>

    <div class="mt-4 p-3 border rounded bg-white">
        <h4>Pontos por funcionário</h4>
        <table class="table table-striped mt-3">
            <thead>
                <tr>
                    <th>Funcionário</th>
                    <th>Total de registros</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($porFuncionario as $f): ?>
                <tr>
                    <td><?= htmlspecialchars($f['name']) ?></td>
                    <td><?= $f['total'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php foreach ($byDate as $date => $funcionarios): ?>
    <div class="card mb-3">

        <a data-bs-toggle="collapse"
           href="#d<?= $date ?>"
           role="button"
           style="text-decoration: none; color: inherit;">
            <div class="card-header d-flex justify-content-between">
                <strong><?= date('d/m/Y', strtotime($date)) ?></strong>
                <span class="text-muted"><?= array_sum(array_map('count', $funcionarios)) ?> registros</span>
            </div>
        </a>

        <div class="collapse" id="d<?= $date ?>">
            <div class="card-body">

                <?php foreach ($funcionarios as $funcName => $eventosDoFunc): ?>

                    <div class="card mb-2">
                        <a data-bs-toggle="collapse"
                           href="#f<?= md5($date . $funcName) ?>"
                           role="button"
                           style="text-decoration: none; color: inherit;">
                            <div class="card-header d-flex justify-content-between">
                                <strong><?= htmlspecialchars($funcName) ?></strong>
                                <span class="text-muted"><?= count($eventosDoFunc) ?> registros</span>
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
                                        <button class="btn btn-sm btn-primary me-2"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editModal"
                                                data-id="<?= $ev['id'] ?>"
                                                data-time="<?= date('Y-m-d\TH:i', strtotime($ev['ocorrido_at'])) ?>">
                                            Editar
                                        </button>

                                        <a href="delete_ponto.php?id=<?= $ev['id'] ?>&obra_token=<?= $obraToken ?>"
                                        class="btn btn-sm btn-danger"
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
