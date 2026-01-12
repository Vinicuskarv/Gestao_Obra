<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '../../src/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Busca todas as obras
    $stmt = $conn->query("
        SELECT id, name, token 
        FROM obras 
        ORDER BY created_at DESC
    ");
    $obras = $stmt->fetchAll(PDO::FETCH_ASSOC);
  

    // Verifica obras com ponto irregular
    $stmt = $conn->query("
        SELECT DISTINCT obra_id
        FROM pontos
        GROUP BY obra_id, funcionario_id, DATE(ocorrido_at)
        HAVING COUNT(id) NOT IN (2,4)
    ");
    
    $obrasIrregulares = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Marca flag nas obras
    foreach ($obras as &$obra) {
       
        $obra['tem_ponto_incompleto'] = in_array($obra['token'], $obrasIrregulares, true);

    }

    echo json_encode([
        'success' => true,
        'obras' => $obras
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
