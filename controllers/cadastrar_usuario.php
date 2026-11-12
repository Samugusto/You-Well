<?php
// Endpoint JSON usado pelo app Flutter (novo_usuario.dart) para cadastrar
// gerentes e administradores. O app não usa sessão/cookies, então quem está
// cadastrando se identifica com o nome e a senha do próprio administrador.

// Deixe true enquanto estiver testando: a resposta inclui o motivo técnico
// do erro ("detalhe"). Depois que tudo funcionar, troque para false.
const DEBUG_CADASTRO = true;

ob_start(); // evita que qualquer texto solto estrague o JSON
$respondido = false;

function responder(int $status, bool $success, string $mensagem, ?string $detalhe = null): void
{
    global $respondido;
    $respondido = true;

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');

    $corpo = ['success' => $success, 'mensagem' => $mensagem];
    if (DEBUG_CADASTRO && $detalhe !== null) {
        $corpo['detalhe'] = $detalhe;
    }

    echo json_encode($corpo, JSON_UNESCAPED_UNICODE);
    exit;
}

// Exceção não tratada (ex.: erro de SQL) vira uma resposta JSON legível.
set_exception_handler(function (Throwable $e) {
    $detalhe = get_class($e) . ': ' . $e->getMessage()
        . ' em ' . basename($e->getFile()) . ':' . $e->getLine();
    error_log('cadastrar_usuario.php: ' . $detalhe);
    responder(500, false, 'Erro interno no servidor.', $detalhe);
});

// Erro fatal ou script encerrado antes de responder também vira JSON.
register_shutdown_function(function () {
    global $respondido;
    if ($respondido) {
        return;
    }

    $erro = error_get_last();
    $detalhe = $erro
        ? $erro['message'] . ' em ' . basename($erro['file']) . ':' . $erro['line']
        : 'O script terminou sem enviar resposta (algum arquivo incluído encerrou a execução?).';

    error_log('cadastrar_usuario.php: ' . $detalhe);
    responder(500, false, 'Erro interno no servidor.', $detalhe);
});

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(405, false, 'Método não permitido.');
}

require_once __DIR__ . "/../config/conexao.php";

$adminNome  = trim($_POST['admin_nome'] ?? '');
$adminSenha = $_POST['admin_senha'] ?? '';

$nome      = trim($_POST['nome'] ?? '');
$senha     = $_POST['senha'] ?? '';
$confirmar = $_POST['confirmar_senha'] ?? '';
$papel     = trim($_POST['papel'] ?? '');

if ($adminNome === '' || $adminSenha === '') {
    responder(401, false, 'Informe a senha do administrador para continuar.');
}

$con = conectar();

// 1) Confirma que quem está cadastrando é um administrador de verdade.
$stmtAdmin = mysqli_prepare($con, "SELECT senha, papel FROM usuarios WHERE nome = ?");
mysqli_stmt_bind_param($stmtAdmin, "s", $adminNome);
mysqli_stmt_execute($stmtAdmin);
mysqli_stmt_bind_result($stmtAdmin, $hashAdmin, $papelAdmin);
$achouAdmin = mysqli_stmt_fetch($stmtAdmin);
mysqli_stmt_close($stmtAdmin);

if (!$achouAdmin || !password_verify($adminSenha, $hashAdmin) || $papelAdmin !== 'admin') {
    mysqli_close($con);
    responder(401, false, 'Senha de administrador incorreta ou sem permissão.');
}

// 2) Validações do novo usuário (as mesmas dos formulários web).
if ($nome === '' || $senha === '' || $confirmar === '') {
    mysqli_close($con);
    responder(400, false, 'Preencha o nome e a senha.');
}

if (!in_array($papel, ['gerente', 'admin'], true)) {
    mysqli_close($con);
    responder(400, false, 'Papel de usuário inválido.');
}

if ($senha !== $confirmar) {
    mysqli_close($con);
    responder(400, false, 'As senhas não coincidem.');
}

if (strlen($senha) < 8) {
    mysqli_close($con);
    responder(400, false, 'A senha deve ter pelo menos 8 caracteres.');
}

if (!preg_match('/[A-Z]/', $senha)) {
    mysqli_close($con);
    responder(400, false, 'A senha deve conter pelo menos uma letra maiúscula.');
}

if (!preg_match('/[@#\$%\^&\*\(\)_\+\-=\[\]\{\};":\\|,.<>\/?]/', $senha)) {
    mysqli_close($con);
    responder(400, false, 'A senha deve conter pelo menos um caractere especial.');
}

// 3) Nome de usuário já existe?
$stmtCheck = mysqli_prepare($con, "SELECT id_usuario FROM usuarios WHERE nome = ?");
mysqli_stmt_bind_param($stmtCheck, "s", $nome);
mysqli_stmt_execute($stmtCheck);
mysqli_stmt_store_result($stmtCheck);
$jaExiste = mysqli_stmt_num_rows($stmtCheck) > 0;
mysqli_stmt_close($stmtCheck);

if ($jaExiste) {
    mysqli_close($con);
    responder(409, false, 'Este nome de usuário já está cadastrado.');
}

// 4) Insere o usuário. A ligação do gerente com os setores NÃO é feita aqui:
// ela fica em setores.id_usuario e é definida na tela "Criar Setor" do app.
// Por isso usuarios.setor fica NULL (administrador também acessa todos os setores).
$senhaHash = password_hash($senha, PASSWORD_DEFAULT);

$stmtInsert = mysqli_prepare(
    $con,
    "INSERT INTO usuarios (nome, senha, papel, setor, foto_perfil) VALUES (?, ?, ?, NULL, NULL)"
);
mysqli_stmt_bind_param($stmtInsert, "sss", $nome, $senhaHash, $papel);

$ok = mysqli_stmt_execute($stmtInsert);
$erroBanco = mysqli_error($con);
mysqli_stmt_close($stmtInsert);
mysqli_close($con);

if (!$ok) {
    error_log("cadastrar_usuario.php: " . $erroBanco);
    responder(500, false, 'Erro ao cadastrar o usuário. Tente novamente.', $erroBanco);
}

responder(200, true, 'Usuário criado com sucesso!');