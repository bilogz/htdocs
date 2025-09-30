<?php
session_name('student_session');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'config.php';

// Strict session validation for students
function validateStudentSession() {
    if (!isset($_SESSION['student_id']) || 
        !isset($_SESSION['user_type']) || 
        $_SESSION['user_type'] !== 'student' ||
        isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
        
        // Clear any existing session
        session_unset();
        session_destroy();
        
        // Start a new session for the error message
        session_start();
        $_SESSION['error'] = 'Please log in as a student to access this page.';
        
        // Redirect to student login
        header('Location: login.php?error=unauthorized');
        exit();
    }
}

// Call the validation function
validateStudentSession();

// Regenerate session ID periodically to prevent session fixation
if (!isset($_SESSION['last_session_regeneration']) || (time() - $_SESSION['last_session_regeneration']) > 1800) {
    session_regenerate_id(true);
    $_SESSION['last_session_regeneration'] = time();
}

$student = [];
if (isset($_SESSION['student_id'])) {
    $stmt = $conn->prepare("SELECT full_name FROM users WHERE student_id = ?");
    $stmt->bind_param("i", $_SESSION['student_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    $stmt->close();
}

// Fetch user's profile picture if logged in
$profile_pic = null;
if (isset($_SESSION['student_id'])) {
    $user_query = $conn->prepare("SELECT profile_pic FROM users WHERE student_id = ?");
    $user_query->bind_param("i", $_SESSION['student_id']);
    $user_query->execute();
    $user_result = $user_query->get_result();
    if ($user = $user_result->fetch_assoc()) {
        $profile_pic = $user['profile_pic'];
    }
    $user_query->close();
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Books query with search
if ($search !== '') {
    $books_query = "SELECT * FROM books WHERE title LIKE ? OR author LIKE ? ORDER BY book_id DESC LIMIT 20";
    $books_stmt = $conn->prepare($books_query);
    $like = "%$search%";
    $books_stmt->bind_param("ss", $like, $like);
    $books_stmt->execute();
    $books_result = $books_stmt->get_result();
} else {
    $books_query = "SELECT * FROM books ORDER BY book_id DESC LIMIT 20";
    $books_result = $conn->query($books_query);
}

// eBooks query with search
if ($search !== '') {
    $ebooks_query = "SELECT * FROM ebooks WHERE title LIKE ? OR author LIKE ? ORDER BY created_at DESC";
    $ebooks_stmt = $conn->prepare($ebooks_query);
    $like = "%$search%";
    $ebooks_stmt->bind_param("ss", $like, $like);
    $ebooks_stmt->execute();
    $ebooks_result = $ebooks_stmt->get_result();
} else {
    $ebooks_query = "SELECT * FROM ebooks ORDER BY created_at DESC";
    $ebooks_result = $conn->query($ebooks_query);
}

// Fetch notifications
$notifications_query = "SELECT * FROM notifications 
                      WHERE student_id = ? 
                      ORDER BY created_at DESC 
                      LIMIT 10";
$notif_stmt = $conn->prepare($notifications_query);
$notif_stmt->bind_param("i", $_SESSION['student_id']);
$notif_stmt->execute();
$notifications = $notif_stmt->get_result();

// Get unread count
$unread_query = "SELECT COUNT(*) as unread FROM notifications 
                WHERE student_id = ? AND is_read = FALSE";
$unread_stmt = $conn->prepare($unread_query);
$unread_stmt->bind_param("i", $_SESSION['student_id']);
$unread_stmt->execute();
$unread_count = $unread_stmt->get_result()->fetch_assoc()['unread'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <title>BCP Library Management System</title>
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/fontawesome.css">
    <link rel="stylesheet" href="assets/css/templatemo-cyborg-gaming.css">
    <link rel="stylesheet" href="assets/css/owl.css">
    <link rel="stylesheet" href="assets/css/animate.css">
    <link rel="stylesheet" href="https://unpkg.com/swiper@7/swiper-bundle.min.css" />
    <link rel="stylesheet" href="assets/css/announcement-slider.css">

    <style>
        .borrow-button,
        .read-button {
            background-color: #60b8eb;
            border: none;
            color: white;
            padding: 10px 20px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            margin: 4px 2px;
            cursor: pointer;
            border-radius: 5px;
        }

        .read-button {
            background-color: #888;
        }

        .logo-container {
            display: flex;
            margin-bottom: 10px;
        }

        .logo-container img {
            max-width: 200px;
            height: auto;
        }

        .slider-dots .dot.active {
            transform: scale(1.3);
        }

        /* Style for the nav profile picture */
        .nav li a img {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
            margin-left: 5px;
            vertical-align: middle;
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 5px;
            color: white;
            font-weight: bold;
            z-index: 1000;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }
        
        .notification.success {
            background-color: #4CAF50;
        }
        
        .notification.error {
            background-color: #f44336;
        }

        .schedule-now-button {
            background-color: #60b8eb;
            border: none;
            color: white;
            padding: 10px 20px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            margin: 4px 2px;
            cursor: pointer;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .schedule-now-button:hover {
            background-color: #4a9cd1;
        }

        .schedule-now-button:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }

        .download-btn {
            background-color: #60b8eb;
            border: none;
            color: white;
            padding: 10px 20px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            margin: 4px 2px;
            cursor: pointer;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .download-btn:hover {
            background-color: #4a9cd1;
        }

        .download-btn:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }

        /* Login form styles */
        .login-form {
            background: #1f2122;
            padding: 30px;
            border-radius: 23px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }

        .login-form .form-group label {
            color: #fff;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .login-form .form-control {
            background: #27292a;
            border: 1px solid #404244;
            color: #fff;
            padding: 12px;
            border-radius: 23px;
            transition: all 0.3s ease;
        }

        .login-form .form-control:focus {
            background: #27292a;
            border-color: #ec6090;
            color: #fff;
            box-shadow: none;
        }

        .login-form .btn-primary {
            background: #ec6090;
            border: none;
            padding: 12px;
            border-radius: 23px;
            font-weight: 500;
            text-transform: uppercase;
            transition: all 0.3s ease;
        }

        .login-form .btn-primary:hover {
            background: #fff;
            color: #ec6090;
        }

        .login-form a {
            color: #ec6090;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .login-form a:hover {
            color: #fff;
        }

        .login-form p {
            color: #666;
            margin: 0;
        }

        /* Ebooks section styles */
        .dropdown-menu {
            background-color: #1f2122;
            border: 1px solid #404244;
            border-radius: 8px;
            padding: 8px 0;
        }

        .dropdown-item {
            color: #fff;
            padding: 8px 20px;
            transition: all 0.3s ease;
        }

        .dropdown-item:hover {
            background-color: #27292a;
            color: #ec6090;
        }

        .nav-link.dropdown-toggle {
            color: #fff;
            cursor: pointer;
        }

        .nav-link.dropdown-toggle:hover {
            color: #ec6090;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-available {
            background-color: #28a745;
            color: white;
        }
        .status-borrowed {
            background-color: #ffc107;
            color: #000;
        }
        .status-unavailable {
            background-color: #dc3545;
            color: white;
        }

        .stock-indicator {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
            margin-bottom: 10px;
        }

        .stock-high {
            background-color:rgb(40, 167, 165);
            color: white;
        }

        .stock-medium {
            background-color: #ffc107;
            color: #000;
        }

        .stock-low {
            background-color: #dc3545;
            color: white;
        }

        .stock-none {
            background-color: #6c757d;
            color: white;
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            padding: 3px 6px;
            border-radius: 50%;
            background: #dc3545;
            color: white;
            font-size: 12px;
        }

        .notification-item {
            padding: 12px 15px;
            border-bottom: 1px solid #404040;
            transition: background-color 0.3s;
        }

        .notification-item:hover {
            background-color: #363636;
        }

        .notification-item.unread {
            background-color: rgba(33, 150, 243, 0.1);
        }

        .notification-time {
            font-size: 0.8em;
            color: #888;
            margin-top: 8px;
        }

        .mark-read-btn {
            padding: 2px 8px;
            font-size: 0.8em;
            margin-left: 10px;
            white-space: nowrap;
        }

        .btn-outline-light {
            border-color: #666;
            color: #fff;
        }

        .btn-outline-light:hover {
            background-color: #666;
            color: #fff;
        }

        #mark-all-read {
            font-size: 0.8em;
            padding: 2px 8px;
        }

        #notification-toggle {
            position: relative;
            display: inline-block;
        }

        /* Add styles for scrollable history */
        .history-container {
            max-height: 400px;
            overflow-y: auto;
            padding: 15px;
            background: #2d2d2d;
            border-radius: 8px;
            margin-top: 20px;
        }

        .history-container::-webkit-scrollbar {
            width: 8px;
        }

        .history-container::-webkit-scrollbar-track {
            background: #1a1a1a;
            border-radius: 4px;
        }

        .history-container::-webkit-scrollbar-thumb {
            background: #404040;
            border-radius: 4px;
        }

        .history-container::-webkit-scrollbar-thumb:hover {
            background: #505050;
        }

        .history-item {
            padding: 15px;
            border-bottom: 1px solid #404040;
            transition: background-color 0.3s;
        }

        .history-item:hover {
            background-color: #363636;
        }

        .history-item:last-child {
            border-bottom: none;
        }

        .notification-popup {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 350px;
            background: #2d2d2d;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            z-index: 1000;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .notification-popup-content {
            padding: 15px;
        }

        .notification-popup-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #404040;
        }

        .notification-popup-header h5 {
            margin: 0;
            color: #fff;
        }

        .close-popup {
            background: none;
            border: none;
            color: #888;
            font-size: 20px;
            cursor: pointer;
            padding: 0;
            line-height: 1;
        }

        .close-popup:hover {
            color: #fff;
        }

        .notification-popup-body {
            max-height: 300px;
            overflow-y: auto;
        }

        .popup-notification-item {
            padding: 10px;
            border-bottom: 1px solid #404040;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .popup-notification-item:hover {
            background-color: #363636;
        }

        .popup-notification-item:last-child {
            border-bottom: none;
        }

        .popup-notification-time {
            font-size: 0.8em;
            color: #888;
            margin-top: 5px;
        }

        /* Add these styles for book cover containers */
        .book-card {
            background: #23272b;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            transition: transform 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .book-card:hover {
            transform: translateY(-5px);
        }

        .book-cover-container {
            width: 100%;
            height: 300px;
            overflow: hidden;
            border-radius: 8px;
            margin-bottom: 15px;
            background: #1a1a1a;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .book-cover-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            transition: transform 0.3s ease;
        }

        .book-cover-container img:hover {
            transform: scale(1.05);
        }

        .book-info {
            padding: 10px 0;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .book-info h5 {
            font-size: 1.1em;
            margin-bottom: 12px;
            color: #fff;
            line-height: 1.4;
            height: 2.8em;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .book-info p {
            color: #888;
            margin-bottom: 8px;
            font-size: 0.9em;
        }

        .book-info .badge {
            margin-bottom: 12px;
        }

        .book-info .btn {
            width: 100%;
            margin-top: auto;
            padding: 8px 15px;
        }

        .book-card-scroll {
            margin-bottom: 30px;
        }

        /* Add these new styles for status indicators */
        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }
        .status-dot.available {
            background-color: #4CAF50;
            box-shadow: 0 0 8px rgba(76, 175, 80, 0.5);
        }
        .status-dot.unavailable {
            background-color: #f44336;
            box-shadow: 0 0 8px rgba(244, 67, 54, 0.5);
        }
        .status-dot.enabled {
            background-color: #4CAF50;
            box-shadow: 0 0 8px rgba(76, 175, 80, 0.5);
        }
        .status-dot.disabled {
            background-color: #f44336;
            box-shadow: 0 0 8px rgba(244, 67, 54, 0.5);
        }
        .status-text {
            font-size: 0.9em;
            color: #fff;
        }

        /* Add these new styles for online status */
        .profile-pic-container {
            position: relative;
            display: inline-block;
        }
        .online-status {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 10px;
            height: 10px;
            background-color: #4CAF50;
            border: 2px solid #1f2122;
            border-radius: 50%;
            box-shadow: 0 0 8px rgba(76, 175, 80, 0.5);
        }
        .offline {
            background-color: #f44336;
            box-shadow: 0 0 8px rgba(244, 67, 54, 0.5);
        }
    </style>
</head>

<body>
    <div id="js-preloader" class="js-preloader">
        <div class="preloader-inner">
            <span class="dot"></span>
            <div class="dots">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </div>

    <!-- Header -->
    <header class="header-area header-sticky">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <nav class="main-nav">
                        <a href="index.php" class="logo">
                            <img src="img\libmsLOGO.png" alt="">
                        </a>
                        <h3>Welcome, <?php echo htmlspecialchars($student['full_name'] ?? ''); ?></h3>
                        <ul class="nav">
                            <li><a href="index.php" class="active">Home</a></li>
                            <li><a href="profile.php">
                                <div class="profile-pic-container">
                                    <?php if ($profile_pic): ?>
                                        <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="">
                                        <span class="online-status <?php echo isset($_SESSION['student_id']) ? '' : 'offline'; ?>"></span>
                                    <?php endif; ?>
                                </div>
                            </a></li>
                            <li><a href="my_schedules.php">My Schedules</a></li>
                            <?php if (!isset($_SESSION['student_id'])): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Login
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                    <li><a class="dropdown-item" href="login.php">Student Login</a></li>
                                </ul>
                            </li>
                            <?php endif; ?>
                            <li><a href="#" id="notification-toggle">
                                <i class="fa fa-bell"></i>
                                <?php if ($unread_count > 0): ?>
                                    <span class="notification-badge"><?php echo $unread_count; ?></span>
                                <?php endif; ?>
                            </a></li>
                        </ul>
                        <a class='menu-trigger'>
                            <span>Menu</span>
                        </a>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <div id="notification-panel" style="display: none; position: fixed; top: 80px; right: 20px; width: 300px; background: #2d2d2d; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.3); z-index: 1000;">
        <div style="padding: 15px; border-bottom: 1px solid #404040; display: flex; justify-content: space-between; align-items: center;">
            <h5 style="margin: 0; color: #fff;">Notifications</h5>
            <button id="mark-all-read" class="btn btn-sm btn-outline-light">Mark All Read</button>
        </div>
        <div style="max-height: 400px; overflow-y: auto;">
            <?php if ($notifications->num_rows > 0): ?>
                <?php while ($notification = $notifications->fetch_assoc()): ?>
                    <div class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>" 
                         data-id="<?php echo $notification['notification_id']; ?>">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div style="color: #fff; flex-grow: 1;"><?php echo htmlspecialchars($notification['message']); ?></div>
                            <button class="mark-read-btn btn btn-sm <?php echo $notification['is_read'] ? 'btn-outline-light' : 'btn-light'; ?>" 
                                    data-id="<?php echo $notification['notification_id']; ?>"
                                    data-read="<?php echo $notification['is_read'] ? '1' : '0'; ?>">
                                <?php echo $notification['is_read'] ? 'Mark Unread' : 'Mark Read'; ?>
                            </button>
                        </div>
                        <div class="notification-time">
                            <?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="padding: 15px; color: #888; text-align: center;">
                    No notifications
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div id="notification-popup" class="notification-popup" style="display: none;">
        <div class="notification-popup-content">
            <div class="notification-popup-header">
                <h5>New Notifications</h5>
                <button class="close-popup">&times;</button>
            </div>
            <div class="notification-popup-body">
                <!-- Unread notifications will be loaded here -->
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="page-content">
                    <div class="main-banner">
                        <div class="row">
                            <div class="col-lg-7">
                                <div class="header-text">
                                    <div class="logo-container">
                                        <img src="img/images-removebg-preview.png" alt="Bestlink Logo">
                                    </div>
                                    <h6>Bestlink College of the Philippines</h6>
                                    <h4><em>LIBRARY</em> MANAGEMENT SYSTEM </h4>
                                </div>
                                <div class="main-button">
                                    <a href="browse.php">Browse Now</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="announcement-slide">
                        <div class="inner-slider">
                            <img src="img/BCP Module Grant Dates (1).png" alt="Image 1">
                            <img src="img/BCP SMS Portal (2).jpg" alt="Image 2">
                            <img src="img/BCP SMS Portal (3).png" alt="Image 3">
                            <img src="img/bg(1).jpg" alt="Image 4">
                        </div>

                        <div class="slider-dots">
                        </div>
                    </div>
                    <div class="most-popular">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="heading-section">
                                    <h4><em>Available</em> Books</h4>
                                </div>
                            </div>
                            <!-- Book Section Search, Category, and Status Filters -->
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <input type="text" id="bookSearchInput" class="form-control" placeholder="Search books by title or author...">
                                </div>
                                <div class="col-md-3">
                                    <select id="bookCategoryFilter" class="form-control">
                                        <option value="">All Categories</option>
                                        <?php
                                        $book_cat_result = $conn->query("SELECT DISTINCT category FROM books ORDER BY category ASC");
                                        while ($cat = $book_cat_result->fetch_assoc()) {
                                            echo '<option value="' . htmlspecialchars($cat['category']) . '">' . htmlspecialchars($cat['category']) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select id="bookStatusFilter" class="form-control">
                                        <option value="">All Status</option>
                                        <option value="Available">Available</option>
                                        <option value="Borrowed">Borrowed</option>
                                        <option value="Unavailable">Unavailable</option>
                                    </select>
                                </div>
                            </div>
                            <?php
                            if ($books_result && $books_result->num_rows > 0):
                                while ($book = $books_result->fetch_assoc()):
                                    $already_borrowed = false;
                                    $is_pending_return = false;
                                    $is_pending_approval = false;
                                    if (isset($_SESSION['student_id']) && ($_SESSION['user_type'] ?? '') === 'student') {
                                        $student_id = $_SESSION['student_id'];
                                        // Check if this user has an active borrow
                                        $check_borrow = $conn->prepare("SELECT 1 FROM borrowed_books WHERE student_id = ? AND book_id = ? AND return_date IS NULL");
                                        $check_borrow->bind_param("ii", $student_id, $book['book_id']);
                                        $check_borrow->execute();
                                        $check_borrow->store_result();
                                        if ($check_borrow->num_rows > 0) {
                                            $already_borrowed = true;
                                        }
                                        $check_borrow->close();

                                        // Check if this user has a pending return
                                        $pending_stmt = $conn->prepare("SELECT 1 FROM borrowed_books WHERE student_id = ? AND book_id = ? AND return_date IS NOT NULL AND admin_confirmed_return = 0");
                                        $pending_stmt->bind_param("ii", $student_id, $book['book_id']);
                                        $pending_stmt->execute();
                                        $pending_stmt->store_result();
                                        if ($pending_stmt->num_rows > 0) {
                                            $is_pending_return = true;
                                        }
                                        $pending_stmt->close();

                                        // Check if this user has a pending approval
                                        $pending_approval_stmt = $conn->prepare("SELECT 1 FROM book_schedules WHERE student_id = ? AND book_id = ? AND status = 'pending'");
                                        $pending_approval_stmt->bind_param("ii", $student_id, $book['book_id']);
                                        $pending_approval_stmt->execute();
                                        $pending_approval_stmt->store_result();
                                        if ($pending_approval_stmt->num_rows > 0) {
                                            $is_pending_approval = true;
                                        }
                                        $pending_approval_stmt->close();
                                    }
                            ?>
                            <div class="col-lg-3 col-sm-6 book-card-scroll" data-title="<?php echo strtolower(htmlspecialchars($book['title'])); ?>" 
                                 data-author="<?php echo strtolower(htmlspecialchars($book['author'])); ?>" 
                                 data-category="<?php echo strtolower(htmlspecialchars($book['category'])); ?>" 
                                 data-status="<?php echo strtolower($book['status'] ?? 'available'); ?>">
                                <div class="book-card" id="book-<?php echo $book['book_id']; ?>">
                                    <div class="book-cover-container">
                                        <img src="assets/images/<?php echo htmlspecialchars($book['cover_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($book['title']); ?>"
                                             onerror="this.src='assets/images/default-book-cover.jpg'">
                                    </div>
                                    <div class="book-info">
                                        <h5><?php echo htmlspecialchars($book['title']); ?></h5>
                                        <p class="author">By <?php echo htmlspecialchars($book['author']); ?></p>
                                        <p class="category"><?php echo htmlspecialchars($book['category']); ?></p>
                                        <div class="status-indicator">
                                            <span class="status-dot <?php echo strtolower($book['status']); ?>"></span>
                                            <span class="status-text"><?php echo htmlspecialchars($book['status']); ?></span>
                                        </div>
                                        <div class="stock-indicator <?php 
                                            if ($book['available_stock'] > 10) echo 'stock-high';
                                            else if ($book['available_stock'] > 5) echo 'stock-medium';
                                            else if ($book['available_stock'] > 0) echo 'stock-low';
                                            else echo 'stock-none';
                                        ?>">
                                            Stock: <?php echo htmlspecialchars($book['available_stock']); ?>
                                        </div>
                                        <?php if ($book['status'] === 'Available' && $book['available_stock'] > 0): ?>
                                            <a href="borrow.php?book_id=<?php echo $book['book_id']; ?>" class="btn btn-primary borrow-btn">Borrow</a>
                                        <?php else: ?>
                                            <button class="btn btn-secondary" disabled>Unavailable</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php
                                endwhile;
                            else:
                            ?>
                            <div class="col-12">
                                <p>No books found.</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="gaming-library">
                        <div class="col-lg-12">
                            <div class="heading-section">
                                <h4><em>Available</em> eBooks</h4>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <input type="text" id="ebookSearchInput" class="form-control" placeholder="Search ebooks by title, author, or category...">
                                </div>
                                <div class="col-md-3">
                                    <select id="ebookCategoryFilter" class="form-control">
                                        <option value="">All Categories</option>
                                        <?php
                                        $cat_result = $conn->query("SELECT DISTINCT category FROM ebooks ORDER BY category ASC");
                                        while ($cat = $cat_result->fetch_assoc()) {
                                            echo '<option value="' . htmlspecialchars($cat['category']) . '">' . htmlspecialchars($cat['category']) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select id="ebookStatusFilter" class="form-control">
                                        <option value="">All Status</option>
                                        <option value="Available">Available</option>
                                        <option value="Unavailable">Unavailable</option>
                                    </select>
                                </div>
                            </div>
                            <div id="ebooksScrollable" style="overflow-x: auto; white-space: nowrap; padding-bottom: 10px;">
                                <?php
                                if ($ebooks_result && $ebooks_result->num_rows > 0):
                                    while ($ebook = $ebooks_result->fetch_assoc()):
                                ?>
                                <div class="ebook-card-scroll" data-title="<?php echo strtolower(htmlspecialchars($ebook['title'])); ?>" data-author="<?php echo strtolower(htmlspecialchars($ebook['author'])); ?>" data-category="<?php echo strtolower(htmlspecialchars($ebook['category'])); ?>" data-status="<?php echo strtolower($ebook['status'] ?? 'available'); ?>" style="display: inline-block; vertical-align: top; width: 260px; margin-right: 16px; background: #23272b; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); padding: 16px; color: #fff;">
                                    <img src="assets/images/<?php echo htmlspecialchars($ebook['cover_image'] ?? ''); ?>" alt="<?php echo htmlspecialchars($ebook['title']); ?>" style="width: 100%; height: 180px; object-fit: cover; border-radius: 6px; margin-bottom: 10px;">
                                    <div style="font-weight: bold; font-size: 1.1em; margin-bottom: 4px; max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        <?php 
                                            $title = htmlspecialchars($ebook['title']);
                                            echo (mb_strlen($title) > 30) ? mb_substr($title, 0, 30) . '...' : $title;
                                        ?>
                                    </div>
                                    <div style="color: #60b8eb; margin-bottom: 4px; max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        By <?php 
                                            $author = htmlspecialchars($ebook['author']);
                                            echo (mb_strlen($author) > 25) ? mb_substr($author, 0, 25) . '...' : $author;
                                        ?>
                                    </div>
                                    <div style="font-size: 0.95em; margin-bottom: 4px;">Category: <?php echo htmlspecialchars($ebook['category']); ?></div>
                                    <div style="font-size: 0.95em; margin-bottom: 4px;">Price: <?php echo ($ebook['price'] > 0) ? '$' . number_format($ebook['price'], 2) : 'Free'; ?></div>
                                    <div style="font-size: 0.95em; margin-bottom: 4px;">
                                        <div class="status-indicator">
                                            <span class="status-dot <?php echo strtolower($ebook['status']); ?>"></span>
                                            <span class="status-text"><?php echo htmlspecialchars($ebook['status']); ?></span>
                                        </div>
                                    </div>
                                    <div style="font-size: 0.95em; margin-bottom: 8px; max-height: 48px; overflow: hidden; text-overflow: ellipsis; white-space: pre-line;">
                                        Description: <?php 
                                            $desc = htmlspecialchars($ebook['description']);
                                            echo (mb_strlen($desc) > 80) ? mb_substr($desc, 0, 80) . '...' : $desc;
                                        ?>
                                    </div>
                                    <div>
                                        <?php if (($ebook['status'] ?? 'Available') == 'Available' && !empty($ebook['file_path'])): ?>
                                            <a href="read.php?ebook_id=<?php echo $ebook['id']; ?>" class="btn btn-info btn-sm" target="_blank">Read</a>
                                            <?php if ($ebook['price'] > 0): ?>
                                                <button class="btn btn-secondary btn-sm" disabled>Pay $<?php echo number_format($ebook['price'], 2); ?> to Download</button>
                                            <?php elseif (($ebook['download_status'] ?? 'Enabled') == 'Enabled'): ?>
                                                <a href="download_ebook.php?id=<?php echo $ebook['id']; ?>" class="btn btn-success btn-sm">Download</a>
                                            <?php else: ?>
                                                <button class="btn btn-secondary btn-sm" disabled>Download Disabled</button>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <button class="btn btn-info btn-sm" disabled>Read</button>
                                            <button class="btn btn-secondary btn-sm" disabled>Download Unavailable</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php
                                    endwhile;
                                else:
                                ?>
                                <div style="color: #fff;">No eBooks found.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <p>Copyright © 2024 <a href="#">BCP Library Management System</a>. All rights reserved.
                        <br>Design: <a href="https://templatemo.com" target="_blank"
                            title="free CSS templates">TemplateMo</a>
                    </p>
                </div>
            </div>
        </div>
    </footer>
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
    <script src="assets/js/isotope.min.js"></script>
    <script src="assets/js/owl-carousel.js"></script>
    <script src="assets/js/tabs.js"></script>
    <script src="assets/js/popup.js"></script>
    <script src="assets/js/custom.js"></script>
    <script src="assets/js/functions.js"></script>
    <script>
    $(document).ready(function() {
        // Initialize Bootstrap dropdowns
        $('.dropdown-toggle').dropdown();

        // Function to show smooth notification
        function showNotification(message, type = 'success') {
            const notification = $('<div>')
                .addClass('notification')
                .addClass(type)
                .text(message)
                .hide()
                .appendTo('body')
                .fadeIn(300);
            
            setTimeout(() => {
                notification.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 2000);
        }

        // Handle book borrowing
        $('.borrow-button').off('click').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var bookId = $(this).data('book-id');
            var button = $(this);
            
            // Check if user is logged in
            <?php if (!isset($_SESSION['student_id'])): ?>
                window.location.href = 'login.php?redirect=schedule_borrow.php&book_id=' + bookId;
                return;
            <?php endif; ?>
            
            // Disable button immediately to prevent double clicks
            button.prop('disabled', true);
            
            $.ajax({
                url: 'schedule_borrow.php',
                type: 'POST',
                data: { book_id: bookId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showNotification(response.message);
                        button.text('Scheduled');
                        if (typeof updateBookStats === 'function') {
                            updateBookStats();
                        }
                    } else {
                        showNotification(response.message || 'Unable to schedule book. Please try again.', 'error');
                        button.prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    button.prop('disabled', false);
                    showNotification('Unable to schedule book at this time. Please try again.', 'error');
                }
            });
        });

        // Handle eBook downloads
        $('.main-border-button a').click(function(e) {
            e.preventDefault();
            var downloadUrl = $(this).attr('href');
            
            <?php if (!isset($_SESSION['student_id'])): ?>
                window.location.href = 'login.php?redirect=download.php&url=' + encodeURIComponent(downloadUrl);
                return;
            <?php endif; ?>
            
            window.location.href = downloadUrl;
        });

        // Handle form validation
        function validateForm() {
            const studentId = document.getElementById('student_id').value.trim();
            const password = document.getElementById('password').value.trim();
            
            if (!studentId || !password) {
                showNotification("Please fill in all fields", "error");
                return false;
            }
            return true;
        }

        // Handle ebook download
        $('.download-btn').click(function() {
            var button = $(this);
            var ebookId = button.data('ebook-id');
            
            // Disable button immediately to prevent double clicks
            button.prop('disabled', true);
            
            $.ajax({
                url: 'download_ebook.php',
                type: 'POST',
                data: { ebook_id: ebookId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showNotification(response.message);
                        // If there's a download URL, trigger the download
                        if (response.download_url) {
                            window.location.href = response.download_url;
                        }
                    } else {
                        showNotification(response.message || 'Unable to download e-book. Please try again.', 'error');
                        button.prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    button.prop('disabled', false);
                    showNotification('Unable to download e-book at this time. Please try again.', 'error');
                }
            });
        });

        // Toggle notification panel
        $('#notification-toggle').click(function(e) {
            e.preventDefault();
            $('#notification-panel').toggle();
        });

        // Close notification panel when clicking outside
        $(document).click(function(e) {
            if (!$(e.target).closest('#notification-panel, #notification-toggle').length) {
                $('#notification-panel').hide();
            }
        });

        // Mark single notification as read/unread
        $(document).on('click', '.mark-read-btn', function(e) {
            e.stopPropagation();
            const button = $(this);
            const notificationId = button.data('id');
            const isRead = button.data('read') === '1';
            const item = button.closest('.notification-item');
            
            $.ajax({
                url: 'mark_notification_read.php',
                method: 'POST',
                data: { 
                    notification_id: notificationId,
                    mark_read: !isRead
                },
                success: function(response) {
                    if (response.success) {
                        if (!isRead) {
                            item.removeClass('unread');
                            button.removeClass('btn-light').addClass('btn-outline-light');
                            button.text('Mark Unread');
                            button.data('read', '1');
                        } else {
                            item.addClass('unread');
                            button.removeClass('btn-outline-light').addClass('btn-light');
                            button.text('Mark Read');
                            button.data('read', '0');
                        }
                        updateNotificationBadge();
                    } else {
                        console.error('Error updating notification:', response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                }
            });
        });

        // Mark all notifications as read
        $('#mark-all-read').click(function() {
            $.ajax({
                url: 'mark_notification_read.php',
                method: 'POST',
                data: { mark_all_read: true },
                success: function(response) {
                    if (response.success) {
                        $('.notification-item').removeClass('unread');
                        $('.mark-read-btn').removeClass('btn-light').addClass('btn-outline-light')
                            .text('Mark Unread').data('read', '1');
                        updateNotificationBadge();
                        showNotification('All notifications marked as read', 'success');
                    } else {
                        console.error('Error marking all as read:', response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                }
            });
        });
        
        // Function to update notification badge count
        function updateNotificationBadge() {
            $.ajax({
                url: 'get_unread_notifications.php',
                method: 'GET',
                success: function(response) {
                    const count = parseInt(response);
                    const badge = $('.notification-badge');
                    
                    if (count > 0) {
                        if (badge.length) {
                            badge.text(count);
                            badge.show();
                        } else {
                            $('#notification-toggle').append(`<span class="notification-badge">${count}</span>`);
                        }
                    } else {
                        badge.hide();
                    }
                }
            });
        }
        
        // Function to show popup with unread notifications
        function showUnreadNotifications() {
            $.ajax({
                url: 'get_unread_notifications.php',
                method: 'GET',
                success: function(response) {
                    const count = parseInt(response);
                    if (count > 0) {
                        // Fetch unread notifications
                        $.ajax({
                            url: 'get_notifications.php',
                            method: 'GET',
                            data: { unread_only: true },
                            success: function(response) {
                                try {
                                    const notifications = JSON.parse(response);
                                    if (notifications.length > 0) {
                                        let html = '';
                                        notifications.forEach(notification => {
                                            html += `
                                                <div class="popup-notification-item" data-id="${notification.notification_id}">
                                                    <div style="color: #fff;">${notification.message}</div>
                                                    <div class="popup-notification-time">
                                                        ${new Date(notification.created_at).toLocaleString()}
                                                    </div>
                                                </div>
                                            `;
                                        });
                                        $('.notification-popup-body').html(html);
                                        $('#notification-popup').show();
                                    }
                                } catch (e) {
                                    console.error('Error parsing notifications:', e);
                                }
                            }
                        });
                    }
                }
            });
        }

        // Show popup when page loads
        showUnreadNotifications();

        // Close popup when clicking the close button
        $('.close-popup').click(function() {
            $('#notification-popup').hide();
        });

        // Mark notification as read when clicked in popup
        $(document).on('click', '.popup-notification-item', function() {
            const notificationId = $(this).data('id');
            const item = $(this);
            
            $.ajax({
                url: 'mark_notification_read.php',
                method: 'POST',
                data: { notification_id: notificationId },
                success: function(response) {
                    if (response.success) {
                        item.fadeOut(300, function() {
                            $(this).remove();
                            if ($('.popup-notification-item').length === 0) {
                                $('#notification-popup').hide();
                            }
                        });
                        updateNotificationBadge();
                    }
                }
            });
        });

        // Auto-hide popup after 10 seconds
        setTimeout(function() {
            $('#notification-popup').fadeOut(500);
        }, 10000);
    });
    </script>
    <script>
    // Real-time search and filter for eBooks
    const ebookSearchInput = document.getElementById('ebookSearchInput');
    const ebookCategoryFilter = document.getElementById('ebookCategoryFilter');
    const ebookStatusFilter = document.getElementById('ebookStatusFilter');
    const ebookCards = document.querySelectorAll('.ebook-card-scroll');

    function filterEbooks() {
        const search = ebookSearchInput.value.toLowerCase();
        const category = ebookCategoryFilter.value.toLowerCase();
        const status = ebookStatusFilter.value.toLowerCase();
        ebookCards.forEach(card => {
            const title = card.getAttribute('data-title');
            const author = card.getAttribute('data-author');
            const cat = card.getAttribute('data-category');
            const stat = card.getAttribute('data-status');
            const matchesSearch = title.includes(search) || author.includes(search) || cat.includes(search);
            const matchesCategory = !category || cat === category;
            const matchesStatus = !status || stat === status;
            card.style.display = (matchesSearch && matchesCategory && matchesStatus) ? 'inline-block' : 'none';
        });
    }
    if (ebookSearchInput) ebookSearchInput.addEventListener('input', filterEbooks);
    if (ebookCategoryFilter) ebookCategoryFilter.addEventListener('change', filterEbooks);
    if (ebookStatusFilter) ebookStatusFilter.addEventListener('change', filterEbooks);
    </script>
    <script>
    // Real-time search and filter for books
    const bookSearchInput = document.getElementById('bookSearchInput');
    const bookCategoryFilter = document.getElementById('bookCategoryFilter');
    const bookStatusFilter = document.getElementById('bookStatusFilter');
    const bookCards = document.querySelectorAll('.book-card-scroll');
    function filterBooks() {
        const search = bookSearchInput.value.toLowerCase();
        const category = bookCategoryFilter.value.toLowerCase();
        const status = bookStatusFilter.value.toLowerCase();
        bookCards.forEach(card => {
            const title = card.getAttribute('data-title');
            const author = card.getAttribute('data-author');
            const cat = card.getAttribute('data-category');
            const stat = card.getAttribute('data-status');
            const matchesSearch = title.includes(search) || author.includes(search);
            const matchesCategory = !category || cat === category;
            const matchesStatus = !status || stat === status;
            card.style.display = (matchesSearch && matchesCategory && matchesStatus) ? '' : 'none';
        });
    }
    if (bookSearchInput) bookSearchInput.addEventListener('input', filterBooks);
    if (bookCategoryFilter) bookCategoryFilter.addEventListener('change', filterBooks);
    if (bookStatusFilter) bookStatusFilter.addEventListener('change', filterBooks);
    </script>
    <script>
    // Remove the old borrowBook function and update the refreshBooks function
    function refreshBooks() {
        $.ajax({
            url: 'refresh_index.php',
            type: 'POST',
            data: { action: 'refresh_books' },
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                try {
                    const result = JSON.parse(response);
                    if (result.success && result.books) {
                        result.books.forEach(book => {
                            const bookCard = $(`#book-${book.book_id}`);
                            if (bookCard.length) {
                                // Update stock indicator
                                let stockClass = '';
                                if (book.stock > 10) {
                                    stockClass = 'stock-high';
                                } else if (book.stock > 5) {
                                    stockClass = 'stock-medium';
                                } else if (book.stock > 0) {
                                    stockClass = 'stock-low';
                                } else {
                                    stockClass = 'stock-none';
                                }
                                
                                // Update stock display
                                const stockIndicator = bookCard.find('.stock-indicator');
                                stockIndicator.attr('class', `stock-indicator ${stockClass}`);
                                stockIndicator.text(`Stock: ${book.stock}`);
                                
                                // Update status
                                const statusDot = bookCard.find('.status-dot');
                                const statusText = bookCard.find('.status-text');
                                statusDot.attr('class', `status-dot ${book.status.toLowerCase()}`);
                                statusText.text(book.status);
                                
                                // Update borrow button
                                const borrowButton = bookCard.find('.borrow-btn');
                                if (book.status === 'Available' && book.stock > 0) {
                                    borrowButton.prop('disabled', false)
                                        .text('Borrow')
                                        .attr('href', `borrow.php?book_id=${book.book_id}`);
                                } else {
                                    borrowButton.replaceWith('<button class="btn btn-secondary" disabled>Unavailable</button>');
                                }
                                
                                // Update data attributes for filtering
                                bookCard.closest('.book-card-scroll')
                                    .attr('data-status', book.status.toLowerCase());
                            }
                        });
                    }
                } catch (e) {
                    console.error('Error refreshing books:', e);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error refreshing books:', error);
            }
        });
    }
    </script>
</body>

</html>