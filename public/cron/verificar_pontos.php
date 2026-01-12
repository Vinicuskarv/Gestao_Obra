<?php
// ini_set('display_errors', 1);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

function logCron($msg) {
    file_put_contents(
        __DIR__ . '/cron.log',
        '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL,
        FILE_APPEND
    );
}

require_once __DIR__ . '/../../src/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->query("
        SELECT obra_id, funcionario_id, COUNT(*) total
        FROM pontos
        WHERE DATE(ocorrido_at) = CURDATE()
        GROUP BY obra_id, funcionario_id
        HAVING total NOT IN (2,4)
    ");

    $pendentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    logCron('Funcionários com ponto incompleto: ' . count($pendentes));

    // Preparar email
    if (count($pendentes) > 0) {
        $mensagem = "Funcionários com ponto incompleto hoje:\n\n";
        foreach ($pendentes as $p) {
            $mensagem .= "Obra ID: {$p['obra_id']}, Funcionario ID: {$p['funcionario_id']}, Total de registros: {$p['total']}\n";
        }

        $to = 'karvvinicius@gmail.com';
        $subject = 'Relatório de Ponto Incompleto';
        $headers = "From: geral@clicktimepro.com\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        if(mail($to, $subject, $mensagem, $headers)) {
            logCron("Email enviado para {$to} com " . count($pendentes) . " registros incompletos.");
        } else {
            logCron("Falha ao enviar email para {$to}");
        }
    } else {
        logCron("Nenhum ponto incompleto hoje, nenhum email enviado.");
    }

} catch (Throwable $e) {
    logCron('ERRO: ' . $e->getMessage());
}
