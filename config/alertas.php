<?php

// Faixas consideradas estáveis para cada sensor. Fora da faixa = crítico.
// 'minimo' ou 'maximo' null significa sem limite naquele lado.
const LIMITES_SENSORES = [
    'temperatura' => [
        'nome' => 'Temperatura',
        'unidade' => '°C',
        'minimo' => 35.0,
        'maximo' => 45.0,
        'alto' => 'alta',
        'baixo' => 'baixa',
        'estavel' => 'estável',
    ],
    'qualidade_ar' => [
        'nome' => 'Qualidade do ar',
        'unidade' => ' ppm',
        'minimo' => null,
        'maximo' => 1000.0,
        'alto' => 'ruim',
        'baixo' => 'ruim',
        'estavel' => 'boa',
    ],
    'ruido' => [
        'nome' => 'Ruído',
        'unidade' => ' dB',
        'minimo' => null,
        'maximo' => 85.0, // limite de exposição de 8h (NR-15)
        'alto' => 'alto',
        'baixo' => 'baixo',
        'estavel' => 'estável',
    ],
];

// Busca o valor mais recente de um sensor (tabelas temperatura, ruido,
// qualidade_ar: colunas id_<tabela>, id_setor, valor, data_hora).
function buscarUltimoValorSensor(mysqli $con, string $tabela, int $idSetor): ?array
{
    if ($idSetor <= 0 || !array_key_exists($tabela, LIMITES_SENSORES)) return null;

    $colunaId = 'id_' . $tabela;
    $sql = "SELECT valor, data_hora FROM `{$tabela}` WHERE id_setor = ? ORDER BY data_hora DESC, `{$colunaId}` DESC LIMIT 1";

    try {
        $stmt = mysqli_prepare($con, $sql);
        if (!$stmt) return null;
        mysqli_stmt_bind_param($stmt, 'i', $idSetor);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        $medicao = $resultado ? mysqli_fetch_assoc($resultado) : null;
        mysqli_stmt_close($stmt);
    } catch (Throwable $erro) {
        error_log("Erro ao buscar {$tabela} do setor {$idSetor}: " . $erro->getMessage());
        return null;
    }

    return $medicao ?: null;
}

// Adiciona a medição mais recente de cada sensor a cada setor.
function anexarUltimasMedicoes(mysqli $con, array $setores): array
{
    foreach ($setores as &$setor) {
        foreach (array_keys(LIMITES_SENSORES) as $sensor) {
            $medicao = buscarUltimoValorSensor($con, $sensor, (int) $setor['id_setor']);
            $setor[$sensor] = $medicao['valor'] ?? null;
            $setor[$sensor . '_data'] = $medicao['data_hora'] ?? null;
        }
    }
    unset($setor);

    return $setores;
}

// Classifica um valor de sensor como alto, baixo ou estável.
// 'excesso' indica o quanto (em %) o valor passou do limite, usado para ordenar.
function classificarSensor(string $sensor, float $valor): array
{
    $limite = LIMITES_SENSORES[$sensor];
    $casas = $sensor === 'qualidade_ar' ? 0 : 1;
    $valorFormatado = number_format($valor, $casas, ',', '.') . $limite['unidade'];
    $nome = $limite['nome'];

    if ($limite['maximo'] !== null && $valor > $limite['maximo']) {
        return [
            'nivel' => 'critico',
            'texto' => "Crítico: {$nome} do setor {$limite['alto']}: {$valorFormatado}",
            'classe' => 'janelinhaA',
            'excesso' => ($valor - $limite['maximo']) / max(abs($limite['maximo']), 1),
        ];
    }

    if ($limite['minimo'] !== null && $valor < $limite['minimo']) {
        return [
            'nivel' => 'critico',
            'texto' => "Crítico: {$nome} do setor {$limite['baixo']}: {$valorFormatado}",
            'classe' => 'janelinhaA',
            'excesso' => ($limite['minimo'] - $valor) / max(abs($limite['minimo']), 1),
        ];
    }

    return [
        'nivel' => 'estavel',
        'texto' => "Avisos: {$nome} do setor {$limite['estavel']}: {$valorFormatado}",
        'classe' => 'janelinhaM',
        'excesso' => 0.0,
    ];
}

// Quantidade máxima de alertas guardados. Ao passar disso, os mais velhos são apagados.
const LIMITE_ALERTAS = 10;

// Cria as tabelas de alertas:
// - alertas: histórico (no máximo LIMITE_ALERTAS linhas);
// - alertas_controle: última medição já transformada em alerta por setor/sensor,
//   para não gerar o mesmo alerta de novo a cada carregamento da página.
function garantirTabelasAlertas(mysqli $con): void
{
    $sqlAlertas = "CREATE TABLE IF NOT EXISTS alertas (id_alerta INT NOT NULL AUTO_INCREMENT, id_setor INT NOT NULL, sensor VARCHAR(20) NOT NULL, nivel VARCHAR(10) NOT NULL, texto VARCHAR(255) NOT NULL, classe VARCHAR(20) NOT NULL, excesso DECIMAL(12,4) NOT NULL DEFAULT 0, data_medicao DATETIME NOT NULL, criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (id_alerta), KEY idx_alertas_setor (id_setor)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if (!mysqli_query($con, $sqlAlertas)) throw new RuntimeException('Não foi possível preparar a tabela de alertas.');

    $sqlControle = "CREATE TABLE IF NOT EXISTS alertas_controle (id_setor INT NOT NULL, sensor VARCHAR(20) NOT NULL, ultima_medicao DATETIME NOT NULL, PRIMARY KEY (id_setor, sensor)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if (!mysqli_query($con, $sqlControle)) throw new RuntimeException('Não foi possível preparar o controle de alertas.');
}

