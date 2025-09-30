<?php
require_once 'config.php';

// Set up error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Fix and Diagnose Return Requests</h1>";

// Diagnose the current state
echo "<h2>Current Database Status</h2>";

// Check borrowed_books table
$borrowed_query = "SELECT COUNT(*) as count FROM borrowed_books";
$borrowed_result = $conn->query($borrowed_query);
$borrowed_count = $borrowed_result->fetch_assoc()['count'];
echo "<p>Total borrowed_books records: " . $borrowed_count . "</p>";

// Check records with return_date set
$returned_query = "SELECT COUNT(*) as count FROM borrowed_books WHERE return_date IS NOT NULL";
$returned_result = $conn->query($returned_query);
$returned_count = $returned_result->fetch_assoc()['count'];
echo "<p>Records with return_date IS NOT NULL: " . $returned_count . "</p>";

// Check records with admin_confirmed_return = 0
$unconfirmed_query = "SELECT COUNT(*) as count FROM borrowed_books WHERE admin_confirmed_return = 0";
$unconfirmed_result = $conn->query($unconfirmed_query);
$unconfirmed_count = $unconfirmed_result->fetch_assoc()['count'];
echo "<p>Records with admin_confirmed_return = 0: " . $unconfirmed_count . "</p>";

// Check for records that should appear in return requests
$pending_returns_query = "SELECT COUNT(*) as count FROM borrowed_books WHERE return_date IS NOT NULL AND admin_confirmed_return = 0";
$pending_returns_result = $conn->query($pending_returns_query);
$pending_returns_count = $pending_returns_result->fetch_assoc()['count'];
echo "<p>Records with return_date IS NOT NULL AND admin_confirmed_return = 0: " . $pending_returns_count . "</p>";

// Actions to fix the issue
echo "<h2>Actions</h2>";
echo "<form method='post' action=''>";
echo "<button type='submit' name='action' value='create_return_records'>Create New Return Request Records</button>";
echo "<button type='submit' name='action' value='reset_existing'>Reset All Existing Records</button>";
echo "</form>";

if (isset($_POST['action'])) {
    if ($_POST['action'] == 'create_return_records') {
        echo "<h3>Creating New Return Request Records</h3>";
        
        // Get a sample book and student
        $book_query = "SELECT book_id FROM books LIMIT 1";
        $book_result = $conn->query($book_query);
        
        $student_query = "SELECT student_id FROM users WHERE user_type = 'student' LIMIT 1";
        $student_result = $conn->query($student_query);
        
        if ($book_result->num_rows == 0 || $student_result->num_rows == 0) {
            die("<p>Error: Unable to find either books or students in the database.</p>");
        }
        
        $book_id = $book_result->fetch_assoc()['book_id'];
        $student_id = $student_result->fetch_assoc()['student_id'];
        
        echo "<p>Using book ID: " . $book_id . ", student ID: " . $student_id . "</p>";
        
        // Create a new borrow record
        $borrow_date = date('Y-m-d H:i:s', strtotime('-7 days'));
        $due_date = date('Y-m-d H:i:s', strtotime('+7 days'));
        $return_date = date('Y-m-d H:i:s');
        
        $insert_query = "INSERT INTO borrowed_books (student_id, book_id, borrow_date, due_date, return_date, admin_confirmed_return) 
                        VALUES (?, ?, ?, ?, ?, 0)";
        $stmt = $conn->prepare($insert_query);
        
        if (!$stmt) {
            die("<p>Error preparing statement: " . $conn->error . "</p>");
        }
        
        $stmt->bind_param("iisss", $student_id, $book_id, $borrow_date, $due_date, $return_date);
        
        if ($stmt->execute()) {
            $new_id = $stmt->insert_id;
            echo "<p>Success! Created new return request with ID: " . $new_id . "</p>";
        } else {
            echo "<p>Error creating record: " . $stmt->error . "</p>";
        }
    } elseif ($_POST['action'] == 'reset_existing') {
        echo "<h3>Resetting Existing Records</h3>";
        
        // Reset all borrowed_books records
        $reset_query = "UPDATE borrowed_books SET return_date = NULL, admin_confirmed_return = 0 WHERE 1";
        if ($conn->query($reset_query)) {
            echo "<p>Successfully reset all borrowed_books records.</p>";
            
            // Now mark a couple as returned
            $mark_returned_query = "UPDATE borrowed_books SET return_date = NOW() WHERE record_id IN (SELECT record_id FROM (SELECT record_id FROM borrowed_books LIMIT 2) as temp)";
            if ($conn->query($mark_returned_query)) {
                echo "<p>Marked 2 records as returned.</p>";
            } else {
                echo "<p>Error marking records as returned: " . $conn->error . "</p>";
            }
        } else {
            echo "<p>Error resetting records: " . $conn->error . "</p>";
        }
    }
    
    // Refresh the status after action
    echo "<h3>Updated Status</h3>";
    $new_count_query = "SELECT COUNT(*) as count FROM borrowed_books WHERE return_date IS NOT NULL AND admin_confirmed_return = 0";
    $new_count_result = $conn->query($new_count_query);
    $new_count = $new_count_result->fetch_assoc()['count'];
    echo "<p>Records with return_date IS NOT NULL AND admin_confirmed_return = 0: " . $new_count . "</p>";
    
    // Show the actual records
    $records_query = "SELECT bb.*, b.title as book_title, u.full_name as student_name 
                     FROM borrowed_books bb 
                     JOIN books b ON bb.book_id = b.book_id 
                     JOIN users u ON bb.student_id = u.student_id 
                     WHERE bb.return_date IS NOT NULL AND bb.admin_confirmed_return = 0";
    $records_result = $conn->query($records_query);
    
    if ($records_result && $records_result->num_rows > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Book</th><th>Student</th><th>Return Date</th></tr>";
        
        while ($record = $records_result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $record['record_id'] . "</td>";
            echo "<td>" . htmlspecialchars($record['book_title']) . "</td>";
            echo "<td>" . htmlspecialchars($record['student_name']) . "</td>";
            echo "<td>" . $record['return_date'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No return request records found after action.</p>";
        if (!$records_result) {
            echo "<p>Error: " . $conn->error . "</p>";
        }
    }
}

// Link back to admin page
echo "<p><a href='admin_page.php'>Return to admin page</a></p>";

$conn->close();
?> 