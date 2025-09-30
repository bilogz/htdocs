<?php
session_start();
file_put_contents('debug_session.txt', print_r($_SESSION, true));
require 'config.php';

// Only allow students to borrow books
if (!isset($_SESSION['student_id']) || ($_SESSION['user_type'] ?? '') !== 'student') {
    die(json_encode(['success' => false, 'message' => 'Please login as a student to borrow books']));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_id'])) {
    $book_id = $_POST['book_id'];
    $student_id = $_SESSION['student_id'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Check if book is available
        $stmt = $conn->prepare("SELECT stock FROM books WHERE book_id = ? AND status = 'Available' FOR UPDATE");
        if (!$stmt) {
            file_put_contents('debug_borrow.txt', 'Error preparing statement: ' . $conn->error . "\n", FILE_APPEND);
            throw new Exception("Error preparing statement: " . $conn->error);
        }
        
        if (!$stmt->bind_param("i", $book_id)) {
            file_put_contents('debug_borrow.txt', 'Error binding parameters: ' . $stmt->error . "\n", FILE_APPEND);
            throw new Exception("Error binding parameters: " . $stmt->error);
        }
        
        if (!$stmt->execute()) {
            file_put_contents('debug_borrow.txt', 'Error executing statement: ' . $stmt->error . "\n", FILE_APPEND);
            throw new Exception("Error executing statement: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $book = $result->fetch_assoc();
        file_put_contents('debug_borrow.txt', 'Book: ' . print_r($book, true) . "\n", FILE_APPEND);
        
        if (!$book || $book['stock'] <= 0) {
            file_put_contents('debug_borrow.txt', 'Book not available for borrowing\n', FILE_APPEND);
            throw new Exception("Book is not available for borrowing");
        }
        
        // Check if student has already borrowed this book
        $stmt = $conn->prepare("SELECT * FROM borrowed_books WHERE student_id = ? AND book_id = ? AND return_date IS NULL");
        if (!$stmt) {
            file_put_contents('debug_borrow.txt', 'Error preparing statement (borrowed_books): ' . $conn->error . "\n", FILE_APPEND);
            throw new Exception("Error preparing statement: " . $conn->error);
        }
        
        if (!$stmt->bind_param("ii", $student_id, $book_id)) {
            file_put_contents('debug_borrow.txt', 'Error binding parameters (borrowed_books): ' . $stmt->error . "\n", FILE_APPEND);
            throw new Exception("Error binding parameters: " . $stmt->error);
        }
        
        if (!$stmt->execute()) {
            file_put_contents('debug_borrow.txt', 'Error executing statement (borrowed_books): ' . $stmt->error . "\n", FILE_APPEND);
            throw new Exception("Error executing statement: " . $stmt->error);
        }
        
        $borrowed_result = $stmt->get_result();
        file_put_contents('debug_borrow.txt', 'Borrowed result num_rows: ' . $borrowed_result->num_rows . "\n", FILE_APPEND);
        if ($borrowed_result->num_rows > 0) {
            file_put_contents('debug_borrow.txt', 'Already borrowed this book\n', FILE_APPEND);
            throw new Exception("You have already borrowed this book");
        }
        
        // Create borrowed_books table if it doesn't exist
        $create_table = "
        CREATE TABLE IF NOT EXISTS borrowed_books (
            borrow_id INT PRIMARY KEY AUTO_INCREMENT,
            student_id INT,
            book_id INT,
            borrow_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            due_date TIMESTAMP,
            return_date TIMESTAMP NULL,
            FOREIGN KEY (student_id) REFERENCES users(student_id),
            FOREIGN KEY (book_id) REFERENCES books(book_id)
        )";
        
        if (!$conn->query($create_table)) {
            throw new Exception("Error creating borrowed_books table: " . $conn->error);
        }
        
        // Insert borrow record
        $due_date = date('Y-m-d H:i:s', strtotime('+14 days')); // 2 weeks borrowing period
        $stmt = $conn->prepare("INSERT INTO borrowed_books (student_id, book_id, due_date) VALUES (?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Error preparing statement: " . $conn->error);
        }
        
        if (!$stmt->bind_param("iis", $student_id, $book_id, $due_date)) {
            throw new Exception("Error binding parameters: " . $stmt->error);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Error executing statement: " . $stmt->error);
        }
        
        // Update book stock
        $stmt = $conn->prepare("UPDATE books SET stock = stock - 1 WHERE book_id = ?");
        if (!$stmt) {
            throw new Exception("Error preparing statement: " . $conn->error);
        }
        
        if (!$stmt->bind_param("i", $book_id)) {
            throw new Exception("Error binding parameters: " . $stmt->error);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Error executing statement: " . $stmt->error);
        }
        
        // Update book status if stock becomes 0
        $stmt = $conn->prepare("UPDATE books SET status = CASE WHEN stock = 0 THEN 'Unavailable' ELSE status END WHERE book_id = ?");
        if (!$stmt) {
            throw new Exception("Error preparing statement: " . $conn->error);
        }
        
        if (!$stmt->bind_param("i", $book_id)) {
            throw new Exception("Error binding parameters: " . $stmt->error);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Error executing statement: " . $stmt->error);
        }
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Book borrowed successfully. Due date: ' . $due_date
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        file_put_contents('debug_borrow.txt', 'Exception: ' . $e->getMessage() . "\n", FILE_APPEND);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    
    $stmt->close();
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
}
?> 