<?php
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/../config/conexao.php';
$idSetor = $_POST['id_setor'] ?? '';
if ($idSetor === '' || !ctype_digit((string) $idSetor) || (int) $idSetor <= 0) { echo json_encode(['success'=>false,'mensagem'=>'ID do setor inválido.'], JSON_UNESCAPED_UNICODE); exit; }
$con = conectar(); $tabela = 'setor_' . (int) $idSetor;
$resultado = mysqli_query($con, "SELECT temperatura, ruido, qualidade_ar, data_hora FROM `{$tabela}` ORDER BY id_leitura DESC LIMIT 1");
$l = $resultado ? mysqli_fetch_assoc($resultado) : null;
$medicao = static function (?array $l, string $campo): ?array { return $l ? ['valor'=>(float)$l[$campo], 'data_hora'=>$l['data_hora']] : null; };
echo json_encode(['success'=>true,'temperatura'=>$medicao($l,'temperatura'),'ruido'=>$medicao($l,'ruido'),'qualidade_ar'=>$medicao($l,'qualidade_ar')], JSON_UNESCAPED_UNICODE);
mysqli_close($con);
