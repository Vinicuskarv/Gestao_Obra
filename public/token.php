<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/Database.php';

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (!preg_match('#^/token/([^/]+)/obra/([^/]+)/?$#', $path, $m)) {
    http_response_code(404);
    echo "Not found.";
    exit;
}
$companyToken = $m[1];
$obraToken = $m[2];

session_start();
$_SESSION['companyToken'] = $companyToken;
$_SESSION['obraToken'] = $obraToken;


if (!isset($_SESSION['funcionario_id'])) {
    header("Location: /login_funcionario.php?company=" . $obraToken . "&obra=" . $companyToken);
    exit;
}


$funcionario_id = $_SESSION['funcionario_id'];

if (!defined('COMPANY_TOKEN') || $companyToken !== COMPANY_TOKEN) {
    http_response_code(403);
    echo "Acesso negado (empresa).";
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare('SELECT id, name, token FROM obras WHERE token = :token LIMIT 1');
    $stmt->execute([':token' => $obraToken]);
    $obra = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$obra) {
        http_response_code(404);
        echo "Obra não encontrada.";
        exit;
    }

    // busca pontos marcados no dia atual
    $stmt2 = $conn->prepare('
        SELECT tipo, ocorrido_at 
        FROM pontos 
        WHERE obra_id = :obra 
        AND funcionario_id = :funcionario
        AND DATE(ocorrido_at) = CURDATE() 
        ORDER BY ocorrido_at ASC
    ');
    $stmt2->execute([
        ':obra' => (int)$obra['id'],
        ':funcionario' => (int)$funcionario_id
    ]);
    $pontos = $stmt2->fetchAll(PDO::FETCH_ASSOC);


    // agrupa por tipo (último de cada tipo para exibir)
    $ultimosPontos = [];
    foreach ($pontos as $p) {
        $ultimosPontos[$p['tipo']] = $p['ocorrido_at'];
    }
} catch (Exception $e) {
    http_response_code(500);
    echo "Erro interno.";
    exit;
}

