<?php

header("Content-Type: application/json; charset=UTF-8");

require_once 'conexao.php';

$con = conectar();

$nome = $_POST['nome'] ?? '';
$senha = $_POST['senha'] ?? '';

if (empty($nome) || empty($senha)) {
    echo json_encode([
        "success" => false,
        "mensagem" => "Nome e senha são obrigatórios"
    ]);
    exit;
}

$sql = "SELECT * FROM usuarios WHERE nome = ? AND senha = ?";

$stmt = mysqli_prepare($con, $sql);

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "mensagem" => "Erro ao preparar consulta"
    ]);
    exit;
}

mysqli_stmt_bind_param($stmt, "ss", $nome, $senha);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {

    $usuario = mysqli_fetch_assoc($result);

    echo json_encode([
        "success" => true,
        "id_usuario" => $usuario["id_usuario"],
        "nome" => $usuario["nome"],
        "papel" => $usuario["papel"],
        "setor" => $usuario["setor"]
    ]);

} else {

    echo json_encode([
        "success" => false,
        "mensagem" => "Usuário ou senha inválidos"
    ]);
}

mysqli_stmt_close($stmt);
mysqli_close($con);

?>