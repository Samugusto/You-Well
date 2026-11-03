<?php
function conectar(): mysqli
{
    $arquivoConfig = __DIR__ . '/database.local.php';
    $config = is_file($arquivoConfig) ? require $arquivoConfig : [];

    if (!is_array($config)) {
        throw new RuntimeException('A configuração local do banco de dados é inválida.');
    }

    $host = $config['host'] ?? 'localhost';
    $user = $config['user'] ?? '';
    $password = $config['password'] ?? '';
    $database = $config['database'] ?? '';
    $port = (int) ($config['port'] ?? 3306);

    if ($user === '' || $database === '') {
        throw new RuntimeException('Configure usuário e banco em config/database.local.php.');
    }

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