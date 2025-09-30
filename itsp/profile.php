<?php
session_name('student_session');
session_start();

// Include the database configuration file
require 'config.php'; // or db.php

// Check if the user is logged in
if (!isset($_SESSION['student_id'])) {
    die("Please log in to view your borrowed schedule.");
}

$student_id = $_SESSION['student_id'];

// Initialize database connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Fetch student information
$student_sql = "SELECT * FROM users WHERE student_id = ?";
$student_stmt = $conn->prepare($student_sql);

// Check if prepare failed
if ($student_stmt === false) {
    die('Error preparing SQL statement: ' . $conn->error);  // This will output the specific error
}

$student_stmt->bind_param("i", $student_id);
$student_stmt->execute();
$student_result = $student_stmt->get_result();
$student = $student_result->fetch_assoc();

// Check if user has any active borrows
$status_query = "SELECT COUNT(*) as cnt FROM borrowed_books WHERE student_id = ? AND return_date IS NULL";
$stmt = $conn->prepare($status_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$status_result = $stmt->get_result()->fetch_assoc();
$user_status = ($status_result['cnt'] > 0) ? "With Borrowed Book" : "All Returned";
$stmt->close();

// Fetch returned books
$returned_query = "
    SELECT b.*, bb.borrow_date, bb.return_date
    FROM borrowed_books bb
    JOIN books b ON bb.book_id = b.book_id
    WHERE bb.student_id = ? AND bb.return_date IS NOT NULL
    ORDER BY bb.return_date DESC
";
$stmt = $conn->prepare($returned_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$returned_books = $stmt->get_result();

// Fetch book statistics
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM borrowed_books WHERE student_id = ? AND return_date IS NOT NULL) as books_returned,
    (SELECT COUNT(*) FROM borrowed_books WHERE student_id = ? AND return_date IS NULL) as books_borrowed,
    (SELECT COUNT(*) FROM books WHERE available_quantity > 0) as books_available";

$stats_stmt = $conn->prepare($stats_query);

if ($stats_stmt === false) {
    die('Error preparing statistics statement: ' . $conn->error);
}

if (!$stats_stmt->bind_param("ii", $student_id, $student_id)) {
    die('Error binding parameters: ' . $stats_stmt->error);
}

if (!$stats_stmt->execute()) {
    die('Error executing statistics query: ' . $stats_stmt->error);
}

$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();
$stats_stmt->close();

// Fetch notifications
$notifications_query = "SELECT * FROM notifications 
                      WHERE student_id = ? 
                      ORDER BY created_at DESC 
                      LIMIT 10";
$notif_stmt = $conn->prepare($notifications_query);

if ($notif_stmt === false) {
    die('Error preparing notifications statement: ' . $conn->error);
}

$notif_stmt->bind_param("i", $student_id);
$notif_stmt->execute();
$notifications = $notif_stmt->get_result();

// Get unread count
$unread_query = "SELECT COUNT(*) as unread FROM notifications 
                WHERE student_id = ? AND is_read = FALSE";
$unread_stmt = $conn->prepare($unread_query);

if ($unread_stmt === false) {
    die('Error preparing unread count statement: ' . $conn->error);
}

$unread_stmt->bind_param("i", $student_id);
$unread_stmt->execute();
$unread_count = $unread_stmt->get_result()->fetch_assoc()['unread'];

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_picture'])) {
    $target_dir = "assets/images/profile_pictures/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $target_file = $target_dir . basename($_FILES["profile_picture"]["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    $new_filename = "profile_" . $student_id . "." . $imageFileType;
    $target_file = $target_dir . $new_filename;
    
    // Check if image file is a actual image
    $check = getimagesize($_FILES["profile_picture"]["tmp_name"]);
    if($check !== false) {
        // Allow certain file formats
        if($imageFileType == "jpg" || $imageFileType == "png" || $imageFileType == "jpeg" || $imageFileType == "gif") {
            if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
                // Update database with new profile picture path
                $update_sql = "UPDATE users SET profile_pic = ? WHERE student_id = ?";
                $update_stmt = $conn->prepare($update_sql);
                
                if ($update_stmt === false) {
                    die("Error preparing profile picture update statement: " . $conn->error);
                }
                
                $relative_path = "assets/images/profile_pictures/" . $new_filename;
                $update_stmt->bind_param("si", $relative_path, $student_id);
                $update_stmt->execute();
                
                // Update the student array with new path
                $student['profile_pic'] = $relative_path;
                
                $success_message = "Profile picture updated successfully!";
                
                // Return JSON response for AJAX upload
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    die(json_encode([
                        'success' => true,
                        'message' => 'Profile picture updated successfully!',
                        'newPath' => $relative_path
                    ]));
                }
            } else {
                $error_message = "Sorry, there was an error uploading your file.";
            }
        } else {
            $error_message = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        }
    } else {
        $error_message = "File is not an image.";
    }
}

