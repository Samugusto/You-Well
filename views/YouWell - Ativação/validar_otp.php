<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

function responder(int $status, array $dados): void
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

$dados = json_decode(file_get_contents('php://input'), true);
$email = filter_var(
    is_array($dados) ? ($dados['email'] ?? '') : '',
    FILTER_VALIDATE_EMAIL
);
$codigo = is_array($dados) ? ($dados['codigo'] ?? '') : '';

if (!$email || !is_string($codigo) || !preg_match('/^\d{6}$/', $codigo)) {
    responder(400, ['error' => 'E-mail ou código inválido.']);
}

require_once __DIR__ . '/../../config/conexao.php';

try {
    $con = conectar();
    mysqli_set_charset($con, 'utf8mb4');
    $con->begin_transaction();

    $stmt = mysqli_prepare(
        $con,
        'SELECT id_verificacao, codigo_hash
         FROM verificacoes_email
         WHERE email = ? AND usado = 0
           AND criado_em >= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 15 MINUTE)
         ORDER BY id_verificacao DESC
         LIMIT 1
         FOR UPDATE'
    );
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $verificacao = mysqli_fetch_assoc($resultado);
    mysqli_stmt_close($stmt);

    if (!$verificacao || !hash_equals($verificacao['codigo_hash'], hash('sha256', $codigo))) {
        $con->rollback();
        mysqli_close($con);
        responder(400, ['error' => 'Código incorreto ou expirado.']);
    }

    $idVerificacao = (int) $verificacao['id_verificacao'];
    $stmt = mysqli_prepare(
        $con,
        'UPDATE verificacoes_email SET usado = 1 WHERE id_verificacao = ? AND usado = 0'
    );
    mysqli_stmt_bind_param($stmt, 'i', $idVerificacao);
    mysqli_stmt_execute($stmt);
    $codigoConsumido = mysqli_stmt_affected_rows($stmt) === 1;
    mysqli_stmt_close($stmt);

    if (!$codigoConsumido) {
        $con->rollback();
        mysqli_close($con);
        responder(400, ['error' => 'Este código já foi utilizado.']);
    }

    $con->commit();
    mysqli_close($con);

    session_regenerate_id(true);
    $_SESSION['otp_cadastro_autorizado'] = true;
    $_SESSION['otp_cadastro_email'] = $email;
    $_SESSION['otp_cadastro_verificado_em'] = time();

    responder(200, ['success' => true]);
} catch (mysqli_sql_exception $e) {
    if (isset($con) && $con instanceof mysqli) {
        try {
            $con->rollback();
            mysqli_close($con);
        } catch (mysqli_sql_exception $erroRollback) {
            error_log('Falha ao reverter a validação OTP: ' . $erroRollback->getMessage());
        }
    }

    error_log('Falha no banco ao validar OTP: ' . $e->getMessage());
    responder(500, ['error' => 'Não foi possível validar o código agora.']);
}
