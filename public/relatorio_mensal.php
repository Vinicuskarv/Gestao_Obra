<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/Database.php';

echo '<pre>';

$obraToken = $_GET['obra_token'] ?? '';
$mes = $_GET['mes'] ?? date('Y-m');
$funcionarioId = $_GET['funcionario_id'] ?? null;

var_dump($_GET);

$db = new Database();
$conn = $db->getConnection();

$stmt = $conn->prepare('SELECT id, name FROM obras WHERE token = :t');
$stmt->execute([':t' => $obraToken]);
$obra = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$obra) {
    die('Obra não encontrada');
}

$inicio = $mes . '-01 00:00:00';
$fim = date('Y-m-t 23:59:59', strtotime($inicio));
echo "Obra OK: {$obra['name']}\n";
$sql = '
    SELECT f.name, p.ocorrido_at
    FROM pontos p
    JOIN funcionarios f ON f.id = p.funcionario_id
    WHERE p.obra_id = :obra
    AND p.ocorrido_at BETWEEN :ini AND :fim
';


$params = [
    ':obra' => $obra['id'],
    ':ini' => $inicio,
    ':fim' => $fim
];

echo "\nSQL:\n$sql\n";
print_r($params);

if ($funcionarioId) {
    $sql .= ' AND f.id = :fid';
    $params[':fid'] = $funcionarioId;
}

// echo "\nSQL:\n$sql\n";
// print_r($params);

$stmt = $conn->prepare($sql);
$stmt->execute($params);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "\nSQL:\n$sql\n";
print_r($rows);

?>

<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<title>Relatório Mensal</title>
</head>
<body>

<h3>Relatório — <?= htmlspecialchars($obra['name']) ?></h3>

<ul>
<?php if (empty($rows)): ?>
    <div class="alert alert-warning">
        Nenhum registro encontrado para o período selecionado.
    </div>
<?php else: ?>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Funcionário</th>
                <th>Data / Hora</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['name']) ?></td>
                <td><?= date('d/m/Y H:i', strtotime($r['ocorrido_at'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

</ul>

</body>
</html>