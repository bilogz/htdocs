<?php
require_once 'config.php';

// Add return_date column to borrowed_books table
$sql = "ALTER TABLE borrowed_books ADD COLUMN return_date DATETIME NULL";

if ($conn->query($sql) === TRUE) {
    echo "Column return_date added successfully";
} else {
    echo "Error adding column: " . $conn->error;
}

$conn->close();
?> 