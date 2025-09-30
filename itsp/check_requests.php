<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    die(json_encode(['error' => 'Unauthorized']));
}

// Get count of pending requests
$query = "SELECT COUNT(*) as count FROM book_schedules WHERE status = 'pending'";
$result = $conn->query($query);
$count = $result->fetch_assoc()['count'];

// Get the last check time from session
$last_check = isset($_SESSION['last_request_check']) ? $_SESSION['last_request_check'] : 0;

// Get new requests since last check
$query = "SELECT COUNT(*) as count FROM book_schedules WHERE status = 'pending' AND created_at > FROM_UNIXTIME(?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $last_check);
$stmt->execute();
$result = $stmt->get_result();
$new_requests = $result->fetch_assoc()['count'];

// Update last check time
$_SESSION['last_request_check'] = time();

// Return the counts
echo json_encode([
    'pending_count' => $count,
    'new_requests' => $new_requests
]); 