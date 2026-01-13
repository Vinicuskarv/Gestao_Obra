<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/Database.php';

$erro = '';
$sucesso = '';

$companyToken = $_GET['company'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $phone = trim($_POST['phone']);
    $obraId = $_GET['company'] ?? null;

        $db = new Database();
        $conn = $db->getConnection();

        // Verifica funcionário
        $stmt = $conn->prepare("
            SELECT id, token, code, name 
            FROM funcionarios 
            WHERE token = :valor 
            LIMIT 1
        ");
        $stmt->execute([':valor' => $phone]);
        $func = $stmt->fetch(PDO::FETCH_ASSOC);

        // Se não achou pelo token, tenta pelo code
        if (!$func) {
            $stmt = $conn->prepare("
                SELECT id, token, code, name 
                FROM funcionarios 
                WHERE code = :valor 
                LIMIT 1
            ");
            $stmt->execute([':valor' => $phone]);
            $func = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if ($func) {

            $funcionario_id = $func['id'];

            // Registrar ponto
            $stmt = $conn->prepare("
                INSERT INTO pontos (obra_id, ocorrido_at, funcionario_id, funcionario_name)
                VALUES (:obra, UTC_TIMESTAMP(6), :funcionario, :funcionario_name)
            ");

            $stmt->execute([
                ':obra'        => $obraId,
                ':funcionario' => $funcionario_id,
                ':funcionario_name' => $func['name']
            ]);

            $sucesso = "Ponto registrado com sucesso para: " . $func['name'];

        } else {
            $erro = "Funcionário não encontrado pelo token ou código.";
        }


   
}
?>


<!doctype html>
<html>
<head>
    <title>Marcação de Ponto</title>

    <!-- Biblioteca para ler QR Codes -->
    <script src="https://unpkg.com/html5-qrcode"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="css/styles.css" rel="stylesheet">
    
</head>
<body>

<div class="container-card">
    <h3>Login Funcionario</h3>

    <?php if ($erro): ?>
        <p style="color: red;border: 1px solid; padding: 11px;border-radius: 4px;"><?= $erro ?></p>
    <?php endif; ?>
    <?php if ($sucesso): ?>
        <p style="color: #2ef21d;border: 1px solid;padding: 11px;border-radius: 4px;"><?= $sucesso ?></p>
    <?php endif; ?>

    <form method="post" id="loginForm">
        <input type="text" id="phone" style="width: auto;" name="phone" placeholder="Digite" required>
        <button type="submit">Enviar</button>
    </form>


    <div id="qr-reader" style="width:100%;"></div>
</div>

<script>
    const qrCodeRegionId = "qr-reader";
    const html5QrCode = new Html5Qrcode(qrCodeRegionId);

    function onScanSuccess(decodedText) {
        document.getElementById("phone").value = decodedText;

        // Para a câmera
        html5QrCode.stop();

        // Envia o formulário automaticamente
        document.getElementById("loginForm").submit();
    }

    Html5Qrcode.getCameras().then(cameras => {
        if (cameras.length === 0) {
            alert("Nenhuma câmera encontrada.");
            return;
        }

        let backCamera = cameras.find(cam =>
            cam.label.toLowerCase().includes("back") ||
            cam.label.toLowerCase().includes("traseira") ||
            cam.label.toLowerCase().includes("environment")
        );

        let cameraId = backCamera ? backCamera.id : cameras[cameras.length - 1].id;

        html5QrCode.start(
            cameraId,
            { fps: 10, qrbox: 200 },
            onScanSuccess
        );
    });
</script>


</body>
</html>
