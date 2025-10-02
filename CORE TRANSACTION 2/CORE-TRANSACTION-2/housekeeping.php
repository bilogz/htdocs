<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

function respond(array $payload, int $code = 200){ http_response_code($code); echo json_encode($payload); exit; }
function input(){ if($_POST) return $_POST; $r=file_get_contents('php://input'); $d=json_decode($r,true); return is_array($d)?$d:[]; }

function assignCleaning(array $d){ $task='HK-'.rand(100,999); $room=$d['room']??('RM-'.rand(100,499)); $staff=$d['staff']??'Housekeeper'; log_transaction('Housekeeping', $task.' ('.$room.')', null, 'Assigned'); log_activity('Housekeeping','assignCleaning',$task,$_SESSION['user_id']??null,['room'=>$room,'staff'=>$staff]); return ['success'=>true,'module'=>'Housekeeping','action'=>'assignCleaning','data'=>compact('task','room','staff'),'timestamp'=>date('c')]; }
function logInspection(array $d){ $task='IN-'.rand(100,999); $room=$d['room']??('RM-'.rand(100,499)); $score=(int)($d['score']??rand(80,100)); log_transaction('Housekeeping', $task.' ('.$room.')', null, 'Logged'); log_activity('Housekeeping','logInspection',$task,$_SESSION['user_id']??null,['room'=>$room,'score'=>$score]); return ['success'=>true,'module'=>'Housekeeping','action'=>'logInspection','data'=>compact('task','room','score'),'timestamp'=>date('c')]; }

$in = input();
$action = $in['action'] ?? ($_GET['action'] ?? null);
// Only admin/staff can perform housekeeping operations
require_role(['admin','staff']);
switch($action){
  case 'assignCleaning': respond(assignCleaning($in));
  case 'logInspection': respond(logInspection($in));
  default: respond(['success'=>false,'error'=>'Unknown action','action'=>$action],400);
}
?>


