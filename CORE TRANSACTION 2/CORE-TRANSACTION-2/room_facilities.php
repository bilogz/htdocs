<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

function respond(array $payload, int $code = 200){ http_response_code($code); echo json_encode($payload); exit; }
function input(){ if($_POST) return $_POST; $r=file_get_contents('php://input'); $d=json_decode($r,true); return is_array($d)?$d:[]; }

function blockRoom(array $d){ $room=$d['room']??('RM-'.rand(100,499)); $reason=$d['reason']??'Maintenance'; log_transaction('Room Facilities', 'Block '.$room, null, 'Blocked'); log_activity('Room Facilities','blockRoom',$room,$_SESSION['user_id']??null,['reason'=>$reason]); return ['success'=>true,'module'=>'Room Facilities','action'=>'blockRoom','data'=>compact('room','reason'),'timestamp'=>date('c')]; }
function createMaintenance(array $d){ $room=$d['room']??('RM-'.rand(100,499)); $ticket='MT-'.rand(100,999); $cost=(float)($d['cost']??rand(200,1500)); log_transaction('Room Facilities', $ticket.' ('.$room.')', $cost, 'Created'); log_activity('Room Facilities','createMaintenance',$ticket,$_SESSION['user_id']??null,['room'=>$room,'cost'=>$cost]); return ['success'=>true,'module'=>'Room Facilities','action'=>'createMaintenance','data'=>compact('room','ticket','cost'),'timestamp'=>date('c')]; }

$in = input();
$action = $in['action'] ?? ($_GET['action'] ?? null);
// Only admin/staff can perform room facilities operations
require_role(['admin','staff']);
switch($action){
  case 'blockRoom': respond(blockRoom($in));
  case 'createMaintenance': respond(createMaintenance($in));
  default: respond(['success'=>false,'error'=>'Unknown action','action'=>$action],400);
}
?>


