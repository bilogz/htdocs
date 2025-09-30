<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

// Debug session information
error_log("Handler Session data: " . print_r($_SESSION, true));

// Validate admin session
if (!isset($_SESSION['is_admin']) || 
    !$_SESSION['is_admin'] || 
    !isset($_SESSION['admin_id']) || 
    !isset($_SESSION['user_type']) || 
    $_SESSION['user_type'] !== 'admin') {
    
    error_log("Unauthorized access attempt in handler. Session data: " . print_r($_SESSION, true));
    echo json_encode([
        'success' => false, 
        'message' => 'Session expired. Please refresh and log in again.',
        'session_error' => true
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'approve_return':
            $record_id = $_POST['record_id'] ?? 0;
            
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Get book and student information
                $info_query = "SELECT bb.*, b.title, u.student_id, u.full_name 
                              FROM borrowed_books bb 
                              JOIN books b ON bb.book_id = b.book_id 
                              JOIN users u ON bb.student_id = u.student_id 
                              WHERE bb.record_id = ?";
                $info_stmt = $conn->prepare($info_query);
                if (!$info_stmt) {
                    throw new Exception("Database error: " . $conn->error);
                }
                $info_stmt->bind_param("i", $record_id);
                $info_stmt->execute();
                $result = $info_stmt->get_result();
                $borrow = $result->fetch_assoc();
                
                if (!$borrow) {
                    throw new Exception("Return record not found");
                }
                
                // Update borrowed_books record
                $update_stmt = $conn->prepare("UPDATE borrowed_books 
                    SET admin_confirmed_return = 1, 
                        return_date = CURRENT_TIMESTAMP,
                        status = 'completed' 
                    WHERE record_id = ?");
                if (!$update_stmt) {
                    throw new Exception("Database error: " . $conn->error);
                }
                $update_stmt->bind_param("i", $record_id);
                if (!$update_stmt->execute()) {
                    throw new Exception("Error updating borrow record: " . $update_stmt->error);
                }
                
                // Update book stock
                $update_stock = $conn->prepare("UPDATE books 
                    SET available_stock = available_stock + 1 
                    WHERE book_id = ?");
                if (!$update_stock) {
                    throw new Exception("Database error: " . $conn->error);
                }
                $update_stock->bind_param("i", $borrow['book_id']);
                if (!$update_stock->execute()) {
                    throw new Exception("Error updating book stock: " . $update_stock->error);
                }
                
                // Create notification
                $message = "Your return of '{$borrow['title']}' has been confirmed.";
                $notif_stmt = $conn->prepare("INSERT INTO notifications (student_id, message, type) 
                    VALUES (?, ?, 'return_approved')");
                if (!$notif_stmt) {
                    throw new Exception("Database error: " . $conn->error);
                }
                $notif_stmt->bind_param("is", $borrow['student_id'], $message);
                if (!$notif_stmt->execute()) {
                    throw new Exception("Error creating notification: " . $notif_stmt->error);
                }
                
                $conn->commit();
                echo json_encode([
                    'success' => true, 
                    'message' => 'Return request approved successfully'
                ]);
                
            } catch (Exception $e) {
                $conn->rollback();
                error_log("Error in approve_return: " . $e->getMessage());
                echo json_encode([
                    'success' => false, 
                    'message' => $e->getMessage()
                ]);
            }
            break;
            
        case 'reject_return':
            $record_id = $_POST['record_id'] ?? 0;
            
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Get book and student information
                $info_query = "SELECT bb.*, b.title, u.student_id 
                              FROM borrowed_books bb 
                              JOIN books b ON bb.book_id = b.book_id 
                              JOIN users u ON bb.student_id = u.student_id 
                              WHERE bb.record_id = ?";
                $info_stmt = $conn->prepare($info_query);
                $info_stmt->bind_param("i", $record_id);
                $info_stmt->execute();
                $result = $info_stmt->get_result();
                $borrow = $result->fetch_assoc();
                
                if (!$borrow) {
                    throw new Exception("Return record not found");
                }
                
                // Update borrowed_books record
                $update_stmt = $conn->prepare("UPDATE borrowed_books 
                    SET return_date = NULL,
                        status = 'borrowed' 
                    WHERE record_id = ?");
                $update_stmt->bind_param("i", $record_id);
                $update_stmt->execute();
                
                // Create notification
                $message = "Your return of '{$borrow['title']}' has been rejected. Please visit the library.";
                $notif_stmt = $conn->prepare("INSERT INTO notifications (student_id, message, type) 
                    VALUES (?, ?, 'return_rejected')");
                $notif_stmt->bind_param("is", $borrow['student_id'], $message);
                $notif_stmt->execute();
                
                $conn->commit();
                echo json_encode([
                    'success' => true, 
                    'message' => 'Return request rejected successfully'
                ]);
                
            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode([
                    'success' => false, 
                    'message' => $e->getMessage()
                ]);
            }
            break;
            
        case 'send_fine_notification':
            if (!isset($_POST['record_id']) || !isset($_POST['fine_amount'])) {
                echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
                exit;
            }

            $record_id = intval($_POST['record_id']);
            $fine_amount = floatval($_POST['fine_amount']);
            $message = isset($_POST['message']) ? $_POST['message'] : '';

            try {
                // Get borrow record details
                $record_query = "SELECT bb.*, b.title as book_title, u.student_id, u.full_name, u.email 
                                FROM borrowed_books bb 
                                JOIN books b ON bb.book_id = b.book_id 
                                JOIN users u ON bb.student_id = u.student_id 
                                WHERE bb.record_id = ?";
                $stmt = $conn->prepare($record_query);
                $stmt->bind_param("i", $record_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $record = $result->fetch_assoc();

                if (!$record) {
                    throw new Exception("Record not found");
                }

                // Insert into fines table
                $insert_fine = "INSERT INTO fines (student_id, book_id, amount, status, record_id) 
                               VALUES (?, ?, ?, 'pending', ?)";
                $stmt = $conn->prepare($insert_fine);
                $stmt->bind_param("iidi", $record['student_id'], $record['book_id'], $fine_amount, $record_id);
                $stmt->execute();
                $fine_id = $stmt->insert_id;

                // Create notification message
                $notification_message = "Fine Notice: You have been charged $" . number_format($fine_amount, 2) . 
                                      " for overdue book '{$record['book_title']}'";
                if (!empty($message)) {
                    $notification_message .= ". Note: " . $message;
                }

                // Insert notification
                $insert_notif = "INSERT INTO notifications (student_id, message, type) VALUES (?, ?, 'fine')";
                $stmt = $conn->prepare($insert_notif);
                $stmt->bind_param("is", $record['student_id'], $notification_message);
                $stmt->execute();

                echo json_encode([
                    'success' => true,
                    'message' => 'Fine notification sent successfully'
                ]);

            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ]);
            }
            break;
            
        case 'mark_fine_paid':
            if (!isset($_POST['fine_id'])) {
                echo json_encode(['success' => false, 'message' => 'Fine ID is required']);
                exit;
            }

            $fine_id = intval($_POST['fine_id']);

            try {
                // Start transaction
                $conn->begin_transaction();

                // Update fine status
                $update_fine = "UPDATE fines SET status = 'paid', paid_at = CURRENT_TIMESTAMP WHERE fine_id = ?";
                $stmt = $conn->prepare($update_fine);
                $stmt->bind_param("i", $fine_id);
                $stmt->execute();

                // Get fine details for notification
                $fine_query = "SELECT f.*, b.title as book_title, u.student_id, u.full_name 
                              FROM fines f 
                              JOIN books b ON f.book_id = b.book_id 
                              JOIN users u ON f.student_id = u.student_id 
                              WHERE f.fine_id = ?";
                $stmt = $conn->prepare($fine_query);
                $stmt->bind_param("i", $fine_id);
                $stmt->execute();
                $fine = $stmt->get_result()->fetch_assoc();

                // Create notification
                $message = "Your fine of $" . number_format($fine['amount'], 2) . " for book '{$fine['book_title']}' has been marked as paid.";
                $notif_stmt = $conn->prepare("INSERT INTO notifications (student_id, message, type) VALUES (?, ?, 'fine_paid')");
                $notif_stmt->bind_param("is", $fine['student_id'], $message);
                $notif_stmt->execute();

                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Fine marked as paid successfully']);

            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;

        case 'cancel_fine':
            if (!isset($_POST['fine_id'])) {
                echo json_encode(['success' => false, 'message' => 'Fine ID is required']);
                exit;
            }

            $fine_id = intval($_POST['fine_id']);

            try {
                // Start transaction
                $conn->begin_transaction();

                // Get fine details before cancelling
                $fine_query = "SELECT f.*, b.title as book_title, u.student_id, u.full_name 
                              FROM fines f 
                              JOIN books b ON f.book_id = b.book_id 
                              JOIN users u ON f.student_id = u.student_id 
                              WHERE f.fine_id = ?";
                $stmt = $conn->prepare($fine_query);
                $stmt->bind_param("i", $fine_id);
                $stmt->execute();
                $fine = $stmt->get_result()->fetch_assoc();

                // Update fine status
                $update_fine = "UPDATE fines SET status = 'cancelled' WHERE fine_id = ?";
                $stmt = $conn->prepare($update_fine);
                $stmt->bind_param("i", $fine_id);
                $stmt->execute();

                // Create notification
                $message = "Your fine of $" . number_format($fine['amount'], 2) . " for book '{$fine['book_title']}' has been cancelled.";
                $notif_stmt = $conn->prepare("INSERT INTO notifications (student_id, message, type) VALUES (?, ?, 'fine_cancelled')");
                $notif_stmt->bind_param("is", $fine['student_id'], $message);
                $notif_stmt->execute();

                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Fine cancelled successfully']);

            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;

        case 'filter_fines':
            $status = $_POST['status'] ?? '';
            $date_range = $_POST['date_range'] ?? 'all';
            $search = $_POST['search'] ?? '';

            try {
                $where_conditions = [];
                $params = [];
                $types = '';

                // Status filter
                if (!empty($status)) {
                    $where_conditions[] = "f.status = ?";
                    $params[] = $status;
                    $types .= 's';
                }

                // Date range filter
                switch ($date_range) {
                    case 'today':
                        $where_conditions[] = "DATE(f.created_at) = CURRENT_DATE";
                        break;
                    case 'week':
                        $where_conditions[] = "f.created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)";
                        break;
                    case 'month':
                        $where_conditions[] = "f.created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 1 MONTH)";
                        break;
                }

                // Search filter
                if (!empty($search)) {
                    $where_conditions[] = "(u.full_name LIKE ? OR b.title LIKE ?)";
                    $params[] = "%$search%";
                    $params[] = "%$search%";
                    $types .= 'ss';
                }

                // Build query
                $query = "SELECT f.*, b.title as book_title, u.full_name as student_name, u.email as student_email 
                         FROM fines f 
                         JOIN books b ON f.book_id = b.book_id 
                         JOIN users u ON f.student_id = u.student_id";

                if (!empty($where_conditions)) {
                    $query .= " WHERE " . implode(" AND ", $where_conditions);
                }

                $query .= " ORDER BY f.created_at DESC";

                $stmt = $conn->prepare($query);
                if (!empty($params)) {
                    $stmt->bind_param($types, ...$params);
                }
                $stmt->execute();
                $result = $stmt->get_result();

                // Build HTML for response
                $html = '';
                if ($result->num_rows > 0) {
                    while ($fine = $result->fetch_assoc()) {
                        $html .= '<tr>
                            <td>
                                <div class="student-info">
                                    <strong>' . htmlspecialchars($fine['student_name']) . '</strong><br>
                                    <small>' . htmlspecialchars($fine['student_email']) . '</small>
                                </div>
                            </td>
                            <td>' . htmlspecialchars($fine['book_title']) . '</td>
                            <td>$' . number_format($fine['amount'], 2) . '</td>
                            <td>' . date('Y-m-d', strtotime($fine['created_at'])) . '</td>
                            <td>
                                <span class="badge badge-' . 
                                ($fine['status'] === 'paid' ? 'success' : 
                                    ($fine['status'] === 'pending' ? 'warning' : 'secondary')) . 
                                '">' . ucfirst($fine['status']) . '</span>
                            </td>
                            <td>' . ($fine['paid_at'] ? date('Y-m-d', strtotime($fine['paid_at'])) : '-') . '</td>
                            <td>';
                        
                        if ($fine['status'] === 'pending') {
                            $html .= '<button class="btn btn-success btn-sm" onclick="markFinePaid(' . $fine['fine_id'] . ')">
                                        Mark as Paid
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="cancelFine(' . $fine['fine_id'] . ')">
                                        Cancel
                                    </button>';
                        }
                        
                        $html .= '</td></tr>';
                    }
                } else {
                    $html = '<tr><td colspan="7" class="text-center">No fines found</td></tr>';
                }

                echo json_encode([
                    'success' => true,
                    'html' => $html
                ]);

            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ]);
            }
            break;

        case 'get_fine_details':
            if (!isset($_POST['fine_id'])) {
                echo json_encode(['success' => false, 'message' => 'Fine ID is required']);
                exit;
            }

            $fine_id = intval($_POST['fine_id']);

            try {
                $query = "SELECT f.*, b.title as book_title, u.full_name as student_name 
                         FROM fines f 
                         JOIN books b ON f.book_id = b.book_id 
                         JOIN users u ON f.student_id = u.student_id 
                         WHERE f.fine_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $fine_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $fine = $result->fetch_assoc();

                if ($fine) {
                    echo json_encode([
                        'success' => true,
                        'fine' => $fine
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Fine not found'
                    ]);
                }
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ]);
            }
            break;

        case 'search_students':
            // Validate search term
            if (empty($_POST['search_term'])) {
                echo json_encode(['success' => true, 'students' => []]);
                exit;
            }

            try {
                $search_term = '%' . trim($_POST['search_term']) . '%';
                
                // Search query
                $query = "SELECT student_id, full_name, email 
                         FROM users 
                         WHERE user_type = 'student' 
                         AND (student_id LIKE ? OR full_name LIKE ? OR email LIKE ?)
                         ORDER BY full_name 
                         LIMIT 10";
                
                $stmt = $conn->prepare($query);
                if (!$stmt) {
                    throw new Exception("Database error: " . $conn->error);
                }
                
                $stmt->bind_param('sss', $search_term, $search_term, $search_term);
                
                if (!$stmt->execute()) {
                    throw new Exception("Search failed: " . $stmt->error);
                }
                
                $result = $stmt->get_result();
                $students = [];
                
                while ($row = $result->fetch_assoc()) {
                    $students[] = [
                        'student_id' => htmlspecialchars($row['student_id']),
                        'full_name' => htmlspecialchars($row['full_name']),
                        'email' => htmlspecialchars($row['email'])
                    ];
                }
                
                echo json_encode([
                    'success' => true,
                    'students' => $students
                ]);
                
            } catch (Exception $e) {
                error_log("Student search error: " . $e->getMessage());
                echo json_encode([
                    'success' => false,
                    'message' => 'Error searching for students'
                ]);
            }
            exit;

        case 'save_fine':
            try {
                // Validate required fields
                if (!isset($_POST['student_id']) || empty($_POST['student_id'])) {
                    throw new Exception('Please select a student');
                }
                if (!isset($_POST['book_id']) || empty($_POST['book_id'])) {
                    throw new Exception('Please select a book');
                }
                if (!isset($_POST['amount']) || empty($_POST['amount'])) {
                    throw new Exception('Please enter fine amount');
                }
                if (!isset($_POST['reason']) || empty($_POST['reason'])) {
                    throw new Exception('Please enter fine reason');
                }

                $conn->begin_transaction();

                // Verify student exists
                $check_student = $conn->prepare("SELECT student_id FROM users WHERE student_id = ? AND user_type = 'student'");
                $check_student->bind_param('s', $_POST['student_id']);
                $check_student->execute();
                if (!$check_student->get_result()->fetch_assoc()) {
                    throw new Exception('Selected student not found');
                }

                // Insert or update fine
                if (isset($_POST['fine_id']) && !empty($_POST['fine_id'])) {
                    // Update existing fine
                    $update_fine = $conn->prepare("UPDATE fines 
                                                 SET book_id = ?, amount = ?, reason = ?, 
                                                     status = ?, updated_at = NOW() 
                                                 WHERE fine_id = ? AND student_id = ?");
                    $update_fine->bind_param('idssss', 
                        $_POST['book_id'],
                        $_POST['amount'],
                        $_POST['reason'],
                        $_POST['status'],
                        $_POST['fine_id'],
                        $_POST['student_id']
                    );
                    $update_fine->execute();
                    
                    if ($update_fine->affected_rows === 0) {
                        throw new Exception('Fine not found or you do not have permission to update it');
                    }
                } else {
                    // Insert new fine
                    $insert_fine = $conn->prepare("INSERT INTO fines 
                                                 (student_id, book_id, amount, reason, status, created_at, updated_at) 
                                                 VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
                    $insert_fine->bind_param('sidss', 
                        $_POST['student_id'],
                        $_POST['book_id'],
                        $_POST['amount'],
                        $_POST['reason'],
                        $_POST['status']
                    );
                    $insert_fine->execute();
                }

                // Create notification for the student
                $notification = $conn->prepare("INSERT INTO notifications (student_id, message, type) 
                                              VALUES (?, ?, 'fine')");
                $message = "A fine of $" . number_format($_POST['amount'], 2) . " has been issued. Reason: " . $_POST['reason'];
                $notification->bind_param('ss', $_POST['student_id'], $message);
                $notification->execute();

                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Fine saved successfully']);
            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;

        case 'search_books':
            // Validate search term
            if (empty($_POST['search_term'])) {
                echo json_encode(['success' => true, 'books' => []]);
                exit;
            }

            try {
                $search_term = '%' . $_POST['search_term'] . '%';
                $query = "SELECT book_id, title, author, category, available_stock 
                         FROM books 
                         WHERE title LIKE ? OR book_id LIKE ?
                         ORDER BY title ASC 
                         LIMIT 10";
                
                $stmt = $conn->prepare($query);
                $stmt->bind_param('ss', $search_term, $search_term);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $books = [];
                while ($row = $result->fetch_assoc()) {
                    $books[] = $row;
                }
                
                echo json_encode([
                    'success' => true,
                    'books' => $books
                ]);
                
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error searching books: ' . $e->getMessage()
                ]);
            }
            exit;
            
        default:
            echo json_encode([
                'success' => false, 
                'message' => 'Invalid action'
            ]);
    }
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid request method'
    ]);
} 