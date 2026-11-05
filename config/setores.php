<?php


// ======================================================
// CADASTRAR SETOR
// ======================================================


function criarSetor(mysqli $con, int $idGerente, string $nome): array
{
    try {
        $nome = trim($nome);


        if ($idGerente <= 0 || $nome === '') {
            return ['status' => 'erro'];
        }


        // Verifica se o usuário selecionado existe e é gerente.
        $sqlGerente = "
            SELECT id_usuario
            FROM usuarios
            WHERE id_usuario = ? AND papel = 'gerente'
            LIMIT 1
        ";


        $stmtGerente = mysqli_prepare($con, $sqlGerente);


        if (!$stmtGerente) {
            return ['status' => 'erro'];
        }


        mysqli_stmt_bind_param($stmtGerente, 'i', $idGerente);
        mysqli_stmt_execute($stmtGerente);


        $resultado = mysqli_stmt_get_result($stmtGerente);
        $gerente = mysqli_fetch_assoc($resultado);


        mysqli_stmt_close($stmtGerente);


        if (!$gerente) {
            return ['status' => 'gerente_invalido'];
        }


        // Verifica se já existe um setor com esse nome
        // vinculado ao mesmo gerente.
        $sqlDuplicado = "
            SELECT id_setor
            FROM setores
            WHERE id_usuario = ? AND nome = ?
            LIMIT 1
        ";


        $stmtDuplicado = mysqli_prepare($con, $sqlDuplicado);


        if (!$stmtDuplicado) {
            return ['status' => 'erro'];
        }


        mysqli_stmt_bind_param($stmtDuplicado, 'is', $idGerente, $nome);
        mysqli_stmt_execute($stmtDuplicado);


        $resultado = mysqli_stmt_get_result($stmtDuplicado);
        $duplicado = mysqli_fetch_assoc($resultado);


        mysqli_stmt_close($stmtDuplicado);


        if ($duplicado) {
            return ['status' => 'duplicado'];
        }


        // Insere o setor utilizando o ID do gerente.
        $sqlInserir = "
            INSERT INTO setores (id_usuario, nome)
            VALUES (?, ?)
        ";


        $stmtInserir = mysqli_prepare($con, $sqlInserir);


        if (!$stmtInserir) {
            return ['status' => 'erro'];
        }


        mysqli_stmt_bind_param($stmtInserir, 'is', $idGerente, $nome);


        if (!mysqli_stmt_execute($stmtInserir)) {
            $codigoErro = mysqli_stmt_errno($stmtInserir);
            mysqli_stmt_close($stmtInserir);


            return [
                'status' => $codigoErro === 1062
                    ? 'duplicado'
                    : 'erro'
            ];
        }


        $idSetor = (int) mysqli_insert_id($con);


        mysqli_stmt_close($stmtInserir);


        return [
            'status' => 'criado',
            'id_setor' => $idSetor
        ];


    } catch (Throwable $erro) {
        error_log('Erro ao criar setor: ' . $erro->getMessage());


        return ['status' => 'erro'];
    }
}




// ======================================================
// LISTAR SETORES DE UM USUÁRIO
// ======================================================


function listarSetores(mysqli $con, int $idUsuario): array
{
    $sql = "
        SELECT
            s.id_setor,
            s.nome,
            s.id_usuario,
            u.nome AS responsavel
        FROM setores s
        LEFT JOIN usuarios u ON u.id_usuario = s.id_usuario
        WHERE s.id_usuario = ?
        ORDER BY s.nome
    ";


    $stmt = mysqli_prepare($con, $sql);


    if (!$stmt) {
        throw new RuntimeException('Não foi possível consultar os setores.');
    }


    mysqli_stmt_bind_param($stmt, 'i', $idUsuario);


    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        throw new RuntimeException('Não foi possível consultar os setores.');
    }


    $resultado = mysqli_stmt_get_result($stmt);
    $setores = [];


    while ($setor = mysqli_fetch_assoc($resultado)) {
        $setores[] = $setor;
    }


    mysqli_stmt_close($stmt);


    return $setores;
}




// ======================================================
// LISTAR TODOS OS SETORES
// ======================================================


function listarTodosSetores(mysqli $con): array
{
    $sql = "
        SELECT
            s.id_setor,
            s.nome,
            s.id_usuario,
            u.nome AS responsavel
        FROM setores s
        LEFT JOIN usuarios u ON u.id_usuario = s.id_usuario
        ORDER BY s.nome
    ";


    $resultado = mysqli_query($con, $sql);


    if (!$resultado) {
        return [];
    }


    $setores = [];


    while ($setor = mysqli_fetch_assoc($resultado)) {
        $setores[] = $setor;
    }


    mysqli_free_result($resultado);


    return $setores;
}




// ======================================================
// EXCLUIR SETOR
// ======================================================


function excluirSetor(mysqli $con, int $idUsuario, int $idSetor): bool
{
    $sql = "
        DELETE FROM setores
        WHERE id_setor = ? AND id_usuario = ?
    ";


    $stmt = mysqli_prepare($con, $sql);


    if (!$stmt) {
        return false;
    }


    mysqli_stmt_bind_param($stmt, 'ii', $idSetor, $idUsuario);


    $executou = mysqli_stmt_execute($stmt);
    $linhasAfetadas = mysqli_stmt_affected_rows($stmt);


    mysqli_stmt_close($stmt);


    return $executou && $linhasAfetadas > 0;
}