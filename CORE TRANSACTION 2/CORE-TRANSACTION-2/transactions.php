<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

$limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 50;
$sinceId = isset($_GET['since_id']) ? max(0, (int)$_GET['since_id']) : null;
$rows = fetch_transactions($limit, $sinceId);
echo json_encode(['success'=>true,'rows'=>$rows]);
?>


