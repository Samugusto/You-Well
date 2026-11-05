<?php


header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}


require_once __DIR__ . "/../config/conexao.php";


$metodo = $_SERVER['REQUEST_METHOD'];
$rota = $_GET['rota'] ?? '';


try {
    $con = conectar();


    // Testar a conexão com o banco
    if ($rota === 'status' && $metodo === 'GET') {


        echo json_encode([
            "success" => true,
            "message" => "API YouWell conectada ao banco de dados!"
        ]);

        mysqli_close($con);

        exit;
    }


    // Rota não encontrada
    http_response_code(404);


    echo json_encode([
        "success" => false,
        "message" => "Rota não encontrada."
    ]);
    mysqli_close($con);


} catch (Exception $e) {


    http_response_code(500);


    echo json_encode([
        "success" => false,
        "message" => "Erro interno na API."
    ]);
}
