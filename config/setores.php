<?php

function garantirTabelaSetores(mysqli $con): void
{
    $sql = "CREATE TABLE IF NOT EXISTS setores (id_setor INT NOT NULL AUTO_INCREMENT, id_usuario INT NULL, nome VARCHAR(255) NOT NULL, gerente VARCHAR(255) NOT NULL, criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (id_setor), UNIQUE KEY uq_setores_usuario_nome (id_usuario, nome), KEY idx_setores_usuario (id_usuario)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if (!mysqli_query($con, $sql)) throw new RuntimeException('Não foi possível preparar o catálogo de setores.');

    $sqlDispositivos = "CREATE TABLE IF NOT EXISTS dispositivos_esp32 (id_dispositivo INT NOT NULL AUTO_INCREMENT, id_setor INT NOT NULL, token_hash CHAR(64) NOT NULL, ativo TINYINT(1) NOT NULL DEFAULT 1, criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (id_dispositivo), UNIQUE KEY uq_dispositivos_token (token_hash), KEY idx_dispositivos_setor (id_setor)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if (!mysqli_query($con, $sqlDispositivos)) throw new RuntimeException('Não foi possível preparar a tabela de dispositivos.');
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
        // deixar um setor sem dispositivo. As medições ficam nas tabelas
        // temperatura, ruido e qualidade_ar, separadas pelo id_setor.
        try {
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


// Executa um comando com o id do setor; ignora tabelas que não existem.
function executarExclusaoRelacionada(mysqli $con, string $sql, int $idSetor): void
{
    try {
        $stmt = mysqli_prepare($con, $sql);
        if (!$stmt) return;
        mysqli_stmt_bind_param($stmt, 'i', $idSetor);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    } catch (mysqli_sql_exception $erro) {
        // 1146 = tabela não existe; 1054 = coluna não existe. Demais erros sobem.
        if (!in_array($erro->getCode(), [1146, 1054], true)) throw $erro;
    }
}

// Exclui o setor e tudo que depende dele (leituras, dispositivo ESP32,
// vínculo com gerentes, alertas). Somente administradores podem chamar
// (a permissão é verificada em controllers/painel.php).
function excluirSetor(mysqli $con, int $idSetor): bool
{
    if ($idSetor <= 0) return false;

    try {
        $stmt = mysqli_prepare($con, 'SELECT id_setor FROM setores WHERE id_setor = ? LIMIT 1');
        if (!$stmt) return false;
        mysqli_stmt_bind_param($stmt, 'i', $idSetor);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        $existe = mysqli_stmt_num_rows($stmt) > 0;
        mysqli_stmt_close($stmt);
        if (!$existe) return false;

        mysqli_begin_transaction($con);

        executarExclusaoRelacionada($con, 'DELETE FROM temperatura WHERE id_setor = ?', $idSetor);
        executarExclusaoRelacionada($con, 'DELETE FROM ruido WHERE id_setor = ?', $idSetor);
        executarExclusaoRelacionada($con, 'DELETE FROM qualidade_ar WHERE id_setor = ?', $idSetor);
        executarExclusaoRelacionada($con, 'DELETE FROM dispositivos_esp32 WHERE id_setor = ?', $idSetor);
        executarExclusaoRelacionada($con, 'DELETE FROM alertas WHERE id_setor = ?', $idSetor);
        executarExclusaoRelacionada($con, 'DELETE FROM alertas_controle WHERE id_setor = ?', $idSetor);
        executarExclusaoRelacionada($con, 'UPDATE usuarios SET setor = NULL WHERE setor = ?', $idSetor);

        $stmt = mysqli_prepare($con, 'DELETE FROM setores WHERE id_setor = ?');
        mysqli_stmt_bind_param($stmt, 'i', $idSetor);
        mysqli_stmt_execute($stmt);
        $linhasAfetadas = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);

        if ($linhasAfetadas <= 0) {
            mysqli_rollback($con);
            return false;
        }

        mysqli_commit($con);
    } catch (Throwable $erro) {
        try { mysqli_rollback($con); } catch (Throwable $e) {}
        error_log('Erro ao excluir setor ' . $idSetor . ': ' . $erro->getMessage());
        return false;
    }

    // Tabela de medições criada pela API do ESP32 (fora da transação: DROP faz commit implícito).
    try {
        mysqli_query($con, 'DROP TABLE IF EXISTS `setor_' . $idSetor . '`');
    } catch (Throwable $erro) {
        error_log('Erro ao remover tabela setor_' . $idSetor . ': ' . $erro->getMessage());
    }

    return true;
}