// Check if there is an error in the URL (e.g., book not found or out of stock)
if (!isset($error_message)) {
    $error_message = '';
    if (isset($_GET['error'])) {
        if ($_GET['error'] == 'book_not_found') {
            $error_message = "The book was not found in the system.";
        } elseif ($_GET['error'] == 'out_of_stock') {
            $error_message = "Sorry, the book is out of stock.";
        }
    }
}

// Check if the book borrowing was successful
if (!isset($success_message)) {
    $success_message = '';
    if (isset($_GET['success']) && $_GET['success'] == 'book_borrowed') {
        $success_message = "✅ Your book was borrowed successfully!";
    }
}

// Fetch the student's borrowed books and calculate overdue fees
$sql = "SELECT bb.record_id, bb.book_id, bb.borrow_date, bb.due_date, bb.status, bb.admin_confirmed_return, bb.return_date, b.title, b.cover_image
        FROM borrowed_books bb
        JOIN books b ON bb.book_id = b.book_id
        WHERE bb.student_id = ? AND bb.return_date IS NULL";
$stmt = $conn->prepare($sql);

// Check if prepare failed
if (!$stmt) {
    die("Prepare failed (fetch borrowed books): " . $conn->error);
}

$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <title>Profile - BCP Library Management System</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> <!-- jQuery for AJAX -->
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/fontawesome.css">
    <link rel="stylesheet" href="assets/css/templatemo-cyborg-gaming.css">
    <link rel="stylesheet" href="assets/css/owl.css">
    <link rel="stylesheet" href="assets/css/animate.css">
    <link rel="stylesheet" href="https://unpkg.com/swiper@7/swiper-bundle.min.css" />
    <link rel="stylesheet" href="assets/css/styles.css">

    <style>
        .table-container {
            background: #2d2d2d;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            padding: 20px;
            margin-bottom: 30px;
        }

        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 0;
            color: #e0e0e0;
            background: #2d2d2d;
        }

        .table th {
            background: #1a1a1a;
            color: #ffffff;
            font-weight: 500;
            padding: 12px 15px;
            text-align: left;
            border: none;
        }

        .table td {
            padding: 12px 15px;
            border-bottom: 1px solid #404040;
            vertical-align: middle;
            color: #e0e0e0;
            background: #2d2d2d;
        }

        .table tbody tr:hover {
            background-color: #404040;
            color: #ffffff;
        }

        .table tbody tr:nth-child(even) td {
            background: #363636;
        }

        .table tbody tr:nth-child(odd) td {
            background: #2d2d2d;
        }

        .table img {
            max-width: 50px;
            height: auto;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
        }

        .status.pending {
            background: rgb(91, 91, 91);
            color: #ffffff;
        }

        .status.borrowed {
            background: #404040;
            color: #ffffff;
        }

        .status.returned {
            background: #2d2d2d;
            color: #4CAF50;
            border: 1px solid #4CAF50;
        }

        .return-book {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            background: #404040;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .return-book:hover {
            background: #505050;
            transform: translateY(-1px);
        }

        .return-book:disabled {
            background: #2d2d2d;
            cursor: not-allowed;
            color: #808080;
            border: 1px solid #404040;
        }

        .section-title {
            color: #ffffff;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #404040;
            font-weight: 600;
        }

        .overdue-fee {
            color: #ff6b6b;
            font-weight: 500;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
        }

        .badge-warning {
            background: #404040;
            color: #ffd700;
        }

        .badge-success {
            background: #2d2d2d;
            color: #4CAF50;
            border: 1px solid #4CAF50;
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
            padding: 15px;
            border-bottom: 1px solid #404040;
            cursor: pointer;
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
            margin-top: 5px;
        }

        #notification-toggle {
            position: relative;
            display: inline-block;
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
            width: 12px;
            height: 12px;
            background-color: #4CAF50;
            border: 2px solid #1f2122;
            border-radius: 50%;
            box-shadow: 0 0 8px rgba(76, 175, 80, 0.5);
        }
        .offline {
            background-color: #f44336;
            box-shadow: 0 0 8px rgba(244, 67, 54, 0.5);
        }
        .profile-pic {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #4CAF50;
            box-shadow: 0 0 15px rgba(76, 175, 80, 0.3);
        }
    </style>
