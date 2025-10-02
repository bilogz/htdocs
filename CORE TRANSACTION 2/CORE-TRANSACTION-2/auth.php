<?php
require_once __DIR__ . '/db.php';

function auth_start_session(): void {
  if (session_status() === PHP_SESSION_NONE) {
    session_start();
  }
}

function auth_login(string $email, string $password): array {
  auth_start_session();
  $user = user_get_by_email($email);
  if (!$user || !user_verify_password($user, $password)) {
    throw new Exception('Invalid credentials');
  }
  $_SESSION['user_id'] = (int)$user['id'];
  $_SESSION['role'] = $user['role'];
  $_SESSION['name'] = $user['name'];
  return ['id'=>$user['id'],'name'=>$user['name'],'email'=>$user['email'],'role'=>$user['role']];
}

function auth_register(string $name, string $email, string $password, string $role = 'customer'): array {
  auth_start_session();
  $existing = user_get_by_email($email);
  if ($existing) { throw new Exception('Email already registered'); }
  $user = user_create($name, $email, $password, $role);
  $_SESSION['user_id'] = (int)$user['id'];
  $_SESSION['role'] = $user['role'];
  $_SESSION['name'] = $user['name'];
  return $user;
}

function auth_logout(): void {
  auth_start_session();
  $_SESSION = [];
  if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
  }
  session_destroy();
}

function auth_user(): ?array {
  auth_start_session();
  if (!empty($_SESSION['user_id'])) {
    $u = user_get_by_id((int)$_SESSION['user_id']);
    if ($u) return $u;
  }
  return null;
}

function require_role(array $roles): void {
  $u = auth_user();
  if (!$u || !in_array($u['role'], $roles, true)) {
    http_response_code(403);
    echo json_encode(['success'=>false,'error'=>'Forbidden','needed_roles'=>$roles]);
    exit;
  }
}
?>



