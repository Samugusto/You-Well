<?php
function conectar(): mysqli
{
    $arquivoConfig = __DIR__ . '/database.local.php';
    $config = is_file($arquivoConfig) ? require $arquivoConfig : [];

    $hostRequisicao = strtolower((string) parse_url(
        'http://' . ($_SERVER['HTTP_HOST'] ?? ''),
        PHP_URL_HOST
    ));
    if (
        in_array($hostRequisicao, ['localhost', '127.0.0.1', '::1'], true)
        && !is_file($arquivoConfig)
    ) {
        throw new RuntimeException('Configure o banco de dados local em config/database.local.php.');
    }

    if (!is_array($config)) {
        throw new RuntimeException('A configuração local do banco de dados é inválida.');
    }

    $host = $config['host'] ?? '162.241.3.35';
    $user = $config['user'] ?? 'devsbeecom';
    
    $password = $config['password'] ?? '$Projeto26';
    $database = $config['database'] ?? 'devsbeec_monitoramento_ambiente';
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