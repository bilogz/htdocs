<?php
require_once 'config.php';

// Set up error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Checking borrowed_books Table</h1>";

// Check if the table exists
$check_table = "SHOW TABLES LIKE 'borrowed_books'";
$table_exists = $conn->query($check_table);

if ($table_exists->num_rows == 0) {
    die("<p>Error: borrowed_books table does not exist!</p>");
}

// Check table structure
echo "<h2>Table Structure</h2>";
$structure = $conn->query("DESCRIBE borrowed_books");
if (!$structure) {
    die("<p>Error: Cannot describe table - " . $conn->error . "</p>");
}

echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

while ($row = $structure->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
    echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
    echo "</tr>";
}
echo "</table>";

// Check for pending return requests using the same query from admin page
$return_query = "SELECT bb.*, b.title as book_title, b.cover_image, u.full_name as student_name, u.email as student_email, 
                 DATEDIFF(bb.return_date, bb.due_date) as days_overdue, 
                 GREATEST(DATEDIFF(bb.return_date, bb.due_date),0) * 1 as overdue_fee 
                 FROM borrowed_books bb 
                 JOIN books b ON bb.book_id = b.book_id 
                 JOIN users u ON bb.student_id = u.student_id 
                 WHERE bb.return_date IS NOT NULL AND bb.admin_confirmed_return = 0 
                 ORDER BY bb.return_date DESC";

echo "<h2>Return Query</h2>";
echo "<pre>" . htmlspecialchars($return_query) . "</pre>";

$return_result = $conn->query($return_query);
if (!$return_result) {
    echo "<p>Error executing return query: " . $conn->error . "</p>";
} else {
    echo "<p>Query executed successfully. Found " . $return_result->num_rows . " pending return requests.</p>";
    
    if ($return_result->num_rows > 0) {
        echo "<h2>Pending Return Requests</h2>";
        echo "<table border='1'>";
        
        // Get column names
        $row = $return_result->fetch_assoc();
        echo "<tr>";
        foreach (array_keys($row) as $key) {
            echo "<th>" . htmlspecialchars($key) . "</th>";
        }
        echo "</tr>";
        
        // Reset pointer and display data
        $return_result->data_seek(0);
        while ($row = $return_result->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $key => $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No pending return requests found.</p>";
        
        // Check if any records have return_date set
        $has_returns = $conn->query("SELECT COUNT(*) as count FROM borrowed_books WHERE return_date IS NOT NULL");
        $return_count = $has_returns->fetch_assoc()['count'];
        echo "<p>Total records with return_date set: $return_count</p>";
        
        if ($return_count > 0) {
            // Check records with return_date set
            $return_records = $conn->query("SELECT * FROM borrowed_books WHERE return_date IS NOT NULL LIMIT 5");
            
            echo "<h2>Sample Records with return_date Set</h2>";
            echo "<table border='1'>";
            
            // Get column names
            $row = $return_records->fetch_assoc();
            echo "<tr>";
            foreach (array_keys($row) as $key) {
                echo "<th>" . htmlspecialchars($key) . "</th>";
            }
            echo "</tr>";
            
            // Reset pointer and display data
            $return_records->data_seek(0);
            while ($row = $return_records->fetch_assoc()) {
                echo "<tr>";
                foreach ($row as $key => $value) {
                    echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        }
        
        // Check if any records have admin_confirmed_return = 0
        $has_unconfirmed = $conn->query("SELECT COUNT(*) as count FROM borrowed_books WHERE admin_confirmed_return = 0");
        $unconfirmed_count = $has_unconfirmed->fetch_assoc()['count'];
        echo "<p>Total records with admin_confirmed_return = 0: $unconfirmed_count</p>";
    }
}

// Check the notification counts (same as check_admin_notifications.php)
$borrow_query = "SELECT COUNT(*) as count FROM book_schedules WHERE status = 'pending'";
$borrow_result = $conn->query($borrow_query);
$pending_borrow = 0;
if ($borrow_result) {
    $row = $borrow_result->fetch_assoc();
    $pending_borrow = $row['count'];
}

$return_query = "SELECT COUNT(*) as count FROM borrowed_books WHERE return_date IS NOT NULL AND admin_confirmed_return = 0";
$return_result = $conn->query($return_query);
$pending_return = 0;
if ($return_result) {
    $row = $return_result->fetch_assoc();
    $pending_return = $row['count'];
}

echo "<h2>Notification Counts</h2>";
echo "<p>Pending borrow requests: $pending_borrow</p>";
echo "<p>Pending return requests: $pending_return</p>";

// Add a form to manually fix the test return requests
echo "<h2>Fix Test Return Requests</h2>";
echo "<form method='post' action='fix_return_requests.php'>";
echo "<button type='submit' name='action' value='fix_returns'>Fix Return Requests</button>";
echo "</form>";

$conn->close();
?> 