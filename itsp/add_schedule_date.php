<?php
require_once 'config.php';

// Add schedule_date column to book_schedules table
$sql = "ALTER TABLE book_schedules ADD COLUMN schedule_date DATETIME DEFAULT CURRENT_TIMESTAMP";

if ($conn->query($sql) === TRUE) {
    echo "Column schedule_date added successfully";
} else {
    echo "Error adding column: " . $conn->error;
}

$conn->close();
?> 