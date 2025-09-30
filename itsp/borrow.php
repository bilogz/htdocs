<?php
session_name('student_session');
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['student_id'])) {
    header('Location: login.php?redirect=borrow.php&book_id=' . $_GET['book_id']);
    exit();
}

// Get book details
$book_id = isset($_GET['book_id']) ? (int)$_GET['book_id'] : 0;
$book_query = "SELECT * FROM books WHERE book_id = ?";
$stmt = $conn->prepare($book_query);

if ($stmt === false) {
    die("Error preparing book query: " . $conn->error);
}

$stmt->bind_param("i", $book_id);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get user details
$user_query = "SELECT * FROM users WHERE student_id = ?";
$stmt = $conn->prepare($user_query);

if ($stmt === false) {
    die("Error preparing user query: " . $conn->error);
}

$stmt->bind_param("i", $_SESSION['student_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Check if user has already borrowed or requested 3 or more books
$active_borrows_query = "SELECT 
                           (SELECT COUNT(*) FROM book_schedules 
                            WHERE student_id = ? AND status IN ('pending', 'confirmed')) +
                           (SELECT COUNT(*) FROM borrowed_books 
                            WHERE student_id = ? AND return_date IS NULL) 
                         AS total_active_books";
$stmt = $conn->prepare($active_borrows_query);

if ($stmt === false) {
    die("Error preparing active books query: " . $conn->error);
}

$stmt->bind_param("ii", $_SESSION['student_id'], $_SESSION['student_id']);
$stmt->execute();
$result = $stmt->get_result();
$active_books = $result->fetch_assoc();
$stmt->close();

$max_books_allowed = 3;
$currently_borrowed = $active_books['total_active_books'];
$can_borrow_more = ($currently_borrowed < $max_books_allowed);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $schedule_date = $_POST['schedule_date'];
    $return_date = $_POST['return_date'];
    $purpose = $_POST['purpose'];
    
    // Check again if user can borrow more books (in case of race condition)
    if (!$can_borrow_more) {
        $error_message = "You have reached the maximum limit of {$max_books_allowed} books that can be borrowed at once.";
    } else {
        // Insert into book_schedules
        $insert_query = "INSERT INTO book_schedules (student_id, book_id, schedule_date, return_date, purpose, status) 
                        VALUES (?, ?, ?, ?, ?, 'pending')";
        $stmt = $conn->prepare($insert_query);
        
        if ($stmt === false) {
            die("Error preparing insert query: " . $conn->error);
        }
        
        $stmt->bind_param("iisss", $_SESSION['student_id'], $book_id, $schedule_date, $return_date, $purpose);
        
        if ($stmt->execute()) {
            // Add notification for admin
            $notification_query = "INSERT INTO notifications (student_id, message, type) 
                                 VALUES (?, ?, 'borrow_request')";
            $message = "New borrow request from " . $user['full_name'] . " for book: " . $book['title'];
            $stmt = $conn->prepare($notification_query);
            
            if ($stmt === false) {
                die("Error preparing notification query: " . $conn->error);
            }
            
            $stmt->bind_param("is", $_SESSION['student_id'], $message);
            $stmt->execute();
            
            // Redirect to pending page
            header('Location: pending_approval.php?book_id=' . $book_id);
            exit();
        } else {
            $error_message = "Error inserting schedule: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrow Book - Library Management System</title>
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/fontawesome.css">
    <link rel="stylesheet" href="assets/css/templatemo-cyborg-gaming.css">
    <link rel="stylesheet" href="borrow.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="borrow-container">
                    <h2 class="text-center mb-4">Borrow Book</h2>
                    
                    <?php if ($book): ?>
                        <div class="alert alert-info mb-4">
                            <div class="d-flex align-items-center">
                                <div class="circle-icon">
                                    <i class="fas fa-info-circle"></i>
                                </div>
                                <div>
                                    <strong>Welcome, <?php echo htmlspecialchars($user['full_name']); ?>!</strong>
                                    <p class="mb-0">Please fill out the form below to request to borrow this book. The librarian will review your request.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="book-details">
                            <div class="row align-items-center">
                                <div class="col-md-4 text-center">
                                    <img src="assets/images/<?php echo htmlspecialchars($book['cover_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($book['title']); ?>" 
                                         class="book-cover">
                                </div>
                                <div class="col-md-8">
                                    <h4><?php echo htmlspecialchars($book['title']); ?></h4>
                                    <p class="text-muted">By <?php echo htmlspecialchars($book['author']); ?></p>
                                    <p><strong>Category:</strong> <?php echo htmlspecialchars($book['category']); ?></p>
                                    <p><strong>Available Stock:</strong> <?php echo htmlspecialchars($book['available_stock']); ?></p>
                                    <span class="status-badge <?php echo $book['status'] === 'Available' ? 'status-available' : 'status-unavailable'; ?>">
                                        <?php echo htmlspecialchars($book['status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-danger mb-4">
                                <div class="d-flex align-items-center">
                                    <div class="circle-icon bg-danger">
                                        <i class="fas fa-exclamation-circle"></i>
                                    </div>
                                    <div>
                                        <strong>Error!</strong>
                                        <p class="mb-0"><?php echo $error_message; ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!$can_borrow_more): ?>
                            <div class="alert alert-warning mb-4">
                                <div class="d-flex align-items-center">
                                    <div class="circle-icon" style="background-color: rgba(255, 193, 7, 0.1);">
                                        <i class="fas fa-exclamation-triangle" style="color: #ffc107;"></i>
                                    </div>
                                    <div>
                                        <strong>Borrowing Limit Reached!</strong>
                                        <p class="mb-0">You have already borrowed or requested <?php echo $currently_borrowed; ?> books. The maximum allowed is <?php echo $max_books_allowed; ?> books at a time. Please return some books before borrowing more.</p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($book['status'] === 'Available' && $book['available_stock'] > 0 && $can_borrow_more): ?>
                            <form method="POST" action="" id="borrowForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="schedule_date">Schedule Date</label>
                                            <input type="date" class="form-control" id="schedule_date" name="schedule_date" 
                                                min="<?php echo date('Y-m-d'); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="return_date">Expected Return Date</label>
                                            <input type="date" class="form-control" id="return_date" name="return_date" 
                                                min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group mb-4">
                                    <label for="purpose">Purpose of Borrowing</label>
                                    <textarea class="form-control" id="purpose" name="purpose" rows="3" required 
                                        placeholder="Please explain why you need to borrow this book..."></textarea>
                                </div>

                                <div class="terms-section mb-4">
                                    <h5>Terms and Conditions</h5>
                                    <div class="terms-content">
                                        <h6>Important Rules:</h6>
                                        <ul>
                                            <li>✓ Maximum borrowing period: 7 days</li>
                                            <li>✓ Late return penalty: ₱10 per day</li>
                                            <li>✓ Damaged book fine: Up to 50% of book value</li>
                                            <li>✓ Lost book replacement: Full book value</li>
                                        </ul>
                                        <h6>Borrower's Agreement:</h6>
                                        <p>
                                            By borrowing this book, I agree to:
                                            <br>1. Return the book in the same condition as received
                                            <br>2. Pay any applicable fines for late returns
                                            <br>3. Report any damage immediately
                                            <br>4. Replace the book if lost or severely damaged
                                            <br>5. Follow the library's borrowing rules and regulations
                                        </p>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="termsAgreement" required>
                                        <label class="form-check-label" for="termsAgreement">
                                            I have read and agree to the terms and conditions
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <a href="index.php" class="btn btn-secondary w-100">Cancel</a>
                                    </div>
                                    <div class="col-md-6">
                                        <button type="submit" class="btn btn-primary w-100" id="submitBtn">Submit Request</button>
                                    </div>
                                </div>
                            </form>

                            <script>
                                // Validate return date is not more than 7 days from schedule date
                                document.getElementById('borrowForm').addEventListener('submit', function(e) {
                                    const scheduleDate = new Date(document.getElementById('schedule_date').value);
                                    const returnDate = new Date(document.getElementById('return_date').value);
                                    const diffTime = Math.abs(returnDate - scheduleDate);
                                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                                    
                                    if (diffDays > 7) {
                                        e.preventDefault();
                                        alert('Borrowing period cannot exceed 7 days. Please adjust your return date.');
                                    }
                                });

                                // Update return date maximum when schedule date changes
                                document.getElementById('schedule_date').addEventListener('change', function() {
                                    const scheduleDate = new Date(this.value);
                                    const maxReturnDate = new Date(scheduleDate);
                                    maxReturnDate.setDate(maxReturnDate.getDate() + 7);
                                    
                                    const returnDateInput = document.getElementById('return_date');
                                    returnDateInput.max = maxReturnDate.toISOString().split('T')[0];
                                    
                                    // If current return date is beyond max, update it
                                    if (new Date(returnDateInput.value) > maxReturnDate) {
                                        returnDateInput.value = maxReturnDate.toISOString().split('T')[0];
                                    }
                                });
                            </script>
                        <?php elseif (!$can_borrow_more): ?>
                            <div class="text-center mt-4">
                                <a href="index.php" class="btn btn-primary">Back to Books</a>
                                <a href="my_books.php" class="btn btn-info ml-2">View My Books</a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning text-center">
                                This book is currently unavailable for borrowing.
                            </div>
                            <div class="text-center">
                                <a href="index.php" class="btn btn-primary">Back to Books</a>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="alert alert-danger text-center">
                            Book not found.
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
    <script>
        // Set minimum dates for schedule and return
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('schedule_date').min = today;
        
        // Update return date minimum when schedule date changes
        document.getElementById('schedule_date').addEventListener('change', function() {
            const scheduleDate = new Date(this.value);
            const minReturnDate = new Date(scheduleDate);
            minReturnDate.setDate(minReturnDate.getDate() + 1);
            document.getElementById('return_date').min = minReturnDate.toISOString().split('T')[0];
        });
    </script>
</body>
</html>
