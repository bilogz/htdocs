<?php
require_once 'config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Fixing Book Schedules Status</h1>";

// Update all blank status values to 'pending'
$update_query = "UPDATE book_schedules SET status = 'pending' WHERE status IS NULL OR status = ''";

if ($conn->query($update_query)) {
    $affected = $conn->affected_rows;
    echo "<p>Updated $affected records to have status 'pending'</p>";
} else {
    echo "<p>Error updating records: " . $conn->error . "</p>";
}

// Check if we have any pending records now
$check_query = "SELECT COUNT(*) as count FROM book_schedules WHERE status = 'pending'";
$result = $conn->query($check_query);
$row = $result->fetch_assoc();

echo "<p>Number of pending requests now: " . $row['count'] . "</p>";

// If we still don't have any pending records, try to insert a new one
if ($row['count'] == 0) {
    echo "<h2>Inserting a new pending request</h2>";
    
    // Get a book ID
    $book_query = "SELECT book_id FROM books LIMIT 1";
    $book_result = $conn->query($book_query);
    
    // Get a student ID
    $student_query = "SELECT student_id FROM users WHERE user_type = 'student' LIMIT 1";
    $student_result = $conn->query($student_query);
    
    if ($book_result->num_rows > 0 && $student_result->num_rows > 0) {
        $book_id = $book_result->fetch_assoc()['book_id'];
        $student_id = $student_result->fetch_assoc()['student_id'];
        
        $insert_query = "INSERT INTO book_schedules (book_id, student_id, status, schedule_date, return_date) 
                        VALUES ($book_id, $student_id, 'pending', NOW(), DATE_ADD(NOW(), INTERVAL 14 DAY))";
        
        if ($conn->query($insert_query)) {
            echo "<p>Successfully inserted a new pending request.</p>";
        } else {
            echo "<p>Error inserting: " . $conn->error . "</p>";
        }
    } else {
        echo "<p>Could not find a book or student to create a pending request.</p>";
    }
}

// Show the updated records
$records_query = "SELECT * FROM book_schedules LIMIT 10";
$records_result = $conn->query($records_query);

echo "<h2>Updated Records (up to 10)</h2>";

if ($records_result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Book ID</th><th>Student ID</th><th>Status</th><th>Schedule Date</th></tr>";
    
    while ($record = $records_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($record['schedule_id']) . "</td>";
        echo "<td>" . htmlspecialchars($record['book_id']) . "</td>";
        echo "<td>" . htmlspecialchars($record['student_id']) . "</td>";
        echo "<td>" . htmlspecialchars($record['status'] ?? "NULL") . "</td>";
        echo "<td>" . htmlspecialchars($record['schedule_date'] ?? "NULL") . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>No records found in book_schedules table.</p>";
}

$conn->close();
echo "<p>All done! Please <a href='admin_page.php'>return to the admin page</a> and check if data is now visible.</p>";
?> 