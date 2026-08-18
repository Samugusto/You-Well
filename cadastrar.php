<?php
session_start();
require_once "conexao.php";

$erro = "";
$sucesso = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = trim($_POST["nome"]);
    $senha = $_POST["senha"];
    $confirmar_senha = $_POST["confirmar_senha"];
    $setor = trim($_POST["setor"]);

    // Validações
    if (empty($nome) || empty($senha) || empty($confirmar_senha) || empty($setor)) {
        $erro = "Todos os campos são obrigatórios.";
    } elseif ($senha !== $confirmar_senha) {
        $erro = "As senhas não coincidem.";
    } elseif (strlen($senha) < 8) {
        $erro = "A senha deve ter pelo menos 8 caracteres.";
    } elseif (!preg_match('/[A-Z]/', $senha)) {
        $erro = "A senha deve conter pelo menos uma letra maiúscula.";
    } elseif (!preg_match('/[@#\$%\^&\*\(\)_\+\-=\[\]\{\};":\\|,.<>\/?]/', $senha)) {
        $erro = "A senha deve conter pelo menos um caractere especial.";
    } else {
        $con = conectar();

        // Verificar se usuário já existe
        $sql_check = "SELECT id_usuario FROM usuarios WHERE nome = ?";
        $stmt_check = mysqli_prepare($con, $sql_check);
        mysqli_stmt_bind_param($stmt_check, "s", $nome);
        mysqli_stmt_execute($stmt_check);
        mysqli_stmt_store_result($stmt_check);

        if (mysqli_stmt_num_rows($stmt_check) > 0) {
            $erro = "Este nome de usuário já está cadastrado.";
        } else {
            // Hash da senha
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

            // Inserir novo usuário
            $sql_insert = "INSERT INTO usuarios (nome, senha, papel, setor) VALUES (?, ?, ?, 'funcionario')";
            $stmt_insert = mysqli_prepare($con, $sql_insert);
            mysqli_stmt_bind_param($stmt_insert, "sss", $nome, $senha_hash, $setor);

            if (mysqli_stmt_execute($stmt_insert)) {
                $sucesso = "Cadastro realizado com sucesso! Redirecionando para o login...";
                echo '<meta http-equiv="refresh" content="2;url=entrar.php">';
            } else {
                $erro = "Erro ao cadastrar: " . mysqli_error($con);
            }
            mysqli_stmt_close($stmt_insert);
        }
        mysqli_stmt_close($stmt_check);
        mysqli_close($con);
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YouWell - Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600&display=swap" rel="stylesheet">
    <link href="styleLogin.css" rel="stylesheet">
    <link href="img/Sem título.png" rel="icon">
</head>

<body>
    <div id="loader">
        <img src="img/Sem título2.png" alt="Loading" class="spinner-img">
    </div>

    <div class="bloco">
        <div class="iconF">
            <i class="bi bi-door-open-fill iconT"></i>
        </div>
        <h1 class="title">Bem-Vindo(a)!</h1>
        <h1 class="title2">que bom ter você conosco</h1>
        <div class="footerdiv"></div>

        <?php if ($erro != ""): ?>
            <div style="background-color: #ff4444; color: white; padding: 10px; margin: 10px; border-radius: 5px; text-align: center;">
                <?php echo $erro; ?>
            </div>
        <?php endif; ?>

        <?php if ($sucesso != ""): ?>
            <div style="background-color: #00ffb3; color: #145944; padding: 10px; margin: 10px; border-radius: 5px; text-align: center;">
                <?php echo $sucesso; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="input-floating">
                <i class="bi bi-person-circle iconny"></i>
                <input id="nome" name="nome" type="text" required><i class="bi bi-exclamation-circle" id="icone3"></i><i
                    class="bi bi-check-circle" id="icone4"></i>
                <label for="nome">Nome Completo</label>
            </div>

            <div class="input-floating-senha">
                <i class="bi bi-lock-fill iconny2"></i>
                <input id="senha" name="senha" type="password" required id="password"><i class="bi bi-eye-fill icone" id="botaoSenha"></i>
                <label for="senha">Senha</label>
            </div>
            <div class="progress-container">
                <div class="progress-bar" id="progress-bar"></div>
            </div>
            <p id="req-maiuscula">Requer uma letra maiúscula</p>
            <p id="req-caracteres">Requer mais de 7 caracteres</p>
            <p id="req-especial">Requer um caracter especial (@, #, etc.)</p>

            <div class="input-floating">
                <input id="confirmar" name="confirmar_senha" type="password" required><i class="bi bi-exclamation-circle" id="icone5"></i><i
                    class="bi bi-check-circle" id="icone6"></i>
                <i class="bi bi-lock-fill iconny"></i>
                <label for="confirmar">Confirmar Senha</label>
            </div>

            <div class="input-floating">
                <input id="setor" name="setor" type="number" required><i class="bi bi-exclamation-circle" id="icone7"></i><i
                    class="bi bi-check-circle" id="icone8"></i>
                <i class="bi bi-asterisk iconny"></i>
                <label for="setor">Setor</label>
            </div>

            <button type="submit" class="botao" id="cadastrar">Entrar</button>
        </form>
        <a href="entrar.php">
            <h1 class="outro">Já tem um cadastro? Clique aqui!</h1>
        </a>
    </div>
    <script src="script.js"></script>
</body>

</html>