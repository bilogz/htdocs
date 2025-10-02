<?php
header('Content-Type: application/json');
require_once __DIR__ . '/auth.php';

function read_input(): array {
  if (!empty($_POST)) return $_POST;
  $raw = file_get_contents('php://input');
  if (!$raw) return [];
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}

$in = read_input();
$action = $in['action'] ?? ($_GET['action'] ?? null);

try {
  switch ($action) {
    case 'login':
      $email = $in['email'] ?? '';
      $password = $in['password'] ?? '';
      $user = auth_login($email, $password);
      echo json_encode(['success'=>true,'action'=>'login','user'=>$user]);
      exit;
    case 'register':
      // Disable public self-registration for customers; allow only staff/admin creation
      $name = $in['name'] ?? '';
      $email = $in['email'] ?? '';
      $password = $in['password'] ?? '';
      $role = $in['role'] ?? 'staff';
      $me = auth_user();
      if (!$me || !in_array($me['role'], ['admin'], true)) {
        http_response_code(403);
        echo json_encode(['success'=>false,'error'=>'Registration is restricted to administrators']);
        exit;
      }
      $user = auth_register($name, $email, $password, $role);
      echo json_encode(['success'=>true,'action'=>'register','user'=>$user]);
      exit;
    case 'logout':
      auth_logout();
      echo json_encode(['success'=>true,'action'=>'logout']);
      exit;
    case 'me':
      $u = auth_user();
      echo json_encode(['success'=>true,'action'=>'me','user'=>$u]);
      exit;
    default:
      http_response_code(400);
      echo json_encode(['success'=>false,'error'=>'Unknown action','action'=>$action]);
      exit;
  }
} catch (Throwable $e) {
  http_response_code(400);
  echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
?>



