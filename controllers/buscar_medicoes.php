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


function buscarUltimaMedicao($con, $tabela, $idSetor) {
    // O nome da tabela vem de uma lista fixa abaixo.
    $sql = "SELECT valor, data_hora
            FROM $tabela
            WHERE id_setor = ?
            ORDER BY data_hora DESC
            LIMIT 1";


    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "i", $idSetor);
    mysqli_stmt_execute($stmt);


    $resultado = mysqli_stmt_get_result($stmt);
    $medicao = mysqli_fetch_assoc($resultado);


    mysqli_stmt_close($stmt);


    if (!$medicao) {
        return null;
    }


    return [
        "valor" => (float)$medicao["valor"],
        "data_hora" => $medicao["data_hora"]
    ];
}


echo json_encode([
    "success" => true,
    "temperatura" => buscarUltimaMedicao($con, "temperatura", $idSetor),
    "ruido" => buscarUltimaMedicao($con, "ruido", $idSetor),
    "qualidade_ar" => buscarUltimaMedicao($con, "qualidade_ar", $idSetor)
], JSON_UNESCAPED_UNICODE);


mysqli_close($con);
?>