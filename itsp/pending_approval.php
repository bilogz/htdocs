<?php
session_name('student_session');
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['student_id'])) {
    header('Location: login.php');
    exit();
}

$book_id = isset($_GET['book_id']) ? (int)$_GET['book_id'] : 0;

// Get the latest pending request for this book
$request_query = "SELECT bs.*, b.title, b.author, b.cover_image 
                 FROM book_schedules bs 
                 JOIN books b ON bs.book_id = b.book_id 
                 WHERE bs.student_id = ? AND bs.book_id = ? 
                 ORDER BY bs.created_at DESC LIMIT 1";
$stmt = $conn->prepare($request_query);
$stmt->bind_param("ii", $_SESSION['student_id'], $book_id);
$stmt->execute();
$request = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Approval - Library Management System</title>
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/templatemo-cyborg-gaming.css">
    <style>
        .pending-container {
            background: #1f2122;
            border-radius: 23px;
            padding: 30px;
            margin-top: 120px;
            margin-bottom: 50px;
        }
        .book-details {
            background: #27292a;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .book-cover {
            width: 200px;
            height: 300px;
            object-fit: cover;
            border-radius: 10px;
        }
        .status-pending {
            background-color: #ffc107;
            color: #000;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 500;
            display: inline-block;
            margin: 10px 0;
        }
        .request-details {
            background: #27292a;
            border-radius: 15px;
            padding: 20px;
            margin-top: 20px;
        }
        .request-details p {
            margin-bottom: 10px;
            color: #fff;
        }
        .request-details strong {
            color: #ec6090;
        }
        .container {
            padding-top: 30px;
            padding-bottom: 30px;
        }
        .header-area {
            margin-bottom: 40px;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="pending-container">
                    <h2 class="text-center mb-4">Borrow Request Status</h2>
                    
                    <?php if ($request): ?>
                        <div class="book-details">
                            <div class="row">
                                <div class="col-md-4 text-center">
                                    <img src="assets/images/<?php echo htmlspecialchars($request['cover_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($request['title']); ?>" 
                                         class="book-cover">
                                </div>
                                <div class="col-md-8">
                                    <h4><?php echo htmlspecialchars($request['title']); ?></h4>
                                    <p class="text-muted">By <?php echo htmlspecialchars($request['author']); ?></p>
                                    <div class="status-pending">
                                        Pending Approval
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="request-details">
                            <h5>Request Details</h5>
                            <p><strong>Schedule Date:</strong> <?php echo date('F j, Y', strtotime($request['schedule_date'])); ?></p>
                            <p><strong>Expected Return Date:</strong> <?php echo date('F j, Y', strtotime($request['return_date'])); ?></p>
                            <p><strong>Purpose:</strong> <?php echo htmlspecialchars($request['purpose']); ?></p>
                            <p><strong>Request Date:</strong> <?php echo date('F j, Y g:i A', strtotime($request['created_at'])); ?></p>
                        </div>

                        <div class="text-center mt-4">
                            <p class="text-muted">Your request is being reviewed by the library staff. You will be notified once it's approved or rejected.</p>
                            <a href="index.php" class="btn btn-primary">Back to Books</a>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger text-center">
                            No pending request found for this book.
                        </div>
                        <div class="text-center">
                            <a href="index.php" class="btn btn-primary">Back to Books</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
</body>
</html> 