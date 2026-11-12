<?php
// Dados reais dos gráficos do painel (página inicial e páginas de sensores).
// Admin recebe todos os setores; gerente, somente os seus.
session_start();
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../config/setores.php';
require_once __DIR__ . '/../config/alertas.php';

if (!isset($_SESSION['id_usuario']) || !in_array($_SESSION['papel'] ?? null, ['admin', 'gerente'], true)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'mensagem' => 'Sessão expirada.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $con = conectar();
    $setores = $_SESSION['papel'] === 'admin'
        ? listarTodosSetores($con)
        : listarSetores($con, (int) $_SESSION['id_usuario']);
    $setores = anexarUltimasMedicoes($con, $setores);
    $dados = montarDadosGraficos($con, $setores);
    mysqli_close($con);

    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
} catch (Throwable $erro) {
    if (isset($con) && $con instanceof mysqli) mysqli_close($con);
    error_log('Erro em dados_graficos.php: ' . $erro->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'mensagem' => 'Não foi possível carregar os dados dos gráficos.'], JSON_UNESCAPED_UNICODE);
}
