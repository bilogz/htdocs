<?php
// Start the session to access session variables
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

// Check if there is an error in the URL (e.g., book not found or out of stock)
$error_message = '';
if (isset($_GET['error'])) {
    if ($_GET['error'] == 'book_not_found') {
        $error_message = "The book was not found in the system.";
    } elseif ($_GET['error'] == 'out_of_stock') {
        $error_message = "Sorry, the book is out of stock.";
    }
}

// Check if the book borrowing was successful
$success_message = '';
if (isset($_GET['success']) && $_GET['success'] == 'book_borrowed') {
    $success_message = "✅ Your book was borrowed successfully!";
}

// Fetch the student's borrowed books and calculate overdue fees
$sql = "SELECT bb.record_id, bb.book_id, bb.borrow_date, bb.due_date, bb.status, b.title, b.cover_image
        FROM borrowed_books bb
        JOIN books b ON bb.book_id = b.book_id
        WHERE bb.student_id = ? AND bb.return_date IS NULL";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed (fetch borrowed books): " . $conn->error);
}
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

// Debugging: Check if any rows are returned
if ($result->num_rows === 0) {
    echo "<p>No books found for this student or the books might be already returned.</p>";
} else {
    echo "<p>Books fetched from the database:</p>";
    while ($row = $result->fetch_assoc()) {
        echo "<p>Book ID: " . $row['book_id'] . " | Title: " . $row['title'] . " | Status: " . $row['status'] . "</p>";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrowed Books</title>
</head>
<body>
    <h1>Your Borrowed Books</h1>

    <?php
    // Display error message if available
    if ($error_message) {
        echo "<p style='color: red;'>$error_message</p>";
    }

    // Display success message if available
    if ($success_message) {
        echo "<p style='color: green;'>$success_message</p>";
    }

    // Check if any books are borrowed
    if ($result->num_rows > 0) {
        echo "<table border='1'>
                <tr>
                    <th>Book Title</th>
                    <th>Cover Image</th>
                    <th>Borrow Date</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Overdue Fee</th>
                    <th>Action</th>
                </tr>";

        while ($row = $result->fetch_assoc()) {
            $borrow_date = $row['borrow_date'];
            $due_date = $row['due_date'];
            $current_date = date('Y-m-d');
            $overdue_fee = 0;

            // Check if the book is overdue
            if ($current_date > $due_date) {
                // Calculate overdue fee (assuming $1 per day overdue)
                $due_date_timestamp = strtotime($due_date);
                $current_date_timestamp = strtotime($current_date);
                $days_overdue = ceil(($current_date_timestamp - $due_date_timestamp) / (60 * 60 * 24));
                $overdue_fee = $days_overdue * 1; // $1 per day overdue
            }

            echo "<tr>
                    <td>" . $row['title'] . "</td>
                    <td><img src='assets/images/" . $row['cover_image'] . "' alt='" . $row['title'] . "' width='100'></td>
                    <td>" . $borrow_date . "</td>
                    <td>" . $due_date . "</td>
                    <td>" . $row['status'] . "</td>
                    <td>" . ($overdue_fee > 0 ? "$" . $overdue_fee : "No fee") . "</td>
                    <td><a href='return_book.php?record_id=" . $row['record_id'] . "'>Return</a></td>
                </tr>";
        }
        echo "</table>";
    } else {
        echo "<p>You haven't borrowed any books yet.</p>";
    }
    ?>
</body>
</html>
