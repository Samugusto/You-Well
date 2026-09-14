<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once "conexao.php";

$erro = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nome = trim($_POST["nome"]);
    $senha = $_POST["senha"];

    $con = conectar();

    $sql = "SELECT * FROM usuarios WHERE nome = ?";

    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "s", $nome);
    mysqli_stmt_execute($stmt);

    $resultado = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($resultado) > 0) {

        $usuario = mysqli_fetch_assoc($resultado);

        if (password_verify($senha, $usuario['senha'])) {

            $_SESSION['id_usuario'] = $usuario['id_usuario'];
            $_SESSION['nome'] = $usuario['nome'];
            $_SESSION['papel'] = $usuario['papel'];
            $_SESSION['setor'] = $usuario['setor'];

            if ($usuario['papel'] == "admin") {
                header("Location: adm.php");
                exit();
            } elseif ($usuario['papel'] == "gerente") {
                header("Location: painel.php");
                exit();
            } else {
                $erro = "Tipo de usuário inválido.";
            }
        } else {
            $erro = "Usuário ou senha inválidos.";
        }
    } else {

        $erro = "Usuário não encontrado.";
    }

    mysqli_close($con);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YouWell - Login</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="icon" href="img/Sem título.png"
    </head>

<body>

    <div class="bloco">
        <div class="iconF">
            <i class="bi bi-door-open-fill"></i>
        </div>
        <h1 class="title">Bem-Vindo(a) de volta :)</h1>
        <h1 class="title2">acompanhe os setores da sua empresa</h1>
        <div class="footerdiv"></div>

        <?php if (!empty($erro)) { ?>
            <div style="background-color: #ff4444; color: white; padding: 10px; margin: 10px; border-radius: 5px; text-align: center;">
                <?php echo $erro; ?>
            </div>
        <?php } ?>

        <form method="POST" action="">

            <div class="input-floating">
                <i class="bi bi-person-circle iconny"></i>
                <input id="nome" name="nome" type="text" required><i class="bi bi-exclamation-circle" id="icone3"></i><i
                    class="bi bi-check-circle" id="icone4"></i>
                <label for="nome">Nome Completo</label>
            </div>

            <div class="input-floating-senha">
                <i class="bi bi-lock-fill iconny"></i>
                <input id="senha" name="senha" type="password" required><i class="bi bi-eye-fill icone" id="botaoSenha"></i>
                <label for="senha">Senha</label>
            </div>

            <br>

            <button class="botao" type="submit">Entrar</button>

        </form>

        <br>

    </div>
    <script src="script.js"></script>
</body>

</html>