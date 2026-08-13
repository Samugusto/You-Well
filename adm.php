<?php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header("Location: entrar.php");
    exit();
}

if ($_SESSION['papel'] != "admin") {
    header("Location: entrar.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Área Administrativa</title>
</head>
<body>

<h1>Área Administrativa</h1>

<p>Bem-vindo, <?php echo $_SESSION['nome']; ?></p>

<p>Setor: <?php echo $_SESSION['setor']; ?></p>

<a href="logout.php">Sair</a>

</body>
</html>