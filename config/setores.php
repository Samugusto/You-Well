<?php

function garantirTabelaSetores(mysqli $con): void
{
    $sql = "CREATE TABLE IF NOT EXISTS setores (id_setor INT NOT NULL AUTO_INCREMENT, id_usuario INT NULL, nome VARCHAR(255) NOT NULL, gerente VARCHAR(255) NOT NULL, criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (id_setor), UNIQUE KEY uq_setores_usuario_nome (id_usuario, nome), KEY idx_setores_usuario (id_usuario)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if (!mysqli_query($con, $sql)) throw new RuntimeException('Não foi possível preparar o catálogo de setores.');

    $sqlDispositivos = "CREATE TABLE IF NOT EXISTS dispositivos_esp32 (id_dispositivo INT NOT NULL AUTO_INCREMENT, id_setor INT NOT NULL, token_hash CHAR(64) NOT NULL, ativo TINYINT(1) NOT NULL DEFAULT 1, criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (id_dispositivo), UNIQUE KEY uq_dispositivos_token (token_hash), KEY idx_dispositivos_setor (id_setor)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if (!mysqli_query($con, $sqlDispositivos)) throw new RuntimeException('Não foi possível preparar a tabela de dispositivos.');
}

// Cria a tabela de medições usada pela API (Api/receber_dados.php).
function garantirTabelaMedicoesSetor(mysqli $con, int $idSetor): void
{
    $tabela = 'setor_' . $idSetor;
    $sql = "CREATE TABLE IF NOT EXISTS `{$tabela}` (id_medicao INT NOT NULL AUTO_INCREMENT, temperatura DECIMAL(6,2) NULL, ruido DECIMAL(6,2) NULL, qualidade_ar DECIMAL(8,2) NULL, data_hora TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (id_medicao), KEY idx_data_hora (data_hora)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if (!mysqli_query($con, $sql)) throw new RuntimeException('Não foi possível criar a tabela de medições do setor.');
}

// Gera um token para o ESP32, salva apenas o hash e devolve o token em texto puro.
function gerarTokenDispositivo(mysqli $con, int $idSetor): string
{
    $token = bin2hex(random_bytes(32));
    $hash = hash('sha256', $token);

    $stmt = mysqli_prepare($con, 'INSERT INTO dispositivos_esp32 (id_setor, token_hash, ativo) VALUES (?, ?, 1)');
    if (!$stmt) throw new RuntimeException('Falha ao preparar o cadastro do dispositivo.');
    mysqli_stmt_bind_param($stmt, 'is', $idSetor, $hash);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        throw new RuntimeException('Falha ao salvar o token do dispositivo.');
    }
    mysqli_stmt_close($stmt);

    return $token;
}


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
            SELECT id_usuario, nome
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
            INSERT INTO setores (id_usuario, nome, gerente)
            VALUES (?, ?, ?)
        ";


        $stmtInserir = mysqli_prepare($con, $sqlInserir);


        if (!$stmtInserir) {
            return ['status' => 'erro'];
        }


        mysqli_stmt_bind_param($stmtInserir, 'iss', $idGerente, $nome, $gerente['nome']);


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

        // Gera o token do ESP32. Se falhar, remove o setor para não
        // deixar um setor sem dispositivo.
        try {
            garantirTabelaMedicoesSetor($con, $idSetor);
            $token = gerarTokenDispositivo($con, $idSetor);
        } catch (Throwable $erroToken) {
            mysqli_query($con, 'DELETE FROM setores WHERE id_setor = ' . $idSetor);
            throw $erroToken;
        }


        return [
            'status' => 'criado',
            'id_setor' => $idSetor,
            'token' => $token
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
