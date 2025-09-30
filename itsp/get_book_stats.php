<?php
session_start();
require 'config.php';

if (!isset($_SESSION['student_id'])) {
    die(json_encode(['error' => 'Not logged in']));
}

$student_id = $_SESSION['student_id'];

// Fetch book statistics
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM borrowed_books WHERE student_id = ? AND return_date IS NOT NULL) as books_returned,
    (SELECT COUNT(*) FROM borrowed_books WHERE student_id = ? AND return_date IS NULL) as books_borrowed,
    (SELECT COUNT(*) FROM books WHERE status = 'Available') as books_available";

$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param("ii", $student_id, $student_id);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();
$stats_stmt->close();

// Return the statistics as JSON
header('Content-Type: application/json');
echo json_encode($stats);
?> 