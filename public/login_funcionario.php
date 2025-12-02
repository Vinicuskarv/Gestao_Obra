<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/Database.php';

$erro = '';

$companyToken = $_GET['company'] ?? null;
$obraToken = $_GET['obra'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $phone = trim($_POST['phone']);
    $obraId = $_GET['company'] ?? null;

    try {
        $db = new Database();
        $conn = $db->getConnection();

        // Verifica funcionário
        $stmt = $conn->prepare("SELECT id, token, name FROM funcionarios WHERE token = :c LIMIT 1");
        $stmt->execute([':c' => $phone]);
        $func = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($func) {

            $funcionario_id = $func['id'];

            // Registrar ponto
            $stmt = $conn->prepare("
                INSERT INTO pontos (obra_id, ocorrido_at, funcionario_id)
                VALUES (:obra, UTC_TIMESTAMP(6), :funcionario)
            ");

            $stmt->execute([
                ':obra'        => $obraId,
                ':funcionario' => $funcionario_id
            ]);

            $erro = "Ponto registrado com sucesso para: " . $func['name'];

        } else {
            $erro = "Funcionário não encontrado.";
        }

    } catch (Exception $e) {
        $erro = "Erro interno.";
    }
}
?>


<!doctype html>
<html>
<head>
    <title>Login Funcionário</title>

    <!-- Biblioteca para ler QR Codes -->
    <script src="https://unpkg.com/html5-qrcode"></script>

    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: auto;
            background: #f0f0f0;
            font-family: Arial;
        }

        .container {
            background: white;
            padding: 25px;
            width: 320px;
            text-align: center;
            border-radius: 10px;
            box-shadow: 0 0 10px #0003;
        }

        input {
            width: 90%;
            padding: 10px;
            margin-top: 10px;
            border-radius: 5px;
            border: 1px solid #aaa;
        }

        button {
            margin-top: 10px;
            padding: 10px 20px;
            background: #008cff;
            border: none;
            color: white;
            border-radius: 5px;
            cursor: pointer;
        }

        #qr-reader {
            margin-top: 20px;
        }
    </style>
</head>
<body>

<div class="container">
    <h3>Login do Funcionário</h3>

    <?php if ($erro): ?>
        <p style="color:red"><?= $erro ?></p>
    <?php endif; ?>

    <form method="post">
        <input type="text" id="phone" name="phone" placeholder="Digite" required>
        <button type="submit">Entrar</button>
    </form>

    <h4>Ou ler QR Code:</h4>
    <div id="qr-reader" style="width:250px;"></div>
</div>

<script>
function onScanSuccess(decodedText) {
    document.getElementById("phone").value = decodedText;
}

let html5QrcodeScanner = new Html5QrcodeScanner(
    "qr-reader",
    { fps: 10, qrbox: 200 }
);

html5QrcodeScanner.render(onScanSuccess);
</script>

</body>
</html>
