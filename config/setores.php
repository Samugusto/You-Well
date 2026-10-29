<?php

function garantirTabelaSetores(mysqli $con): void
{
    // 1. Cria a tabela 'setores' se ainda não existir.
    // Se a tabela já existir, a cláusula IF NOT EXISTS faz o MySQL ignorar este comando sem gerar erro.
    $sql = "CREATE TABLE IF NOT EXISTS setores (
        id_setor INT NOT NULL AUTO_INCREMENT,
        id_usuario INT NULL,
        nome VARCHAR(255) NOT NULL,
        gerente VARCHAR(100) NOT NULL,
        criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id_setor),
        UNIQUE KEY uq_setores_usuario_nome (id_usuario, nome),
        KEY idx_setores_usuario (id_usuario)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if (!mysqli_query($con, $sql)) {
        throw new RuntimeException('Não foi possível preparar o catálogo de setores.');
    }

    // 2. Verifica se a coluna 'id_usuario' existe (para compatibilidade com esquemas legados)
    $colunas = mysqli_query($con, "SHOW COLUMNS FROM setores LIKE 'id_usuario'");
    if (mysqli_num_rows($colunas) === 0) {
        if (!mysqli_query($con, 'ALTER TABLE setores ADD COLUMN id_usuario INT NULL AFTER id_setor')) {
            throw new RuntimeException('Não foi possível atualizar o catálogo de setores.');
        }
    }
    mysqli_free_result($colunas);

    // 3. Migra registros antigos: preenche 'id_usuario' a partir do nome do gerente na tabela 'usuarios'
    try {
        @mysqli_query($con, 'UPDATE setores s INNER JOIN usuarios u ON u.nome = s.gerente SET s.id_usuario = u.id_usuario WHERE s.id_usuario IS NULL');
    } catch (Throwable $e) {
        // Prossegue mesmo se houver divergência em registros legados
    }

    // 4. Garante que o campo 'nome' tenha capacidade de até 255 caracteres
    $colunaNome = mysqli_query($con, "SHOW COLUMNS FROM setores LIKE 'nome'");
    $definicaoNome = mysqli_fetch_assoc($colunaNome);
    mysqli_free_result($colunaNome);
    if ($definicaoNome && strtolower($definicaoNome['Type']) !== 'varchar(255)') {
        if (!mysqli_query($con, 'ALTER TABLE setores MODIFY nome VARCHAR(255) NOT NULL')) {
            throw new RuntimeException('Não foi possível ampliar o campo de nome dos setores.');
        }
    }

    // 5. Garante que a coluna de data de criação 'criado_em' exista
    $colunaCriacao = mysqli_query($con, "SHOW COLUMNS FROM setores LIKE 'criado_em'");
    if (mysqli_num_rows($colunaCriacao) === 0) {
        if (!mysqli_query($con, 'ALTER TABLE setores ADD COLUMN criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP')) {
            throw new RuntimeException('Não foi possível adicionar a data de criação dos setores.');
        }
    }
    mysqli_free_result($colunaCriacao);

    // 6. Garante a existência do índice para consultas por usuário ('idx_setores_usuario')
    $indiceUsuario = mysqli_query($con, "SHOW INDEX FROM setores WHERE Key_name = 'idx_setores_usuario'");
    if (mysqli_num_rows($indiceUsuario) === 0) {
        if (!mysqli_query($con, 'ALTER TABLE setores ADD KEY idx_setores_usuario (id_usuario)')) {
            throw new RuntimeException('Não foi possível preparar a consulta dos setores.');
        }
    }
    mysqli_free_result($indiceUsuario);

    // 7. Garante a restrição de unicidade (uq_setores_usuario_nome):
    // Impede que um mesmo usuário crie dois setores com o mesmo nome.
    $indiceUnico = mysqli_query($con, "SHOW INDEX FROM setores WHERE Key_name = 'uq_setores_usuario_nome'");
    $temIndiceUnico = mysqli_num_rows($indiceUnico) > 0;
    mysqli_free_result($indiceUnico);
    if (!$temIndiceUnico) {
        // Antes de aplicar o índice único, verifica se já existem nomes duplicados para evitar falha
        $duplicados = mysqli_query($con, 'SELECT id_usuario, nome FROM setores WHERE id_usuario IS NOT NULL GROUP BY id_usuario, nome HAVING COUNT(*) > 1 LIMIT 1');
        $existemDuplicados = mysqli_num_rows($duplicados) > 0;
        mysqli_free_result($duplicados);
        if (!$existemDuplicados && !mysqli_query($con, 'ALTER TABLE setores ADD UNIQUE KEY uq_setores_usuario_nome (id_usuario, nome)')) {
            throw new RuntimeException('Não foi possível proteger os nomes dos setores contra duplicidade.');
        }
    }
}

