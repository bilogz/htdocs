<?php
session_name('student_session');
session_start();
require_once 'config.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['student_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

$student_id = $_SESSION['student_id'];

// Mark a single notification as read/unread
if (isset($_POST['notification_id'])) {
    $notification_id = $_POST['notification_id'];
    
    // Determine if we're marking as read or unread
    $mark_read = isset($_POST['mark_read']) ? (bool)$_POST['mark_read'] : true;
    $is_read = $mark_read ? 1 : 0;
    
    // Update the notification in the database
    $stmt = $conn->prepare("UPDATE notifications SET is_read = ? WHERE notification_id = ? AND student_id = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        exit();
    }
    
    $stmt->bind_param("iii", $is_read, $notification_id, $student_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => $mark_read ? 'Notification marked as read' : 'Notification marked as unread']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating notification: ' . $stmt->error]);
    }
    
    $stmt->close();
}
// Mark all notifications as read
elseif (isset($_POST['mark_all_read'])) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE student_id = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        exit();
    }
    
    $stmt->bind_param("i", $student_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'All notifications marked as read']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating notifications: ' . $stmt->error]);
    }
    
    $stmt->close();
}
else {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
}
?> 