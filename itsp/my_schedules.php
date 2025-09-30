<?php
session_name('student_session');
session_start();
require 'config.php';

// Check if user is logged in
if (!isset($_SESSION['student_id'])) {
    header('Location: login.php');
    exit();
}

$student_id = $_SESSION['student_id'];

// Handle schedule cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_schedule') {
    $schedule_id = $_POST['schedule_id'];
    
    // Verify the schedule belongs to the student
    $check = $conn->prepare("SELECT 1 FROM book_schedules WHERE schedule_id = ? AND student_id = ? AND status = 'pending'");
    $check->bind_param("ii", $schedule_id, $student_id);
    $check->execute();
    
    if ($check->get_result()->num_rows > 0) {
        $cancel = $conn->prepare("UPDATE book_schedules SET status = 'cancelled' WHERE schedule_id = ?");
        $cancel->bind_param("i", $schedule_id);
        if ($cancel->execute()) {
            $success_message = "Schedule cancelled successfully.";
        } else {
            $error_message = "Error cancelling schedule.";
        }
    } else {
        $error_message = "Invalid schedule or already processed.";
    }
}

// Fetch pending schedules
$schedules_query = "
    SELECT bs.*, b.title, b.cover_image, b.author
    FROM book_schedules bs
    JOIN books b ON bs.book_id = b.book_id
    WHERE bs.student_id = ? AND bs.status = 'pending'
    ORDER BY bs.schedule_date ASC";
$stmt = $conn->prepare($schedules_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$schedules_result = $stmt->get_result();

// Fetch notifications
$notifications_query = "SELECT * FROM notifications 
                      WHERE student_id = ? 
                      ORDER BY created_at DESC 
                      LIMIT 10";
$notif_stmt = $conn->prepare($notifications_query);
$notif_stmt->bind_param("i", $student_id);
$notif_stmt->execute();
$notifications = $notif_stmt->get_result();

// Get unread count
$unread_query = "SELECT COUNT(*) as unread FROM notifications 
                WHERE student_id = ? AND is_read = FALSE";
$unread_stmt = $conn->prepare($unread_query);
$unread_stmt->bind_param("i", $student_id);
$unread_stmt->execute();
$unread_count = $unread_stmt->get_result()->fetch_assoc()['unread'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Schedules - BCP Library Management System</title>
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/fontawesome.css">
    <link rel="stylesheet" href="assets/css/templatemo-cyborg-gaming.css">
    <style>
        .schedule-card {
            background: #1f2122;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .book-cover {
            width: 100px;
            height: 150px;
            object-fit: cover;
            border-radius: 5px;
        }
        .cancel-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
        }
        .cancel-btn:hover {
            background-color: #c82333;
        }
        
        /* Notification styles */
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
    </style>
</head>
<body>
    <header class="header-area header-sticky">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <nav class="main-nav">
                        <a href="index.php" class="logo">
                            <img src="img/libmsLOGO.png" alt="">
                        </a>
                        <ul class="nav">
                            <li><a href="index.php">Home</a></li>
                            <li><a href="profile.php">Profile</a></li>
                            <li><a href="my_schedules.php" class="active">My Schedules</a></li>
                            <li><a href="#" id="notification-toggle">
                                <i class="fa fa-bell"></i>
                                <?php if ($unread_count > 0): ?>
                                    <span class="notification-badge"><?php echo $unread_count; ?></span>
                                <?php endif; ?>
                            </a></li>
                            <li><a href="logout.php">Logout</a></li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="page-content">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="heading-section">
                                <h4><em>My</em> Pending Schedules</h4>
                            </div>
                            
                            <?php if (isset($success_message)): ?>
                                <div class="alert alert-success"><?php echo $success_message; ?></div>
                            <?php endif; ?>
                            
                            <?php if (isset($error_message)): ?>
                                <div class="alert alert-danger"><?php echo $error_message; ?></div>
                            <?php endif; ?>

                            <?php if ($schedules_result->num_rows > 0): ?>
                                <?php while ($schedule = $schedules_result->fetch_assoc()): ?>
                                    <div class="schedule-card">
                                        <div class="row">
                                            <div class="col-md-2">
                                                <img src="assets/images/<?php echo htmlspecialchars($schedule['cover_image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($schedule['title']); ?>" 
                                                     class="book-cover">
                                            </div>
                                            <div class="col-md-7">
                                                <h5><?php echo htmlspecialchars($schedule['title']); ?></h5>
                                                <p><strong>Author:</strong> <?php echo htmlspecialchars($schedule['author']); ?></p>
                                                <p><strong>Scheduled for:</strong> <?php echo date('F j, Y g:i A', strtotime($schedule['schedule_date'])); ?></p>
                                                <p><strong>Status:</strong> <span class="badge bg-warning">Pending</span></p>
                                            </div>
                                            <div class="col-md-3 text-end">
                                                <form method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to cancel this schedule?');">
                                                    <input type="hidden" name="action" value="cancel_schedule">
                                                    <input type="hidden" name="schedule_id" value="<?php echo $schedule['schedule_id']; ?>">
                                                    <button type="submit" class="cancel-btn">Cancel Schedule</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p>You have no pending schedules.</p>
                            <?php endif; ?>
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

    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
    <script>
    $(document).ready(function() {
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
        
        // Check for new notifications every minute
        setInterval(updateNotificationBadge, 60000);
    });
    </script>
</body>
</html> 