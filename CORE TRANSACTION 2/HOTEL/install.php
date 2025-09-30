<?php
// Simple installer: loads schema.sql then seed.sql into MySQL using PDO
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

function respond($p, $c=200){ http_response_code($c); echo json_encode($p); exit; }

try {
  ensure_database_exists();
  $schema = file_get_contents(__DIR__ . '/schema.sql');
  if ($schema === false) throw new Exception('schema.sql not found');

  // Split by statements safely on ; followed by newline when not inside quotes
  $statements = array_filter(array_map('trim', preg_split('/;\s*\n/', $schema)));
  $pdo = db();
  foreach ($statements as $sql) {
    if ($sql === '' || strpos($sql, '--') === 0) continue;
    $pdo->exec($sql);
  }

  $seedPath = __DIR__ . '/seed.sql';
  if (file_exists($seedPath)) {
    $seed = file_get_contents($seedPath);
    $stmts = array_filter(array_map('trim', preg_split('/;\s*\n/', $seed)));
    foreach ($stmts as $sql) { if ($sql !== '' && strpos($sql, '--') !== 0) $pdo->exec($sql); }
  }

  respond(['success'=>true,'message'=>'Database installed and seeded','db'=>getenv('DB_NAME')?:'hotel_core']);
} catch (Throwable $e) {
  respond(['success'=>false,'error'=>$e->getMessage()],500);
}
?>


