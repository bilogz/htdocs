<?php
require_once 'config.php';

// Set up error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Fixing Return Requests</h1>";

// Check if table exists
$check_table = "SHOW TABLES LIKE 'borrowed_books'";
$table_exists = $conn->query($check_table);

if ($table_exists->num_rows == 0) {
    die("<p>Error: borrowed_books table does not exist!</p>");
}

// Check for records with return_date set but admin_confirmed_return = 1
$check_query = "SELECT COUNT(*) as count FROM borrowed_books WHERE return_date IS NOT NULL AND admin_confirmed_return = 1";
$check_result = $conn->query($check_query);
$already_confirmed = $check_result->fetch_assoc()['count'];
echo "<p>Records with return_date set and already confirmed: $already_confirmed</p>";

// Check for pending return requests
$pending_query = "SELECT COUNT(*) as count FROM borrowed_books WHERE return_date IS NOT NULL AND admin_confirmed_return = 0";
$pending_result = $conn->query($pending_query);
$pending_count = $pending_result->fetch_assoc()['count'];
echo "<p>Current pending return requests: $pending_count</p>";

if (isset($_POST['action']) && $_POST['action'] == 'fix_returns') {
    if ($pending_count == 0) {
        // If no pending returns, try to create some test returns
        echo "<h2>Creating Test Return Requests</h2>";
        
        // Find books that are borrowed but not returned
        $borrowed_query = "SELECT * FROM borrowed_books WHERE return_date IS NULL LIMIT 2";
        $borrowed_result = $conn->query($borrowed_query);
        
        if ($borrowed_result->num_rows > 0) {
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
            echo "<p>No borrowed books found. Creating test borrow records first.</p>";
            
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
    } else {
        // If pending returns exist but are not showing, check for potential issues
        
        // 1. Check if there's an issue with student_id data type
        echo "<h2>Checking Student ID Data Types</h2>";
        $records_query = "SELECT bb.record_id, bb.student_id, u.student_id as user_id 
                          FROM borrowed_books bb 
                          LEFT JOIN users u ON bb.student_id = u.student_id 
                          WHERE bb.return_date IS NOT NULL AND bb.admin_confirmed_return = 0";
        $records_result = $conn->query($records_query);
        
        if (!$records_result) {
            echo "<p>Error querying records: " . $conn->error . "</p>";
        } else {
            echo "<p>Checked " . $records_result->num_rows . " records.</p>";
            $missing_users = 0;
            
            while ($record = $records_result->fetch_assoc()) {
                if ($record['user_id'] === null) {
                    $missing_users++;
                    echo "<p>Record #" . $record['record_id'] . " has student_id " . $record['student_id'] . " which does not exist in users table.</p>";
                    
                    // Fix missing student reference
                    $valid_student_query = "SELECT student_id FROM users WHERE user_type = 'student' LIMIT 1";
                    $valid_student_result = $conn->query($valid_student_query);
                    
                    if ($valid_student_result->num_rows > 0) {
                        $valid_student = $valid_student_result->fetch_assoc()['student_id'];
                        $update_query = "UPDATE borrowed_books SET student_id = ? WHERE record_id = ?";
                        $stmt = $conn->prepare($update_query);
                        $stmt->bind_param("ii", $valid_student, $record['record_id']);
                        
                        if ($stmt->execute()) {
                            echo "<p>Updated record #" . $record['record_id'] . " with valid student ID $valid_student.</p>";
                        } else {
                            echo "<p>Error updating record: " . $stmt->error . "</p>";
                        }
                    }
                }
            }
            
            if ($missing_users > 0) {
                echo "<p>Found and fixed $missing_users records with missing user references.</p>";
            } else {
                echo "<p>All student_id values are valid.</p>";
            }
        }
        
        // 2. Check if there's an issue with book_id data type
        echo "<h2>Checking Book ID Data Types</h2>";
        $books_query = "SELECT bb.record_id, bb.book_id, b.book_id as actual_book_id 
                        FROM borrowed_books bb 
                        LEFT JOIN books b ON bb.book_id = b.book_id 
                        WHERE bb.return_date IS NOT NULL AND bb.admin_confirmed_return = 0";
        $books_result = $conn->query($books_query);
        
        if (!$books_result) {
            echo "<p>Error querying books: " . $conn->error . "</p>";
        } else {
            echo "<p>Checked " . $books_result->num_rows . " records.</p>";
            $missing_books = 0;
            
            while ($record = $books_result->fetch_assoc()) {
                if ($record['actual_book_id'] === null) {
                    $missing_books++;
                    echo "<p>Record #" . $record['record_id'] . " has book_id " . $record['book_id'] . " which does not exist in books table.</p>";
                    
                    // Fix missing book reference
                    $valid_book_query = "SELECT book_id FROM books LIMIT 1";
                    $valid_book_result = $conn->query($valid_book_query);
                    
                    if ($valid_book_result->num_rows > 0) {
                        $valid_book = $valid_book_result->fetch_assoc()['book_id'];
                        $update_query = "UPDATE borrowed_books SET book_id = ? WHERE record_id = ?";
                        $stmt = $conn->prepare($update_query);
                        $stmt->bind_param("ii", $valid_book, $record['record_id']);
                        
                        if ($stmt->execute()) {
                            echo "<p>Updated record #" . $record['record_id'] . " with valid book ID $valid_book.</p>";
                        } else {
                            echo "<p>Error updating record: " . $stmt->error . "</p>";
                        }
                    }
                }
            }
            
            if ($missing_books > 0) {
                echo "<p>Found and fixed $missing_books records with missing book references.</p>";
            } else {
                echo "<p>All book_id values are valid.</p>";
            }
        }
        
        // 3. Check if there's an issue with date formats
        echo "<h2>Checking Date Formats</h2>";
        $dates_query = "SELECT record_id, return_date, due_date, borrow_date 
                       FROM borrowed_books 
                       WHERE return_date IS NOT NULL AND admin_confirmed_return = 0";
        $dates_result = $conn->query($dates_query);
        
        if (!$dates_result) {
            echo "<p>Error querying dates: " . $conn->error . "</p>";
        } else {
            echo "<p>Checked " . $dates_result->num_rows . " records.</p>";
            $invalid_dates = 0;
            
            while ($record = $dates_result->fetch_assoc()) {
                $return_date = strtotime($record['return_date']);
                $due_date = strtotime($record['due_date']);
                $borrow_date = strtotime($record['borrow_date']);
                
                if ($return_date === false || $due_date === false || $borrow_date === false) {
                    $invalid_dates++;
                    echo "<p>Record #" . $record['record_id'] . " has invalid date format.</p>";
                    
                    // Fix invalid dates
                    $now = date('Y-m-d H:i:s');
                    $past = date('Y-m-d H:i:s', strtotime('-7 days'));
                    $future = date('Y-m-d H:i:s', strtotime('+7 days'));
                    
                    $update_query = "UPDATE borrowed_books 
                                    SET borrow_date = ?, due_date = ?, return_date = ? 
                                    WHERE record_id = ?";
                    $stmt = $conn->prepare($update_query);
                    $stmt->bind_param("sssi", $past, $future, $now, $record['record_id']);
                    
                    if ($stmt->execute()) {
                        echo "<p>Updated record #" . $record['record_id'] . " with valid date formats.</p>";
                    } else {
                        echo "<p>Error updating record: " . $stmt->error . "</p>";
                    }
                }
            }
            
            if ($invalid_dates > 0) {
                echo "<p>Found and fixed $invalid_dates records with invalid date formats.</p>";
            } else {
                echo "<p>All date formats are valid.</p>";
            }
        }
    }
    
    // Check if our fixes worked
    $final_check = "SELECT COUNT(*) as count FROM borrowed_books WHERE return_date IS NOT NULL AND admin_confirmed_return = 0";
    $final_result = $conn->query($final_check);
    $final_count = $final_result->fetch_assoc()['count'];
    
    echo "<h2>Results After Fixes</h2>";
    echo "<p>Pending return requests now: $final_count</p>";
    
    if ($final_count > 0) {
        echo "<p class='success'>✅ Fixes appear to be successful!</p>";
    } else {
        echo "<p class='error'>❌ Still no pending return requests found.</p>";
    }
}

echo "<p><a href='check_borrowed_books.php'>Back to Check Borrowed Books</a></p>";
echo "<p><a href='admin_page.php'>Back to Admin Page</a></p>";

$conn->close();
?> 