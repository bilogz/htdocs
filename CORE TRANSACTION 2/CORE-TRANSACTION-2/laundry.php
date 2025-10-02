<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

function respond(array $payload, int $code = 200){ http_response_code($code); echo json_encode($payload); exit; }
function input(){ if($_POST) return $_POST; $r=file_get_contents('php://input'); $d=json_decode($r,true); return is_array($d)?$d:[]; }

function newOrder(array $d){ $order='LD-'.rand(1000,9999); $guest=$d['guest']??'Guest'; $amount=(float)($d['amount']??rand(120,800)); log_transaction('Laundry', $order.' ('.$guest.')', $amount, 'Queued'); log_activity('Laundry','newOrder',$order,$_SESSION['user_id']??null,['guest'=>$guest,'amount'=>$amount]); return ['success'=>true,'module'=>'Laundry','action'=>'newOrder','data'=>compact('order','guest','amount'),'timestamp'=>date('c')]; }
function completeOrder(array $d){ $order=$d['order']??('LD-'.rand(1000,9999)); log_transaction('Laundry', $order, null, 'Completed'); log_activity('Laundry','completeOrder',$order,$_SESSION['user_id']??null); return ['success'=>true,'module'=>'Laundry','action'=>'completeOrder','data'=>compact('order'),'timestamp'=>date('c')]; }

$in = input();
$action = $in['action'] ?? ($_GET['action'] ?? null);
// Only admin/staff can perform laundry operations
require_role(['admin','staff']);
switch($action){
  case 'newOrder': respond(newOrder($in));
  case 'completeOrder': respond(completeOrder($in));
  default: respond(['success'=>false,'error'=>'Unknown action','action'=>$action],400);
}
?>


