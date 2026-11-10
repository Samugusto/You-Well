<?php


header('Content-Type: application/json; charset=UTF-8');


require_once __DIR__ . '/../config/conexao.php';


mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);


try {


    $idSetor = $_POST['id_setor'] ?? '';


    // Validar ID do setor
    if (
        $idSetor === '' ||
        !ctype_digit((string)$idSetor) ||
        (int)$idSetor <= 0
    ) {
        echo json_encode([
            'success' => false,
            'mensagem' => 'ID do setor inválido.'
        ], JSON_UNESCAPED_UNICODE);


        exit;
    }


    $con = conectar();


    // Buscar as últimas 24 medições de cada sensor
    function buscarUltimasMedicoes(
        mysqli $con,
        string $tabela,
        string $idColuna,
        int $idSetor
    ): array {


        $sql = "
            SELECT valor, data_hora
            FROM `$tabela`
            WHERE id_setor = ?
            ORDER BY data_hora DESC, `$idColuna` DESC
            LIMIT 24
        ";


        $stmt = $con->prepare($sql);


        $stmt->bind_param("i", $idSetor);


        $stmt->execute();


        $resultado = $stmt->get_result();


        $medicoes = [];


        while ($linha = $resultado->fetch_assoc()) {


            $medicoes[] = [
                'valor' => (float)$linha['valor'],
                'data_hora' => $linha['data_hora']
            ];


        }


        $stmt->close();


        // Organizar da medição mais antiga para a mais recente
        return array_reverse($medicoes);
    }


    // Temperatura
    $temperatura = buscarUltimasMedicoes(
        $con,
        'temperatura',
        'id_temperatura',
        (int)$idSetor
    );


    // Ruído
    $ruido = buscarUltimasMedicoes(
        $con,
        'ruido',
        'id_ruido',
        (int)$idSetor
    );


    // Qualidade do ar
    $qualidadeAr = buscarUltimasMedicoes(
        $con,
        'qualidade_ar',
        'id_qualidade_ar',
        (int)$idSetor
    );


    echo json_encode([
        'success' => true,
        'temperatura' => $temperatura,
        'ruido' => $ruido,
        'qualidade_ar' => $qualidadeAr
    ], JSON_UNESCAPED_UNICODE);


    $con->close();


} catch (Throwable $e) {


    http_response_code(500);


    error_log(
        'Erro buscar_historico_medicoes.php: ' . $e->getMessage()
    );


    echo json_encode([
        'success' => false,
        'mensagem' => 'Erro ao buscar histórico das medições.'
    ], JSON_UNESCAPED_UNICODE);


}