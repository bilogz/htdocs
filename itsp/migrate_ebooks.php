<?php
require_once 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Start transaction
    $conn->begin_transaction();

    // Get all ebooks from the old table
    $sql = "SELECT * FROM ebooks WHERE id IS NOT NULL";
    $result = $conn->query($sql);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // Check if ebook already exists in new table
            $check_sql = "SELECT ebook_id FROM ebooks WHERE title = ? AND author = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("ss", $row['title'], $row['author']);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows == 0) {
                // Insert into new table
                $insert_sql = "INSERT INTO ebooks (title, author, description, file_path, status) 
                             VALUES (?, ?, ?, ?, 'Available')";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("ssss", 
                    $row['title'],
                    $row['author'],
                    $row['description'],
                    $row['file_path']
                );
                $insert_stmt->execute();
            }
        }
    }

    // Commit transaction
    $conn->commit();
    echo "Migration completed successfully!";

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo "Error during migration: " . $e->getMessage();
}

// Close connection
$conn->close();
?> 