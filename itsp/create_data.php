<?php
require_once 'config.php';

// Set up error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Create Test Return Requests</h1>";

// Get a book and student for test data
$books = $conn->query("SELECT book_id FROM books LIMIT 2");
$students = $conn->query("SELECT student_id FROM users WHERE user_type = 'student' LIMIT 1");

if ($books->num_rows == 0) {
    die("<p>Error: No books found in the database. Please add books first.</p>");
}

if ($students->num_rows == 0) {
    die("<p>Error: No students found in the database. Please add students first.</p>");
}

$student = $students->fetch_assoc()['student_id'];
echo "<p>Using student ID: $student</p>";

$book_ids = [];
while ($book = $books->fetch_assoc()) {
    $book_ids[] = $book['book_id'];
    echo "<p>Using book ID: " . $book['book_id'] . "</p>";
}

// Create borrow records
$borrow_date = date('Y-m-d H:i:s', strtotime('-7 days'));
$due_date = date('Y-m-d H:i:s', strtotime('+7 days'));

foreach ($book_ids as $book_id) {
    // First, check if there's already a borrowed record for this book/student
    $check_query = "SELECT * FROM borrowed_books WHERE book_id = ? AND student_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("ii", $book_id, $student);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $existing = $check_result->fetch_assoc();
        $record_id = $existing['record_id'];
        echo "<p>Found existing borrow record #$record_id for book ID $book_id. Updating it to be returned.</p>";
        
        // Update the existing record to mark it as returned
        $return_date = date('Y-m-d H:i:s');
        $update_query = "UPDATE borrowed_books SET return_date = ?, admin_confirmed_return = 0 WHERE record_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("si", $return_date, $record_id);
        
        if ($update_stmt->execute()) {
            echo "<p>Successfully updated record #$record_id to mark as returned but not confirmed.</p>";
        } else {
            echo "<p>Error updating record: " . $update_stmt->error . "</p>";
        }
    } else {
        // Create a new borrow record
        $insert_query = "INSERT INTO borrowed_books (student_id, book_id, borrow_date, due_date) 
                        VALUES (?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("iiss", $student, $book_id, $borrow_date, $due_date);
        
        if ($insert_stmt->execute()) {
            $record_id = $conn->insert_id;
            echo "<p>Created new borrow record #$record_id for book ID $book_id.</p>";
            
            // Now mark it as returned but not confirmed
            $return_date = date('Y-m-d H:i:s');
            $update_query = "UPDATE borrowed_books SET return_date = ?, admin_confirmed_return = 0 WHERE record_id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("si", $return_date, $record_id);
            
            if ($update_stmt->execute()) {
                echo "<p>Updated record #$record_id to mark as returned but not confirmed.</p>";
            } else {
                echo "<p>Error updating record: " . $update_stmt->error . "</p>";
            }
        } else {
            echo "<p>Error creating borrow record: " . $insert_stmt->error . "</p>";
        }
    }
}

// Check the current state of return requests
echo "<h2>Current Return Requests</h2>";
$return_query = "SELECT bb.record_id, bb.student_id, bb.book_id, bb.return_date, bb.admin_confirmed_return, 
                b.title as book_title, u.full_name as student_name
                FROM borrowed_books bb 
                JOIN books b ON bb.book_id = b.book_id 
                JOIN users u ON bb.student_id = u.student_id 
                WHERE bb.return_date IS NOT NULL AND bb.admin_confirmed_return = 0";
$return_result = $conn->query($return_query);

if ($return_result && $return_result->num_rows > 0) {
    echo "<p>Found " . $return_result->num_rows . " return requests:</p>";
    echo "<table border='1'>";
    echo "<tr><th>Record ID</th><th>Book</th><th>Student</th><th>Return Date</th></tr>";
    
    while ($row = $return_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['record_id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['book_title']) . " (ID: " . $row['book_id'] . ")</td>";
        echo "<td>" . htmlspecialchars($row['student_name']) . " (ID: " . $row['student_id'] . ")</td>";
        echo "<td>" . $row['return_date'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>No return requests found after creating test data.</p>";
    if (!$return_result) {
        echo "<p>Error: " . $conn->error . "</p>";
    }
}

echo "<p><a href='admin_page.php'>Return to Admin Page</a></p>";

$conn->close();
?> 