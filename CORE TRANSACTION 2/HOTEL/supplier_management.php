<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

function respond(array $payload, int $code = 200){ http_response_code($code); echo json_encode($payload); exit; }
function input(){ if($_POST) return $_POST; $r=file_get_contents('php://input'); $d=json_decode($r,true); return is_array($d)?$d:[]; }

function createPO(array $d){ $po='PO-'.rand(1000,9999); $vendor=$d['vendor']??'Default Vendor'; $amount=(float)($d['amount']??rand(2000,15000)); log_transaction('Supplier Management', $po.' ('.$vendor.')', $amount, 'Issued'); return ['success'=>true,'module'=>'Supplier Management','action'=>'createPO','data'=>compact('po','vendor','amount'),'timestamp'=>date('c')]; }
function receiveDelivery(array $d){ $po=$d['po']??('PO-'.rand(1000,9999)); $dr='DR-'.rand(1000,9999); log_transaction('Supplier Management', $dr.' ('.$po.')', null, 'Received'); return ['success'=>true,'module'=>'Supplier Management','action'=>'receiveDelivery','data'=>compact('po','dr'),'timestamp'=>date('c')]; }

$in = input();
$action = $in['action'] ?? ($_GET['action'] ?? null);
// Only admin/staff can perform supplier management operations
require_role(['admin','staff']);
switch($action){
  case 'createPO': respond(createPO($in));
  case 'receiveDelivery': respond(receiveDelivery($in));
  default: respond(['success'=>false,'error'=>'Unknown action','action'=>$action],400);
}
?>


