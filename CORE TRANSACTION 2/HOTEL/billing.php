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
  return ($raw && ($d = json_decode($raw, true)) && is_array($d)) ? $d : [];
}

function postCharge(array $data): array {
  $folio = $data['folio'] ?? ('F-' . rand(1000,9999));
  $amount = (float)($data['amount'] ?? rand(300, 2000));
  $desc = $data['description'] ?? 'Room Charge';
  log_transaction('Billing', 'CH-' . rand(1000,9999) . ' (' . $folio . ')', $amount, 'Posted');
  log_activity('Billing', 'postCharge', $folio, $_SESSION['user_id'] ?? null, ['amount'=>$amount,'description'=>$desc]);
  return ['success'=>true,'module'=>'Billing','action'=>'postCharge','data'=>compact('folio','amount','desc'),'timestamp'=>date('c')];
}
function generateInvoice(array $data): array {
  $folio = $data['folio'] ?? ('F-' . rand(1000,9999));
  $invoice = 'INV-'.rand(1000,9999);
  log_transaction('Billing', $invoice . ' (' . $folio . ')', null, 'Generated');
  log_activity('Billing', 'generateInvoice', $invoice, $_SESSION['user_id'] ?? null, ['folio'=>$folio]);
  return ['success'=>true,'module'=>'Billing','action'=>'generateInvoice','data'=>['invoiceNo'=>$invoice,'folio'=>$folio],'timestamp'=>date('c')];
}
function recordPayment(array $data): array {
  $invoiceNo = $data['invoiceNo'] ?? ('INV-' . rand(1000,9999));
  $amount = (float)($data['amount'] ?? rand(500, 4000));
  log_transaction('Billing', $invoiceNo, $amount, 'Paid');
  log_activity('Billing', 'recordPayment', $invoiceNo, $_SESSION['user_id'] ?? null, ['amount'=>$amount]);
  return ['success'=>true,'module'=>'Billing','action'=>'recordPayment','data'=>compact('invoiceNo','amount'),'timestamp'=>date('c')];
}

$input = read_input();
$action = $input['action'] ?? ($_GET['action'] ?? null);
// Only admin/staff can perform billing operations
require_role(['admin','staff']);
switch ($action) {
  case 'postCharge': respond(postCharge($input));
  case 'generateInvoice': respond(generateInvoice($input));
  case 'recordPayment': respond(recordPayment($input));
  default: respond(['success'=>false,'error'=>'Unknown action','action'=>$action],400);
}
?>


