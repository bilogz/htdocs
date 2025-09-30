<?php
session_start();
require 'config.php';

// Check if user is admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

if (isset($_GET['book_id'])) {
    $book_id = $_GET['book_id'];
    
    $stmt = $conn->prepare("SELECT * FROM books WHERE book_id = ?");
    if (!$stmt) {
        die(json_encode(['success' => false, 'message' => 'Error preparing statement: ' . $conn->error]));
    }
    
    if (!$stmt->bind_param("i", $book_id)) {
        die(json_encode(['success' => false, 'message' => 'Error binding parameters: ' . $stmt->error]));
    }
    
    if (!$stmt->execute()) {
        die(json_encode(['success' => false, 'message' => 'Error executing statement: ' . $stmt->error]));
    }
    
    $result = $stmt->get_result();
    $book = $result->fetch_assoc();
    
    if ($book) {
        echo json_encode(['success' => true, 'data' => $book]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Book not found']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'No book ID provided']);
}
?> 