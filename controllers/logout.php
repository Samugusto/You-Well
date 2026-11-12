<?php
session_start();
require_once __DIR__ . "/../config/base_url.php";

// Remove todas as variáveis de sessão
$_SESSION = [];

// Destrói a sessão
session_destroy();

// Redireciona para a tela de login
header("Location: " . youwellBasePath() . "/entrar/");
exit();
?>
