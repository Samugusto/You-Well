<?php
function conectar() {
    // Configurações do banco no outro computador
    $host = "10.140.169.10";  //trocar IP quando o banco estiver ativo ;)
    $user = "root";
    $password = "123456"; 
    $database = "monitoramento_ambiente";
    $PORT = 3306;
    
    // Criar conexão
    $con = mysqli_connect($host, $user, $password, $database, $PORT);
    
    // Verificar conexão
    if (!$con) {
        die("Erro na conexão: " . mysqli_connect_error());
    }
    
    // Definir charset para UTF-8
    mysqli_set_charset($con, "utf8");
    
    return $con;
}
?>