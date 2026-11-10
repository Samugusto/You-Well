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


    // Função para buscar a última medição de cada sensor
    function buscarUltimaMedicao(
        mysqli $con,
        string $tabela,
        string $idColuna,
        int $idSetor
    ): ?array {


        $sql = "
            SELECT valor, data_hora
            FROM `$tabela`
            WHERE id_setor = ?
            ORDER BY data_hora DESC, `$idColuna` DESC
            LIMIT 1
        ";


        $stmt = $con->prepare($sql);


        $stmt->bind_param("i", $idSetor);


        $stmt->execute();


        $resultado = $stmt->get_result();


        $medicao = $resultado->fetch_assoc();


        $stmt->close();


        if (!$medicao) {
            return null;
        }


        return [
            'valor' => (float)$medicao['valor'],
            'data_hora' => $medicao['data_hora']
        ];
    }


    // Buscar última medição de cada sensor
    $temperatura = buscarUltimaMedicao(
        $con,
        'temperatura',
        'id_temperatura',
        (int)$idSetor
    );


    $ruido = buscarUltimaMedicao(
        $con,
        'ruido',
        'id_ruido',
        (int)$idSetor
    );


    $qualidadeAr = buscarUltimaMedicao(
        $con,
        'qualidade_ar',
        'id_qualidade_ar',
        (int)$idSetor
    );


    // Retornar dados para o Flutter
    echo json_encode([
        'success' => true,
        'temperatura' => $temperatura,
        'ruido' => $ruido,
        'qualidade_ar' => $qualidadeAr
    ], JSON_UNESCAPED_UNICODE);


    $con->close();


} catch (Throwable $e) {


    http_response_code(500);


    error_log('Erro buscar_medicoes.php: ' . $e->getMessage());


    echo json_encode([
        'success' => false,
        'mensagem' => 'Erro interno ao buscar as medições.'
    ], JSON_UNESCAPED_UNICODE);


}