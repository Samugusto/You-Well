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

$sql = "SELECT * FROM usuarios WHERE nome = ?";

$stmt = mysqli_prepare($con, $sql);

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "mensagem" => "Erro ao preparar consulta"
    ]);
    exit;
}

mysqli_stmt_bind_param($stmt, "s", $nome);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if ($usuario = mysqli_fetch_assoc($result)) {
    if (password_verify($senha, $usuario["senha"])) {
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
} else {
    echo json_encode([
        "success" => false,
        "mensagem" => "Usuário ou senha inválidos"
    ]);
}

mysqli_stmt_close($stmt);
mysqli_close($con);

?>