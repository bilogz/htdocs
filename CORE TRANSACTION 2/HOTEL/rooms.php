<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

function respond(array $payload, int $code = 200){ http_response_code($code); echo json_encode($payload); exit; }
function input(){ if($_POST) return $_POST; $r=file_get_contents('php://input'); $d=json_decode($r,true); return is_array($d)?$d:[]; }

init_schema();

$in = input();
$action = $in['action'] ?? ($_GET['action'] ?? 'list');

try {
  switch ($action) {
    case 'list':
      $stmt = db()->query("SELECT id, room_no, type, status, rate FROM rooms ORDER BY room_no ASC");
      $rows = $stmt->fetchAll();
      respond(['success'=>true,'action'=>'list','data'=>['rows'=>$rows]]);
    case 'setStatus':
      require_role(['admin','staff']);
      $roomNo = $in['room_no'] ?? '';
      $status = $in['status'] ?? '';
      if (!$roomNo || !$status) throw new Exception('room_no and status are required');
      $stmt = db()->prepare("UPDATE rooms SET status = ? WHERE room_no = ?");
      $stmt->execute([$status, $roomNo]);
      log_activity('Room Facilities','setStatus',$roomNo,$_SESSION['user_id']??null,['status'=>$status]);
      respond(['success'=>true,'action'=>'setStatus','data'=>['room_no'=>$roomNo,'status'=>$status]]);
    default:
      respond(['success'=>false,'error'=>'Unknown action','action'=>$action],400);
  }
} catch (Throwable $e) {
  respond(['success'=>false,'error'=>$e->getMessage()],500);
}
?>


