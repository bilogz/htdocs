<?php
require_once 'config.php';

// Set up error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Create Test Return Request Data</h1>";

// Check if we have books and students
$books_query = "SELECT book_id, title FROM books LIMIT 2";
$books_result = $conn->query($books_query);

$students_query = "SELECT student_id, full_name FROM users WHERE user_type = 'student' LIMIT 2";
$students_result = $conn->query($students_query);

if (!$books_result || $books_result->num_rows == 0) {
    die("Error: No books found in the database.");
}

if (!$students_result || $students_result->num_rows == 0) {
    die("Error: No student users found in the database.");
}

echo "<h2>Create Return Requests</h2>";
echo "<form method='post' action=''>";
echo "<button type='submit' name='action' value='create'>Create Test Return Requests</button>";
echo "</form>";

if (isset($_POST['action']) && $_POST['action'] == 'create') {
    // Get a book and student
    $book = $books_result->fetch_assoc();
    $student = $students_result->fetch_assoc();
    
    echo "<p>Using book ID: " . $book['book_id'] . " (" . $book['title'] . ")</p>";
    echo "<p>Using student ID: " . $student['student_id'] . " (" . $student['full_name'] . ")</p>";
    
    // Current time minus days
    $borrow_date = date('Y-m-d H:i:s', strtotime('-30 days'));
    $due_date = date('Y-m-d H:i:s', strtotime('-15 days'));
    $return_date = date('Y-m-d H:i:s', strtotime('-2 days'));
    
    // Create a direct SQL insert for the return request
    $sql = "INSERT INTO borrowed_books (student_id, book_id, borrow_date, due_date, return_date, admin_confirmed_return, status) 
            VALUES (?, ?, ?, ?, ?, 0, 'returned')";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Error preparing statement: " . $conn->error);
    }
    
    $stmt->bind_param("iisss", $student['student_id'], $book['book_id'], $borrow_date, $due_date, $return_date);
    
    if ($stmt->execute()) {
        $id = $conn->insert_id;
        echo "<p class='success'>Successfully created return request #$id</p>";
    } else {
        echo "<p class='error'>Error: " . $stmt->error . "</p>";
    }
    
    // Check if record was created
    $check_query = "SELECT * FROM borrowed_books WHERE record_id = $id";
    $check_result = $conn->query($check_query);
    
    if ($check_result && $check_result->num_rows > 0) {
        $record = $check_result->fetch_assoc();
        echo "<h3>Created Record</h3>";
        echo "<table border='1'>";
        foreach ($record as $key => $value) {
            echo "<tr><th>$key</th><td>" . ($value === null ? 'NULL' : $value) . "</td></tr>";
        }
        echo "</table>";
    }
    
    // Now check if it shows up in the admin query
    $admin_query = "SELECT COUNT(*) as count FROM borrowed_books 
                    WHERE return_date IS NOT NULL AND admin_confirmed_return = 0";
    $admin_result = $conn->query($admin_query);
    $count = $admin_result->fetch_assoc()['count'];
    
    echo "<h3>Return Request Count</h3>";
    echo "<p>Admin query returns $count return requests</p>";
    
    // Show the records
    if ($count > 0) {
        $records_query = "SELECT bb.*, b.title, u.full_name 
                         FROM borrowed_books bb 
                         JOIN books b ON bb.book_id = b.book_id 
                         JOIN users u ON bb.student_id = u.student_id 
                         WHERE bb.return_date IS NOT NULL AND bb.admin_confirmed_return = 0";
        $records_result = $conn->query($records_query);
        
        if ($records_result && $records_result->num_rows > 0) {
            echo "<h3>Return Request Records</h3>";
            echo "<table border='1'>";
            echo "<tr><th>ID</th><th>Book</th><th>Student</th><th>Return Date</th><th>Status</th></tr>";
            
            while ($row = $records_result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['record_id'] . "</td>";
                echo "<td>" . $row['title'] . " (ID: " . $row['book_id'] . ")</td>";
                echo "<td>" . $row['full_name'] . " (ID: " . $row['student_id'] . ")</td>";
                echo "<td>" . $row['return_date'] . "</td>";
                echo "<td>" . $row['status'] . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        } else {
            echo "<p>Error with join query: " . $conn->error . "</p>";
        }
    }
}

echo "<p><a href='admin_page.php'>Return to Admin Page</a></p>";

$conn->close();
?> 