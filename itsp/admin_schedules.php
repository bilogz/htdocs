<?php
session_start();
require 'config.php';

// Check if user is admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: index.php');
    exit();
}

// Handle schedule confirmation or cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'confirm_borrow') {
        $schedule_id = $_POST['schedule_id'];
        $book_id = $_POST['book_id'];
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Update schedule status
            $update_schedule = $conn->prepare("UPDATE book_schedules SET status = 'confirmed' WHERE schedule_id = ?");
            $update_schedule->bind_param("i", $schedule_id);
            $update_schedule->execute();
            
            // Create borrow record
            $insert_borrow = $conn->prepare("INSERT INTO borrowed_books (book_id, student_id, borrow_date, due_date) 
                                           SELECT book_id, student_id, NOW(), DATE_ADD(NOW(), INTERVAL 14 DAY) 
                                           FROM book_schedules WHERE schedule_id = ?");
            $insert_borrow->bind_param("i", $schedule_id);
            $insert_borrow->execute();
            
            // Update book stock
            $update_stock = $conn->prepare("UPDATE books SET stock = stock - 1 WHERE book_id = ?");
            $update_stock->bind_param("i", $book_id);
            $update_stock->execute();
            
            $conn->commit();
            $success_message = "Book borrow confirmed successfully!";
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Error confirming borrow: " . $e->getMessage();
        }
    } elseif ($_POST['action'] === 'cancel_schedule') {
        $schedule_id = $_POST['schedule_id'];
        
        try {
            $cancel_schedule = $conn->prepare("UPDATE book_schedules SET status = 'cancelled' WHERE schedule_id = ?");
            $cancel_schedule->bind_param("i", $schedule_id);
            
            if ($cancel_schedule->execute()) {
                $success_message = "Schedule cancelled successfully!";
            } else {
                $error_message = "Error cancelling schedule.";
            }
        } catch (Exception $e) {
            $error_message = "Error cancelling schedule: " . $e->getMessage();
        }
    }
}

// Handle eBook deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete_ebook') {
    $ebook_id = intval($_POST['ebook_id']);
    $stmt = $conn->prepare("DELETE FROM ebooks WHERE id = ?");
    if (!$stmt) {
        die("Error preparing eBook delete: " . $conn->error);
    }
    if (!$stmt->bind_param("i", $ebook_id)) {
        die("Error binding eBook delete: " . $stmt->error);
    }
    if (!$stmt->execute()) {
        die("Error executing eBook delete: " . $stmt->error);
    }
    $stmt->close();
    header("Location: admin_page.php?ebook_deleted=1");
    exit();
}

// Handle search
$search = isset($_GET['search']) ? $_GET['search'] : '';
$search_condition = '';
$search_params = [];

if (!empty($search)) {
    $search_condition = "AND (u.full_name LIKE ? OR u.student_id LIKE ?)";
    $search_params = ["%$search%", "%$search%"];
}

// Fetch pending schedules with search
$schedules_query = "
    SELECT bs.*, b.title, b.cover_image, u.full_name as student_name, u.email as student_email, u.student_id
    FROM book_schedules bs
    JOIN books b ON bs.book_id = b.book_id
    JOIN users u ON bs.student_id = u.student_id
    WHERE bs.status = 'pending' $search_condition
    ORDER BY bs.schedule_date ASC";

$stmt = $conn->prepare($schedules_query);
if (!empty($search_params)) {
    $stmt->bind_param("ss", ...$search_params);
}
$stmt->execute();
$schedules_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Book Schedules</title>
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
        .confirm-btn {
            background-color: #60b8eb;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 10px;
        }
        .cancel-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
        }
        .confirm-btn:hover {
            background-color: #4a9cd1;
        }
        .cancel-btn:hover {
            background-color: #c82333;
        }
        .search-box {
            margin-bottom: 20px;
        }
        .search-box input {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            width: 300px;
        }
        .search-box button {
            background-color: #60b8eb;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <header class="header-area header-sticky">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <nav class="main-nav">
                        <a href="admin_page.php" class="logo">
                            <img src="img\libmsLOGO.png" alt="">
                        </a>
                        <ul class="nav">
                            <li><a href="admin_page.php">Dashboard</a></li>
                            <li><a href="admin_schedules.php" class="active">Schedules</a></li>
                            <li><a href="admin_ebooks.php">eBooks</a></li>
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
                                <h4><em>Pending</em> Book Schedules</h4>
                            </div>
                            
                            <!-- Search Box -->
                            <div class="search-box">
                                <form method="GET" action="">
                                    <input type="text" name="search" placeholder="Search by Name or Student ID" value="<?php echo htmlspecialchars($search); ?>">
                                    <button type="submit">Search</button>
                                    <?php if (!empty($search)): ?>
                                        <a href="admin_schedules.php" class="btn btn-secondary">Clear</a>
                                    <?php endif; ?>
                                </form>
                            </div>
                            
                            <?php if (isset($success_message)): ?>
                                <div class="alert alert-success"><?php echo $success_message; ?></div>
                            <?php endif; ?>
                            
                            <?php if (isset($error_message)): ?>
                                <div class="alert alert-danger"><?php echo $error_message; ?></div>
                            <?php endif; ?>

                            <?php if ($schedules_result && $schedules_result->num_rows > 0): ?>
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
                                                <p><strong>Student ID:</strong> <?php echo htmlspecialchars($schedule['student_id']); ?></p>
                                                <p><strong>Student:</strong> <?php echo htmlspecialchars($schedule['student_name']); ?></p>
                                                <p><strong>Email:</strong> <?php echo htmlspecialchars($schedule['student_email']); ?></p>
                                                <p><strong>Scheduled for:</strong> <?php echo date('F j, Y g:i A', strtotime($schedule['schedule_date'])); ?></p>
                                            </div>
                                            <div class="col-md-3 text-end">
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="action" value="confirm_borrow">
                                                    <input type="hidden" name="schedule_id" value="<?php echo $schedule['schedule_id']; ?>">
                                                    <input type="hidden" name="book_id" value="<?php echo $schedule['book_id']; ?>">
                                                    <button type="submit" class="confirm-btn">Confirm Borrow</button>
                                                </form>
                                                <form method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to cancel this schedule?');">
                                                    <input type="hidden" name="action" value="cancel_schedule">
                                                    <input type="hidden" name="schedule_id" value="<?php echo $schedule['schedule_id']; ?>">
                                                    <button type="submit" class="cancel-btn">Cancel</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p>No pending schedules found.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
</body>
</html> 