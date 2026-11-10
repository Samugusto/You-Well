<?php

// Faixa de temperatura considerada estável (°C). Valores abaixo de
// TEMPERATURA_MINIMA são "baixa" e acima de TEMPERATURA_MAXIMA são "alta".
const TEMPERATURA_MINIMA = 35.0;
const TEMPERATURA_MAXIMA = 45.0;

// Busca a medição mais recente de um setor na tabela setor_{id}
// (preenchida pela API do ESP32). Retorna null se não houver dados.
function buscarUltimaMedicaoSetor(mysqli $con, int $idSetor): ?array
{
    if ($idSetor <= 0) return null;

    $tabela = 'setor_' . $idSetor;
    $sql = "SELECT temperatura, ruido, qualidade_ar, data_hora FROM `{$tabela}` ORDER BY data_hora DESC, id_medicao DESC LIMIT 1";

    try {
        $resultado = mysqli_query($con, $sql);
    } catch (Throwable $erro) {
        // A tabela ainda não existe (setor sem dispositivo/medições).
        return null;
    }
    if (!$resultado) return null;

    $medicao = mysqli_fetch_assoc($resultado);
    mysqli_free_result($resultado);

    return $medicao ?: null;
}

// Adiciona a última medição (temperatura, ruído, qualidade do ar) a cada setor.
function anexarUltimasMedicoes(mysqli $con, array $setores): array
{
    foreach ($setores as &$setor) {
        $medicao = buscarUltimaMedicaoSetor($con, (int) $setor['id_setor']);
        $setor['temperatura'] = $medicao['temperatura'] ?? null;
        $setor['ruido'] = $medicao['ruido'] ?? null;
        $setor['qualidade_ar'] = $medicao['qualidade_ar'] ?? null;
        $setor['data_medicao'] = $medicao['data_hora'] ?? null;
    }
    unset($setor);

    return $setores;
}

// Classifica a temperatura em baixa, estável ou alta.
function classificarTemperatura(float $temperatura): array
{
    $valor = number_format($temperatura, 1, ',', '.') . '°C';

    if ($temperatura < TEMPERATURA_MINIMA) {
        return [
            'nivel' => 'baixa',
            'texto' => "Aviso: a temperatura do setor está baixa: {$valor}",
            'classe' => 'janelinhaB',
        ];
    }

    if ($temperatura > TEMPERATURA_MAXIMA) {
        return [
            'nivel' => 'alta',
            'texto' => "Crítico: a temperatura do setor está alta: {$valor}",
            'classe' => 'janelinhaA',
        ];
    }

    return [
        'nivel' => 'estavel',
        'texto' => "Normal: a temperatura do setor está estável: {$valor}",
        'classe' => 'janelinhaE',
    ];
}

// Gera um alerta de temperatura para cada setor que já possui medição.
// Espera setores com a chave 'temperatura' (ver anexarUltimasMedicoes).
function gerarAlertasTemperatura(array $setores): array
{
    $alertas = [];

    foreach ($setores as $setor) {
        if (!isset($setor['temperatura']) || !is_numeric($setor['temperatura'])) continue;

        $classificacao = classificarTemperatura((float) $setor['temperatura']);
        $alertas[] = [
            'setor' => $setor['nome'],
            'texto' => $classificacao['texto'],
            'classe' => $classificacao['classe'],
            'nivel' => $classificacao['nivel'],
            'data_hora' => $setor['data_medicao'] ?? null,
        ];
    }

    // Alertas que exigem atenção (alta/baixa) aparecem primeiro.
    usort($alertas, fn ($a, $b) => ($a['nivel'] === 'estavel') <=> ($b['nivel'] === 'estavel'));

    return $alertas;
}
