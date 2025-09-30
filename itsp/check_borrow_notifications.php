<?php
require_once 'config.php';

// Add error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get count of pending borrow requests from book_schedules
$query = "SELECT COUNT(*) as count FROM book_schedules WHERE status = 'pending'";
$result = $conn->query($query);

// Debug information
$error = $conn->error;
$count = 0;

if ($result) {
    $row = $result->fetch_assoc();
    $count = $row['count'];
} else {
    // If query fails, log error
    error_log("Error in check_borrow_notifications.php: " . $error);
}

// Set proper content type for JSON
header('Content-Type: application/json');

// Return the count as JSON
echo json_encode([
    'success' => ($error ? false : true),
    'pending_count' => $count,
    'error' => $error
]);

$conn->close();
?> 