<?php
require_once 'config.php';

// Set up error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Book Schedules Table Check</h1>";

// Check if table exists
$check_table = $conn->query("SHOW TABLES LIKE 'book_schedules'");
if ($check_table->num_rows == 0) {
    echo "<p>Table book_schedules does not exist!</p>";
    exit;
}

// Show table structure
echo "<h2>Table Structure</h2>";
$structure = $conn->query("DESCRIBE book_schedules");
if (!$structure) {
    echo "<p>Error checking structure: " . $conn->error . "</p>";
} else {
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = $structure->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

// Count rows
$count = $conn->query("SELECT COUNT(*) as total FROM book_schedules");
$count_row = $count->fetch_assoc();
echo "<p>Total records: " . $count_row['total'] . "</p>";

// Check for pending requests
echo "<h2>Pending Requests</h2>";
$pending = $conn->query("SELECT COUNT(*) as pending FROM book_schedules WHERE status = 'pending'");
$pending_row = $pending->fetch_assoc();
echo "<p>Pending requests: " . $pending_row['pending'] . "</p>";

// Show sample data
echo "<h2>Sample Data (10 rows)</h2>";
$data = $conn->query("SELECT * FROM book_schedules LIMIT 10");
if (!$data || $data->num_rows == 0) {
    echo "<p>No data in the table or error: " . $conn->error . "</p>";
} else {
    echo "<table border='1'>";
    
    // Header row
    $row = $data->fetch_assoc();
    echo "<tr>";
    foreach (array_keys($row) as $key) {
        echo "<th>" . htmlspecialchars($key) . "</th>";
    }
    echo "</tr>";
    
    // Put the first row back and get all rows
    $data->data_seek(0);
    while ($row = $data->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value ?? "NULL") . "</td>";
        }
        echo "</tr>";
    }
    
    echo "</table>";
}

// Check the pending borrow requests query
echo "<h2>Checking Query</h2>";
$query = "SELECT bs.*, b.title as book_title, b.cover_image, u.full_name as student_name, u.email as student_email
    FROM book_schedules bs
    JOIN books b ON bs.book_id = b.book_id
    JOIN users u ON bs.student_id = u.student_id
    WHERE bs.status = 'pending'
    ORDER BY bs.schedule_date DESC";

echo "<pre>" . htmlspecialchars($query) . "</pre>";

$pending_result = $conn->query($query);
if (!$pending_result) {
    echo "<p>Error executing query: " . $conn->error . "</p>";
} else if ($pending_result->num_rows == 0) {
    echo "<p>No pending requests found with the query.</p>";
    
    // Check the tables to make sure they have data
    echo "<h3>Checking Books Table</h3>";
    $books_count = $conn->query("SELECT COUNT(*) as count FROM books");
    $books_row = $books_count->fetch_assoc();
    echo "<p>Books count: " . $books_row['count'] . "</p>";
    
    echo "<h3>Checking Users Table</h3>";
    $users_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'student'");
    $users_row = $users_count->fetch_assoc();
    echo "<p>Student users count: " . $users_row['count'] . "</p>";
    
    // Check just book_schedules with status 'pending'
    echo "<h3>Double-checking Pending Status</h3>";
    $status_check = $conn->query("SELECT status, COUNT(*) as count FROM book_schedules GROUP BY status");
    echo "<table border='1'><tr><th>Status</th><th>Count</th></tr>";
    while ($status_row = $status_check->fetch_assoc()) {
        echo "<tr><td>" . htmlspecialchars($status_row['status']) . "</td><td>" . $status_row['count'] . "</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p>Found " . $pending_result->num_rows . " pending requests.</p>";
    
    echo "<table border='1'>";
    $row = $pending_result->fetch_assoc();
    echo "<tr>";
    foreach (array_keys($row) as $key) {
        echo "<th>" . htmlspecialchars($key) . "</th>";
    }
    echo "</tr>";
    
    // Put the first row back and get all rows
    $pending_result->data_seek(0);
    while ($row = $pending_result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value ?? "NULL") . "</td>";
        }
        echo "</tr>";
    }
    
    echo "</table>";
}

// Close connection
$conn->close();
?> 