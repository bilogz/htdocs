<?php
session_name('admin_session');
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define $is_ajax variable
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

require_once 'config.php';

// Debug session information
error_log("Admin page session data: " . print_r($_SESSION, true));

// Strict session validation
function validateAdminSession() {
    global $is_ajax;
    
    if (!isset($_SESSION['is_admin']) || 
        !$_SESSION['is_admin'] || 
        !isset($_SESSION['admin_id']) || 
        !isset($_SESSION['user_type']) || 
        $_SESSION['user_type'] !== 'admin') {
        
        error_log("Invalid admin session. Session data: " . print_r($_SESSION, true));
        
        // Clear any existing session
        session_unset();
        session_destroy();
        
        if (!$is_ajax) {
            // Start a new session for the error message
            session_start();
            $_SESSION['error'] = 'Please log in as an administrator to access this page.';
            
            // Redirect to admin login
            header('Location: admin_login.php');
            exit();
        } else {
            // For AJAX requests, return JSON response
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false, 
                'message' => 'Session expired. Please refresh and log in again.',
                'session_error' => true
            ]);
            exit();
        }
    }
}

// Call the validation function
validateAdminSession();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_book':
                $cover_image = '';
                if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === 0) {
                    $cover_dir = 'assets/images/';
                    $cover_image = time() . '_' . basename($_FILES['cover_image']['name']);
                    move_uploaded_file($_FILES['cover_image']['tmp_name'], $cover_dir . $cover_image);
                }
                
                // Ensure status is properly capitalized and consistent with available_stock
                $status = $_POST['status'];
                $available_stock = (int)$_POST['available_stock'];
                
                // If there's available stock, make sure status is "Available"
                if ($available_stock > 0 && strtolower($status) !== 'borrowed') {
                    $status = 'Available';
                }
                // If there's no available stock, make sure status is "Unavailable" unless it's specifically "Borrowed"
                elseif ($available_stock <= 0 && strtolower($status) !== 'borrowed') {
                    $status = 'Unavailable';
                }
                // Ensure consistent capitalization for "Borrowed" status
                elseif (strtolower($status) === 'borrowed') {
                    $status = 'Borrowed';
                }
                
                $stmt = $conn->prepare("INSERT INTO books (title, author, category, description, cover_image, available_stock, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                if (!$stmt) {
                    die("Error preparing statement: " . $conn->error);
                }
                $stmt->bind_param("ssssssi", 
                    $_POST['title'],
                    $_POST['author'],
                    $_POST['category'],
                    $_POST['description'],
                    $cover_image,
                    $available_stock, // Use the variable, not $_POST
                    $status // Use the corrected status, not $_POST
                );
                $stmt->execute();
                $stmt->close();
                break;
                
            case 'create_test_returns':
                // Get a book that is currently borrowed
                $borrowed_query = "SELECT bb.*, b.title as book_title, u.full_name as student_name 
                                 FROM borrowed_books bb 
                                 JOIN books b ON bb.book_id = b.book_id 
                                 JOIN users u ON bb.student_id = u.student_id 
                                 WHERE bb.return_date IS NULL 
                                 LIMIT 2";
                $borrowed_result = $conn->query($borrowed_query);
                
                if ($borrowed_result && $borrowed_result->num_rows > 0) {
                    $count = 0;
                    while ($book = $borrowed_result->fetch_assoc()) {
                        // Mark the book as returned but not confirmed
                        $update_query = "UPDATE borrowed_books 
                                       SET return_date = NOW(), 
                                           admin_confirmed_return = 0,
                                           status = 'returned' 
                                       WHERE record_id = ?";
                        $stmt = $conn->prepare($update_query);
                        $stmt->bind_param("i", $book['record_id']);
                        
                        if ($stmt->execute()) {
                            $count++;
                        }
                    }
                    $_SESSION['success'] = "Created $count test return requests.";
                } else {
                    // If no borrowed books found, create new borrow and return records
                    $book_query = "SELECT book_id, title FROM books WHERE status = 'available' LIMIT 2";
                    $book_result = $conn->query($book_query);
                    
                    $student_query = "SELECT student_id, full_name FROM users WHERE user_type = 'student' LIMIT 2";
                    $student_result = $conn->query($student_query);
                    
                    if ($book_result->num_rows > 0 && $student_result->num_rows > 0) {
                        $count = 0;
                        while ($book = $book_result->fetch_assoc()) {
                            $student = $student_result->fetch_assoc();
                            if (!$student) {
                                $student_result->data_seek(0);
                                $student = $student_result->fetch_assoc();
                            }
                            
                            // Create a new borrow record that has been returned
                            $insert_query = "INSERT INTO borrowed_books 
                                           (book_id, student_id, borrow_date, due_date, return_date, admin_confirmed_return, status) 
                                           VALUES (?, ?, 
                                                   DATE_SUB(NOW(), INTERVAL 14 DAY), 
                                                   DATE_SUB(NOW(), INTERVAL 7 DAY), 
                                                   NOW(), 0, 'returned')";
                            $stmt = $conn->prepare($insert_query);
                            $stmt->bind_param("ii", $book['book_id'], $student['student_id']);
                            
                            if ($stmt->execute()) {
                                $count++;
                            }
                        }
                        $_SESSION['success'] = "Created $count new test return requests.";
                    } else {
                        $_SESSION['error'] = "Could not create test data. Please add books and students first.";
                    }
                }
                // Redirect to refresh the page
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit();
                break;
                
            case 'approve_return':
                if (isset($_POST['record_id'])) {
                    // Start a transaction
                    $conn->begin_transaction();
                    
                    try {
                        // Get book_id from the borrowed_books record
                        $get_book_stmt = $conn->prepare("SELECT book_id FROM borrowed_books WHERE record_id = ?");
                        $get_book_stmt->bind_param("i", $_POST['record_id']);
                        $get_book_stmt->execute();
                        $result = $get_book_stmt->get_result();
                        
                        if ($row = $result->fetch_assoc()) {
                            $book_id = $row['book_id'];
                            
                            // Update borrowed_books record
                            $update_borrow = $conn->prepare("UPDATE borrowed_books SET admin_confirmed_return = 1, status = 'completed' WHERE record_id = ?");
                            $update_borrow->bind_param("i", $_POST['record_id']);
                            $update_borrow->execute();
                            
                            // Increase book available_stock by 1
                            $update_stock = $conn->prepare("UPDATE books SET available_stock = available_stock + 1 WHERE book_id = ?");
                            $update_stock->bind_param("i", $book_id);
                            $update_stock->execute();
                            
                            // Update book status based on stock
                            $update_status = $conn->prepare("UPDATE books SET status = 'Available' WHERE book_id = ? AND available_stock > 0");
                            $update_status->bind_param("i", $book_id);
                            $update_status->execute();
                            
                            $conn->commit();
                            $_SESSION['success'] = "Return request approved successfully.";
                        } else {
                            throw new Exception("Book record not found");
                        }
                    } catch (Exception $e) {
                        $conn->rollback();
                        $_SESSION['error'] = "Error approving return request: " . $e->getMessage();
                    }
                    
                    // Redirect to refresh the page
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit();
                }
                break;
                
            case 'reject_return':
                if (isset($_POST['record_id'])) {
                    $stmt = $conn->prepare("UPDATE borrowed_books SET return_date = NULL, status = 'borrowed' WHERE record_id = ?");
                    $stmt->bind_param("i", $_POST['record_id']);
                    if ($stmt->execute()) {
                        $_SESSION['success'] = "Return request rejected successfully.";
                    } else {
                        $_SESSION['error'] = "Error rejecting return request: " . $conn->error;
                    }
                }
                // Redirect to refresh the page
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit();
                break;

            case 'edit_book':
                $cover_image = $_POST['current_cover'];
                if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === 0) {
                    $cover_dir = 'assets/images/';
                    $cover_image = time() . '_' . basename($_FILES['cover_image']['name']);
                    move_uploaded_file($_FILES['cover_image']['tmp_name'], $cover_dir . $cover_image);
                    // Delete old cover if exists
                    if ($_POST['current_cover'] && file_exists($cover_dir . $_POST['current_cover'])) {
                        unlink($cover_dir . $_POST['current_cover']);
                    }
                }
                
                // Get the status and stock values from the form
                $status = $_POST['status'];
                $available_stock = (int)$_POST['available_stock'];
                
                // Log the received values for debugging
                error_log("Edit Book [ID: {$_POST['book_id']}] - Received Status: " . $status . ", Stock: " . $available_stock);
                
                // Only apply auto-corrections in certain cases
                if ($status === 'Available' && $available_stock <= 0) {
                    // If admin sets Available but stock is 0, override to Unavailable
                    $old_status = $status;
                    $status = 'Unavailable';
                    error_log("Auto-corrected Status from {$old_status} to {$status} - Book has zero stock");
                }
                elseif ($status === 'Unavailable' && $available_stock > 0) {
                    // Let admin choose to make a book Unavailable even if stock > 0
                    error_log("Keeping Unavailable status despite stock > 0 (admin decision)");
                }
                elseif ($status === 'Borrowed') {
                    error_log("Book marked as Borrowed by admin - This is a manual status option");
                }
                
                // Insert the updated book information
                $stmt = $conn->prepare("UPDATE books SET title = ?, author = ?, category = ?, description = ?, cover_image = ?, available_stock = ?, status = ? WHERE book_id = ?");
                if (!$stmt) {
                    echo json_encode(['success' => false, 'message' => 'Error preparing statement: ' . $conn->error]);
                    exit();
                }
                if (!$stmt->bind_param("sssssisi",
                    $_POST['title'],
                    $_POST['author'],
                    $_POST['category'],
                    $_POST['description'],
                    $cover_image,
                    $available_stock,
                    $status,
                    $_POST['book_id']
                )) {
                    echo json_encode(['success' => false, 'message' => 'Error binding parameters: ' . $stmt->error]);
                    exit();
                }
                if (!$stmt->execute()) {
                    echo json_encode(['success' => false, 'message' => 'Error executing statement: ' . $stmt->error]);
                    exit();
                }
                echo json_encode(['success' => true, 'message' => 'Book updated successfully']);
                $stmt->close();
                exit();
                break;

            case 'delete_book':
                // Get cover image before deleting
                $stmt = $conn->prepare("SELECT cover_image FROM books WHERE book_id = ?");
                $stmt->bind_param("i", $_POST['book_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                $book = $result->fetch_assoc();
                
                // Delete from database
                $delete_stmt = $conn->prepare("DELETE FROM books WHERE book_id = ?");
                $delete_stmt->bind_param("i", $_POST['book_id']);
                $delete_stmt->execute();
                $delete_stmt->close();
                
                // Delete cover image
                if ($book && $book['cover_image']) {
                    @unlink('assets/images/' . $book['cover_image']);
                }
                
                // Return JSON response for AJAX requests
                if ($is_ajax) {
                    echo json_encode(['success' => true, 'message' => 'Book deleted successfully']);
                    exit();
                }
                break;

            case 'add_ebook':
                // Forward to the add_new_ebook.php handler
                include 'add_new_ebook.php';
                exit(); // Stop processing after include
                break;

            case 'edit_ebook':
                // Redirect to the handle_ebook.php file for processing
                if (!isset($_POST['ebook_id'])) {
                    echo json_encode(['success' => false, 'message' => 'Ebook ID is required']);
                    exit();
                }
                
                // Forward the request to handle_ebook.php
                include 'handle_ebook.php';
                exit(); // This ensures we don't continue processing after the include
                break;

            case 'delete_ebook':
                // Get file paths before deleting
                $stmt = $conn->prepare("SELECT cover_image, file_path FROM ebooks WHERE id = ?");
                $stmt->bind_param("i", $_POST['ebook_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                $ebook = $result->fetch_assoc();
                
                // Delete from database
                $delete_stmt = $conn->prepare("DELETE FROM ebooks WHERE id = ?");
                $delete_stmt->bind_param("i", $_POST['ebook_id']);
                $delete_stmt->execute();
                $delete_stmt->close();
                
                // Delete files
                if ($ebook) {
                    @unlink('assets/images/' . $ebook['cover_image']);
                    @unlink('ebooks/' . $ebook['file_path']);
                }
                
                // Return JSON response for AJAX requests
                if ($is_ajax) {
                    echo json_encode(['success' => true, 'message' => 'eBook deleted successfully']);
                    exit();
                }
                break;

            case 'approve_borrow':
                $schedule_id = $_POST['schedule_id'];
                
                // Start transaction
                $conn->begin_transaction();
                
                try {
                    // Get schedule information
                    $schedule_query = "SELECT bs.*, b.title, b.available_stock 
                                      FROM book_schedules bs 
                                      JOIN books b ON bs.book_id = b.book_id 
                                      WHERE bs.schedule_id = ?";
                    $schedule_stmt = $conn->prepare($schedule_query);
                    if (!$schedule_stmt) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }
                    $schedule_stmt->bind_param("i", $schedule_id);
                    $schedule_stmt->execute();
                    $schedule_result = $schedule_stmt->get_result();
                    $schedule = $schedule_result->fetch_assoc();
                    
                    // Check if book is available
                    if ($schedule['available_stock'] <= 0) {
                        throw new Exception("Book is not available for borrowing");
                    }
                    
                    // Update schedule status
                    $update_schedule = $conn->prepare("UPDATE book_schedules SET status = 'confirmed' WHERE schedule_id = ?");
                    if (!$update_schedule) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }
                    $update_schedule->bind_param("i", $schedule_id);
                    $update_schedule->execute();
                    
                    // Create record in borrowed_books
                    $insert_borrow = $conn->prepare("INSERT INTO borrowed_books (student_id, book_id, borrow_date, due_date, purpose) VALUES (?, ?, CURRENT_TIMESTAMP, DATE_ADD(CURRENT_DATE, INTERVAL 14 DAY), ?)");
                    if (!$insert_borrow) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }
                    $insert_borrow->bind_param("iis", $schedule['student_id'], $schedule['book_id'], $schedule['purpose']);
                    $insert_borrow->execute();
                    
                    // Update book stock
                    $update_stock = $conn->prepare("UPDATE books SET available_stock = available_stock - 1 WHERE book_id = ?");
                    if (!$update_stock) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }
                    $update_stock->bind_param("i", $schedule['book_id']);
                    $update_stock->execute();
                    
                    // Create notification
                    $due_date = date('Y-m-d', strtotime('+14 days'));
                    $message = "Your borrow request for '{$schedule['title']}' has been approved. Due date: {$due_date}";
                    $notif_stmt = $conn->prepare("INSERT INTO notifications (student_id, message, type) VALUES (?, ?, 'borrow_approved')");
                    if (!$notif_stmt) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }
                    $notif_stmt->bind_param("is", $schedule['student_id'], $message);
                    $notif_stmt->execute();
                    
                    // Commit transaction
                    $conn->commit();
                    
                    if ($is_ajax) {
                        echo json_encode(['success' => true, 'message' => 'Borrow request approved successfully']);
                    }
                    
                } catch (Exception $e) {
                    // Rollback transaction on error
                    $conn->rollback();
                    
                    if ($is_ajax) {
                        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                    } else {
                        die("Error: " . $e->getMessage());
                    }
                }
                break;

            case 'reject_borrow':
                $schedule_id = $_POST['schedule_id'];
                
                // Start transaction
                $conn->begin_transaction();
                
                try {
                    // Get schedule information
                    $schedule_query = "SELECT bs.*, b.title 
                                      FROM book_schedules bs 
                                      JOIN books b ON bs.book_id = b.book_id 
                                      WHERE bs.schedule_id = ?";
                    $schedule_stmt = $conn->prepare($schedule_query);
                    if (!$schedule_stmt) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }
                    $schedule_stmt->bind_param("i", $schedule_id);
                    $schedule_stmt->execute();
                    $schedule_result = $schedule_stmt->get_result();
                    $schedule = $schedule_result->fetch_assoc();
                    
                    // Update schedule status
                    $update_schedule = $conn->prepare("UPDATE book_schedules SET status = 'cancelled' WHERE schedule_id = ?");
                    if (!$update_schedule) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }
                    $update_schedule->bind_param("i", $schedule_id);
                    $update_schedule->execute();
                    
                    // Create notification
                    $message = "Your borrow request for '{$schedule['title']}' has been rejected.";
                    $notif_stmt = $conn->prepare("INSERT INTO notifications (student_id, message, type) VALUES (?, ?, 'borrow_rejected')");
                    if (!$notif_stmt) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }
                    $notif_stmt->bind_param("is", $schedule['student_id'], $message);
                    $notif_stmt->execute();
                    
                    // Commit transaction
                    $conn->commit();
                    
                    if ($is_ajax) {
                        echo json_encode(['success' => true, 'message' => 'Borrow request rejected successfully']);
                    }
                    
                } catch (Exception $e) {
                    // Rollback transaction on error
                    $conn->rollback();
                    
                    if ($is_ajax) {
                        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                    } else {
                        die("Error: " . $e->getMessage());
                    }
                }
                break;

            case 'approve_return':
                $record_id = $_POST['record_id'];
                $stmt = $conn->prepare("UPDATE borrowed_books SET admin_confirmed_return = 1, return_date = CURRENT_TIMESTAMP WHERE record_id = ?");
                if (!$stmt) {
                    die("Prepare failed: " . $conn->error);
                }
                $stmt->bind_param("i", $record_id);
                $stmt->execute();
                
                // Get student and book information for notification
                $info_query = "SELECT bb.student_id, b.title 
                              FROM borrowed_books bb 
                              JOIN books b ON bb.book_id = b.book_id 
                              WHERE bb.record_id = ?";
                $info_stmt = $conn->prepare($info_query);
                if (!$info_stmt) {
                    die("Prepare failed: " . $conn->error);
                }
                $info_stmt->bind_param("i", $record_id);
                $info_stmt->execute();
                $info_result = $info_stmt->get_result();
                $info = $info_result->fetch_assoc();
                
                // Create notification
                if ($info) {
                    $message = "Your return of '{$info['title']}' has been confirmed.";
                    $notif_stmt = $conn->prepare("INSERT INTO notifications (student_id, message, type) VALUES (?, ?, 'return_approved')");
                    if (!$notif_stmt) {
                        die("Prepare failed: " . $conn->error);
                    }
                    $notif_stmt->bind_param("is", $info['student_id'], $message);
                    $notif_stmt->execute();
                }
                
                // Update book stock
                $update_stock = $conn->prepare("UPDATE books b 
                    JOIN borrowed_books bb ON b.book_id = bb.book_id 
                    SET b.available_stock = b.available_stock + 1 
                    WHERE bb.record_id = ?");
                if (!$update_stock) {
                    die("Prepare failed: " . $conn->error);
                }
                $update_stock->bind_param("i", $record_id);
                $update_stock->execute();
                
                $stmt->close();
                $info_stmt->close();
                $update_stock->close();
                break;

            case 'reject_return':
                $record_id = $_POST['record_id'];
                $stmt = $conn->prepare("UPDATE borrowed_books SET return_date = NULL WHERE record_id = ?");
                if (!$stmt) {
                    die("Prepare failed: " . $conn->error);
                }
                $stmt->bind_param("i", $record_id);
                $stmt->execute();
                $stmt->close();
                break;
        }
    }
}

