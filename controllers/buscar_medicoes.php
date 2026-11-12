<?php


session_start();

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $idUsuario = filter_var($_SESSION['id_usuario'] ?? null, FILTER_VALIDATE_INT);
    $papel = $_SESSION['papel'] ?? null;

    if (!$idUsuario || !in_array($papel, ['admin', 'gerente'], true)) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'mensagem' => 'Sessão inválida ou expirada.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    require_once __DIR__ . '/../config/conexao.php';
    require_once __DIR__ . '/../config/setores.php';

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $con = null;
    try {
        $con = conectar();
        $setores = $papel === 'admin'
            ? listarTodosSetores($con)
            : listarSetores($con, (int) $idUsuario);

        $medicoesPorSetor = [];
        foreach ($setores as $setor) {
            $idSetor = (int) $setor['id_setor'];
            $medicoesPorSetor[$idSetor] = [
                'id_setor' => $idSetor,
                'temperatura' => null,
                'ruido' => null,
                'qualidade_ar' => null
            ];
        }

        if ($medicoesPorSetor !== []) {
            $idsSetores = implode(',', array_keys($medicoesPorSetor));
            $sensores = [
                'temperatura' => 'id_temperatura',
                'ruido' => 'id_ruido',
                'qualidade_ar' => 'id_qualidade_ar'
            ];

            foreach ($sensores as $sensor => $colunaId) {
                $sql = "
                    SELECT atual.id_setor, atual.valor
                    FROM `$sensor` AS atual
                    WHERE atual.id_setor IN ($idsSetores)
                    AND NOT EXISTS (
                        SELECT 1
                        FROM `$sensor` AS mais_nova
                        WHERE mais_nova.id_setor = atual.id_setor
                        AND (
                            mais_nova.data_hora > atual.data_hora
                            OR (
                                mais_nova.data_hora = atual.data_hora
                                AND mais_nova.`$colunaId` > atual.`$colunaId`
                            )
                        )
                    )
                ";
                $resultado = mysqli_query($con, $sql);

                while ($linha = mysqli_fetch_assoc($resultado)) {
                    $idSetor = (int) $linha['id_setor'];
                    $medicoesPorSetor[$idSetor][$sensor] = (float) $linha['valor'];
                }

                mysqli_free_result($resultado);
            }
        }

        mysqli_close($con);
        $con = null;

        echo json_encode([
            'success' => true,
            'setores' => array_values($medicoesPorSetor)
        ], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Throwable $e) {
        if ($con instanceof mysqli) {
            mysqli_close($con);
        }

        http_response_code(500);
        error_log('Erro ao atualizar medições do painel: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'mensagem' => 'Não foi possível atualizar as medições.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: GET, POST');
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'mensagem' => 'Método não permitido.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/../config/conexao.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $idSetor = $_POST['id_setor'] ?? '';

    // Validar ID do setor
    if (
        $idSetor === '' ||
        !ctype_digit((string)$idSetor) ||
        (int)$idSetor <= 0
    ) {
        echo json_encode([
            'success' => false,
            'mensagem' => 'ID do setor inválido.'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    $con = conectar();

    // Função para buscar a última medição de cada sensor
    function buscarUltimaMedicao(
        mysqli $con,
        string $tabela,
        string $idColuna,
        int $idSetor
    ): ?array {
        $sql = "
            SELECT valor, data_hora
            FROM `$tabela`
            WHERE id_setor = ?
            ORDER BY data_hora DESC, `$idColuna` DESC
            LIMIT 1
        ";

        $stmt = $con->prepare($sql);
        $stmt->bind_param("i", $idSetor);
        $stmt->execute();

        $resultado = $stmt->get_result();
        $medicao = $resultado->fetch_assoc();
        $stmt->close();

        if (!$medicao) {
            return null;
        }

        return [
            'valor' => (float)$medicao['valor'],
            'data_hora' => $medicao['data_hora']
        ];
    }

    // Buscar última medição de cada sensor
    $temperatura = buscarUltimaMedicao(
        $con,
        'temperatura',
        'id_temperatura',
        (int)$idSetor
    );

    $ruido = buscarUltimaMedicao(
        $con,
        'ruido',
        'id_ruido',
        (int)$idSetor
    );

    $qualidadeAr = buscarUltimaMedicao(
        $con,
        'qualidade_ar',
        'id_qualidade_ar',
        (int)$idSetor
    );

    // Retornar dados para o Flutter
    echo json_encode([
        'success' => true,
        'temperatura' => $temperatura,
        'ruido' => $ruido,
        'qualidade_ar' => $qualidadeAr
    ], JSON_UNESCAPED_UNICODE);

    $con->close();
} catch (Throwable $e) {
    http_response_code(500);
    error_log('Erro buscar_medicoes.php: ' . $e->getMessage());

    echo json_encode([
        'success' => false,
        'mensagem' => 'Erro interno ao buscar as medições.'
    ], JSON_UNESCAPED_UNICODE);
}