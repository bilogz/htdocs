<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

function respond(array $payload, int $code = 200): void {
  http_response_code($code);
  echo json_encode($payload);
  exit;
}

function read_input(): array {
  if (!empty($_POST)) return $_POST;
  $raw = file_get_contents('php://input');
  if (!$raw) return [];
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}

function createReservation(array $data): array {
  $guest = trim($data['guest'] ?? 'Guest');
  $room = (string)($data['room'] ?? 'TBD');
  $checkIn = $data['checkIn'] ?? date('Y-m-d');
  $checkOut = $data['checkOut'] ?? date('Y-m-d', strtotime('+1 day'));
  $source = $data['source'] ?? 'Online';
  $remarks = isset($data['remarks']) ? (string)$data['remarks'] : null;
  $row = reservation_create($guest, $room, $checkIn, $checkOut, $source, $remarks);
  $resNo = $row['reservation_no'] ?? reservation_generate_no();
  log_transaction('Front Office', $resNo, null, 'Booked');
  log_activity('Front Office', 'createReservation', $resNo, $_SESSION['user_id'] ?? null, ['guest'=>$guest,'room'=>$room]);
  return [
    'success' => true,
    'module' => 'Front Office',
    'action' => 'createReservation',
    'data' => [
      'reservationNo' => $resNo,
      'guest' => $row['guest'] ?? $guest,
      'room' => $row['room'] ?? $room,
      'checkIn' => $row['check_in'] ?? $checkIn,
      'checkOut' => $row['check_out'] ?? $checkOut,
      'status' => $row['status'] ?? 'Booked',
      'source' => $row['source'] ?? $source,
      'remarks' => $row['remarks'] ?? $remarks
    ],
    'timestamp' => date('c')
  ];
}

function listReservations(array $data): array {
  $limit = isset($data['limit']) ? max(1, (int)$data['limit']) : 100;
  $offset = isset($data['offset']) ? max(0, (int)$data['offset']) : 0;
  $rows = reservation_list($limit, $offset);
  return ['success'=>true,'module'=>'Front Office','action'=>'listReservations','data'=>['rows'=>$rows],'timestamp'=>date('c')];
}

function updateReservation(array $data): array {
  $reservationNo = $data['reservationNo'] ?? '';
  if (!$reservationNo) { throw new Exception('reservationNo is required'); }
  $map = [];
  if (isset($data['guest'])) $map['guest'] = trim($data['guest']);
  if (isset($data['room'])) $map['room'] = (string)$data['room'];
  if (isset($data['checkIn'])) $map['check_in'] = (string)$data['checkIn'];
  if (isset($data['checkOut'])) $map['check_out'] = (string)$data['checkOut'];
  if (isset($data['status'])) $map['status'] = (string)$data['status'];
  if (isset($data['source'])) $map['source'] = (string)$data['source'];
  if (array_key_exists('remarks', $data)) $map['remarks'] = $data['remarks'];
  $row = reservation_update($reservationNo, $map);
  return ['success'=>true,'module'=>'Front Office','action'=>'updateReservation','data'=>['reservationNo'=>$reservationNo,'row'=>$row],'timestamp'=>date('c')];
}

function deleteReservation(array $data): array {
  $reservationNo = $data['reservationNo'] ?? '';
  if (!$reservationNo) { throw new Exception('reservationNo is required'); }
  $ok = reservation_delete($reservationNo);
  log_activity('Front Office', 'deleteReservation', $reservationNo, $_SESSION['user_id'] ?? null);
  return ['success'=>$ok,'module'=>'Front Office','action'=>'deleteReservation','data'=>['reservationNo'=>$reservationNo],'timestamp'=>date('c')];
}

function checkInGuest(array $data): array {
  $reservationNo = $data['reservationNo'] ?? ('RF-' . rand(1000, 9999));
  reservation_update($reservationNo, ['status' => 'Checked-in']);
  log_transaction('Front Office', $reservationNo, null, 'Checked-in');
  log_activity('Front Office', 'checkInGuest', $reservationNo, $_SESSION['user_id'] ?? null);
  return [
    'success' => true,
    'module' => 'Front Office',
    'action' => 'checkInGuest',
    'data' => [ 'reservationNo' => $reservationNo, 'status' => 'Checked-in' ],
    'timestamp' => date('c')
  ];
}

function checkOutGuest(array $data): array {
  $reservationNo = $data['reservationNo'] ?? ('RF-' . rand(1000, 9999));
  $amount = (float)($data['amount'] ?? rand(1500, 6500));
  reservation_update($reservationNo, ['status' => 'Checked-out']);
  log_transaction('Front Office', $reservationNo, $amount, 'Checked-out');
  log_activity('Front Office', 'checkOutGuest', $reservationNo, $_SESSION['user_id'] ?? null, ['amount'=>$amount]);
  return [
    'success' => true,
    'module' => 'Front Office',
    'action' => 'checkOutGuest',
    'data' => [ 'reservationNo' => $reservationNo, 'status' => 'Checked-out', 'amount' => $amount ],
    'timestamp' => date('c')
  ];
}

$input = read_input();
$action = $input['action'] ?? ($_GET['action'] ?? null);

// Guard: only staff/admin can access all reservation operations
if (in_array($action, ['createReservation','listReservations','updateReservation','deleteReservation','checkInGuest','checkOutGuest'], true)) {
  require_role(['admin','staff']);
}

try {
  switch ($action) {
    case 'createReservation':
      $res = createReservation($input);
      respond($res);
    case 'listReservations':
      respond(listReservations($input));
    case 'updateReservation':
      respond(updateReservation($input));
    case 'deleteReservation':
      respond(deleteReservation($input));
    case 'checkInGuest':
      respond(checkInGuest($input));
    case 'checkOutGuest':
      respond(checkOutGuest($input));
    default:
      respond(['success' => false, 'error' => 'Unknown action', 'action' => $action], 400);
  }
} catch (Throwable $e) {
  respond(['success' => false, 'error' => $e->getMessage()], 500);
}
?>


