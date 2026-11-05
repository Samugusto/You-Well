<?php


header('Content-Type: application/json; charset=UTF-8');


require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../config/setores.php';


try {
    $con = conectar();


    $nome = trim($_POST['nome'] ?? '');
    $idGerente = $_POST['id_gerente'] ?? '';


    if ($nome === '') {
        echo json_encode([
            'success' => false,
            'message' => 'Informe o nome do setor.'
        ]);
        exit;
    }


    if (!ctype_digit((string) $idGerente) || (int) $idGerente <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Selecione um gerente válido.'
        ]);
        exit;
    }


    $resultado = criarSetor(
        $con,
        (int) $idGerente,
        $nome
    );


    if ($resultado['status'] === 'criado') {
        echo json_encode([
            'success' => true,
            'message' => 'Setor criado com sucesso!',
            'id_setor' => $resultado['id_setor']
        ], JSON_UNESCAPED_UNICODE);


    } elseif ($resultado['status'] === 'duplicado') {
        echo json_encode([
            'success' => false,
            'message' => 'Esse gerente já possui um setor com esse nome.'
        ], JSON_UNESCAPED_UNICODE);


    } elseif ($resultado['status'] === 'gerente_invalido') {
        echo json_encode([
            'success' => false,
            'message' => 'O gerente selecionado não é válido.'
        ], JSON_UNESCAPED_UNICODE);


    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Não foi possível criar o setor.'
        ], JSON_UNESCAPED_UNICODE);
    }


    mysqli_close($con);


} catch (Throwable $erro) {
    http_response_code(500);


    echo json_encode([
        'success' => false,
        'message' => 'Erro ao cadastrar o setor.'
    ], JSON_UNESCAPED_UNICODE);
}]