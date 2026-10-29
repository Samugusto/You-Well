<?php
header("Content-Type: application/json; charset=UTF-8");


require_once __DIR__ . "/../config/conexao.php";


$con = conectar();


$idSetor = $_POST["id_setor"] ?? "";


if ($idSetor === "" || !ctype_digit((string)$idSetor)) {
    echo json_encode([
        "success" => false,
        "mensagem" => "ID do setor inválido."
    ]);
    exit;
}


$sql = "SELECT id_setor, nome
        FROM setores
        WHERE id_setor = ?";


$stmt = mysqli_prepare($con, $sql);
mysqli_stmt_bind_param($stmt, "i", $idSetor);
mysqli_stmt_execute($stmt);


$resultado = mysqli_stmt_get_result($stmt);
$setor = mysqli_fetch_assoc($resultado);


if ($setor) {
    echo json_encode([
        "success" => true,
        "id_setor" => (int)$setor["id_setor"],
        "nome_setor" => $setor["nome"]
    ]);
} else {
    echo json_encode([
        "success" => false,
        "mensagem" => "Esse ID não existe na tabela setores."
    ]);
}


mysqli_stmt_close($stmt);
mysqli_close($con);
?>