</head>

<body>

    <header class="header-area header-sticky">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <nav class="main-nav">
                        <a href="index.html" class="logo">
                            <img src="img\libmsLOGO.png" alt="">
                        </a>
                        <h3>Welcome, <?php echo htmlspecialchars($student['full_name'] ?? ''); ?>
                        </h3>
                        <div class="search-input">
                            <form id="search" action="#">
                                
                                <i class="fa fa-search"></i>
                            </form>
                        </div>
                        <ul class="nav">
                            <li><a href="index.php" class="active">Home</a></li>
                            <li><a href="profile.php">Profile <?php if (isset($student['profile_pic']) && !empty($student['profile_pic'])): ?><img src="<?php echo htmlspecialchars($student['profile_pic']); ?>" alt=""><?php endif; ?></a></li>
                            <li><a href="my_schedules.php">My Schedules</a></li>
                            <li><a href="#" id="notification-toggle">
                                Notifications
                                <?php if ($unread_count > 0): ?>
                                    <span class="notification-badge"><?php echo $unread_count; ?></span>
                                <?php endif; ?>
                            </a></li>
                            <?php if (!isset($_SESSION['student_id'])): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Login
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                    <li><a class="dropdown-item" href="login.php">Login</a></li>
                                </ul>
                            </li>
                            <?php endif; ?>
                        </ul>
                        <a class='menu-trigger'>
                            <span>Menu</span>
                        </a>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="page-content">
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success_message): ?>
                        <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="main-profile">
                                <div class="row">
                                    <div class="col-lg-4">
                                    <div class="profile-pic-container">
    <?php if (isset($student['profile_pic']) && !empty($student['profile_pic'])): ?>
        <img src="<?php echo htmlspecialchars($student['profile_pic']); ?>" alt="Profile Picture" class="profile-pic">
        <span class="online-status <?php echo isset($_SESSION['student_id']) ? '' : 'offline'; ?>"></span>
    <?php else: ?>
        <img src="assets/images/default-profile.jpg" alt="Default Profile Picture" class="profile-pic">
        <span class="online-status <?php echo isset($_SESSION['student_id']) ? '' : 'offline'; ?>"></span>
    <?php endif; ?>
    
    <form method="post" enctype="multipart/form-data" class="profile-picture-upload" id="profile-upload-form">
        <label for="profile-upload" style="margin-bottom: 0;">
            <?php if (isset($student['profile_pic']) && !empty($student['profile_pic'])): ?>
                <i class="fa fa-camera"></i>
            <?php else: ?>
                <span style="font-size: 14px;">Upload Photo</span>
            <?php endif; ?>
        </label>
        <input type="file" id="profile-upload" name="profile_picture" accept="image/*">
    </form>
</div>
</div>
<div class="col-lg-4 align-self-center">
    <div class="main-info header-text">
        <span>Student ID: <?php echo htmlspecialchars($student_id); ?></span>
        <h4><?php echo htmlspecialchars($student['full_name'] ?? ''); ?></h4>
        <p><?php echo htmlspecialchars($student['email'] ?? ''); ?></p>
        <div class="main-border-button">


        
            <a href="logout.php">Log Out</a>
        </div>
    </div>
