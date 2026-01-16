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

    CASE
        WHEN COUNT(*) >= 2 THEN
            SUBSTRING_INDEX(
                SUBSTRING_INDEX(
                    GROUP_CONCAT(TIME(p.ocorrido_at) ORDER BY p.ocorrido_at),
                    ',', 2
                ),
                ',', -1
            )
        ELSE NULL
    END AS saida_almoco,

    CASE
        WHEN COUNT(*) >= 3 THEN
            SUBSTRING_INDEX(
                SUBSTRING_INDEX(
                    GROUP_CONCAT(TIME(p.ocorrido_at) ORDER BY p.ocorrido_at),
                    ',', 3
                ),
                ',', -1
            )
        ELSE NULL
    END AS retorno,

    CASE
        WHEN COUNT(*) >= 4 THEN
            MAX(TIME(p.ocorrido_at))
        ELSE NULL
    END AS saida,

    /* TOTAL DE HORAS */
    CASE
        WHEN COUNT(*) >= 4 THEN
            SEC_TO_TIME(
                TIME_TO_SEC(MAX(p.ocorrido_at)) -
                TIME_TO_SEC(MIN(p.ocorrido_at)) -
                (
                    TIME_TO_SEC(
                        SUBSTRING_INDEX(
                            SUBSTRING_INDEX(
                                GROUP_CONCAT(p.ocorrido_at ORDER BY p.ocorrido_at),
                                ',', 3
                            ),
                            ',', -1
                        )
                    ) -
                    TIME_TO_SEC(
                        SUBSTRING_INDEX(
                            SUBSTRING_INDEX(
                                GROUP_CONCAT(p.ocorrido_at ORDER BY p.ocorrido_at),
                                ',', 2
                            ),
                            ',', -1
                        )
                    )
                )
            )
        WHEN COUNT(*) >= 2 THEN
            SEC_TO_TIME(
                TIME_TO_SEC(MAX(p.ocorrido_at)) -
                TIME_TO_SEC(MIN(p.ocorrido_at))
            )
        ELSE NULL
    END AS total_horas

FROM pontos p
WHERE p.obra_id = :obra
  AND p.ocorrido_at BETWEEN :inicio AND :fim
";

if (!empty($funcionarioId)) {
    $sql .= " AND p.funcionario_id = :funcionario";
}

$sql .= "
GROUP BY p.funcionario_id, DATE(p.ocorrido_at)
ORDER BY funcionario, data
";



$params = [
    ':obra' => $obraToken,
    ':inicio' => $inicio . ' 00:00:00',
    ':fim' => $fim . ' 23:59:59'
];

if (!empty($funcionarioId)) {
    $params[':funcionario'] = $funcionarioId;
}
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

$porFuncionario = [];

foreach ($registros as $r) {
    $id = $r['funcionario_id'];

    if (!isset($porFuncionario[$id])) {
        $porFuncionario[$id] = [
            'nome' => $r['funcionario'],
            'registros' => [],
            'total_segundos' => 0
        ];
    }

    $porFuncionario[$id]['registros'][] = $r;

    if (!empty($r['total_horas'])) {
        list($h, $m, $s) = explode(':', $r['total_horas']);
        $porFuncionario[$id]['total_segundos'] += ($h * 3600) + ($m * 60) + $s;
    }
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


function hora($time) {
    return empty($time) ? '-' : date('H:i', strtotime($time));
}
function formatarHoras($segundos) {
    $h = floor($segundos / 3600);
    $m = floor(($segundos % 3600) / 60);
    return sprintf('%02d:%02d', $h, $m);
}




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
<?php foreach ($porFuncionario as $func): ?>
    <h4><?= htmlspecialchars($func['nome']) ?></h4>

    <table class="table table-bordered table-sm" style="margin-bottom:30px;">
        <thead>
            <tr>
                <th>Data</th>
                <th>Entrada</th>
                <th>Saída Almoço</th>
                <th>Retorno</th>
                <th>Saída</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($func['registros'] as $r): ?>
                <tr class="<?= empty($r['saida']) ? 'incompleto' : '' ?>">
                    <td>
                        <?= date('d/m/Y', strtotime($r['data'])) ?><br>
                        <small><?= diaSemana($r['data']) ?></small>
                    </td>
                    <td class="horario"><?= hora($r['entrada']) ?></td>
                    <td class="horario"><?= hora($r['saida_almoco']) ?></td>
                    <td class="horario"><?= hora($r['retorno']) ?></td>
                    <td class="horario"><?= hora($r['saida']) ?></td>
                    <td class="horario"><?= hora($r['total_horas']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="5" style="text-align:right;">Total do mês</th>
                <th class="horario">
                    <?= formatarHoras($func['total_segundos']) ?>
                </th>
            </tr>
        </tfoot>
    </table>
<?php endforeach; ?>


</body>
</html>