// Gera um alerta para cada medição nova (temperatura, qualidade do ar, ruído)
// e mantém só os LIMITE_ALERTAS mais recentes. Espera setores preenchidos
// por anexarUltimasMedicoes.
function registrarAlertas(mysqli $con, array $setores): void
{
    garantirTabelasAlertas($con);

    $controle = [];
    $resultado = mysqli_query($con, 'SELECT id_setor, sensor, ultima_medicao FROM alertas_controle');
    while ($resultado && ($linha = mysqli_fetch_assoc($resultado))) {
        $controle[$linha['id_setor'] . '|' . $linha['sensor']] = $linha['ultima_medicao'];
    }

    $novos = [];
    foreach ($setores as $setor) {
        $idSetor = (int) $setor['id_setor'];
        foreach (array_keys(LIMITES_SENSORES) as $sensor) {
            $valor = $setor[$sensor] ?? null;
            $dataMedicao = $setor[$sensor . '_data'] ?? null;
            if (!is_numeric($valor) || !$dataMedicao) continue;

            $ultima = $controle[$idSetor . '|' . $sensor] ?? null;
            if ($ultima !== null && strtotime($dataMedicao) <= strtotime($ultima)) continue;

            $novos[] = ['id_setor' => $idSetor, 'sensor' => $sensor, 'data' => $dataMedicao]
                + classificarSensor($sensor, (float) $valor);
        }
    }
    if (!$novos) return;

    // Insere da medição mais antiga para a mais nova, para que o id reflita a idade do alerta.
    usort($novos, fn ($a, $b) => strtotime($a['data']) <=> strtotime($b['data']));

    mysqli_begin_transaction($con);
    try {
        $stmtAlerta = mysqli_prepare($con, 'INSERT INTO alertas (id_setor, sensor, nivel, texto, classe, excesso, data_medicao) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmtControle = mysqli_prepare($con, 'INSERT INTO alertas_controle (id_setor, sensor, ultima_medicao) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE ultima_medicao = VALUES(ultima_medicao)');
        foreach ($novos as $alerta) {
            $excesso = (float) $alerta['excesso'];
            mysqli_stmt_bind_param($stmtAlerta, 'issssds', $alerta['id_setor'], $alerta['sensor'], $alerta['nivel'], $alerta['texto'], $alerta['classe'], $excesso, $alerta['data']);
            mysqli_stmt_execute($stmtAlerta);
            mysqli_stmt_bind_param($stmtControle, 'iss', $alerta['id_setor'], $alerta['sensor'], $alerta['data']);
            mysqli_stmt_execute($stmtControle);
        }
        mysqli_stmt_close($stmtAlerta);
        mysqli_stmt_close($stmtControle);

        // Mantém apenas os LIMITE_ALERTAS mais recentes; apaga os mais velhos.
        mysqli_query($con, 'DELETE FROM alertas WHERE id_alerta NOT IN (SELECT id_alerta FROM (SELECT id_alerta FROM alertas ORDER BY id_alerta DESC LIMIT ' . (int) LIMITE_ALERTAS . ') AS recentes)');

        mysqli_commit($con);
    } catch (Throwable $erro) {
        mysqli_rollback($con);
        throw $erro;
    }
}

// Lista os alertas guardados. $idsSetores = null lista de todos os setores
// (admin); um array limita aos setores informados (gerente).
// Ordem: críticos primeiro, depois os que mais passaram do limite, depois os mais novos.
function listarAlertas(mysqli $con, ?array $idsSetores = null): array
{
    garantirTabelasAlertas($con);

    $sql = 'SELECT a.id_alerta, a.id_setor, s.nome AS setor, a.sensor, a.nivel, a.texto, a.classe, a.excesso, a.data_medicao FROM alertas a INNER JOIN setores s ON s.id_setor = a.id_setor';
    if ($idsSetores !== null) {
        $idsSetores = array_values(array_filter(array_map('intval', $idsSetores), fn ($id) => $id > 0));
        if (!$idsSetores) return [];
        $sql .= ' WHERE a.id_setor IN (' . implode(',', $idsSetores) . ')';
    }

    $resultado = mysqli_query($con, $sql);
    $alertas = [];
    while ($resultado && ($linha = mysqli_fetch_assoc($resultado))) $alertas[] = $linha;

    usort($alertas, function ($a, $b) {
        $porNivel = ($a['nivel'] === 'estavel') <=> ($b['nivel'] === 'estavel');
        if ($porNivel !== 0) return $porNivel;
        $porExcesso = (float) $b['excesso'] <=> (float) $a['excesso'];
        return $porExcesso !== 0 ? $porExcesso : (int) $b['id_alerta'] <=> (int) $a['id_alerta'];
    });

    return $alertas;
}
