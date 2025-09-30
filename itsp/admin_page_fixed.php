<?php
session_name('admin_session');
session_start();
require_once 'config.php';

// Strict session validation function definition
function validateAdminSession() {
    if (!isset($_SESSION['is_admin']) || 
        !$_SESSION['is_admin'] || 
        !isset($_SESSION['admin_id']) || 
        !isset($_SESSION['user_type']) || 
        $_SESSION['user_type'] !== 'admin') {
        
        // Clear any existing session
        session_unset();
        session_destroy();
        
        // Start a new session for the error message
        session_start();
        $_SESSION['error'] = 'Please log in as an administrator to access this page.';
        
        // Redirect to admin login
        header('Location: admin_login.php?error=unauthorized');
        exit();
    }
}

// Call the validation function
validateAdminSession();

// Check if it's an AJAX request
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Regenerate session ID periodically to prevent session fixation
if (!isset($_SESSION['last_regeneration']) || (time() - $_SESSION['last_regeneration']) > 1800) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// Handle AJAX request for real-time updates
if ($is_ajax) {
    $response = [];
    
    // Your AJAX handling code...
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Process any form submissions (approve, reject, etc.)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Your action handling code...
}

// Check due dates
function checkDueDates($conn) {
    // Your due date checking code...
}

// Call the function to check due dates
checkDueDates($conn);

// Fetch pending borrow requests
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

// Fetch return requests
$return_query = "SELECT bb.*, b.title as book_title, b.cover_image, u.full_name as student_name, u.email as student_email, DATEDIFF(bb.return_date, bb.due_date) as days_overdue, GREATEST(DATEDIFF(bb.return_date, bb.due_date),0) * 1 as overdue_fee FROM borrowed_books bb JOIN books b ON bb.book_id = b.book_id JOIN users u ON bb.student_id = u.student_id WHERE bb.return_date IS NOT NULL AND bb.admin_confirmed_return = 0 ORDER BY bb.return_date DESC";
$return_result = $conn->query($return_query);
if (!$return_result || $return_result->num_rows == 0) {
    error_log('DEBUG: No pending return requests found. SQL: ' . $return_query);
}

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
    <title>Admin Dashboard - Library Management System</title>
    
    <!-- Debug information -->
    <?php
    $css_files = [
        'vendor/bootstrap/css/bootstrap.min.css',
        'assets/css/fontawesome.css',
        'assets/css/templatemo-cyborg-gaming.css'
    ];
    
    foreach ($css_files as $css_file) {
        if (file_exists($css_file)) {
            echo "<!-- Debug: CSS file exists: $css_file -->\n";
        } else {
            echo "<!-- Debug: CSS file missing: $css_file -->\n";
        }
    }
    ?>
    
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/fontawesome.css">
    <link rel="stylesheet" href="assets/css/templatemo-cyborg-gaming.css">
    <style>
        /* Your CSS styles here */
    </style>
</head>
<body>
    <!-- Content header, navigation, etc. -->
    
    <!-- Return Requests section -->
    <div class="section" id="return-requests">
        <h3>Return Requests <?php if ($return_result && $return_result->num_rows > 0): ?><span class="badge badge-danger"><?php echo $return_result->num_rows; ?></span><?php endif; ?></h3>
        
        <div class="scrollable-table">
            <?php
            if ($return_result && $return_result->num_rows > 0) {
                $all_returns = [];
                $return_result->data_seek(0); // Reset pointer
                while ($row = $return_result->fetch_assoc()) {
                    $all_returns[] = $row;
                }
                echo '<pre style="color:yellow;background:black;">DEBUG RAW DATA: ' . htmlspecialchars(json_encode($all_returns, JSON_PRETTY_PRINT)) . '</pre>';
                $return_result->data_seek(0); // Reset pointer again for the table loop
            }
            ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Book</th>
                        <th>Student</th>
                        <th>Borrow Date</th>
                        <th>Due Date</th>
                        <th>Return Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($return_result && $return_result->num_rows > 0): ?>
                        <?php while ($return = $return_result->fetch_assoc()): ?>
                            <!-- DEBUG: <?php echo htmlspecialchars(json_encode($return)); ?> -->
                            <tr>
                                <td>
                                    <!-- Book details -->
                                </td>
                                <td>
                                    <!-- Student details -->
                                </td>
                                <td><!-- Borrow date --></td>
                                <td><!-- Due date --></td>
                                <td><!-- Return date --></td>
                                <td>
                                    <!-- Status and overdue information -->
                                </td>
                                <td>
                                    <!-- Approval buttons -->
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">No return requests found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Rest of the page content -->
    
    <!-- JavaScript includes -->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
    <script>
        // Your JavaScript code
    </script>
</body>
</html> 