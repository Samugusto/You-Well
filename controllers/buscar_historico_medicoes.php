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


function buscarHistorico($con, $tabela, $idSetor) {
    $sql = "SELECT valor, data_hora
            FROM $tabela
            WHERE id_setor = ?
            ORDER BY data_hora DESC
            LIMIT 24";


    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "i", $idSetor);
    mysqli_stmt_execute($stmt);


    $resultado = mysqli_stmt_get_result($stmt);
    $medicoes = [];


    while ($linha = mysqli_fetch_assoc($resultado)) {
        $medicoes[] = [
            "valor" => (float)$linha["valor"],
            "data_hora" => $linha["data_hora"]
        ];
    }


    mysqli_stmt_close($stmt);


    // Coloca as medições da mais antiga para a mais recente
    return array_reverse($medicoes);
}


echo json_encode([
    "success" => true,
    "temperatura" => buscarHistorico($con, "temperatura", $idSetor),
    "ruido" => buscarHistorico($con, "ruido", $idSetor),
    "qualidade_ar" => buscarHistorico($con, "qualidade_ar", $idSetor)
], JSON_UNESCAPED_UNICODE);


mysqli_close($con);
?>