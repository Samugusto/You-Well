<?php
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

if (!$email) {
    responder(400, ['error' => 'E-mail inválido']);
}

$arquivoConfigEmail = __DIR__ . '/config/resend.local.php';
$configEmail = is_file($arquivoConfigEmail) ? require $arquivoConfigEmail : [];
if (!is_array($configEmail)) {
    responder(500, ['error' => 'A configuração local do serviço de e-mail é inválida.']);
}

$apiKey = getenv('RESEND_API_KEY') ?: ($configEmail['api_key'] ?? '');
$remetente = getenv('RESEND_FROM') ?: ($configEmail['from'] ?? '');
if (!$apiKey || !$remetente) {
    responder(500, ['error' => 'Configure a chave e o remetente em config/resend.local.php.']);
}

require_once __DIR__ . '/config/conexao.php';

try {
    $con = conectar();
    mysqli_set_charset($con, 'utf8mb4');

    mysqli_query(
        $con,
        "CREATE TABLE IF NOT EXISTS verificacoes_email (
            id_verificacao BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            email VARCHAR(254) NOT NULL,
            codigo_hash CHAR(64) NOT NULL,
            usado TINYINT(1) NOT NULL DEFAULT 0,
            criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id_verificacao),
            KEY idx_verificacoes_email_email_usado (email, usado)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $codigo = sprintf('%06d', random_int(0, 999999));
    $codigoHash = hash('sha256', $codigo);

    $con->begin_transaction();

    $stmt = mysqli_prepare(
        $con,
        'UPDATE verificacoes_email SET usado = 1 WHERE email = ? AND usado = 0'
    );
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare(
        $con,
        'INSERT INTO verificacoes_email (email, codigo_hash) VALUES (?, ?)'
    );
    mysqli_stmt_bind_param($stmt, 'ss', $email, $codigoHash);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $payload = [
        'from' => $remetente,
        'to' => [$email],
        'subject' => "Seu código de acesso: $codigo",
        'html' => "<h3>Seu código de acesso</h3><p>Use o código a seguir para acessar a sua conta:</p><h2>$codigo</h2>"
    ];

    $ch = curl_init('https://api.resend.com/emails');
    if ($ch === false) {
        throw new RuntimeException('Não foi possível iniciar o envio do e-mail.');
    }

    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20
    ]);

    $respostaEmail = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $erroCurl = curl_error($ch);
    curl_close($ch);

    if ($respostaEmail === false || $httpCode < 200 || $httpCode >= 300) {
        $con->rollback();
        mysqli_close($con);

        $erroResend = is_string($respostaEmail)
            ? json_decode($respostaEmail, true)
            : null;
        $mensagemResend = is_array($erroResend) && isset($erroResend['message'])
            && is_string($erroResend['message'])
                ? trim($erroResend['message'])
                : '';
        $detalheErro = $erroCurl
            ?: ($mensagemResend ?: 'Resend retornou HTTP ' . $httpCode);

        error_log('Falha ao enviar OTP pelo Resend: ' . $detalheErro);
        responder(502, ['error' => 'Não foi possível enviar o código por e-mail: ' . $detalheErro]);
    }

    $con->commit();
    mysqli_close($con);

    responder(200, ['message' => 'Código enviado com sucesso.']);
} catch (mysqli_sql_exception $e) {
    if (isset($con) && $con instanceof mysqli) {
        try {
            $con->rollback();
            mysqli_close($con);
        } catch (mysqli_sql_exception $erroRollback) {
            error_log('Falha ao reverter a transação OTP: ' . $erroRollback->getMessage());
        }
    }

    error_log('Falha no banco de dados ao gerar OTP: ' . $e->getMessage());
    responder(500, ['error' => 'Não foi possível registrar o código no banco de dados.']);
} catch (RuntimeException $e) {
    if (isset($con) && $con instanceof mysqli) {
        $con->rollback();
        mysqli_close($con);
    }

    error_log('Falha ao gerar OTP: ' . $e->getMessage());
    responder(500, ['error' => 'Não foi possível enviar o código de acesso.']);
}
