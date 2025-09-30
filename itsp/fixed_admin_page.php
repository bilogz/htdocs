<?php
session_name('admin_session');
session_start();
require_once 'config.php';

// Strict session validation
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

// Rest of the existing code up to line 614
// ...

// Fetch all borrow requests (history)
$all_schedules_query = "SELECT bs.*, b.title as book_title, b.cover_image, u.full_name as student_name, u.email as student_email
    FROM book_schedules bs
    JOIN books b ON bs.book_id = b.book_id
    JOIN users u ON bs.student_id = u.student_id
    ORDER BY bs.schedule_date DESC";
$all_schedules_result = $conn->query($all_schedules_query);

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
    
    <!-- Rest of the HTML content -->
</head>
<body>
    <!-- Rest of the HTML content -->
</body>
</html> 