</div>
                                    <div class="col-lg-4 align-self-center">
                                        <ul>
                                            <li>Books Returned <span><?php echo $stats['books_returned']; ?></span></li>
                                            <li>Books Borrowed <span><?php echo $stats['books_borrowed']; ?></span></li>
                                            <li>Books Available <span><?php echo $stats['books_available']; ?></span></li>
                                        </ul>
                                    </div>
                                </div>

                                <!-- Borrowed Books Table -->
                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="table-container">
                                            <h4 class="section-title">Currently Borrowed Books</h4>
                                            <?php
                                            if ($result->num_rows > 0) {
                                                echo "<table class='table'>
                                                        <thead>
                                                            <tr>
                                                                <th>Book Title</th>
                                                                <th>Cover</th>
                                                                <th>Borrow Date</th>
                                                                <th>Due Date</th>
                                                                <th>Status</th>
                                                                <th>Overdue Fee</th>
                                                                <th>Action</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>";
                                                
                                                while ($row = $result->fetch_assoc()) {
                                                    $borrow_date = date('Y-m-d', strtotime($row['borrow_date']));
                                                    $due_date = date('Y-m-d', strtotime($row['due_date']));
                                                    $current_date = date('Y-m-d');
                                                    $overdue_fee = 0;

                                                    if ($current_date > $due_date && empty($row['return_date'])) {
                                                        $days_overdue = (strtotime($current_date) - strtotime($due_date)) / (60 * 60 * 24);
                                                        $overdue_fee = $days_overdue * 1;
                                                    }

                                                    $status_class = '';
                                                    $status_text = '';
                                                    if ($row['admin_confirmed_return'] == 0) {
                                                        if (empty($row['return_date'])) {
                                                            $status_class = $row['status'] == 'pending' ? 'pending' : 'borrowed';
                                                            $status_text = $row['status'] == 'pending' ? 'Pending Approval' : 'Borrowed';
                                                        } else {
                                                            $status_class = 'pending';
                                                            $status_text = 'Pending Return';
                                                        }
                                                    } else {
                                                        $status_class = 'returned';
                                                        $status_text = 'Returned';
                                                    }

                                                    echo "<tr id='book-".$row['book_id']."'>
                                                            <td>" . htmlspecialchars($row['title']) . "</td>
                                                            <td><img src='assets/images/" . htmlspecialchars($row['cover_image']) . "' alt='" . htmlspecialchars($row['title']) . "'></td>
                                                            <td>" . htmlspecialchars($borrow_date) . "</td>
                                                            <td>" . htmlspecialchars($due_date) . "</td>
                                                            <td><span class='status " . $status_class . "'>" . $status_text . "</span></td>
                                                            <td class='" . ($overdue_fee > 0 ? 'overdue-fee' : '') . "'>" . 
                                                            ($overdue_fee > 0 ? "$" . number_format($overdue_fee, 2) : "No fee") . 
                                                            "</td>";

                                                    if ($row['admin_confirmed_return'] == 0) {
                                                        if (empty($row['return_date'])) {
                                                            echo "<td><button class='return-book' data-record-id='".$row['record_id']."'>Return Book</button></td>";
                                                        } else {
                                                            echo "<td><button class='return-book' disabled>Pending Approval</button></td>";
                                                        }
                                                    } else {
                                                        echo "<td><button class='return-book' disabled>Returned</button></td>";
                                                    }

                                                    echo "</tr>";
                                                }
                                                echo "</tbody></table>";
                                            } else {
                                                echo "<p class='text-center'>You haven't borrowed any books yet.</p>";
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="table-container">
                                            <h4 class="section-title">Returned Books History</h4>
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th>Title</th>
                                                        <th>Borrow Date</th>
                                                        <th>Return Date</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php while ($book = $returned_books->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($book['title']); ?></td>
                                                        <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($book['borrow_date']))); ?></td>
                                                        <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($book['return_date']))); ?></td>
                                                    </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <h5>Status: <span class="badge badge-<?php echo ($user_status == 'With Borrowed Book') ? 'warning' : 'success'; ?>">
                                    <?php echo $user_status; ?>
                                </span></h5>
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
                        <br>Design: <a href="https://templatemo.com" target="_blank" title="free CSS templates">TemplateMo</a>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
    <script src="assets/js/isotope.min.js"></script>
    <script src="assets/js/owl-carousel.js"></script>
    <script src="assets/js/tabs.js"></script>
    <script src="assets/js/popup.js"></script>
    <script src="assets/js/custom.js"></script>
    <script src="assets/js/buttons.js"></script>
    <script src="assets/js/profile.js"></script>
    
    <script>
    // Auto-submit the form when a new profile picture is selected
    $(document).ready(function() {
        // Function to update book statistics
        function updateBookStats() {
            $.ajax({
                url: 'get_book_stats.php',
                type: 'GET',
                success: function(response) {
                    try {
                        var stats = JSON.parse(response);
                        $('.col-lg-4.align-self-center ul li:nth-child(1) span').text(stats.books_returned);
                        $('.col-lg-4.align-self-center ul li:nth-child(2) span').text(stats.books_borrowed);
                        $('.col-lg-4.align-self-center ul li:nth-child(3) span').text(stats.books_available);
                    } catch (e) {
                        console.error('Error updating book stats:', e);
                    }
                }
            });
        }

        // Update stats every 30 seconds
        setInterval(updateBookStats, 30000);

        $('#profile-upload').change(function() {
            if (this.files && this.files[0]) {
                var formData = new FormData($('#profile-upload-form')[0]);
                
                $.ajax({
                    url: 'profile.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        try {
                            var result = JSON.parse(response);
                            if (result.success) {
                                // Update the profile image immediately
                                $('.profile-pic').attr('src', result.newPath);
                                // Also update the profile picture in the header
                                $('.nav li:last-child a img').attr('src', result.newPath);
                            } else {
                                alert(result.message || 'Error updating profile picture');
                            }
                        } catch (e) {
                            console.error('Error parsing response:', e);
                        }
                    },
                    error: function() {
                        alert('Error uploading profile picture');
                    }
                });
            }
        });
        
        // Handle book return action
        $('.return-book').click(function() {
            var record_id = $(this).data('record-id');
            $.ajax({
                url: 'student_return.php',
                type: 'POST',
                data: { record_id: record_id },
                success: function(response) {
                    alert(response);
                    // Update book stats after returning a book
                    updateBookStats();
                    location.reload();
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

        // Mark notification as read when clicked
        $('.notification-item').click(function(e) {
            // Only trigger if not clicking on a button
            if (!$(e.target).is('button')) {
                const notificationId = $(this).data('id');
                const item = $(this);
                
                $.ajax({
                    url: 'mark_notification_read.php',
                    method: 'POST',
                    data: { notification_id: notificationId },
                    success: function(response) {
                        if (response.success) {
                            item.removeClass('unread');
                            // Update the button
                            const button = item.find('.mark-read-btn');
                            button.removeClass('btn-light').addClass('btn-outline-light');
                            button.text('Mark Unread');
                            button.data('read', '1');
                            updateNotificationBadge();
                        } else {
                            console.error('Error updating notification:', response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', error);
                    }
                });
            }
        });
        
        // Mark single notification as read/unread using the button
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
        
        // Update notification badge
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
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                }
            });
        }

        // Check for new notifications every minute
        setInterval(updateNotificationBadge, 60000);
        
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
                    } else {
                        console.error('Error marking all as read:', response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                }
            });
        });
    });
    </script>

    <!-- Notification Panel -->
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
                        <div style="color: #fff;"><?php echo htmlspecialchars($notification['message']); ?></div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div class="notification-time">
                                <?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?>
                            </div>
                            <button class="mark-read-btn btn btn-sm <?php echo $notification['is_read'] ? 'btn-outline-light' : 'btn-light'; ?>" 
                                    data-id="<?php echo $notification['notification_id']; ?>"
                                    data-read="<?php echo $notification['is_read'] ? '1' : '0'; ?>">
                                <?php echo $notification['is_read'] ? 'Mark Unread' : 'Mark Read'; ?>
                            </button>
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
</body>

</html>
