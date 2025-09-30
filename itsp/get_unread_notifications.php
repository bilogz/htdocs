<?php
session_name('student_session');
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['student_id'])) {
    echo '0'; // Return 0 if not logged in
    exit();
}

$student_id = $_SESSION['student_id'];

// Get number of unread notifications
$query = "SELECT COUNT(*) as unread FROM notifications WHERE student_id = ? AND is_read = FALSE";
$stmt = $conn->prepare($query);

if (!$stmt) {
    error_log("Database error: " . $conn->error);
    echo '0';
    exit();
}

$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

// Return just the number
echo $row['unread'];
?> 