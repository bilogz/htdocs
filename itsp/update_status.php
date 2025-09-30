<?php
session_start();
require 'config.php';

// Check if user is admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $book_id = $_POST['book_id'];
    $new_status = $_POST['new_status'];
    
    // Validate status
    $valid_statuses = ['Available', 'Borrowed', 'Unavailable'];
    if (!in_array($new_status, $valid_statuses)) {
        die(json_encode(['success' => false, 'message' => 'Invalid status']));
    }
    
    // Update status
    $stmt = $conn->prepare("UPDATE books SET status = ? WHERE book_id = ?");
    if (!$stmt) {
        die(json_encode(['success' => false, 'message' => 'Error preparing statement: ' . $conn->error]));
    }
    
    if (!$stmt->bind_param("si", $new_status, $book_id)) {
        die(json_encode(['success' => false, 'message' => 'Error binding parameters: ' . $stmt->error]));
    }
    
    if (!$stmt->execute()) {
        die(json_encode(['success' => false, 'message' => 'Error updating status: ' . $stmt->error]));
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Status updated successfully'
    ]);
    
    $stmt->close();
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?> 