function criarSetor(mysqli $con, int $idUsuario, string $nome): string
{
    try {
        // 1. Verifica preventivamente se já existe um setor com o mesmo nome para este usuário
        $sqlDuplicado = "SELECT id_setor FROM setores WHERE id_usuario = ? AND nome = ? LIMIT 1";
        $stmtDuplicado = mysqli_prepare($con, $sqlDuplicado);
        if (!$stmtDuplicado) {
            return 'erro';
        }

        mysqli_stmt_bind_param($stmtDuplicado, 'is', $idUsuario, $nome);
        if (!mysqli_stmt_execute($stmtDuplicado)) {
            mysqli_stmt_close($stmtDuplicado);
            return 'erro';
        }
        mysqli_stmt_store_result($stmtDuplicado);
        $duplicado = mysqli_stmt_num_rows($stmtDuplicado) > 0;
        mysqli_stmt_close($stmtDuplicado);

        if ($duplicado) {
            return 'duplicado';
        }

        // 2. Busca o nome do usuário para preencher o campo legado 'gerente'
        $sqlGerente = 'SELECT nome FROM usuarios WHERE id_usuario = ? LIMIT 1';
        $stmtGerente = mysqli_prepare($con, $sqlGerente);
        if (!$stmtGerente) {
            return 'erro';
        }
        mysqli_stmt_bind_param($stmtGerente, 'i', $idUsuario);
        if (!mysqli_stmt_execute($stmtGerente)) {
            mysqli_stmt_close($stmtGerente);
            return 'erro';
        }
        $resultadoGerente = mysqli_stmt_get_result($stmtGerente);
        $gerente = mysqli_fetch_assoc($resultadoGerente);
        mysqli_stmt_close($stmtGerente);
        if (!$gerente) {
            return 'erro';
        }

        $nomeGerente = $gerente['nome'];

        // 3. Insere o novo setor no banco de dados
        $sqlInserir = "INSERT INTO setores (id_usuario, nome, gerente) VALUES (?, ?, ?)";
        $stmtInserir = mysqli_prepare($con, $sqlInserir);
        if (!$stmtInserir) {
            return 'erro';
        }

        mysqli_stmt_bind_param($stmtInserir, 'iss', $idUsuario, $nome, $nomeGerente);
        if (!mysqli_stmt_execute($stmtInserir)) {
            $codigoErro = mysqli_stmt_errno($stmtInserir);
            mysqli_stmt_close($stmtInserir);
            // Código de erro 1062 = violação de chave única (registro duplicado)
            return $codigoErro === 1062 ? 'duplicado' : 'erro';
        }

        // Recupera o ID auto-incremental gerado para o novo setor
        $idSetor = (int) mysqli_insert_id($con);
        mysqli_stmt_close($stmtInserir);

        if ($idSetor <= 0) {
            return 'erro';
        }

        // 4. Cria a tabela individual de leituras de sensores para este setor: 'setor_{id_setor}'
        $tabelaLeituras = 'setor_' . $idSetor;
        $sqlCriarTabela = "CREATE TABLE IF NOT EXISTS `{$tabelaLeituras}` (
            id_leitura BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            temperatura DECIMAL(10,2) NULL,
            ruido DECIMAL(10,2) NULL,
            qualidade_ar DECIMAL(10,2) NULL,
            data_hora TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id_leitura),
            KEY idx_data_hora (data_hora)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        try {
            $tabelaCriada = mysqli_query($con, $sqlCriarTabela);
        } catch (mysqli_sql_exception $erro) {
            $tabelaCriada = false;
        }

        // 5. Caso a criação da tabela de leituras falhe, remove o setor criado para evitar inconsistência (rollback)
        if (!$tabelaCriada) {
            $sqlRemover = "DELETE FROM setores WHERE id_setor = ? AND id_usuario = ?";
            $stmtRemover = mysqli_prepare($con, $sqlRemover);
            if ($stmtRemover) {
                mysqli_stmt_bind_param($stmtRemover, 'ii', $idSetor, $idUsuario);
                mysqli_stmt_execute($stmtRemover);
                mysqli_stmt_close($stmtRemover);
            }
            return 'erro';
        }

        return 'criado';
    } catch (mysqli_sql_exception $erro) {
        return 'erro';
    }
}

