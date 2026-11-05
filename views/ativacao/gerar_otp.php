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

$arquivoConfigEmail = __DIR__ . '/../../config/resend.local.php';
$configEmail = is_file($arquivoConfigEmail) ? require $arquivoConfigEmail : [];
if (!is_array($configEmail)) {
    responder(500, ['error' => 'A configuração local do serviço de e-mail é inválida.']);
}

$apiKey = getenv('RESEND_API_KEY') ?: ($configEmail['api_key'] ?? '');
$remetente = getenv('RESEND_FROM') ?: ($configEmail['from'] ?? '');
if (!$apiKey || !$remetente) {
    responder(500, ['error' => 'Configure a chave e o remetente do serviço de e-mail.']);
}

try {
    $codigo = sprintf('%06d', random_int(0, 999999));
    $codigoHash = hash('sha256', $codigo);

    $codigoHtml = htmlspecialchars($codigo, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $htmlEmail = <<<HTML
<!doctype html>
<html lang="pt-BR">
<body style="margin:0;padding:24px;background-color:#f4f7f6;font-family:Arial,sans-serif;color:#24342f">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background-color:#ffffff;border-radius:12px">
          <tr>
            <td align="center" style="padding:32px 24px 16px">
              <img src="https://youwell.netlify.app/img/Sem%20t%C3%ADtulo.png" alt="YouWell" width="88" height="88" style="display:block;width:88px;height:88px;margin:0 auto">
            </td>
          </tr>
          <tr>
            <td align="center" style="padding:8px 32px 32px">
              <h1 style="margin:0 0 16px;font-size:24px;color:#176b58">Confirme seu e-mail</h1>
              <p style="margin:0 0 20px;font-size:16px;line-height:1.5">Use este código para continuar seu cadastro no YouWell:</p>
              <p style="margin:0 0 20px;padding:16px;background-color:#f1f7f5;border-radius:8px;font-size:32px;font-weight:bold;letter-spacing:8px;color:#176b58">{$codigoHtml}</p>
              <p style="margin:0;font-size:14px;line-height:1.5;color:#65736e">O código expira em 15 minutos. Se você não solicitou esta verificação, ignore este e-mail.</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;

    $payload = [
        'from' => $remetente,
        'to' => [$email],
        'subject' => 'Confirme seu e-mail no YouWell',
        'html' => $htmlEmail
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
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_TIMEOUT => 8
    ]);

    $respostaEmail = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $erroCurl = curl_error($ch);
    curl_close($ch);

    if ($respostaEmail === false || $httpCode < 200 || $httpCode >= 300) {
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

    if (!session_start()) {
        throw new RuntimeException('Não foi possível iniciar a sessão para guardar o código.');
    }

    $_SESSION['otp_email_verificacao'] = [
        'email' => $email,
        'codigo_hash' => $codigoHash,
        'created_at' => time()
    ];
    session_write_close();

    responder(200, ['message' => 'Código enviado com sucesso.']);
} catch (RuntimeException $e) {
    error_log('Falha ao gerar OTP: ' . $e->getMessage());
    responder(500, ['error' => 'Não foi possível enviar o código de acesso.']);
}
