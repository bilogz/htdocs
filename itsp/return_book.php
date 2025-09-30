<?php
// Start the session and include the database config
session_start();
require 'config.php';

// Check if the user is logged in
if (!isset($_SESSION['student_id'])) {
    die("You must be logged in to return books.");
}

// Get the record ID from the request
if (isset($_GET['record_id'])) {
    $record_id = $_GET['record_id'];
    
    // Update the borrowed book record to mark it as returned
    $sql = "UPDATE borrowed_books SET return_date = NOW(), status = 'Returned' WHERE record_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("i", $record_id);
    $stmt->execute();

    // Send a response (you can return success message)
    echo "Book returned successfully!";
} else {
    die("No record ID provided.");
}
?>
