<?php
require_once 'config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Fixing Book Status Inconsistencies</h1>";

// Check for available stock and update status accordingly
$update_query = "UPDATE books SET status = 
                    CASE 
                        WHEN available_stock > 0 THEN 'Available'
                        ELSE 'Unavailable'
                    END";

if ($conn->query($update_query)) {
    $affected = $conn->affected_rows;
    echo "<p>Updated $affected book records to have correct status based on stock</p>";
} else {
    echo "<p>Error updating records: " . $conn->error . "</p>";
}

// Check uppercase/lowercase inconsistencies
$check_query = "SELECT book_id, status FROM books";
$result = $conn->query($check_query);

$fixed_count = 0;
if ($result && $result->num_rows > 0) {
    while ($book = $result->fetch_assoc()) {
        // Fix capitalization - ensure it's always "Available" not "available"
        $current_status = $book['status'];
        $correct_status = null;
        
        if (strtolower($current_status) === 'available' && $current_status !== 'Available') {
            $correct_status = 'Available';
        } elseif (strtolower($current_status) === 'unavailable' && $current_status !== 'Unavailable') {
            $correct_status = 'Unavailable';
        } elseif (strtolower($current_status) === 'borrowed' && $current_status !== 'Borrowed') {
            $correct_status = 'Borrowed';
        }
        
        if ($correct_status) {
            $update_stmt = $conn->prepare("UPDATE books SET status = ? WHERE book_id = ?");
            $update_stmt->bind_param("si", $correct_status, $book['book_id']);
            if ($update_stmt->execute()) {
                $fixed_count++;
            }
            $update_stmt->close();
        }
    }
}

echo "<p>Fixed capitalization for $fixed_count book status values</p>";

// Show the updated records
$books_query = "SELECT book_id, title, available_stock, status FROM books LIMIT 20";
$books_result = $conn->query($books_query);

echo "<h2>Updated Book Status (up to 20 books)</h2>";

if ($books_result && $books_result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Title</th><th>Available Stock</th><th>Status</th></tr>";
    
    while ($book = $books_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($book['book_id']) . "</td>";
        echo "<td>" . htmlspecialchars($book['title']) . "</td>";
        echo "<td>" . htmlspecialchars($book['available_stock']) . "</td>";
        echo "<td>" . htmlspecialchars($book['status']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>No books found in the database.</p>";
}

$conn->close();
echo "<p>All done! Please <a href='index.php'>return to the home page</a> and check if the book status is now consistent.</p>";
?> 