<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/Database.php';

$erro = '';


$companyToken = $_GET['company'] ?? null;
$obraToken = $_GET['obra'] ?? null;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone']);

    try {
        $db = new Database();
        $conn = $db->getConnection();

        $stmt = $conn->prepare("SELECT id, name FROM funcionarios WHERE phone = :c LIMIT 1");
        $stmt->execute([':c' => $phone]);
        $func = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($func) {
            session_start();
            $_SESSION['funcionario_id'] = $func['id'];
            $_SESSION['funcionario_nome'] = $func['nome'];

            // Redireciona para a tela de marcar ponto
            header("Location: /token/$obraToken/obra/$companyToken");

            exit;
        } else {
            $erro = "Não encontrado.";
        }
    } catch (Exception $e) {
        $erro = "Erro interno.";
    }
}
?>
<!doctype html>
<html>
<body>
    <h3>Login do Funcionário</h3>

    <?php if ($erro): ?>
        <p style="color:red"><?= $erro ?></p>
    <?php endif; ?>

    <form method="post">
        <input type="text" name="phone" required>
        <button type="submit">Entrar</button>
    </form>
</body>
</html>