$tiposDisponiveis = ['entrada', 'pausa_inicio', 'pausa_fim', 'saida'];
?><!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Marcar Ponto — <?= htmlspecialchars($obra['name'], ENT_QUOTES, 'UTF-8') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/css/styles.css" rel="stylesheet">
    <style>
        .marcar-hora-container {
            max-width: 500px;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .btn-group-ponto {
            position: relative;
            display: flex;
            gap: 8px;
            align-items: center;
            margin-bottom: 12px;
        }
        .btn-marcar {
            flex: 1;
            text-align: left;
        }
        .hora-feedback {
            font-size: 0.85em;
            font-weight: bold;
            color: #28a745;
            background: #e8f5e9;
            padding: 4px 8px;
            border-radius: 3px;
            min-width: 70px;
            text-align: center;
        }
        .btn-editar-ponto {
            padding: 6px 10px;
            font-size: 0.85em;
        }
        .alert-feedback {
            margin-top: 10px;
            display: none;
        }
        .data-atual {
            text-align: center;
            margin-bottom: 20px;
            font-size: 0.95em;
            color: #666;
        }
        .historico-dia {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        .historico-item {
            padding: 8px 0;
            font-size: 0.9em;
            display: flex;
            justify-content: space-between;
        }
        .historico-tipo {
            font-weight: 500;
            color: #333;
        }
        .historico-hora {
            color: #28a745;
            font-weight: bold;
        }
    </style>
</head>
<body class="p-4">
    <div class="marcar-hora-container">
        <h2><?= htmlspecialchars($obra['name'], ENT_QUOTES, 'UTF-8') ?></h2>
        
        <div class="data-atual">
            <strong>Data:</strong> <?= date('d/m/Y') ?>
        </div>

        <div id="alertFeedback" class="alert alert-success alert-feedback" role="alert">
            Ponto marcado com sucesso!
        </div>

        <form id="formPonto">
            <input type="hidden" name="token" value="<?= htmlspecialchars($companyToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="obra" value="<?= (int)$obra['id'] ?>">

            <!-- Entrada -->
            <div class="btn-group-ponto">
                <button type="button" class="btn btn-primary w-100 btn-marcar" data-type="entrada" <?= isset($ultimosPontos['entrada']) ? 'disabled' : '' ?>>
                    Marcar Entrada
                </button>
                <?php if (isset($ultimosPontos['entrada'])): ?>
                    <div class="hora-feedback"><?= date('H:i:s', strtotime($ultimosPontos['entrada'])) ?></div>
                    <button type="button" class="btn btn-outline-secondary btn-sm btn-editar-ponto" data-type="entrada" data-hora="<?= htmlspecialchars(date('H:i:s', strtotime($ultimosPontos['entrada'])), ENT_QUOTES) ?>">Editar</button>
                <?php endif; ?>
            </div>

            <!-- Entrada almoço -->
            <div class="btn-group-ponto">
                <button type="button" class="btn btn-secondary w-100 btn-marcar" data-type="pausa_inicio" <?= isset($ultimosPontos['pausa_inicio']) ? 'disabled' : '' ?>>
                    Entrada almoço
                </button>
                <?php if (isset($ultimosPontos['pausa_inicio'])): ?>
                    <div class="hora-feedback"><?= date('H:i:s', strtotime($ultimosPontos['pausa_inicio'])) ?></div>
                    <button type="button" class="btn btn-outline-secondary btn-sm btn-editar-ponto" data-type="pausa_inicio" data-hora="<?= htmlspecialchars(date('H:i:s', strtotime($ultimosPontos['pausa_inicio'])), ENT_QUOTES) ?>">Editar</button>
                <?php endif; ?>
            </div>

            <!-- Saída almoço -->
            <div class="btn-group-ponto">
                <button type="button" class="btn btn-secondary w-100 btn-marcar" data-type="pausa_fim" <?= isset($ultimosPontos['pausa_fim']) ? 'disabled' : '' ?>>
                    Saída almoço
                </button>
                <?php if (isset($ultimosPontos['pausa_fim'])): ?>
                    <div class="hora-feedback"><?= date('H:i:s', strtotime($ultimosPontos['pausa_fim'])) ?></div>
                    <button type="button" class="btn btn-outline-secondary btn-sm btn-editar-ponto" data-type="pausa_fim" data-hora="<?= htmlspecialchars(date('H:i:s', strtotime($ultimosPontos['pausa_fim'])), ENT_QUOTES) ?>">Editar</button>
                <?php endif; ?>
            </div>

            <!-- Saída -->
            <div class="btn-group-ponto">
                <button type="button" class="btn btn-danger w-100 btn-marcar" data-type="saida" <?= isset($ultimosPontos['saida']) ? 'disabled' : '' ?>>
                    Marcar Saída
                </button>
                <?php if (isset($ultimosPontos['saida'])): ?>
                    <div class="hora-feedback"><?= date('H:i:s', strtotime($ultimosPontos['saida'])) ?></div>
                    <button type="button" class="btn btn-outline-secondary btn-sm btn-editar-ponto" data-type="saida" data-hora="<?= htmlspecialchars(date('H:i:s', strtotime($ultimosPontos['saida'])), ENT_QUOTES) ?>">Editar</button>
                <?php endif; ?>
            </div>
        </form>

        <!-- Histórico do dia -->
        <?php if (!empty($pontos)): ?>
        <div class="historico-dia">
            <h5>Histórico do dia</h5>
            <?php foreach ($pontos as $p): ?>
                <div class="historico-item">
                    <span class="historico-tipo"><?= ucfirst(str_replace('_', ' ', htmlspecialchars($p['tipo']))) ?></span>
                    <span class="historico-hora"><?= date('H:i:s', strtotime($p['ocorrido_at'])) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Modal de edição -->
    <div class="modal fade" id="editarPontoModal" tabindex="-1" aria-labelledby="editarPontoLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editarPontoLabel">Editar Hora</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="editarHoraInput" class="form-label">Nova Hora (HH:MM:SS)</label>
                        <input type="time" id="editarHoraInput" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btnConfirmarEdicao">Salvar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const formPonto = document.getElementById('formPonto');
        const alertFeedback = document.getElementById('alertFeedback');
        const botoesMarcar = document.querySelectorAll('.btn-marcar');
        const botoesEditar = document.querySelectorAll('.btn-editar-ponto');
        const editarHoraInput = document.getElementById('editarHoraInput');
        const btnConfirmarEdicao = document.getElementById('btnConfirmarEdicao');
        const editarPontoModal = new bootstrap.Modal(document.getElementById('editarPontoModal'));

        let tipoEmEdicao = null;

        // Marcar novo ponto
        botoesMarcar.forEach(btn => {
            btn.addEventListener('click', async function(e) {
                e.preventDefault();
                const type = this.dataset.type;
                
                const now = new Date();
                const hora = now.toLocaleTimeString('pt-BR', { 
                    hour: '2-digit', 
                    minute: '2-digit', 
                    second: '2-digit' 
                });

                try {
                    const resp = await fetch('/mark_ponto.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({
                            token: formPonto.elements['token'].value,
                            obra: formPonto.elements['obra'].value,
                            type: type,
                            hora: hora,
                            funcionario: <?= json_encode($funcionario_id) ?>
                        })
                    });

                    if (resp.ok) {
                        alertFeedback.style.display = 'block';
                        setTimeout(() => {
                            alertFeedback.style.display = 'none';
                            location.reload();
                        }, 1500);
                    } else {
                        alert('Erro ao registrar ponto.');
                    }
                } catch (err) {
                    alert('Erro de conexão.');
                }
            });
        });

        // Abrir modal de edição
        botoesEditar.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                tipoEmEdicao = this.dataset.type;
                const horaAtual = this.dataset.hora;
                editarHoraInput.value = horaAtual.substring(0, 5); // HH:MM
                editarPontoModal.show();
            });
        });

        // Confirmar edição
        btnConfirmarEdicao.addEventListener('click', async function() {
            if (!tipoEmEdicao) return;
            const novaHora = editarHoraInput.value; // HH:MM
            if (!novaHora) {
                alert('Informe a hora.');
                return;
            }

            try {
                const resp = await fetch('/update_ponto.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        token: formPonto.elements['token'].value,
                        obra: formPonto.elements['obra'].value,
                        type: tipoEmEdicao,
                        hora: novaHora + ':00',
                        funcionario: <?= json_encode($funcionario_id) ?>
                    })
                });

                if (resp.ok) {
                    editarPontoModal.hide();
                    alertFeedback.innerHTML = '<strong>Hora atualizada com sucesso!</strong>';
                    alertFeedback.style.display = 'block';
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    alert('Erro ao atualizar hora.');
                }
            } catch (err) {
                alert('Erro de conexão.');
            }
        });
    });
    </script>
</body>
</html>