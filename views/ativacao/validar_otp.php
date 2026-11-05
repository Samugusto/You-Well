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

$verificacao = $_SESSION['otp_email_verificacao'] ?? null;
$criacao = is_array($verificacao) ? ($verificacao['created_at'] ?? null) : null;
$verificacaoValida = is_array($verificacao)
    && isset($verificacao['email'], $verificacao['codigo_hash'])
    && is_string($verificacao['email'])
    && is_string($verificacao['codigo_hash'])
    && is_int($criacao)
    && $criacao <= time()
    && $criacao >= time() - 900
    && hash_equals($verificacao['email'], $email)
    && hash_equals($verificacao['codigo_hash'], hash('sha256', $codigo));

if (!$verificacaoValida) {
    responder(400, ['error' => 'Código incorreto ou expirado.']);
}

unset($_SESSION['otp_email_verificacao']);
session_regenerate_id(true);
$_SESSION['otp_cadastro_autorizado'] = true;
$_SESSION['otp_cadastro_email'] = $email;
$_SESSION['otp_cadastro_verificado_em'] = time();

responder(200, ['success' => true]);
