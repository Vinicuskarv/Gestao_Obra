<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/Database.php';

$obraToken = $_GET['obra_token'] ?? '';
$mes = $_GET['mes'] ?? '';
$funcionarioId = $_GET['funcionario_id'] ?? '';

$inicio = $mes . '-01';
$fim = date('Y-m-t', strtotime($inicio));

$db = new Database();
$conn = $db->getConnection();

$sql = "
    SELECT
        p.funcionario_id,
        p.funcionario_name AS funcionario,
        DATE(p.ocorrido_at) AS data,
        MIN(TIME(p.ocorrido_at)) AS entrada,

        SUBSTRING_INDEX(
            GROUP_CONCAT(TIME(p.ocorrido_at) ORDER BY p.ocorrido_at),
            ',', -3
        ) AS saida_almoco,

        SUBSTRING_INDEX(
            SUBSTRING_INDEX(
                GROUP_CONCAT(TIME(p.ocorrido_at) ORDER BY p.ocorrido_at),
                ',', 3
            ),
            ',', -1
        ) AS retorno,

        MAX(TIME(p.ocorrido_at)) AS saida
    FROM pontos p
    WHERE p.obra_id = :obra
      AND p.ocorrido_at BETWEEN :inicio AND :fim
";

$params = [
    ':obra' => $obraToken,
    ':inicio' => $inicio . ' 00:00:00',
    ':fim' => $fim . ' 23:59:59'
];

if (!empty($funcionarioId)) {
    $sql .= " AND p.funcionario_id = :funcionario";
    $params[':funcionario'] = $funcionarioId;
}
function diaSemana($data) {
    $formatter = new IntlDateFormatter(
        'pt_BR',
        IntlDateFormatter::FULL,
        IntlDateFormatter::NONE,
        'America/Sao_Paulo',
        IntlDateFormatter::GREGORIAN,
        'EEEE'
    );

    return ucfirst($formatter->format(new DateTime($data)));
}


$sql .= "
    GROUP BY p.funcionario_id, DATE(p.ocorrido_at)
    ORDER BY funcionario, data
";
function hora($time) {
    return $time ? date('H:i', strtotime($time)) : '-';
}

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<title>Relatório Mensal</title>
<style>
    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }

    thead {
        background: #f1f3f5;
    }

    th {
        text-align: center;
        padding: 8px;
        border: 1px solid #dee2e6;
        font-weight: 600;
    }

    td {
        padding: 6px;
        border: 1px solid #dee2e6;
    }

    td.horario {
        text-align: center;
        font-family: monospace;
    }

    tr:nth-child(even) {
        background-color: #fafafa;
    }

    tr.incompleto {
        background-color: #fff3cd;
    }

    .funcionario {
        font-weight: 600;
    }

    @media print {
        body {
            font-size: 12px;
        }
    }
</style>

</head>
<body>


<h3>Relatório Mensal de Horários</h3>
<p>Mês: <?= date('m/Y', strtotime($inicio)) ?></p>

<table class="table table-bordered table-sm">
    <thead class="table-light">
        <tr>
            <th>Data</th>
            <th>Funcionário</th>
            <th>Entrada</th>
            <th>Saída Almoço</th>
            <th>Retorno</th>
            <th>Saída</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($registros)): ?>
            <tr>
                <td colspan="6" style="text-align:center;">Nenhum registro encontrado</td>
            </tr>
        <?php else: ?>
            <?php foreach ($registros as $r): 
                $incompleto = empty($r['entrada']) || empty($r['saida']);
            ?>
                <tr class="<?= $incompleto ? 'incompleto' : '' ?>">
                    <td>
                        <?= date('d/m/Y', strtotime($r['data'])) ?><br>
                        <small><?= diaSemana($r['data']) ?></small>
                    </td>

                    <td class="funcionario">
                        <?= htmlspecialchars($r['funcionario']) ?>
                    </td>

                    <td class="horario"><?= hora($r['entrada']) ?></td>
                    <td class="horario"><?= hora($r['saida_almoco']) ?></td>
                    <td class="horario"><?= hora($r['retorno']) ?></td>
                    <td class="horario"><?= hora($r['saida']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>

</table>

</body>
</html>