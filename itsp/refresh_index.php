<?php
session_start();
require 'config.php';

// Check if it's an AJAX request
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if (!$is_ajax) {
    die(json_encode(['success' => false, 'message' => 'Invalid request']));
}

if (isset($_POST['action']) && $_POST['action'] === 'refresh_books') {
    // Clear any existing output buffering
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set headers to prevent caching
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Get the latest books data
    $books_query = "SELECT * FROM books WHERE status = 'Available' ORDER BY book_id DESC";
    $books_result = $conn->query($books_query);
    
    $books = [];
    if ($books_result && $books_result->num_rows > 0) {
        while ($book = $books_result->fetch_assoc()) {
            $books[] = [
                'book_id' => $book['book_id'],
                'title' => $book['title'],
                'author' => $book['author'],
                'category' => $book['category'],
                'description' => $book['description'],
                'cover_image' => $book['cover_image'],
                'available_stock' => $book['available_stock'],
                'status' => $book['status']
            ];
        }
    }
    
    // Return the updated books data
    echo json_encode([
        'success' => true,
        'books' => $books
    ]);
    exit();
}

// If no valid action was provided
echo json_encode(['success' => false, 'message' => 'Invalid action']);
exit();
?> 