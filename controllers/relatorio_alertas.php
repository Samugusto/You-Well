<?php
// Dados do relatório de alertas dos últimos 30 dias (o PDF é montado no navegador).
// Os alertas são calculados a partir das leituras de temperatura, ruído e
// qualidade do ar, usando os mesmos limites da página de alertas.
// Admin recebe todos os setores; gerente, somente os seus.
session_start();
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../config/setores.php';
require_once __DIR__ . '/../config/alertas.php';

const DIAS_RELATORIO = 30;
const MAX_CRITICOS_POR_SETOR = 100;

if (!isset($_SESSION['id_usuario']) || !in_array($_SESSION['papel'] ?? null, ['admin', 'gerente'], true)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'mensagem' => 'Sessão expirada.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Condições SQL de "alto" e "baixo" de um sensor (limites vêm de constantes).
function condicoesSensor(string $sensor): array
{
    $limite = LIMITES_SENSORES[$sensor];
    $alto = $limite['maximo'] !== null ? sprintf('valor > %.4F', $limite['maximo']) : '0';
    $baixo = $limite['minimo'] !== null ? sprintf('valor <= %.4F', $limite['minimo']) : '0';
    return [$alto, $baixo];
}

function numeroOuNull($valor): ?float
{
    return $valor === null ? null : round((float) $valor, 2);
}

try {
    $con = conectar();
    $papel = $_SESSION['papel'];
    $setores = $papel === 'admin'
        ? listarTodosSetores($con)
        : listarSetores($con, (int) $_SESSION['id_usuario']);

    // Período calculado pelo relógio do MySQL (o mesmo que grava data_hora).
    $periodo = mysqli_fetch_assoc(mysqli_query($con, 'SELECT NOW() AS fim, NOW() - INTERVAL ' . DIAS_RELATORIO . ' DAY AS inicio'));

    $dadosSetores = [];
    foreach ($setores as $setor) {
        $dadosSetores[(int) $setor['id_setor']] = [
            'id_setor' => (int) $setor['id_setor'],
            'nome' => $setor['nome'],
            'responsavel' => $setor['responsavel'] ?? ($setor['gerente'] ?? ''),
            'sensores' => [],
            'dias' => [],
            'criticos' => [],
            'criticos_omitidos' => 0,
        ];
        foreach (array_keys(LIMITES_SENSORES) as $sensor) {
            $dadosSetores[(int) $setor['id_setor']]['sensores'][$sensor] = [
                'leituras' => 0, 'altas' => 0, 'baixas' => 0, 'minimo' => null, 'maximo' => null, 'media' => null,
            ];
        }
    }

    $ids = array_keys($dadosSetores);
    if ($ids) {
        $listaIds = implode(',', array_map('intval', $ids));
        $filtro = "id_setor IN ({$listaIds}) AND data_hora >= NOW() - INTERVAL " . DIAS_RELATORIO . ' DAY';

        foreach (array_keys(LIMITES_SENSORES) as $sensor) {
            [$condAlto, $condBaixo] = condicoesSensor($sensor);

            // Resumo por dia.
            $sqlDias = "SELECT id_setor, DATE(data_hora) AS dia, COUNT(*) AS leituras, MIN(valor) AS minimo, MAX(valor) AS maximo, AVG(valor) AS media, SUM({$condAlto}) AS altas, SUM({$condBaixo}) AS baixas FROM `{$sensor}` WHERE {$filtro} GROUP BY id_setor, DATE(data_hora)";
            $resultado = mysqli_query($con, $sqlDias);
            while ($resultado && ($linha = mysqli_fetch_assoc($resultado))) {
                $id = (int) $linha['id_setor'];
                $dadosSetores[$id]['dias'][] = [
                    'dia' => $linha['dia'],
                    'sensor' => $sensor,
                    'leituras' => (int) $linha['leituras'],
                    'minimo' => numeroOuNull($linha['minimo']),
                    'maximo' => numeroOuNull($linha['maximo']),
                    'media' => numeroOuNull($linha['media']),
                    'altas' => (int) $linha['altas'],
                    'baixas' => (int) $linha['baixas'],
                ];
            }

            // Resumo do período inteiro por sensor.
            $sqlTotal = "SELECT id_setor, COUNT(*) AS leituras, MIN(valor) AS minimo, MAX(valor) AS maximo, AVG(valor) AS media, SUM({$condAlto}) AS altas, SUM({$condBaixo}) AS baixas FROM `{$sensor}` WHERE {$filtro} GROUP BY id_setor";
            $resultado = mysqli_query($con, $sqlTotal);
            while ($resultado && ($linha = mysqli_fetch_assoc($resultado))) {
                $dadosSetores[(int) $linha['id_setor']]['sensores'][$sensor] = [
                    'leituras' => (int) $linha['leituras'],
                    'altas' => (int) $linha['altas'],
                    'baixas' => (int) $linha['baixas'],
                    'minimo' => numeroOuNull($linha['minimo']),
                    'maximo' => numeroOuNull($linha['maximo']),
                    'media' => numeroOuNull($linha['media']),
                ];
            }

            // Leituras críticas (cada uma é um alerta), das mais recentes para as mais antigas.
            if ($condAlto !== '0' || $condBaixo !== '0') {
                $sqlCriticos = "SELECT id_setor, valor, data_hora FROM `{$sensor}` WHERE {$filtro} AND (({$condAlto}) OR ({$condBaixo})) ORDER BY data_hora DESC LIMIT 5000";
                $resultado = mysqli_query($con, $sqlCriticos);
                while ($resultado && ($linha = mysqli_fetch_assoc($resultado))) {
                    $classificacao = classificarSensor($sensor, (float) $linha['valor']);
                    $dadosSetores[(int) $linha['id_setor']]['criticos'][] = [
                        'data_hora' => $linha['data_hora'],
                        'sensor' => $sensor,
                        'valor' => round((float) $linha['valor'], 2),
                        'texto' => $classificacao['texto'],
                    ];
                }
            }
        }
    }
    mysqli_close($con);

    // Ordena e limita os alertas críticos de cada setor.
    $totalLeituras = 0;
    $totalCriticos = 0;
    foreach ($dadosSetores as &$dadosSetor) {
        usort($dadosSetor['dias'], fn ($a, $b) => [$b['dia'], $a['sensor']] <=> [$a['dia'], $b['sensor']]);
        usort($dadosSetor['criticos'], fn ($a, $b) => strcmp($b['data_hora'], $a['data_hora']));

        $criticosSetor = 0;
        foreach ($dadosSetor['sensores'] as $resumoSensor) {
            $totalLeituras += $resumoSensor['leituras'];
            $criticosSetor += $resumoSensor['altas'] + $resumoSensor['baixas'];
        }
        $totalCriticos += $criticosSetor;
        $dadosSetor['total_criticos'] = $criticosSetor;
        $dadosSetor['criticos_omitidos'] = max(0, $criticosSetor - MAX_CRITICOS_POR_SETOR);
        $dadosSetor['criticos'] = array_slice($dadosSetor['criticos'], 0, MAX_CRITICOS_POR_SETOR);
    }
    unset($dadosSetor);

    $limites = [];
    foreach (LIMITES_SENSORES as $sensor => $limite) {
        $limites[$sensor] = ['nome' => $limite['nome'], 'unidade' => trim($limite['unidade']), 'minimo' => $limite['minimo'], 'maximo' => $limite['maximo']];
    }

    echo json_encode([
        'success' => true,
        'dias' => DIAS_RELATORIO,
        'periodo' => $periodo,
        'gerado_por' => ['nome' => (string) $_SESSION['nome'], 'papel' => $papel === 'admin' ? 'Administrador' : 'Gerente'],
        'gerado_em' => $periodo['fim'],
        'limites' => $limites,
        'max_criticos_por_setor' => MAX_CRITICOS_POR_SETOR,
        'resumo' => [
            'setores' => count($dadosSetores),
            'leituras' => $totalLeituras,
            'criticos' => $totalCriticos,
            'setores_com_alerta' => count(array_filter($dadosSetores, fn ($s) => $s['total_criticos'] > 0)),
        ],
        'setores' => array_values($dadosSetores),
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $erro) {
    if (isset($con) && $con instanceof mysqli) {
        try { mysqli_close($con); } catch (Throwable $e) {}
    }
    error_log('Erro em relatorio_alertas.php: ' . $erro->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'mensagem' => 'Não foi possível gerar o relatório.'], JSON_UNESCAPED_UNICODE);
}
