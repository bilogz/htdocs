<?php
require_once 'config.php';

// Set up error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Fix Borrowed Books Status</h1>";

// Check the current status values
$status_query = "SELECT status, COUNT(*) as count FROM borrowed_books GROUP BY status";
$status_result = $conn->query($status_query);

echo "<h2>Current Status Values</h2>";
if ($status_result && $status_result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>Status</th><th>Count</th></tr>";
    
    while ($row = $status_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . ($row['status'] ? htmlspecialchars($row['status']) : 'NULL') . "</td>";
        echo "<td>" . $row['count'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>No records found or query failed</p>";
}

// Fix actions
echo "<h2>Fix Actions</h2>";
echo "<form method='post' action=''>";
echo "<button type='submit' name='action' value='update_status'>Update Status Field</button>";
echo "</form>";

if (isset($_POST['action']) && $_POST['action'] === 'update_status') {
    // Set all borrowed_books records with return_date IS NULL to "borrowed"
    $update_borrowed = "UPDATE borrowed_books SET status = 'borrowed' WHERE return_date IS NULL";
    if ($conn->query($update_borrowed)) {
        echo "<p>Updated records with return_date IS NULL to 'borrowed'</p>";
    } else {
        echo "<p>Error updating records: " . $conn->error . "</p>";
    }
    
    // Set all borrowed_books records with return_date IS NOT NULL to "returned"
    $update_returned = "UPDATE borrowed_books SET status = 'returned' WHERE return_date IS NOT NULL AND admin_confirmed_return = 0";
    if ($conn->query($update_returned)) {
        echo "<p>Updated records with return_date IS NOT NULL AND admin_confirmed_return = 0 to 'returned'</p>";
    } else {
        echo "<p>Error updating records: " . $conn->error . "</p>";
    }
    
    // Set all borrowed_books records with admin_confirmed_return = 1 to "completed" 
    $update_completed = "UPDATE borrowed_books SET status = 'completed' WHERE admin_confirmed_return = 1";
    if ($conn->query($update_completed)) {
        echo "<p>Updated records with admin_confirmed_return = 1 to 'completed'</p>";
    } else {
        echo "<p>Error updating records: " . $conn->error . "</p>";
    }
    
    // Show the updated status values
    echo "<h2>Updated Status Values</h2>";
    $updated_status = $conn->query($status_query);
    
    if ($updated_status && $updated_status->num_rows > 0) {
        echo "<table border='1'>";
        echo "<tr><th>Status</th><th>Count</th></tr>";
        
        while ($row = $updated_status->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . ($row['status'] ? htmlspecialchars($row['status']) : 'NULL') . "</td>";
            echo "<td>" . $row['count'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    // Check the query used by admin_page.php
    $admin_query = "SELECT COUNT(*) as count FROM borrowed_books WHERE return_date IS NOT NULL AND admin_confirmed_return = 0";
    $admin_result = $conn->query($admin_query);
    $admin_count = $admin_result->fetch_assoc()['count'];
    
    echo "<h2>Return Request Query Check</h2>";
    echo "<p>Records matching admin page query (return_date IS NOT NULL AND admin_confirmed_return = 0): " . $admin_count . "</p>";
    
    // Check with added status condition
    $status_check = "SELECT COUNT(*) as count FROM borrowed_books WHERE return_date IS NOT NULL AND admin_confirmed_return = 0 AND status = 'returned'";
    $status_check_result = $conn->query($status_check);
    $status_check_count = $status_check_result->fetch_assoc()['count'];
    
    echo "<p>Records with status = 'returned' AND return_date IS NOT NULL AND admin_confirmed_return = 0: " . $status_check_count . "</p>";
    
    // Show a sample of records
    echo "<h2>Sample Return Request Records</h2>";
    $sample_query = "SELECT bb.*, b.title as book_title, u.full_name as student_name 
                    FROM borrowed_books bb 
                    JOIN books b ON bb.book_id = b.book_id 
                    JOIN users u ON bb.student_id = u.student_id 
                    WHERE bb.return_date IS NOT NULL AND bb.admin_confirmed_return = 0 
                    ORDER BY bb.return_date DESC LIMIT 5";
    $sample_result = $conn->query($sample_query);
    
    if ($sample_result && $sample_result->num_rows > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Book</th><th>Student</th><th>Status</th><th>Return Date</th></tr>";
        
        while ($record = $sample_result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $record['record_id'] . "</td>";
            echo "<td>" . htmlspecialchars($record['book_title']) . "</td>";
            echo "<td>" . htmlspecialchars($record['student_name']) . "</td>";
            echo "<td>" . htmlspecialchars($record['status']) . "</td>";
            echo "<td>" . $record['return_date'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No sample records found</p>";
        if (!$sample_result) {
            echo "<p>Error: " . $conn->error . "</p>";
        }
    }
}

echo "<p><a href='admin_page.php'>Return to admin page</a></p>";

$conn->close();
?> 