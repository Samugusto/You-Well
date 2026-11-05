<?php
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/../config/conexao.php';
$idSetor = $_POST['id_setor'] ?? '';
if ($idSetor === '' || !ctype_digit((string)$idSetor) || (int)$idSetor <= 0) { echo json_encode(['success'=>false,'mensagem'=>'ID do setor inválido.'], JSON_UNESCAPED_UNICODE); exit; }
$con = conectar(); $tabela = 'setor_' . (int)$idSetor;
$resultado = mysqli_query($con, "SELECT temperatura, ruido, qualidade_ar, data_hora FROM `{$tabela}` ORDER BY id_leitura DESC LIMIT 24");
$temperatura = $ruido = $qualidadeAr = [];
if ($resultado) while ($l = mysqli_fetch_assoc($resultado)) {
    $temperatura[] = ['valor'=>(float)$l['temperatura'], 'data_hora'=>$l['data_hora']];
    $ruido[] = ['valor'=>(float)$l['ruido'], 'data_hora'=>$l['data_hora']];
    $qualidadeAr[] = ['valor'=>(float)$l['qualidade_ar'], 'data_hora'=>$l['data_hora']];
}
echo json_encode(['success'=>true,'temperatura'=>array_reverse($temperatura),'ruido'=>array_reverse($ruido),'qualidade_ar'=>array_reverse($qualidadeAr)], JSON_UNESCAPED_UNICODE);
mysqli_close($con);
