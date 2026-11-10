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

// Gera alertas de temperatura, qualidade do ar e ruído para cada setor
// que já possui medição. Espera setores preenchidos por anexarUltimasMedicoes.
// Ordem: críticos primeiro, e entre eles os que mais passaram do limite.
function gerarAlertasSetores(array $setores): array
{
    $alertas = [];

    foreach ($setores as $setor) {
        foreach (array_keys(LIMITES_SENSORES) as $sensor) {
            if (!isset($setor[$sensor]) || !is_numeric($setor[$sensor])) continue;

            $classificacao = classificarSensor($sensor, (float) $setor[$sensor]);
            $alertas[] = [
                'setor' => $setor['nome'],
                'sensor' => $sensor,
                'texto' => $classificacao['texto'],
                'classe' => $classificacao['classe'],
                'nivel' => $classificacao['nivel'],
                'excesso' => $classificacao['excesso'],
            ];
        }
    }

    usort($alertas, function ($a, $b) {
        $porNivel = ($a['nivel'] === 'estavel') <=> ($b['nivel'] === 'estavel');
        return $porNivel !== 0 ? $porNivel : $b['excesso'] <=> $a['excesso'];
    });

    return $alertas;
}