// Fetch books and ebooks
$books_query = "SELECT * FROM books ORDER BY book_id DESC";
$books_result = $conn->query($books_query);

$ebooks_query = "SELECT * FROM ebooks ORDER BY id DESC";
$ebooks_result = $conn->query($ebooks_query);

// Fetch pending borrow requests from book_schedules (pending approval)
$pending_schedules_query = "SELECT bs.*, b.title as book_title, b.cover_image, u.full_name as student_name, u.email as student_email
    FROM book_schedules bs
    JOIN books b ON bs.book_id = b.book_id
    JOIN users u ON bs.student_id = u.student_id
    WHERE bs.status = 'pending'
    ORDER BY bs.schedule_date DESC";
$pending_schedules_result = $conn->query($pending_schedules_query);

// Debug information
if (!$pending_schedules_result) {
    error_log("SQL Error in pending schedules query: " . $conn->error);
}

// Get count of pending requests
$pending_count = $pending_schedules_result ? $pending_schedules_result->num_rows : 0;

// Add AJAX endpoint for real-time updates
if ($is_ajax) {
    $response = [
        'pending_requests' => [],
        'pending_count' => $pending_count
    ];
    
    if ($pending_schedules_result) {
        while ($request = $pending_schedules_result->fetch_assoc()) {
            $response['pending_requests'][] = [
                'schedule_id' => $request['schedule_id'],
                'student_name' => $request['student_name'],
                'student_email' => $request['student_email'],
                'book_title' => $request['book_title'],
                'schedule_date' => $request['scheduled_date'],
                'purpose' => $request['purpose'],
                'status' => $request['status']
            ];
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Fetch students with their borrowed books
$students_query = "SELECT 
    u.*,
    COUNT(DISTINCT bs.schedule_id) as total_borrows,
    COUNT(DISTINCT CASE WHEN bs.status = 'confirmed' THEN bs.schedule_id END) as active_borrows,
    COUNT(DISTINCT CASE WHEN bs.status = 'cancelled' THEN bs.schedule_id END) as cancelled_borrows
    FROM users u 
    LEFT JOIN book_schedules bs ON u.student_id = bs.student_id 
    WHERE u.user_type = 'student' 
    GROUP BY u.student_id, u.full_name, u.email";
$students_result = $conn->query($students_query);

// Debug information
if (!$students_result) {
    error_log("SQL Error in students query: " . $conn->error);
}

// Calculate overdue fees
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
$overdue_count = $overdue_result->num_rows;

// Add this new function to check for due dates and send notifications
function checkDueDates($conn) {
    // Check for books due in 2 days
    $due_soon_query = "SELECT bb.student_id, b.title, bb.due_date 
                      FROM borrowed_books bb 
                      JOIN books b ON bb.book_id = b.book_id 
                      WHERE bb.return_date IS NULL 
                      AND bb.due_date BETWEEN CURRENT_DATE AND DATE_ADD(CURRENT_DATE, INTERVAL 2 DAY)";
    $due_soon_result = $conn->query($due_soon_query);
    
    while ($book = $due_soon_result->fetch_assoc()) {
        $message = "Reminder: '{$book['title']}' is due on " . date('Y-m-d', strtotime($book['due_date']));
        $notif_stmt = $conn->prepare("INSERT INTO notifications (student_id, message, type) VALUES (?, ?, 'due_soon')");
        if (!$notif_stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $notif_stmt->bind_param("is", $book['student_id'], $message);
        $notif_stmt->execute();
    }
}

// Call the function to check due dates
checkDueDates($conn);

// Fetch all borrow requests (history)
$all_schedules_query = "SELECT bs.*, b.title as book_title, b.cover_image, u.full_name as student_name, u.email as student_email
    FROM book_schedules bs
    JOIN books b ON bs.book_id = b.book_id
    JOIN users u ON bs.student_id = u.student_id
    ORDER BY bs.schedule_date DESC";
$all_schedules_result = $conn->query($all_schedules_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - BCP Library Management System</title>
    
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/fontawesome.css">
    <link rel="stylesheet" href="assets/css/templatemo-cyborg-gaming.css">
    <link rel="stylesheet" href="admin-style.css">
</head>
<body>
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 1000;">
            <?php 
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 1000;">
            <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <!-- Mobile sidebar toggle button -->
    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Overlay for mobile view -->
    <div class="sidebar-overlay"></div>

    <div class="admin-layout">
        <!-- Sidebar Navigation -->
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <div class="admin-avatar">
                    <?php if (isset($_SESSION['admin_profile_pic']) && !empty($_SESSION['admin_profile_pic'])): ?>
                        <img src="assets/images/<?php echo htmlspecialchars($_SESSION['admin_profile_pic']); ?>" alt="Admin">
                    <?php else: ?>
                        <div class="avatar-placeholder">A</div>
                    <?php endif; ?>
                </div>
                <h3>LibMS Admin</h3>
                <p><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Administrator'); ?></p>
            </div>

            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="#books" class="nav-link active" data-tab="books">
                        <i class="fas fa-book"></i>
                        <span>Books</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#ebooks" class="nav-link" data-tab="ebooks">
                        <i class="fas fa-tablet-alt"></i>
                        <span>eBooks</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#borrow" class="nav-link" data-tab="borrow">
                        <i class="fas fa-hand-holding"></i>
                        <span>Borrow Requests</span>
                        <?php if ($pending_count > 0): ?>
                            <span class="notification-badge"><?php echo $pending_count; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#returns" class="nav-link" data-tab="returns">
                        <i class="fas fa-undo"></i>
                        <span>Return Requests</span>
                        <span class="notification-badge" id="return-notif" style="display:none;"></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#students" class="nav-link" data-tab="students">
                        <i class="fas fa-user-graduate"></i>
                        <span>Students</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#overdue" class="nav-link" data-tab="overdue">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>Overdue Books</span>
                        <?php if ($overdue_count > 0): ?>
                            <span class="notification-badge"><?php echo $overdue_count; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#fines" class="nav-link" data-tab="fines">
                        <i class="fas fa-money-bill-wave"></i>
                        <span>Fines</span>
                    </a>
                </li>
                <li class="nav-item mt-4">
                    <a href="admin_logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="admin-content">
            <!-- Page Header -->
            <div class="admin-header">
                <h1>Admin Dashboard</h1>
                <div class="notification-summary">
                    <?php if ($pending_count > 0): ?>
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-clock"></i>
                            <span>Pending Requests: <strong><?php echo $pending_count; ?></strong></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($overdue_count > 0): ?>
                        <div class="alert alert-danger mb-0">
                            <i class="fas fa-exclamation-circle"></i>
                            <span>Overdue Books: <strong><?php echo $overdue_count; ?></strong></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tab content -->
            <div id="books" class="tab-content active">
                <!-- Books content -->
                <div class="section">
                    <h3>Add New Book</h3>
                    <form action="" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="add_book">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Title</label>
                                    <input type="text" name="title" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Author</label>
                                    <input type="text" name="author" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Category</label>
                                    <input type="text" name="category" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Status</label>
                                    <select name="status" class="form-control" required>
                                        <option value="Available">Available</option>
                                        <option value="Unavailable">Unavailable</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Available Stock</label>
                                    <input type="number" name="available_stock" class="form-control" required min="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Cover Image</label>
                                    <input type="file" name="cover_image" class="form-control" accept="image/*" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Book</button>
                    </form>
                </div>

                <div class="section">
                    <h3>Books List</h3>
                    <div class="scrollable-table">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Cover</th>
                                    <th>Title</th>
                                    <th>Author</th>
                                    <th>Category</th>
                                    <th>Stock</th>
                                    <th>Status</th>
                                    <th>Availability</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($books_result && $books_result->num_rows > 0): ?>
                                    <?php while ($book = $books_result->fetch_assoc()): ?>
                                        <tr data-book-id="<?php echo $book['book_id']; ?>">
                                            <td>
                                                <img src="assets/images/<?php echo htmlspecialchars($book['cover_image']); ?>" 
                                                     alt="" style="width: 50px; height: 75px; object-fit: cover;">
                                            </td>
                                            <td><?php echo htmlspecialchars($book['title']); ?></td>
                                            <td><?php echo htmlspecialchars($book['author']); ?></td>
                                            <td><?php echo htmlspecialchars($book['category']); ?></td>
                                            <td><?php echo htmlspecialchars($book['available_stock']); ?></td>
                                            <td>
                                                <div class="status-indicator">
                                                    <span class="status-dot <?php echo strtolower($book['status'] ?? 'Unavailable'); ?>"></span>
                                                    <span class="status-text"><?php echo htmlspecialchars($book['status'] ?? 'Unavailable'); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="status-indicator">
                                                    <?php if ($book['status'] === 'Borrowed'): ?>
                                                        <span class="status-dot borrowed"></span>
                                                        <span class="status-text">Borrowed</span>
                                                    <?php elseif ($book['available_stock'] > 0): ?>
                                                        <span class="status-dot available"></span>
                                                        <span class="status-text">In Stock (<?php echo $book['available_stock']; ?>)</span>
                                                    <?php else: ?>
                                                        <span class="status-dot unavailable"></span>
                                                        <span class="status-text">Out of Stock</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <button class="btn btn-warning btn-sm" onclick="editBook(<?php echo htmlspecialchars(json_encode($book)); ?>)">Edit</button>
                                                <button class="btn btn-danger btn-sm" onclick="deleteBook(<?php echo $book['book_id']; ?>)">Delete</button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No books found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div id="ebooks" class="tab-content" style="display: none;">
                <!-- eBooks content -->
                <div class="section">
                    <h3>Add eBook</h3>
                    <button type="button" class="btn btn-primary mb-4" data-toggle="modal" data-target="#addEbookModal">
                        <i class="fa fa-plus"></i> Add New eBook
                    </button>
                    <!-- eBooks table follows next -->
                    <div class="scrollable-table">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Cover</th>
                                    <th>Title</th>
                                    <th>Author</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Download</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($ebooks_result && $ebooks_result->num_rows > 0): ?>
                                    <?php while ($ebook = $ebooks_result->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <img src="assets/images/<?php echo htmlspecialchars($ebook['cover_image']); ?>" 
                                                     alt="" style="width: 50px; height: 75px; object-fit: cover;">
                                            </td>
                                            <td><?php echo htmlspecialchars($ebook['title']); ?></td>
                                            <td><?php echo htmlspecialchars($ebook['author']); ?></td>
                                            <td><?php echo htmlspecialchars($ebook['category']); ?></td>
                                            <td>$<?php echo number_format($ebook['price'] ?? 0, 2); ?></td>
                                            <td>
                                                <div class="status-indicator">
                                                    <span class="status-dot <?php echo strtolower($ebook['status'] ?? 'Unavailable'); ?>"></span>
                                                    <span class="status-text"><?php echo htmlspecialchars($ebook['status'] ?? 'Unavailable'); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="status-indicator">
                                                    <span class="status-dot <?php echo strtolower($ebook['download_status'] ?? 'Disabled'); ?>"></span>
                                                    <span class="status-text"><?php echo htmlspecialchars($ebook['download_status'] ?? 'Disabled'); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <button class="btn btn-warning btn-sm" onclick="editEbook(<?php echo htmlspecialchars(json_encode($ebook)); ?>)">Edit</button>
                                                <button class="btn btn-danger btn-sm" onclick="deleteEbook(<?php echo $ebook['id']; ?>)">Delete</button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No eBooks found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div id="borrow" class="tab-content">
                <!-- Borrow Requests content -->
                <div class="section">
                    <h3>Pending Borrow Requests</h3>
                    <div id="borrowRequests">
                        <?php if ($pending_schedules_result && $pending_schedules_result->num_rows > 0): ?>
                            <?php while ($request = $pending_schedules_result->fetch_assoc()): ?>
                                <div class="borrow-request" id="schedule-<?php echo $request['schedule_id']; ?>">
                                    <div class="row align-items-center">
                                        <div class="col-md-2">
                                            <img src="assets/images/<?php echo htmlspecialchars($request['cover_image']); ?>" 
                                                 alt="" style="width: 100px; height: 150px; object-fit: cover; border-radius: 5px;">
                                        </div>
                                        <div class="col-md-7">
                                            <h5><?php echo htmlspecialchars($request['book_title']); ?></h5>
                                            <p class="student-info">
                                                Requested by: <?php echo htmlspecialchars($request['student_name']); ?><br>
                                                Email: <?php echo htmlspecialchars($request['student_email']); ?>
                                            </p>
                                            <p class="schedule-date">
                                                <?php if (isset($request['schedule_date'])): ?>
                                                Schedule Date: <?php echo date('F j, Y', strtotime($request['schedule_date'])); ?><br>
                                                <?php endif; ?>
                                                
                                                <?php if (isset($request['return_date'])): ?>
                                                Expected Return: <?php echo date('F j, Y', strtotime($request['return_date'])); ?>
                                                <?php endif; ?>
                                            </p>
                                            <p>Purpose: <?php echo htmlspecialchars($request['purpose'] ?? 'Not specified'); ?></p>
                                        </div>
                                        <div class="col-md-3">
                                            <form action="" method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="approve_borrow">
                                                <input type="hidden" name="schedule_id" value="<?php echo $request['schedule_id']; ?>">
                                                <input type="hidden" name="book_id" value="<?php echo $request['book_id']; ?>">
                                                <button type="submit" class="btn btn-success btn-sm">Approve</button>
                                            </form>
                                            <form action="" method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="reject_borrow">
                                                <input type="hidden" name="schedule_id" value="<?php echo $request['schedule_id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                No pending borrow requests at this time.
                                <?php if (!$pending_schedules_result): ?>
                                    <div class="text-danger">Error: <?php echo $conn->error; ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div id="returns" class="tab-content" style="display: none;">
                <div class="section">
                    <h3>Return Requests</h3>
                    <?php
                    // Debug information
                    error_reporting(E_ALL);
                    ini_set('display_errors', 1);
                    
                    // Fetch pending return requests with debug output
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
                    
                    // Debug: Print the query
                    echo "<!-- Debug: Return Query = " . htmlspecialchars($return_query) . " -->\n";
                    
                    $return_result = $conn->query($return_query);
                    
                    if (!$return_result) {
                        echo "<!-- Debug: SQL Error = " . htmlspecialchars($conn->error) . " -->";
                        error_log("SQL Error in return requests query: " . $conn->error);
                    }
                    
                    // Debug: Print the number of results
                    echo "<!-- Debug: Number of results = " . ($return_result ? $return_result->num_rows : 'null') . " -->";
                    ?>
                    
                    <div class="scrollable-table">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Book</th>
                                    <th>Student</th>
                                    <th>Borrow Date</th>
                                    <th>Due Date</th>
                                    <th>Return Date</th>
                                    <th>Days Overdue</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($return_result && $return_result->num_rows > 0): ?>
                                    <?php while ($return = $return_result->fetch_assoc()): ?>
                                        <tr id="return-row-<?php echo $return['record_id']; ?>">
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if (!empty($return['cover_image'])): ?>
                                                        <img src="assets/images/<?php echo htmlspecialchars($return['cover_image']); ?>" 
                                                             alt="<?php echo htmlspecialchars($return['book_title']); ?>"
                                                             style="width: 50px; height: 75px; object-fit: cover; margin-right: 10px;">
                                                    <?php endif; ?>
                                                    <span><?php echo htmlspecialchars($return['book_title']); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="student-info">
                                                    <strong><?php echo htmlspecialchars($return['student_name']); ?></strong><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($return['student_email']); ?></small>
                                                </div>
                                            </td>
                                            <td><?php echo date('Y-m-d', strtotime($return['borrow_date'])); ?></td>
                                            <td><?php echo date('Y-m-d', strtotime($return['due_date'])); ?></td>
                                            <td><?php echo date('Y-m-d', strtotime($return['return_date'])); ?></td>
                                            <td>
                                                <?php if ($return['days_overdue'] > 0): ?>
                                                    <span class="badge badge-danger"><?php echo $return['days_overdue']; ?> days</span>
                                                <?php else: ?>
                                                    <span class="badge badge-success">On time</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-success btn-sm" onclick="handleReturn(<?php echo $return['record_id']; ?>, 'approve')">
                                                    Approve
                                                </button>
                                                <button class="btn btn-danger btn-sm" onclick="handleReturn(<?php echo $return['record_id']; ?>, 'reject')">
                                                    Reject
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center">
                                            No pending return requests
                                            <?php if (!$return_result): ?>
                                                <br><small class="text-danger">Error: Query failed</small>
                                            <?php elseif ($return_result->num_rows === 0): ?>
                                                <br><small class="text-muted">No records found matching the criteria</small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div id="students" class="tab-content" style="display: none;">
                <div class="section">
                    <h3>Student Management</h3>
                    <div class="scrollable-table">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Total Borrows</th>
                                    <th>Active Borrows</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($students_result && $students_result->num_rows > 0): ?>
                                    <?php while ($student = $students_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                            <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                                            <td><?php echo $student['total_borrows']; ?></td>
                                            <td><?php echo $student['active_borrows']; ?></td>
                                            <td>
                                                <?php if ($student['active_borrows'] > 0): ?>
                                                    <span class="badge badge-warning">Has Active Borrows</span>
                                                <?php else: ?>
                                                    <span class="badge badge-success">No Active Borrows</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-info btn-sm" onclick="viewStudentDetails('<?php echo $student['student_id']; ?>')">View Details</button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No students found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div id="overdue" class="tab-content">
                <div class="section">
                    <h3>Overdue Books</h3>
                    <?php if ($overdue_result && $overdue_result->num_rows > 0): ?>
                        <div class="scrollable-table">
                            <table class="table overdue-table">
                                <thead>
                                    <tr>
                                        <th>Book</th>
                                        <th>Student</th>
                                        <th>Borrow Date</th>
                                        <th>Due Date</th>
                                        <th>Days Overdue</th>
                                        <th>Overdue Fee</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($overdue = $overdue_result->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if (!empty($overdue['cover_image'])): ?>
                                                        <img src="assets/images/<?php echo htmlspecialchars($overdue['cover_image']); ?>" 
                                                             alt="<?php echo htmlspecialchars($overdue['book_title']); ?>"
                                                             style="width: 50px; height: 75px; object-fit: cover; margin-right: 10px;">
                                                    <?php else: ?>
                                                        <div style="width: 50px; height: 75px; background: #404040; margin-right: 10px; display: flex; align-items: center; justify-content: center;">
                                                            <i class="fa fa-book" style="color: #666;"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <span><?php echo htmlspecialchars($overdue['book_title']); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="student-info">
                                                    <strong><?php echo htmlspecialchars($overdue['student_name']); ?></strong><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($overdue['student_email']); ?></small>
                                                </div>
                                            </td>
                                            <td><?php echo date('Y-m-d', strtotime($overdue['borrow_date'])); ?></td>
                                            <td><?php echo date('Y-m-d', strtotime($overdue['due_date'])); ?></td>
                                            <td>
                                                <span class="badge badge-danger days-overdue">
                                                    <?php echo $overdue['days_overdue']; ?> days
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-danger overdue-fee">
                                                    $<?php echo number_format($overdue['overdue_fee'], 2); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <button class="btn btn-warning btn-sm" onclick="sendOverdueNotification(<?php echo $overdue['record_id']; ?>)">
                                                        <i class="fa fa-bell"></i> Remind
                                                    </button>
                                                    <button class="btn btn-success btn-sm" onclick="extendDueDate(<?php echo $overdue['record_id']; ?>)">
                                                        <i class="fa fa-calendar-plus"></i> Extend
                                                    </button>
                                                    <button class="btn btn-danger btn-sm" onclick="sendFineNotification(<?php echo $overdue['record_id']; ?>, '<?php echo htmlspecialchars($overdue['book_title']); ?>', <?php echo $overdue['days_overdue']; ?>)">
                                                        <i class="fa fa-money-bill"></i> Send Fine Notice
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            No overdue books at this time.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div id="fines" class="tab-content" style="display: none;">
                <div class="section">
                    <h3>Fines Management</h3>
                    
                    <!-- Add New Fine Button -->
                    <div class="mb-4">
                        <button class="btn btn-primary" onclick="showAddFineModal()">
                            <i class="fas fa-plus"></i> Add New Fine
                        </button>
                    </div>

                    <!-- Fines Summary Cards -->
                    <div class="row mb-4">
                        <?php
                        // Get fines statistics
                        $total_fines_query = "SELECT 
                            COUNT(*) as total_fines,
                            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_fines,
                            SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_fines,
                            SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_amount,
                            SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as collected_amount
                            FROM fines";
                        $fines_stats = $conn->query($total_fines_query)->fetch_assoc();
                        ?>
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total Fines</h5>
                                    <h3><?php echo $fines_stats['total_fines']; ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-dark">
                                <div class="card-body">
                                    <h5 class="card-title">Pending Fines</h5>
                                    <h3><?php echo $fines_stats['pending_fines']; ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Collected Amount</h5>
                                    <h3>$<?php echo number_format($fines_stats['collected_amount'], 2); ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Pending Amount</h5>
                                    <h3>$<?php echo number_format($fines_stats['pending_amount'], 2); ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Fines Filter -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <form id="finesFilterForm" class="row">
                                        <div class="col-md-3">
                                            <label>Status</label>
                                            <select class="form-control" id="fineStatus">
                                                <option value="">All</option>
                                                <option value="pending">Pending</option>
                                                <option value="paid">Paid</option>
                                                <option value="cancelled">Cancelled</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label>Date Range</label>
                                            <select class="form-control" id="dateRange">
                                                <option value="all">All Time</option>
                                                <option value="today">Today</option>
                                                <option value="week">This Week</option>
                                                <option value="month">This Month</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label>Search</label>
                                            <input type="text" class="form-control" id="fineSearch" placeholder="Search by student name or book title">
                                        </div>
                                        <div class="col-md-2">
                                            <label>&nbsp;</label>
                                            <button type="button" class="btn btn-primary btn-block" onclick="filterFines()">Filter</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Fines Table -->
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Book</th>
                                    <th>Fine Amount</th>
                                    <th>Issue Date</th>
                                    <th>Status</th>
                                    <th>Payment Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="finesTableBody">
                                <?php
                                $fines_query = "SELECT f.*, b.title as book_title, u.full_name as student_name, u.email as student_email 
                                              FROM fines f 
                                              JOIN books b ON f.book_id = b.book_id 
                                              JOIN users u ON f.student_id = u.student_id 
                                              ORDER BY f.created_at DESC";
                                $fines_result = $conn->query($fines_query);
                                
                                if ($fines_result && $fines_result->num_rows > 0):
                                    while ($fine = $fines_result->fetch_assoc()):
                                ?>
                                    <tr>
                                        <td>
                                            <div class="student-info">
                                                <strong><?php echo htmlspecialchars($fine['student_name']); ?></strong><br>
                                                <small><?php echo htmlspecialchars($fine['student_email']); ?></small>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($fine['book_title']); ?></td>
                                        <td>$<?php echo number_format($fine['amount'], 2); ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($fine['created_at'])); ?></td>
                                        <td>
                                            <span class="badge badge-<?php 
                                                echo $fine['status'] === 'paid' ? 'success' : 
                                                    ($fine['status'] === 'pending' ? 'warning' : 'secondary'); 
                                            ?>">
                                                <?php echo ucfirst($fine['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo $fine['paid_at'] ? date('Y-m-d', strtotime($fine['paid_at'])) : '-'; ?>
                                        </td>
                                        <td>
                                            <?php if ($fine['status'] === 'pending'): ?>
                                                <button class="btn btn-success btn-sm" onclick="markFinePaid(<?php echo $fine['fine_id']; ?>)">
                                                    Mark as Paid
                                                </button>
                                                <button class="btn btn-danger btn-sm" onclick="cancelFine(<?php echo $fine['fine_id']; ?>)">
                                                    Cancel
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No fines found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div id="all_borrows" class="tab-content" style="display: none;">
                <div class="section">
                    <h3>All Borrow Requests (History)</h3>
                    <div class="scrollable-table">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Book</th>
                                    <th>Student</th>
                                    <th>Schedule Date</th>
                                    <th>Return Date</th>
                                    <th>Purpose</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($all_schedules_result && $all_schedules_result->num_rows > 0): ?>
                                    <?php while ($row = $all_schedules_result->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="assets/images/<?php echo htmlspecialchars($row['cover_image']); ?>" 
                                                         alt="" style="width: 40px; height: 60px; object-fit: cover; margin-right: 10px;">
                                                    <?php echo htmlspecialchars($row['book_title']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="student-info">
                                                    <?php echo htmlspecialchars($row['student_name']); ?><br>
                                                    <small><?php echo htmlspecialchars($row['student_email']); ?></small>
                                                </div>
                                            </td>
                                            <td><?php echo date('Y-m-d', strtotime($row['schedule_date'])); ?></td>
                                            <td><?php echo date('Y-m-d', strtotime($row['return_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($row['purpose']); ?></td>
                                            <td>
                                                <?php
                                                    $status = strtolower($row['status']);
                                                    $badgeClass = 'badge-secondary';
                                                    if ($status === 'pending') $badgeClass = 'badge-warning';
                                                    elseif ($status === 'confirmed') $badgeClass = 'badge-success';
                                                    elseif ($status === 'cancelled') $badgeClass = 'badge-danger';
                                                ?>
                                                <span class="badge <?php echo $badgeClass; ?>"><?php echo ucfirst($status); ?></span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center">No borrow requests found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Edit Book Modal -->
    <div class="modal fade" id="editBookModal" tabindex="-1" role="dialog" aria-labelledby="editBookModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editBookModalLabel">Edit Book</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="editBookForm" action="" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_book">
                        <input type="hidden" name="book_id" id="edit_book_id">
                        <input type="hidden" name="current_cover" id="edit_current_cover">
                        <div class="form-group">
                            <label>Title</label>
                            <input type="text" name="title" id="edit_book_title" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Author</label>
                            <input type="text" name="author" id="edit_book_author" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Category</label>
                            <input type="text" name="category" id="edit_book_category" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" id="edit_book_status" class="form-control" required>
                                <option value="Available">Available</option>
                                <option value="Unavailable">Unavailable</option>
                                <option value="Borrowed">Borrowed</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Available Stock</label>
                            <input type="number" name="available_stock" id="edit_book_stock" class="form-control" required min="0">
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" id="edit_book_description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Cover Image (leave empty to keep current)</label>
                            <input type="file" name="cover_image" class="form-control" accept="image/*">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit eBook Modal -->
    <div class="modal fade" id="editEbookModal" tabindex="-1" role="dialog" aria-labelledby="editEbookModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content bg-dark text-white">
                <div class="modal-header">
                    <h5 class="modal-title" id="editEbookModalLabel">Edit eBook</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="editEbookForm" action="" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_ebook">
                        <input type="hidden" name="ebook_id" id="edit_ebook_id">
                        <input type="hidden" name="current_cover" id="edit_ebook_current_cover">
                        <input type="hidden" name="current_file" id="edit_ebook_current_file">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Title</label>
                                    <input type="text" name="title" id="edit_ebook_title" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Author</label>
                                    <input type="text" name="author" id="edit_ebook_author" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Category</label>
                                    <input type="text" name="category" id="edit_ebook_category" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Price</label>
                                    <input type="number" name="price" id="edit_ebook_price" class="form-control" step="0.01" min="0" required>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Status</label>
                                            <select name="status" id="edit_ebook_status" class="form-control" required>
                                                <option value="Available">Available</option>
                                                <option value="Unavailable">Unavailable</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Download Status</label>
                                            <select name="download_status" id="edit_ebook_download_status" class="form-control" required>
                                                <option value="Enabled">Enabled</option>
                                                <option value="Disabled">Disabled</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Description</label>
                                    <textarea name="description" id="edit_ebook_description" class="form-control" rows="6"></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label>Cover Image</label>
                                    <div class="custom-file">
                                        <input type="file" name="cover_image" id="edit_cover_image" class="custom-file-input" accept="image/*">
                                        <label class="custom-file-label" for="edit_cover_image">Choose file...</label>
                                    </div>
                                    <small class="form-text text-muted">Leave empty to keep the current cover image.</small>
                                    <div id="current_cover_preview" class="mt-2 text-center" style="display:none;">
                                        <p class="mb-1">Current cover image:</p>
                                        <img src="" alt="Current cover" style="max-height: 150px; max-width: 100%;" class="img-thumbnail">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label>eBook File</label>
                                    <div class="custom-file">
                                        <input type="file" name="ebook_file" id="edit_ebook_file" class="custom-file-input" accept=".pdf,.epub,.mobi,.doc,.docx,.txt,.rtf">
                                        <label class="custom-file-label" for="edit_ebook_file">Choose file...</label>
                                    </div>
                                    <small class="form-text text-muted">Leave empty to keep the current file. Allowed formats: PDF, EPUB, MOBI, DOC, DOCX, TXT, RTF</small>
                                    <div id="current_file_info_preview" class="mt-2">
                                        <span class="badge badge-info"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Student Details Modal -->
    <div class="modal fade" id="studentDetailsModal" tabindex="-1" role="dialog" aria-labelledby="studentDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="studentDetailsModalLabel">Student Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="student-info mb-4">
                        <h4 id="studentName"></h4>
                        <p>Student ID: <span id="studentId"></span></p>
                        <p>Email: <span id="studentEmail"></span></p>
                    </div>
                    <div class="borrowed-books">
                        <h5>Borrowed Books</h5>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Book Title</th>
                                        <th>Borrow Date</th>
                                        <th>Due Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="borrowedBooksList">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div id="notification" class="notification"></div>

    <!-- Include JavaScript files -->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
    <script src="admin-script.js"></script>
    <script src="assets/js/admin-realtime.js"></script>

    <!-- Add eBook Modal -->
    <div class="modal fade" id="addEbookModal" tabindex="-1" role="dialog" aria-labelledby="addEbookModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content bg-dark text-white">
                <div class="modal-header">
                    <h5 class="modal-title" id="addEbookModalLabel">Add New eBook</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="addEbookForm" action="" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_ebook">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Title</label>
                                    <input type="text" name="title" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Author</label>
                                    <input type="text" name="author" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Category</label>
                                    <input type="text" name="category" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Price</label>
                                    <input type="number" name="price" class="form-control" step="0.01" min="0" value="0" required>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Status</label>
                                            <select name="status" class="form-control" required>
                                                <option value="Available">Available</option>
                                                <option value="Unavailable">Unavailable</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Download Status</label>
                                            <select name="download_status" class="form-control" required>
                                                <option value="Enabled">Enabled</option>
                                                <option value="Disabled">Disabled</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Description</label>
                                    <textarea name="description" class="form-control" rows="6" required></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label>Cover Image</label>
                                    <div class="custom-file">
                                        <input type="file" name="cover_image" id="add_cover_image" class="custom-file-input" accept="image/*" required>
                                        <label class="custom-file-label" for="add_cover_image">Choose file...</label>
                                    </div>
                                    <small class="form-text text-muted">Recommended size: 400x600 pixels. JPG, PNG, GIF formats only.</small>
                                </div>
                                
                                <div class="form-group">
                                    <label>eBook File</label>
                                    <div class="custom-file">
                                        <input type="file" name="ebook_file" id="add_ebook_file" class="custom-file-input" accept=".pdf,.epub,.mobi,.doc,.docx,.txt,.rtf" required>
                                        <label class="custom-file-label" for="add_ebook_file">Choose file...</label>
                                    </div>
                                    <small class="form-text text-muted">Allowed formats: PDF, EPUB, MOBI, DOC, DOCX, TXT, RTF</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add eBook</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html> 