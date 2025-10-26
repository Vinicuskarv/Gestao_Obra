<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '../../src/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    // garante schema mínimo (opcional)
    $conn->exec("CREATE TABLE IF NOT EXISTS funcionarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(150),
        phone VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $conn->exec("CREATE TABLE IF NOT EXISTS obra_funcionario (
        obra_id INT NOT NULL,
        funcionario_id INT NOT NULL,
        role VARCHAR(100) DEFAULT NULL,
        status VARCHAR(50) DEFAULT 'ativo',
        assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (obra_id, funcionario_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $sql = "SELECT f.id, f.name, f.email, f.phone,
                   GROUP_CONCAT(CONCAT(o.id, '::', REPLACE(o.name, '::', ''), '::', COALESCE(ofp.status, 'ativo')) SEPARATOR '||') AS obras
            FROM funcionarios f
            LEFT JOIN obra_funcionario ofp ON ofp.funcionario_id = f.id
            LEFT JOIN obras o ON o.id = ofp.obra_id
            GROUP BY f.id
            ORDER BY f.created_at DESC";
    $stmt = $conn->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $result = array_map(function($r){
        $obras = [];
        if (!empty($r['obras'])) {
            foreach (explode('||', $r['obras']) as $pair) {
                if ($pair === '') continue;
                $parts = explode('::', $pair, 3);
                if (count($parts) === 3) {
                    $obras[] = ['id' => (int)$parts[0], 'name' => $parts[1], 'status' => $parts[2]];
                }
            }
        }
        return [
            'id' => (int)$r['id'],
            'name' => $r['name'],
            'email' => $r['email'],
            'phone' => $r['phone'],
            'obras' => $obras
        ];
    }, $rows);

    echo json_encode(['success' => true, 'funcionarios' => $result], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}