<?php
function conectar(): mysqli
{
    $arquivoConfig = __DIR__ . '/database.local.php';
    if (!is_file($arquivoConfig)) {
        throw new RuntimeException('Crie database.local.php com a configuração local do banco.');
    }

    $config = require $arquivoConfig;

    if (!is_array($config)) {
        throw new RuntimeException('A configuração local do banco de dados é inválida.');
    }

    foreach (['host', 'user', 'password', 'database'] as $requiredKey) {
        if (!isset($config[$requiredKey]) || !is_string($config[$requiredKey]) || $config[$requiredKey] === '') {
            throw new RuntimeException('Configure host, user, password e database em database.local.php.');
        }
    }

    $host = $config['host'];
    $user = $config['user'];
    $password = $config['password'];
    $database = $config['database'];
    $port = (int) ($config['port'] ?? 3306);

    try {
        $con = mysqli_connect($host, $user, $password, $database, $port);
    } catch (mysqli_sql_exception $erro) {
        error_log('Falha na conexão com o banco YouWell: ' . $erro->getMessage());
        throw new RuntimeException('Não foi possível conectar ao banco de dados.');
    }

    if (!$con) {
        error_log('Falha na conexão com o banco YouWell: ' . mysqli_connect_error());
        throw new RuntimeException('Não foi possível conectar ao banco de dados.');
    }

    mysqli_set_charset($con, 'utf8mb4');

    return $con;
}