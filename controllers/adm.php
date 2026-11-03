<?php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header("Location: /You-Well/entrar/");
    exit();
}

if ($_SESSION['papel'] != "admin") {
    header("Location: /You-Well/entrar/");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Área Administrativa</title>
</head>
<body>

<h1>Área Administrativa</h1>

<p>Bem-vindo, <?php echo $_SESSION['nome']; ?></p>

<p>Setor: <?php echo $_SESSION['setor']; ?></p>

<a href="/You-Well/sair/">Sair</a>

</body>
</html>
