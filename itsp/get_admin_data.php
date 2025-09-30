<?php
session_name('admin_session');
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define headers for AJAX response
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Check if user is logged in as admin
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    echo json_encode([
        'success' => false,
        'message' => 'Session expired or invalid',
        'session_error' => true
    ]);
    exit();
}

require_once 'config.php';

// Get the requested data type
$data_type = isset($_GET['type']) ? $_GET['type'] : 'all';

$response = [
    'success' => true,
    'timestamp' => date('Y-m-d H:i:s'),
    'data' => []
];

// Handle different data type requests
switch ($data_type) {
    case 'books':
        // Fetch books data
        $books_query = "SELECT * FROM books ORDER BY book_id DESC";
        $books_result = $conn->query($books_query);
        
        if ($books_result) {
            $books = [];
            while ($book = $books_result->fetch_assoc()) {
                $books[] = $book;
            }
            $response['data'] = $books;
        } else {
            $response['error'] = $conn->error;
        }
        break;
        
    case 'ebooks':
        // Fetch ebooks data
        $ebooks_query = "SELECT * FROM ebooks ORDER BY id DESC";
        $ebooks_result = $conn->query($ebooks_query);
        
        if ($ebooks_result) {
            $ebooks = [];
            while ($ebook = $ebooks_result->fetch_assoc()) {
                $ebooks[] = $ebook;
            }
            $response['data'] = $ebooks;
        } else {
            $response['error'] = $conn->error;
        }
        break;
        
    case 'borrow_requests':
        // Fetch pending borrow requests
        $pending_schedules_query = "SELECT bs.*, b.title as book_title, b.cover_image, u.full_name as student_name, u.email as student_email
            FROM book_schedules bs
            JOIN books b ON bs.book_id = b.book_id
            JOIN users u ON bs.student_id = u.student_id
            WHERE bs.status = 'pending'
            ORDER BY bs.schedule_date DESC";
        $pending_schedules_result = $conn->query($pending_schedules_query);
        
        if ($pending_schedules_result) {
            $requests = [];
            while ($request = $pending_schedules_result->fetch_assoc()) {
                $requests[] = $request;
            }
            $response['data'] = $requests;
            $response['pending_count'] = count($requests);
        } else {
            $response['error'] = $conn->error;
        }
        break;
        
    case 'return_requests':
        // Fetch pending return requests
        $return_query = "SELECT bb.*, b.title as book_title, b.cover_image, 
                       u.full_name as student_name, u.email as student_email,
                       DATEDIFF(CURRENT_DATE, bb.due_date) as days_overdue,
                       DATEDIFF(CURRENT_DATE, bb.due_date) * 1 as overdue_fee
                       FROM borrowed_books bb 
                       JOIN books b ON bb.book_id = b.book_id 
                       JOIN users u ON bb.student_id = u.student_id 
                       WHERE bb.return_date IS NOT NULL 
                       AND bb.admin_confirmed_return = 0
                       ORDER BY bb.return_date DESC";
        $return_result = $conn->query($return_query);
        
        if ($return_result) {
            $returns = [];
            while ($return = $return_result->fetch_assoc()) {
                $returns[] = $return;
            }
            $response['data'] = $returns;
            $response['return_count'] = count($returns);
        } else {
            $response['error'] = $conn->error;
        }
        break;
        
    case 'overdue':
        // Fetch overdue books
        $overdue_query = "SELECT bb.*, b.title as book_title, u.full_name as student_name, u.email as student_email,
            DATEDIFF(CURRENT_DATE, bb.due_date) as days_overdue,
            DATEDIFF(CURRENT_DATE, bb.due_date) * 1 as overdue_fee
            FROM borrowed_books bb 
            JOIN books b ON bb.book_id = b.book_id 
            JOIN users u ON bb.student_id = u.student_id 
            WHERE bb.return_date IS NULL 
            AND bb.due_date < CURRENT_DATE
            AND bb.admin_confirmed_return = 0
            ORDER BY bb.due_date ASC";
        $overdue_result = $conn->query($overdue_query);
        
        if ($overdue_result) {
            $overdue = [];
            while ($book = $overdue_result->fetch_assoc()) {
                $overdue[] = $book;
            }
            $response['data'] = $overdue;
            $response['overdue_count'] = count($overdue);
        } else {
            $response['error'] = $conn->error;
        }
        break;
        
    case 'dashboard_stats':
        // Get counts for dashboard stats
        $stats = [];
        
        // Borrow requests count
        $borrow_query = "SELECT COUNT(*) as count FROM book_schedules WHERE status = 'pending'";
        $result = $conn->query($borrow_query);
        $stats['pending_borrows'] = $result ? $result->fetch_assoc()['count'] : 0;
        
        // Return requests count
        $return_query = "SELECT COUNT(*) as count FROM borrowed_books WHERE return_date IS NOT NULL AND admin_confirmed_return = 0";
        $result = $conn->query($return_query);
        $stats['pending_returns'] = $result ? $result->fetch_assoc()['count'] : 0;
        
        // Overdue books count
        $overdue_query = "SELECT COUNT(*) as count FROM borrowed_books 
                         WHERE return_date IS NULL AND due_date < CURRENT_DATE AND admin_confirmed_return = 0";
        $result = $conn->query($overdue_query);
        $stats['overdue_books'] = $result ? $result->fetch_assoc()['count'] : 0;
        
        // Total books count
        $books_query = "SELECT COUNT(*) as count FROM books";
        $result = $conn->query($books_query);
        $stats['total_books'] = $result ? $result->fetch_assoc()['count'] : 0;
        
        // Total students count
        $students_query = "SELECT COUNT(*) as count FROM users WHERE user_type = 'student'";
        $result = $conn->query($students_query);
        $stats['total_students'] = $result ? $result->fetch_assoc()['count'] : 0;
        
        $response['data'] = $stats;
        break;
        
    default:
        // Return all basic stats for dashboard
        $response['data'] = [
            'pending_borrows' => 0,
            'pending_returns' => 0,
            'overdue_books' => 0,
            'total_books' => 0,
            'total_students' => 0
        ];
        
        // Borrow requests count
        $borrow_query = "SELECT COUNT(*) as count FROM book_schedules WHERE status = 'pending'";
        $result = $conn->query($borrow_query);
        $response['data']['pending_borrows'] = $result ? $result->fetch_assoc()['count'] : 0;
        
        // Return requests count
        $return_query = "SELECT COUNT(*) as count FROM borrowed_books WHERE return_date IS NOT NULL AND admin_confirmed_return = 0";
        $result = $conn->query($return_query);
        $response['data']['pending_returns'] = $result ? $result->fetch_assoc()['count'] : 0;
        
        // Overdue books count
        $overdue_query = "SELECT COUNT(*) as count FROM borrowed_books 
                         WHERE return_date IS NULL AND due_date < CURRENT_DATE AND admin_confirmed_return = 0";
        $result = $conn->query($overdue_query);
        $response['data']['overdue_books'] = $result ? $result->fetch_assoc()['count'] : 0;
        break;
}

// Return the JSON response
echo json_encode($response);
?> 