function listarSetores(mysqli $con, int $idUsuario): array
{
    // 1. Busca os setores cadastrados para o usuário, ordenados por nome
    $sql = "SELECT id_setor, nome FROM setores WHERE id_usuario = ? ORDER BY nome";
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

    // 2. Itera sobre cada setor para buscar os últimos dados dos sensores
    while ($setor = mysqli_fetch_assoc($resultado)) {
        // Inicializa as medições como nulas caso o setor ainda não tenha leituras
        $setor['temperatura'] = null;
        $setor['ruido'] = null;
        $setor['qualidade_ar'] = null;

        $idSetor = (int) $setor['id_setor'];
        $tabelaLeituras = 'setor_' . $idSetor;

        // Consulta a leitura mais recente na tabela específica do setor ('setor_{id}')
        $sqlUltimaLeitura = "SELECT temperatura, ruido, qualidade_ar
            FROM `{$tabelaLeituras}` ORDER BY id_leitura DESC LIMIT 1";
        try {
            $resultadoLeitura = mysqli_query($con, $sqlUltimaLeitura);
            if ($resultadoLeitura) {
                $leitura = mysqli_fetch_assoc($resultadoLeitura);
                if ($leitura) {
                    $setor['temperatura'] = $leitura['temperatura'];
                    $setor['ruido'] = $leitura['ruido'];
                    $setor['qualidade_ar'] = $leitura['qualidade_ar'];
                }
                mysqli_free_result($resultadoLeitura);
            }
        } catch (Throwable $erro) {
            // Se a tabela do setor não existir ainda, tenta criá-la e não trava a listagem
            $sqlCriarTabela = "CREATE TABLE IF NOT EXISTS `{$tabelaLeituras}` (
                id_leitura BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                temperatura DECIMAL(10,2) NULL,
                ruido DECIMAL(10,2) NULL,
                qualidade_ar DECIMAL(10,2) NULL,
                data_hora TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id_leitura),
                KEY idx_data_hora (data_hora)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            try {
                mysqli_query($con, $sqlCriarTabela);
            } catch (Throwable $e) {
                // Silencia para garantir que a página continue funcionando
            }
        }

        $setores[] = $setor;
    }

    mysqli_stmt_close($stmt);
    return $setores;
}

function excluirSetor(mysqli $con, int $idUsuario, int $idSetor): bool
{
    // 1. Remove o setor pertencente ao usuário
    $sql = "DELETE FROM setores WHERE id_setor = ? AND (id_usuario = ? OR (id_usuario IS NULL AND gerente = (SELECT nome FROM usuarios WHERE id_usuario = ? LIMIT 1)))";
    $stmt = mysqli_prepare($con, $sql);
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, 'iii', $idSetor, $idUsuario, $idUsuario);
    $executou = mysqli_stmt_execute($stmt);
    $linhasAfetadas = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);

    if ($executou && $linhasAfetadas > 0) {
        // 2. Remove a tabela individual de leituras associada a esse setor (se existir)
        $tabelaLeituras = 'setor_' . $idSetor;
        try {
            mysqli_query($con, "DROP TABLE IF EXISTS `{$tabelaLeituras}`");
        } catch (Throwable $e) {
            // Silencia caso não consiga remover a tabela auxiliar
        }
        return true;
    }

    return false;
}
