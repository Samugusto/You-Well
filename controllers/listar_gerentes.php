<?php


header('Content-Type: application/json; charset=UTF-8');


require_once __DIR__ . '/../config/conexao.php';


try {
    $con = conectar();


    $sql = "SELECT id_usuario, nome
            FROM usuarios
            WHERE papel = 'gerente'
            ORDER BY nome ASC";


    $resultado = mysqli_query($con, $sql);


    if (!$resultado) {
        throw new RuntimeException('Erro ao consultar os gerentes.');
    }


    $gerentes = [];


    while ($gerente = mysqli_fetch_assoc($resultado)) {
        $gerentes[] = [
            'id_usuario' => (int) $gerente['id_usuario'],
            'nome' => $gerente['nome']
        ];
    }


    echo json_encode([
        'success' => true,
        'gerentes' => $gerentes
    ], JSON_UNESCAPED_UNICODE);


    mysqli_free_result($resultado);
    mysqli_close($con);


} catch (Throwable $erro) {
    http_response_code(500);


    echo json_encode([
        'success' => false,
        'message' => 'Não foi possível carregar os gerentes.'
    ], JSON_UNESCAPED_UNICODE);
}