<?php
require_once 'config.php';

// Set up error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Checking Return Requests</h1>";

// The exact same query used in admin_page.php
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

// Execute the query and show results
$return_result = $conn->query($return_query);
if (!$return_result) {
    echo "<p>Error executing return query: " . $conn->error . "</p>";
} else {
    echo "<p>Query executed successfully. Found " . $return_result->num_rows . " pending return requests.</p>";
    
    if ($return_result->num_rows > 0) {
        echo "<h2>Pending Return Requests</h2>";
        echo "<table border='1'>";
        
        // Get column names for headers
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
        
        // Check if there are any records in the borrowed_books table
        $count_query = "SELECT COUNT(*) as count FROM borrowed_books";
        $count_result = $conn->query($count_query);
        $total_count = $count_result->fetch_assoc()['count'];
        echo "<p>Total records in borrowed_books table: $total_count</p>";
        
        // Check if there are any books with return_date set
        $returned_query = "SELECT COUNT(*) as count FROM borrowed_books WHERE return_date IS NOT NULL";
        $returned_result = $conn->query($returned_query);
        $returned_count = $returned_result->fetch_assoc()['count'];
        echo "<p>Records with return_date set: $returned_count</p>";
        
        // Check if there are any books with admin_confirmed_return = 0
        $pending_query = "SELECT COUNT(*) as count FROM borrowed_books WHERE admin_confirmed_return = 0";
        $pending_result = $conn->query($pending_query);
        $pending_count = $pending_result->fetch_assoc()['count'];
        echo "<p>Records with admin_confirmed_return = 0: $pending_count</p>";
        
        // Check the combination
        $combo_query = "SELECT COUNT(*) as count FROM borrowed_books WHERE return_date IS NOT NULL AND admin_confirmed_return = 0";
        $combo_result = $conn->query($combo_query);
        $combo_count = $combo_result->fetch_assoc()['count'];
        echo "<p>Records with return_date IS NOT NULL AND admin_confirmed_return = 0: $combo_count</p>";
        
        // Check if the joined tables have any issues
        $join_test = "SELECT 
                        COUNT(*) as total,
                        COUNT(b.book_id) as valid_books,
                        COUNT(u.student_id) as valid_users
                      FROM 
                        borrowed_books bb 
                        LEFT JOIN books b ON bb.book_id = b.book_id 
                        LEFT JOIN users u ON bb.student_id = u.student_id
                      WHERE 
                        bb.return_date IS NOT NULL 
                        AND bb.admin_confirmed_return = 0";
        $join_result = $conn->query($join_test);
        $join_data = $join_result->fetch_assoc();
        echo "<p>Total records matching criteria: " . $join_data['total'] . "</p>";
        echo "<p>Records with valid book_id: " . $join_data['valid_books'] . "</p>";
        echo "<p>Records with valid student_id: " . $join_data['valid_users'] . "</p>";
        
        // Show a sample of the borrowed_books table
        $sample_query = "SELECT * FROM borrowed_books LIMIT 10";
        $sample_result = $conn->query($sample_query);
        
        if ($sample_result && $sample_result->num_rows > 0) {
            echo "<h2>Sample of borrowed_books table</h2>";
            echo "<table border='1'>";
            
            // Get column names
            $row = $sample_result->fetch_assoc();
            echo "<tr>";
            foreach (array_keys($row) as $key) {
                echo "<th>" . htmlspecialchars($key) . "</th>";
            }
            echo "</tr>";
            
            // Reset pointer and display data
            $sample_result->data_seek(0);
            while ($row = $sample_result->fetch_assoc()) {
                echo "<tr>";
                foreach ($row as $key => $value) {
                    echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        }
    }
}

echo "<h2>Fix Return Requests Options</h2>";

// Add a form to help create new test return requests
echo "<form method='post' action=''>";
echo "<h3>Create New Test Return Requests</h3>";
echo "<button type='submit' name='action' value='create_returns'>Create Test Return Requests</button>";
echo "</form>";

// Process form submission
if (isset($_POST['action']) && $_POST['action'] == 'create_returns') {
    echo "<h2>Creating Test Return Requests</h2>";
    
    // Find books that are borrowed but not returned
    $borrowed_query = "SELECT * FROM borrowed_books WHERE return_date IS NULL LIMIT 2";
    $borrowed_result = $conn->query($borrowed_query);
    
    if ($borrowed_result && $borrowed_result->num_rows > 0) {
        echo "<p>Found " . $borrowed_result->num_rows . " borrowed books to mark as returned.</p>";
        
        $count = 0;
        while ($book = $borrowed_result->fetch_assoc()) {
            $record_id = $book['record_id'];
            $return_date = date('Y-m-d H:i:s');
            
            // Update the record to mark it as returned but not confirmed
            $update_query = "UPDATE borrowed_books SET return_date = ?, admin_confirmed_return = 0 WHERE record_id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("si", $return_date, $record_id);
            
            if ($stmt->execute()) {
                $count++;
                echo "<p>Updated record #$record_id to mark as returned but not confirmed.</p>";
            } else {
                echo "<p>Error updating record #$record_id: " . $stmt->error . "</p>";
            }
        }
        
        echo "<p>Successfully marked $count books as returned.</p>";
    } else {
        // If no borrowed books found, create some test borrow records first
        echo "<p>No borrowed books found or all books are already marked as returned. Creating new test borrow records...</p>";
        
        // Get a book and student for test data
        $books = $conn->query("SELECT book_id FROM books LIMIT 2");
        $students = $conn->query("SELECT student_id FROM users WHERE user_type = 'student' LIMIT 1");
        
        if ($books->num_rows == 0 || $students->num_rows == 0) {
            die("<p>Error: Not enough books or students in the database</p>");
        }
        
        $student = $students->fetch_assoc()['student_id'];
        $book_ids = [];
        while ($book = $books->fetch_assoc()) {
            $book_ids[] = $book['book_id'];
        }
        
        // Create borrow records
        $borrow_date = date('Y-m-d H:i:s', strtotime('-7 days'));
        $due_date = date('Y-m-d H:i:s', strtotime('+7 days'));
        
        foreach ($book_ids as $book_id) {
            $insert_query = "INSERT INTO borrowed_books (student_id, book_id, borrow_date, due_date) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("iiss", $student, $book_id, $borrow_date, $due_date);
            
            if ($stmt->execute()) {
                $record_id = $conn->insert_id;
                echo "<p>Created borrow record #$record_id for book ID $book_id.</p>";
                
                // Now mark it as returned but not confirmed
                $return_date = date('Y-m-d H:i:s');
                $update_query = "UPDATE borrowed_books SET return_date = ?, admin_confirmed_return = 0 WHERE record_id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("si", $return_date, $record_id);
                
                if ($update_stmt->execute()) {
                    echo "<p>Updated record #$record_id to mark as returned but not confirmed.</p>";
                } else {
                    echo "<p>Error updating record #$record_id: " . $update_stmt->error . "</p>";
                }
            } else {
                echo "<p>Error creating borrow record: " . $stmt->error . "</p>";
            }
        }
    }
    
    echo "<p><a href='check_returns.php'>Refresh to see updated results</a></p>";
}

echo "<p><a href='admin_page.php'>Return to Admin Page</a></p>";

$conn->close();
?> 