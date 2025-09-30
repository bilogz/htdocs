<?php
require_once 'config.php';

// Set up error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Creating Test Return Requests</h1>";

// Check borrowed_books table structure
$structure = $conn->query("DESCRIBE borrowed_books");
if (!$structure) {
    die("<p>Error: Cannot describe borrowed_books table - " . $conn->error . "</p>");
}

echo "<h2>Borrowed Books Table Structure</h2>";
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

// Check existing data
$existing = $conn->query("SELECT COUNT(*) as count FROM borrowed_books");
$existing_count = $existing->fetch_assoc()['count'];
echo "<p>Current borrowed_books records: $existing_count</p>";

// Check for pending return requests
$pending = $conn->query("SELECT COUNT(*) as count FROM borrowed_books WHERE return_date IS NOT NULL AND admin_confirmed_return = 0");
$pending_count = $pending->fetch_assoc()['count'];
echo "<p>Current pending return requests: $pending_count</p>";

// Get a book and student for test data
$books = $conn->query("SELECT book_id, title FROM books LIMIT 5");
$students = $conn->query("SELECT student_id, full_name FROM users WHERE user_type = 'student' LIMIT 5");

if ($books->num_rows == 0) {
    die("<p>Error: No books found in the database</p>");
}

if ($students->num_rows == 0) {
    die("<p>Error: No students found in the database</p>");
}

echo "<h2>Available Books</h2>";
echo "<ul>";
$books_array = [];
while ($book = $books->fetch_assoc()) {
    echo "<li>ID: {$book['book_id']} - {$book['title']}</li>";
    $books_array[] = $book;
}
echo "</ul>";

echo "<h2>Available Students</h2>";
echo "<ul>";
$students_array = [];
while ($student = $students->fetch_assoc()) {
    echo "<li>ID: {$student['student_id']} - {$student['full_name']}</li>";
    $students_array[] = $student;
}
echo "</ul>";

// Create test records
echo "<h2>Creating Test Records</h2>";

// Function to create a return request
function createReturnRequest($conn, $book_id, $student_id, $book_title, $student_name) {
    // Insert a record that has been returned but not confirmed by admin
    $borrow_date = date('Y-m-d H:i:s', strtotime('-14 days'));
    $due_date = date('Y-m-d H:i:s', strtotime('-2 days'));
    $return_date = date('Y-m-d H:i:s');
    
    $query = "INSERT INTO borrowed_books 
              (book_id, student_id, borrow_date, due_date, return_date, admin_confirmed_return, purpose) 
              VALUES (?, ?, ?, ?, ?, 0, 'Test return request')";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        echo "<p>Error preparing statement: " . $conn->error . "</p>";
        return false;
    }
    
    $stmt->bind_param("iisss", $book_id, $student_id, $borrow_date, $due_date, $return_date);
    
    if ($stmt->execute()) {
        $id = $conn->insert_id;
        echo "<p>Created return request #$id for book '$book_title' borrowed by '$student_name'</p>";
        return true;
    } else {
        echo "<p>Error creating return request: " . $stmt->error . "</p>";
        return false;
    }
}

// Create 2 test return requests
if ($pending_count < 2) {
    $num_to_create = 2 - $pending_count;
    echo "<p>Creating $num_to_create new test return requests...</p>";
    
    $created = 0;
    for ($i = 0; $i < $num_to_create && $i < count($books_array); $i++) {
        $book = $books_array[$i];
        $student = $students_array[$i % count($students_array)];
        
        if (createReturnRequest($conn, $book['book_id'], $student['student_id'], $book['title'], $student['full_name'])) {
            $created++;
        }
    }
    
    echo "<p>Successfully created $created test return requests</p>";
} else {
    echo "<p>There are already enough pending return requests. No need to create more.</p>";
}

// Check if we now have pending return requests
$check_pending = $conn->query("SELECT * FROM borrowed_books WHERE return_date IS NOT NULL AND admin_confirmed_return = 0");
if (!$check_pending) {
    echo "<p>Error checking pending requests: " . $conn->error . "</p>";
} else {
    $new_count = $check_pending->num_rows;
    echo "<p>Now have $new_count pending return requests</p>";
    
    if ($new_count > 0) {
        echo "<h2>Pending Return Requests</h2>";
        echo "<table border='1'>";
        
        // Header row
        $row = $check_pending->fetch_assoc();
        echo "<tr>";
        foreach (array_keys($row) as $key) {
            echo "<th>" . htmlspecialchars($key) . "</th>";
        }
        echo "</tr>";
        
        // Data rows
        $check_pending->data_seek(0);
        while ($row = $check_pending->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $key => $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        
        echo "</table>";
    }
}

$conn->close();
echo "<p>All done! <a href='admin_page.php'>Return to admin page</a> to see if return requests are now visible.</p>";
?> 