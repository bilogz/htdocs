<?php
session_name('admin_session');
session_start();
require_once 'config.php';

// Check if user is admin (improved validation)
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true || 
    !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    
    // Log the error for debugging
    error_log('Unauthorized access attempt to get_student_details.php: ' . print_r($_SESSION, true));
    
    echo json_encode([
        'success' => false, 
        'message' => 'Unauthorized access. Please log in as an administrator.',
        'session_error' => true
    ]);
    exit();
}

if (!isset($_GET['student_id'])) {
    echo json_encode(['success' => false, 'message' => 'Student ID is required']);
    exit();
}

$student_id = $_GET['student_id'];

try {
    // Get student details
    $stmt = $conn->prepare("SELECT * FROM users WHERE student_id = ? AND user_type = 'student'");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    $stmt->close();

    if (!$student) {
        throw new Exception("Student not found");
    }

    // Get borrowed books with more details
    $stmt = $conn->prepare("
        SELECT 
            bs.*,
            b.title,
            b.author,
            b.cover_image,
            DATE_FORMAT(bs.schedule_date, '%Y-%m-%d') as borrow_date,
            DATE_FORMAT(DATE_ADD(bs.schedule_date, INTERVAL 14 DAY), '%Y-%m-%d') as due_date,
            CASE 
                WHEN bs.status = 'confirmed' AND DATE_ADD(bs.schedule_date, INTERVAL 14 DAY) < CURDATE() 
                THEN 'overdue'
                ELSE bs.status 
            END as status
        FROM book_schedules bs
        JOIN books b ON bs.book_id = b.book_id
        WHERE bs.student_id = ?
        ORDER BY bs.schedule_date DESC
    ");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $borrowed_books = [];
    while ($row = $result->fetch_assoc()) {
        $borrowed_books[] = $row;
    }
    $stmt->close();

    // Add debug information
    error_log("Student ID: " . $student_id);
    error_log("Student found: " . ($student ? 'Yes' : 'No'));
    error_log("Number of borrowed books: " . count($borrowed_books));

    echo json_encode([
        'success' => true,
        'student' => $student,
        'borrowed_books' => $borrowed_books
    ]);

} catch (Exception $e) {
    error_log("Error in get_student_details.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 