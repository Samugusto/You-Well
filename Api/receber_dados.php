<?php
header('Content-Type: application/json; charset=UTF-8');

function responderApi(int $status, array $dados): void
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    responderApi(405, ['success' => false, 'mensagem' => 'Método não permitido.']);
}

$cabecalho = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (!preg_match('/^Bearer\s+([^\s]+)$/i', $cabecalho, $partes)) {
    responderApi(401, ['success' => false, 'mensagem' => 'Token não informado.']);
}

$token = $partes[1];
if (!preg_match('/^[a-f0-9]{64}$/i', $token)) {
    responderApi(401, ['success' => false, 'mensagem' => 'Token inválido.']);
}

$dados = json_decode(file_get_contents('php://input'), true);
if (!is_array($dados)) {
    responderApi(400, ['success' => false, 'mensagem' => 'JSON inválido.']);
}

$campos = ['temperatura', 'qualidade_ar', 'ruido'];
foreach ($campos as $campo) {
    if (!array_key_exists($campo, $dados) || !is_numeric($dados[$campo]) || !is_finite((float) $dados[$campo])) {
        responderApi(422, ['success' => false, 'mensagem' => 'Medição inválida.']);
    }
}

$temperatura = (float) $dados['temperatura'];
$qualidadeAr = (float) $dados['qualidade_ar'];
$ruido = (float) $dados['ruido'];
if ($temperatura < -100 || $temperatura > 150 || $qualidadeAr < 0 || $qualidadeAr > 100000 || $ruido < 0 || $ruido > 200) {
    responderApi(422, ['success' => false, 'mensagem' => 'Medição fora do intervalo permitido.']);
}

require_once __DIR__ . '/../config/conexao.php';

try {
    $con = conectar();
    $hash = hash('sha256', $token);
    $stmt = mysqli_prepare($con, 'SELECT id_setor FROM dispositivos_esp32 WHERE token_hash = ? AND ativo = 1 LIMIT 1');
    if (!$stmt) {
        throw new RuntimeException('Falha ao preparar autenticação.');
    }
    mysqli_stmt_bind_param($stmt, 's', $hash);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $dispositivo = mysqli_fetch_assoc($resultado);
    mysqli_stmt_close($stmt);

    if (!$dispositivo) {
        mysqli_close($con);
        responderApi(401, ['success' => false, 'mensagem' => 'Token inválido ou dispositivo inativo.']);
    }

    $idSetor = (int) $dispositivo['id_setor'];
    if ($idSetor <= 0) {
        mysqli_close($con);
        responderApi(403, ['success' => false, 'mensagem' => 'Dispositivo sem setor válido.']);
    }

    $tabela = 'setor_' . $idSetor;
    $sql = "INSERT INTO `{$tabela}` (temperatura, ruido, qualidade_ar) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($con, $sql);
    if (!$stmt) {
        throw new RuntimeException('Falha ao preparar o armazenamento.');
    }
    mysqli_stmt_bind_param($stmt, 'ddd', $temperatura, $ruido, $qualidadeAr);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        throw new RuntimeException('Falha ao armazenar medição.');
    }
    mysqli_stmt_close($stmt);
    mysqli_close($con);
    responderApi(201, ['success' => true, 'mensagem' => 'Medição recebida.']);
} catch (Throwable $e) {
    if (isset($con) && $con instanceof mysqli) {
        mysqli_close($con);
    }
    error_log('Falha na API de medições: ' . $e->getMessage());
    responderApi(500, ['success' => false, 'mensagem' => 'Não foi possível processar a medição.']);
}
