<?php
session_name('admin_session');
session_start();
require_once 'config.php';

// Check if user is admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true || 
    !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    
    // Return error json
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Unauthorized access',
        'session_error' => true
    ]);
    exit();
}

// Get counts of various notifications
$notifications = [
    'success' => true,
    'pending_borrow' => 0,
    'pending_returns' => 0,
    'overdue_books' => 0,
    'total_notifications' => 0
];

// Count pending borrow requests
$pending_query = "SELECT COUNT(*) as count FROM book_schedules WHERE status = 'pending'";
$pending_result = $conn->query($pending_query);
if ($pending_result && $row = $pending_result->fetch_assoc()) {
    $notifications['pending_borrow'] = (int)$row['count'];
    $notifications['total_notifications'] += $notifications['pending_borrow'];
}

// Count return requests
$returns_query = "SELECT COUNT(*) as count FROM borrowed_books WHERE return_date IS NOT NULL AND admin_confirmed_return = 0";
$returns_result = $conn->query($returns_query);
if ($returns_result && $row = $returns_result->fetch_assoc()) {
    $notifications['pending_returns'] = (int)$row['count'];
    $notifications['total_notifications'] += $notifications['pending_returns'];
}

// Count overdue books
$overdue_query = "SELECT COUNT(*) as count 
                 FROM book_schedules 
                 WHERE status = 'confirmed' 
                 AND DATE_ADD(schedule_date, INTERVAL 14 DAY) < CURDATE()";
$overdue_result = $conn->query($overdue_query);
if ($overdue_result && $row = $overdue_result->fetch_assoc()) {
    $notifications['overdue_books'] = (int)$row['count'];
    $notifications['total_notifications'] += $notifications['overdue_books'];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($notifications);
exit();
?> 