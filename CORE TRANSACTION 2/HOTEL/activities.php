<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

function respond(array $payload, int $code = 200){ http_response_code($code); echo json_encode($payload); exit; }

function input(){ if($_POST) return $_POST; $r=file_get_contents('php://input'); $d=json_decode($r,true); return is_array($d)?$d:[]; }

// For now, allow staff/admin to read all; customers can only see their own (by user_id)
$in = input();
$action = $in['action'] ?? ($_GET['action'] ?? 'list');

try {
  switch ($action) {
    case 'list':
      $limit = isset($in['limit']) ? max(1, (int)$in['limit']) : (isset($_GET['limit']) ? (int)$_GET['limit'] : 100);
      $offset = isset($in['offset']) ? max(0, (int)$in['offset']) : (isset($_GET['offset']) ? (int)$_GET['offset'] : 0);
      $module = $in['module'] ?? ($_GET['module'] ?? null);
      $userOnly = isset($_GET['mine']) || (!empty($in['mine']));

      $where = [];
      $vals = [];
      if ($module) { $where[] = 'module = ?'; $vals[] = $module; }

      // Role-aware filter
      $viewer = current_user();
      if ($userOnly && $viewer) {
        $where[] = 'user_id = ?';
        $vals[] = $viewer['id'];
      } else if (!$viewer || ($viewer['role'] ?? 'customer') === 'customer') {
        // Customers see their own only by default
        if ($viewer) { $where[] = 'user_id = ?'; $vals[] = $viewer['id']; }
      }

      $sql = 'SELECT id, ts, module, action, reference, user_id, meta FROM activities';
      if ($where) { $sql .= ' WHERE ' . implode(' AND ', $where); }
      $sql .= ' ORDER BY id DESC LIMIT ? OFFSET ?';
      $vals[] = $limit;
      $vals[] = $offset;

      init_schema();
      $stmt = db()->prepare($sql);
      $stmt->execute($vals);
      $rows = $stmt->fetchAll();

      // decode meta JSON
      foreach ($rows as &$row) {
        if (isset($row['meta']) && is_string($row['meta'])) {
          $decoded = json_decode($row['meta'], true);
          $row['meta'] = is_array($decoded) ? $decoded : null;
        }
      }
      respond(['success'=>true,'action'=>'list','data'=>['rows'=>$rows]]);
    default:
      respond(['success'=>false,'error'=>'Unknown action','action'=>$action],400);
  }
} catch (Throwable $e) {
  respond(['success'=>false,'error'=>$e->getMessage()],500);
}
?>


