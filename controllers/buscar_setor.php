<?php


header("Content-Type: application/json; charset=UTF-8");


require_once __DIR__ . "/../config/conexao.php";


try {


    $con = conectar();


    $idSetor = $_POST["id_setor"] ?? "";
    $idUsuario = $_POST["id_usuario"] ?? "";


    // BUSCAR TODOS OS SETORES DE UM USUÁRIO
    if ($idUsuario !== "") {


        if (!ctype_digit((string)$idUsuario) || (int)$idUsuario <= 0) {
            echo json_encode([
                "success" => false,
                "mensagem" => "ID do usuário inválido."
            ]);
            exit;
        }


        $sql = "SELECT id_setor, nome
                FROM setores
                WHERE id_usuario = ?
                ORDER BY nome ASC";


        $stmt = mysqli_prepare($con, $sql);


        mysqli_stmt_bind_param($stmt, "i", $idUsuario);
        mysqli_stmt_execute($stmt);


        $resultado = mysqli_stmt_get_result($stmt);


        $setores = [];


        while ($linha = mysqli_fetch_assoc($resultado)) {
            $setores[] = [
                "id_setor" => (int)$linha["id_setor"],
                "nome" => $linha["nome"]
            ];
        }


        echo json_encode([
            "success" => true,
            "setores" => $setores
        ], JSON_UNESCAPED_UNICODE);


        mysqli_stmt_close($stmt);


    }


    // BUSCAR UM ÚNICO SETOR
    elseif ($idSetor !== "") {


        if (!ctype_digit((string)$idSetor) || (int)$idSetor <= 0) {
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
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                "success" => false,
                "mensagem" => "Esse ID não existe na tabela setores."
            ], JSON_UNESCAPED_UNICODE);
        }


        mysqli_stmt_close($stmt);


    }


    // BUSCAR TODOS OS SETORES
    else {


        $sql = "SELECT id_setor, nome
                FROM setores
                ORDER BY nome ASC";


        $resultado = mysqli_query($con, $sql);


        if (!$resultado) {
            throw new Exception(mysqli_error($con));
        }


        $setores = [];


        while ($linha = mysqli_fetch_assoc($resultado)) {
            $setores[] = [
                "id_setor" => (int)$linha["id_setor"],
                "nome" => $linha["nome"]
            ];
        }


        echo json_encode([
            "success" => true,
            "setores" => $setores
        ], JSON_UNESCAPED_UNICODE);


    }


    mysqli_close($con);


} catch (Throwable $e) {


    http_response_code(500);


    echo json_encode([
        "success" => false,
        "mensagem" => "Erro ao consultar setores: " . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);


}
?>