<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

function respond(array $p, int $c=200){ http_response_code($c); echo json_encode($p); exit; }
function input(){ if($_POST) return $_POST; $r=file_get_contents('php://input'); $d=json_decode($r,true); return is_array($d)?$d:[]; }

init_schema(); // ensure base demo tables; ERD tables live in schema.sql and are installed via install.php

$in = input();
$action = $in['action'] ?? ($_GET['action'] ?? 'ping');

try {
  switch ($action) {
    case 'ping':
      respond(['ok'=>true,'message'=>'ERD API ready']);

    case 'listGuests':
      $rows = db()->query("SELECT guest_id, name, contact_no, email, address FROM customer_guest_management ORDER BY name")->fetchAll();
      respond(['rows'=>$rows]);

    case 'createGuest':
      require_role(['admin','staff']);
      $id = $in['guest_id'] ?? ('G-' . rand(100,999));
      $stmt = db()->prepare("INSERT INTO customer_guest_management (guest_id, name, contact_no, email, address) VALUES (?,?,?,?,?)");
      $stmt->execute([$id, trim($in['name']??'Guest'), $in['contact_no']??null, $in['email']??null, $in['address']??null]);
      respond(['guest_id'=>$id]);

    case 'listRooms':
      $rows = db()->query("SELECT room_id, room_type, capacity, status, facility_name FROM room_facilities ORDER BY room_id")->fetchAll();
      respond(['rows'=>$rows]);

    case 'setRoomStatus':
      require_role(['admin','staff']);
      $stmt = db()->prepare("UPDATE room_facilities SET status=? WHERE room_id=?");
      $stmt->execute([$in['status']??'Available', $in['room_id']??'']);
      respond(['room_id'=>$in['room_id']??'', 'status'=>$in['status']??'Available']);

    case 'createReservation':
      require_role(['admin','staff']);
      $rid = $in['reservation_id'] ?? ('RES-' . rand(1000,9999));
      $stmt = db()->prepare("INSERT INTO reservation (reservation_id, room_id, check_in_date, check_out_date, status, guest_id) VALUES (?,?,?,?,?,?)");
      $stmt->execute([$rid, $in['room_id'], $in['check_in_date'], $in['check_out_date'], $in['status']??'Booked', $in['guest_id']]);
      respond(['reservation_id'=>$rid]);

    case 'createBooking':
      require_role(['admin','staff']);
      $bid = $in['booking_id'] ?? ('BKG-' . rand(1000,9999));
      $stmt = db()->prepare("INSERT INTO booking (booking_id, reservation_id, booking_date, status) VALUES (?,?,?,?)");
      $stmt->execute([$bid, $in['reservation_id'], $in['booking_date'] ?? date('Y-m-d'), $in['status']??'Confirmed']);
      respond(['booking_id'=>$bid]);

    case 'postBill':
      require_role(['admin','staff']);
      $bill = $in['bill_id'] ?? ('BILL-' . rand(1000,9999));
      $stmt = db()->prepare("INSERT INTO billing (bill_id, booking_id, amount, payment_status, payment_date, guest_id) VALUES (?,?,?,?,?,?)");
      $stmt->execute([$bill, $in['booking_id'], (float)($in['amount']??0), $in['payment_status']??'Unpaid', $in['payment_date'] ?? null, $in['guest_id']]);
      respond(['bill_id'=>$bill]);

    default:
      respond(['error'=>'Unknown action','action'=>$action],400);
  }
} catch (Throwable $e) {
  respond(['error'=>$e->getMessage()],500);
}
?>


