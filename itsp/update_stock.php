<?php
session_start();
require 'config.php';

// Check if user is admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $book_id = $_POST['book_id'];
    $new_stock = (int)$_POST['stock'];
    
    // Validate stock value
    if ($new_stock < 0) {
        die(json_encode(['success' => false, 'message' => 'Stock cannot be negative']));
    }
    
    // Update stock
    $stmt = $conn->prepare("UPDATE books SET stock = ? WHERE book_id = ?");
    if (!$stmt) {
        die(json_encode(['success' => false, 'message' => 'Error preparing statement: ' . $conn->error]));
    }
    
    if (!$stmt->bind_param("ii", $new_stock, $book_id)) {
        die(json_encode(['success' => false, 'message' => 'Error binding parameters: ' . $stmt->error]));
    }
    
    if (!$stmt->execute()) {
        die(json_encode(['success' => false, 'message' => 'Error updating stock: ' . $stmt->error]));
    }
    
    // Get updated book information
    $stmt = $conn->prepare("SELECT stock, status FROM books WHERE book_id = ?");
    if (!$stmt) {
        die(json_encode(['success' => false, 'message' => 'Error preparing select statement: ' . $conn->error]));
    }
    
    if (!$stmt->bind_param("i", $book_id)) {
        die(json_encode(['success' => false, 'message' => 'Error binding select parameters: ' . $stmt->error]));
    }
    
    if (!$stmt->execute()) {
        die(json_encode(['success' => false, 'message' => 'Error fetching updated data: ' . $stmt->error]));
    }
    
    $result = $stmt->get_result();
    $book = $result->fetch_assoc();
    
    // Update status based on stock
    $new_status = $book['stock'] > 0 ? 'Available' : 'Unavailable';
    $update_status = $conn->prepare("UPDATE books SET status = ? WHERE book_id = ?");
    if ($update_status) {
        $update_status->bind_param("si", $new_status, $book_id);
        $update_status->execute();
    }
    
    echo json_encode([
        'success' => true,
        'stock' => $book['stock'],
        'status' => $new_status
    ]);
    
    $stmt->close();
    if (isset($update_status)) {
        $update_status->close();
    }
}
?> 