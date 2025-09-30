<?php
session_start();
require 'config.php';

// Only allow admins
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_id'])) {
    $book_id = intval($_POST['book_id']);

    // First, delete all borrowed_books entries for this book
    $stmt = $conn->prepare("DELETE FROM borrowed_books WHERE book_id = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Error preparing borrowed_books delete: ' . $conn->error]);
        exit();
    }
    if (!$stmt->bind_param("i", $book_id)) {
        echo json_encode(['success' => false, 'message' => 'Error binding borrowed_books: ' . $stmt->error]);
        exit();
    }
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Error executing borrowed_books delete: ' . $stmt->error]);
        exit();
    }
    $stmt->close();

    // Now delete the book itself
    $stmt = $conn->prepare("DELETE FROM books WHERE book_id = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Error preparing statement: ' . $conn->error]);
        exit();
    }
    if (!$stmt->bind_param("i", $book_id)) {
        echo json_encode(['success' => false, 'message' => 'Error binding parameters: ' . $stmt->error]);
        exit();
    }
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Error executing statement: ' . $stmt->error]);
        exit();
    }
    $stmt->close();
    echo json_encode(['success' => true, 'message' => 'Book deleted successfully']);
    exit();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}
?> 