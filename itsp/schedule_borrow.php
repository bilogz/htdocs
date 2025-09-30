<?php
session_start();
require_once 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Check database connection
if ($conn->connect_error) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection error. Please try again.'
    ]);
    exit;
}

if (!isset($_SESSION['student_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in first']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$book_id = isset($_POST['book_id']) ? intval($_POST['book_id']) : 0;
$student_id = $_SESSION['student_id'];

if ($book_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid book ID']);
    exit;
}

try {
    // Check if book exists and is available
    $check_book = $conn->prepare("SELECT status, stock FROM books WHERE book_id = ?");
    if (!$check_book) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $check_book->bind_param("i", $book_id);
    if (!$check_book->execute()) {
        throw new Exception("Database error: " . $check_book->error);
    }
    $book_result = $check_book->get_result();
    $book = $book_result->fetch_assoc();
    
    if (!$book) {
        echo json_encode(['success' => false, 'message' => 'Book not found']);
        exit;
    }
    
    if ($book['status'] !== 'Available' || $book['stock'] <= 0) {
        echo json_encode(['success' => false, 'message' => 'Book is not available for borrowing']);
        exit;
    }

    // Check if student already has a pending schedule for this book
    $check_schedule = $conn->prepare("SELECT 1 FROM book_schedules WHERE book_id = ? AND student_id = ? AND status = 'pending'");
    if (!$check_schedule) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $check_schedule->bind_param("ii", $book_id, $student_id);
    if (!$check_schedule->execute()) {
        throw new Exception("Database error: " . $check_schedule->error);
    }
    if ($check_schedule->get_result()->num_rows > 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'You already have a pending schedule for this book. Please wait for it to be confirmed or cancelled.'
        ]);
        exit;
    }

    // Check if student has too many pending schedules (limit to 3)
    $check_pending_count = $conn->prepare("SELECT COUNT(*) as pending_count FROM book_schedules WHERE student_id = ? AND status = 'pending'");
    if (!$check_pending_count) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $check_pending_count->bind_param("i", $student_id);
    if (!$check_pending_count->execute()) {
        throw new Exception("Database error: " . $check_pending_count->error);
    }
    $pending_count = $check_pending_count->get_result()->fetch_assoc()['pending_count'];
    
    if ($pending_count >= 3) {
        echo json_encode([
            'success' => false,
            'message' => 'You have reached the maximum limit of 3 pending schedules. Please wait for some to be confirmed or cancelled.'
        ]);
        exit;
    }

    // Set schedule time to 5 hours from now
    $schedule_date = date('Y-m-d H:i:s', strtotime('+5 hours'));
    
    // Insert the schedule
    $insert_schedule = $conn->prepare("INSERT INTO book_schedules (book_id, student_id, schedule_date, status) VALUES (?, ?, ?, 'pending')");
    if (!$insert_schedule) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $insert_schedule->bind_param("iis", $book_id, $student_id, $schedule_date);
    if (!$insert_schedule->execute()) {
        throw new Exception("Database error: " . $insert_schedule->error);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Book borrow request submitted successfully. Please wait for admin approval.'
    ]);
    
} catch (Exception $e) {
    error_log("Schedule borrow error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while processing your request. Please try again.'
    ]);
} finally {
    // Close all prepared statements
    if (isset($check_book)) $check_book->close();
    if (isset($check_schedule)) $check_schedule->close();
    if (isset($check_pending_count)) $check_pending_count->close();
    if (isset($insert_schedule)) $insert_schedule->close();
}
?> 