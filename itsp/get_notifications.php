<?php
session_name('student_session');
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['student_id'])) {
    echo json_encode([]);
    exit();
}

$student_id = $_SESSION['student_id'];
$unread_only = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';

// Build query based on parameters
if ($unread_only) {
    $query = "SELECT * FROM notifications WHERE student_id = ? AND is_read = FALSE ORDER BY created_at DESC LIMIT 10";
} else {
    $query = "SELECT * FROM notifications WHERE student_id = ? ORDER BY created_at DESC LIMIT 10";
}

$stmt = $conn->prepare($query);

if (!$stmt) {
    error_log("Database error: " . $conn->error);
    echo json_encode([]);
    exit();
}

$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = [
        'notification_id' => $row['notification_id'],
        'message' => $row['message'],
        'is_read' => (bool)$row['is_read'],
        'created_at' => $row['created_at'],
        'type' => $row['type'] ?? 'general'
    ];
}

// Return as JSON
echo json_encode($notifications);
?> 