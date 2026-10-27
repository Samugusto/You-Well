<?php
session_start();

// Remove todas as variáveis de sessão
$_SESSION = [];

// Destrói a sessão
session_destroy();

// Redireciona para a tela de login
header("Location: ../controllers/entrar.php");
exit();
?>

