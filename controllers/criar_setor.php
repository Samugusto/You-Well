<?php


header('Content-Type: application/json; charset=UTF-8');


try {


    require_once __DIR__ . '/../config/conexao.php';


    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);


    $con = conectar();


    $nome = trim($_POST['nome'] ?? '');
    $idUsuario = $_POST['id_gerente'] ?? '';


    // Validar nome
    if ($nome === '') {
        echo json_encode([
            'success' => false,
            'message' => 'Informe o nome do setor.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }


    // Validar ID do gerente
    if (!ctype_digit((string) $idUsuario) || (int) $idUsuario <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Selecione um gerente válido.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }


    $idUsuario = (int) $idUsuario;


    // Verificar se o usuário existe e é gerente
    $sqlGerente = "SELECT id_usuario
                   FROM usuarios
                   WHERE id_usuario = ?
                   AND papel = 'gerente'
                   LIMIT 1";


    $stmt = $con->prepare($sqlGerente);
    $stmt->bind_param("i", $idUsuario);
    $stmt->execute();


    if (!$stmt->get_result()->fetch_assoc()) {
        echo json_encode([
            'success' => false,
            'message' => 'Gerente não encontrado.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }


    $stmt->close();


    // Verificar se já existe setor com o mesmo nome
    // para esse gerente
    $sqlDuplicado = "SELECT id_setor
                     FROM setores
                     WHERE nome = ?
                     AND id_usuario = ?
                     LIMIT 1";


    $stmt = $con->prepare($sqlDuplicado);
    $stmt->bind_param("si", $nome, $idUsuario);
    $stmt->execute();


    if ($stmt->get_result()->fetch_assoc()) {
        echo json_encode([
            'success' => false,
            'message' => 'Esse gerente já possui um setor com esse nome.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }


    $stmt->close();


    // Inserir setor
    $sqlInserir = "INSERT INTO setores (nome, id_usuario)
                   VALUES (?, ?)";


    $stmt = $con->prepare($sqlInserir);
    $stmt->bind_param("si", $nome, $idUsuario);
    $stmt->execute();


    $idSetor = $con->insert_id;


    echo json_encode([
        'success' => true,
        'message' => 'Setor criado com sucesso!',
        'id_setor' => $idSetor
    ], JSON_UNESCAPED_UNICODE);


    $stmt->close();
    $con->close();


} catch (Throwable $erro) {


    error_log('Erro ao criar setor: ' . $erro->getMessage());


    http_response_code(500);


    echo json_encode([
        'success' => false,
        'message' => 'Erro interno ao cadastrar setor.'
    ], JSON_UNESCAPED_UNICODE);
}