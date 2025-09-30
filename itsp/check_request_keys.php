<?php
require_once 'config.php';

// Set up error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Checking Borrow Request Field Names</h1>";

// Run the same query used in admin_page.php
$query = "SELECT bs.*, b.title as book_title, b.cover_image, u.full_name as student_name, u.email as student_email
    FROM book_schedules bs
    JOIN books b ON bs.book_id = b.book_id
    JOIN users u ON bs.student_id = u.student_id
    WHERE bs.status = 'pending'
    ORDER BY bs.schedule_date DESC";

$result = $conn->query($query);

if (!$result) {
    die("Error executing query: " . $conn->error);
}

if ($result->num_rows == 0) {
    die("No pending requests found.");
}

// Get the first row and show its field names
$row = $result->fetch_assoc();

echo "<h2>Available Field Names</h2>";
echo "<pre>";
print_r(array_keys($row));
echo "</pre>";

echo "<h2>First Row Data</h2>";
echo "<pre>";
print_r($row);
echo "</pre>";

// Show sample HTML for date display
echo "<h2>Sample Date Display</h2>";

echo "<p>Schedule Date Field: '" . (isset($row['schedule_date']) ? $row['schedule_date'] : "NOT FOUND") . "'</p>";
echo "<p>Return Date Field: '" . (isset($row['return_date']) ? $row['return_date'] : "NOT FOUND") . "'</p>";

if (isset($row['schedule_date'])) {
    echo "<p>Formatted Schedule Date: " . date('F j, Y', strtotime($row['schedule_date'])) . "</p>";
}

if (isset($row['return_date'])) {
    echo "<p>Formatted Return Date: " . date('F j, Y', strtotime($row['return_date'])) . "</p>";
}

// Close connection
$conn->close();
?> 