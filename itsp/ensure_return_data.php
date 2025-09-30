<?php
require_once 'config.php';

// Set up error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Ensure Return Request Data</h1>";

// Check if we have the necessary data
echo "<h2>Checking Database Status</h2>";

// Check books
$books_query = "SELECT * FROM books LIMIT 5";
$books_result = $conn->query($books_query);

if (!$books_result || $books_result->num_rows == 0) {
    die("<p>Error: No books found in the database. Please add books first.</p>");
} else {
    echo "<p>Found " . $books_result->num_rows . " books.</p>";
}

// Check students
$students_query = "SELECT * FROM users WHERE user_type = 'student' LIMIT 5";
$students_result = $conn->query($students_query);

if (!$students_result || $students_result->num_rows == 0) {
    die("<p>Error: No student users found in the database. Please add students first.</p>");
} else {
    echo "<p>Found " . $students_result->num_rows . " students.</p>";
}

// Check return requests
$return_query = "SELECT COUNT(*) as count FROM borrowed_books WHERE return_date IS NOT NULL AND admin_confirmed_return = 0";
$return_result = $conn->query($return_query);
$return_count = $return_result->fetch_assoc()['count'];

echo "<p>Current return requests: " . $return_count . "</p>";

// Actions
echo "<h2>Actions</h2>";
echo "<form method='post' action=''>";
echo "<button type='submit' name='action' value='add_returns'>Add Test Return Requests</button>";
echo "<button type='submit' name='action' value='clear_returns'>Clear All Return Requests</button>";
echo "</form>";

if (isset($_POST['action'])) {
    if ($_POST['action'] == 'add_returns') {
        echo "<h3>Adding Test Return Requests</h3>";
        
        // Get a book and student
        $book = $books_result->fetch_assoc();
        $student = $students_result->fetch_assoc();
        
        if (!$book || !$student) {
            die("<p>Error: Could not retrieve book or student data.</p>");
        }
        
        $book_id = $book['book_id'];
        $student_id = $student['student_id'];
        
        echo "<p>Using book ID: " . $book_id . " (" . htmlspecialchars($book['title']) . ")</p>";
        echo "<p>Using student ID: " . $student_id . " (" . htmlspecialchars($student['full_name']) . ")</p>";
        
        // Create new return requests
        $added_count = 0;
        for ($i = 0; $i < 2; $i++) {
            // Create a borrow record
            $borrow_date = date('Y-m-d H:i:s', strtotime('-' . (7 + $i) . ' days'));
            $due_date = date('Y-m-d H:i:s', strtotime('+' . (14 - $i) . ' days'));
            $return_date = date('Y-m-d H:i:s', strtotime('-' . (1 + $i) . ' days'));
            
            // Check if this combination already exists
            $check_query = "SELECT COUNT(*) as count FROM borrowed_books 
                           WHERE student_id = ? AND book_id = ? AND borrow_date = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("iis", $student_id, $book_id, $borrow_date);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $exists = $check_result->fetch_assoc()['count'] > 0;
            
            if (!$exists) {
                // Insert new borrow record with return date
                $insert_query = "INSERT INTO borrowed_books 
                                (student_id, book_id, borrow_date, due_date, return_date, admin_confirmed_return, status) 
                                VALUES (?, ?, ?, ?, ?, 0, 'returned')";
                $insert_stmt = $conn->prepare($insert_query);
                $insert_stmt->bind_param("iissss", $student_id, $book_id, $borrow_date, $due_date, $return_date);
                
                if ($insert_stmt->execute()) {
                    $added_count++;
                    echo "<p>Added return request #" . $conn->insert_id . "</p>";
                } else {
                    echo "<p>Error adding return request: " . $insert_stmt->error . "</p>";
                }
            } else {
                echo "<p>A record with book ID " . $book_id . " and student ID " . $student_id . " already exists.</p>";
            }
        }
        
        echo "<p>Added " . $added_count . " new return requests.</p>";
    } elseif ($_POST['action'] == 'clear_returns') {
        echo "<h3>Clearing Return Requests</h3>";
        
        // Reset all borrowed_books records by updating return_date to NULL
        $reset_query = "UPDATE borrowed_books SET return_date = NULL, admin_confirmed_return = 0 WHERE return_date IS NOT NULL";
        if ($conn->query($reset_query)) {
            $affected = $conn->affected_rows;
            echo "<p>Successfully reset " . $affected . " borrowed_books records.</p>";
        } else {
            echo "<p>Error resetting records: " . $conn->error . "</p>";
        }
    }
    
    // Check updated return requests
    $return_query = "SELECT COUNT(*) as count FROM borrowed_books WHERE return_date IS NOT NULL AND admin_confirmed_return = 0";
    $return_result = $conn->query($return_query);
    $return_count = $return_result->fetch_assoc()['count'];
    
    echo "<p>Current return requests after action: " . $return_count . "</p>";
    
    if ($return_count > 0) {
        echo "<h3>Current Return Requests</h3>";
        $records_query = "SELECT bb.*, b.title as book_title, u.full_name as student_name 
                         FROM borrowed_books bb 
                         JOIN books b ON bb.book_id = b.book_id 
                         JOIN users u ON bb.student_id = u.student_id 
                         WHERE bb.return_date IS NOT NULL AND bb.admin_confirmed_return = 0";
        $records_result = $conn->query($records_query);
        
        if ($records_result && $records_result->num_rows > 0) {
            echo "<table border='1'>";
            echo "<tr><th>ID</th><th>Book</th><th>Student</th><th>Borrow Date</th><th>Due Date</th><th>Return Date</th><th>Status</th></tr>";
            
            while ($record = $records_result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $record['record_id'] . "</td>";
                echo "<td>" . htmlspecialchars($record['book_title']) . "</td>";
                echo "<td>" . htmlspecialchars($record['student_name']) . "</td>";
                echo "<td>" . $record['borrow_date'] . "</td>";
                echo "<td>" . $record['due_date'] . "</td>";
                echo "<td>" . $record['return_date'] . "</td>";
                echo "<td>" . $record['status'] . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        } else {
            echo "<p>No return request records found.</p>";
            if (!$records_result) {
                echo "<p>Error: " . $conn->error . "</p>";
            }
        }
    }
}

echo "<p><a href='admin_page.php'>Return to admin page</a></p>";

$conn->close();
?> 