<?php
// Get user information if logged in
$student = [];
$profile_pic = null;
if (isset($_SESSION['student_id'])) {
    $stmt = $conn->prepare("SELECT full_name, profile_pic FROM users WHERE student_id = ?");
    $stmt->bind_param("i", $_SESSION['student_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    $profile_pic = $student['profile_pic'] ?? null;
    $stmt->close();
}

// Get unread notifications count
$unread_count = 0;
if (isset($_SESSION['student_id'])) {
    $unread_query = "SELECT COUNT(*) as unread FROM notifications WHERE student_id = ? AND is_read = FALSE";
    $unread_stmt = $conn->prepare($unread_query);
    $unread_stmt->bind_param("i", $_SESSION['student_id']);
    $unread_stmt->execute();
    $unread_count = $unread_stmt->get_result()->fetch_assoc()['unread'];
    $unread_stmt->close();
}
?>

<header class="header-area header-sticky">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <nav class="main-nav">
                    <a href="index.php" class="logo">
                        <img src="img/libmsLOGO.png" alt="Library Management System">
                    </a>
                    <h3>Welcome, <?php echo htmlspecialchars($student['full_name'] ?? ''); ?></h3>
                    <ul class="nav">
                        <li><a href="index.php" <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'class="active"' : ''; ?>>Home</a></li>
                        <li><a href="profile.php" <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'class="active"' : ''; ?>>
                            <div class="profile-pic-container">
                                <?php if ($profile_pic): ?>
                                    <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile Picture" style="width: 30px; height: 30px; object-fit: cover;">
                                    <span class="online-status <?php echo isset($_SESSION['student_id']) ? '' : 'offline'; ?>"></span>
                                <?php endif; ?>
                            </div>
                        </a></li>
                        <li><a href="my_schedules.php" <?php echo basename($_SERVER['PHP_SELF']) == 'my_schedules.php' ? 'class="active"' : ''; ?>>My Schedules</a></li>
                        <li><a href="terms.php" <?php echo basename($_SERVER['PHP_SELF']) == 'terms.php' ? 'class="active"' : ''; ?>>Terms & Conditions</a></li>
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

<!-- Notification Panel -->
<div id="notification-panel" style="display: none; position: fixed; top: 80px; right: 20px; width: 300px; background: #2d2d2d; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.3); z-index: 1000;">
    <div style="padding: 15px; border-bottom: 1px solid #404040; display: flex; justify-content: space-between; align-items: center;">
        <h5 style="margin: 0; color: #fff;">Notifications</h5>
        <button id="mark-all-read" class="btn btn-sm btn-outline-light">Mark All Read</button>
    </div>
    <div style="max-height: 400px; overflow-y: auto;">
        <?php
        if (isset($_SESSION['student_id'])) {
            $notifications_query = "SELECT * FROM notifications WHERE student_id = ? ORDER BY created_at DESC LIMIT 10";
            $notif_stmt = $conn->prepare($notifications_query);
            $notif_stmt->bind_param("i", $_SESSION['student_id']);
            $notif_stmt->execute();
            $notifications = $notif_stmt->get_result();
            
            if ($notifications->num_rows > 0):
                while ($notification = $notifications->fetch_assoc()):
        ?>
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
        <?php
                endwhile;
            else:
        ?>
                <div style="padding: 15px; color: #888; text-align: center;">
                    No notifications
                </div>
        <?php
            endif;
            $notif_stmt->close();
        }
        ?>
    </div>
</div>

<!-- Notification Popup -->
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