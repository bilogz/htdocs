<?php
require_once 'config.php';

// First, let's check if the table exists
$check_table = "SHOW TABLES LIKE 'borrowed_books'";
$table_exists = $conn->query($check_table);

if ($table_exists->num_rows == 0) {
    // Create the table if it doesn't exist
    $create_table = "CREATE TABLE borrowed_books (
        record_id INT PRIMARY KEY AUTO_INCREMENT,
        student_id VARCHAR(50),
        book_id INT,
        borrow_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        due_date DATETIME,
        return_date DATETIME NULL,
        admin_confirmed_return TINYINT(1) DEFAULT 0,
        status VARCHAR(20) DEFAULT 'borrowed',
        FOREIGN KEY (student_id) REFERENCES users(student_id),
        FOREIGN KEY (book_id) REFERENCES books(book_id)
    )";
    
    if ($conn->query($create_table) === TRUE) {
        echo "Table borrowed_books created successfully<br>";
    } else {
        echo "Error creating table: " . $conn->error . "<br>";
    }
} else {
    // Check if return_date column exists
    $check_column = "SHOW COLUMNS FROM borrowed_books LIKE 'return_date'";
    $column_exists = $conn->query($check_column);
    
    if ($column_exists->num_rows == 0) {
        // Add return_date column if it doesn't exist
        $add_column = "ALTER TABLE borrowed_books ADD COLUMN return_date DATETIME NULL";
        if ($conn->query($add_column) === TRUE) {
            echo "Column return_date added successfully<br>";
        } else {
            echo "Error adding column: " . $conn->error . "<br>";
        }
    } else {
        echo "Column return_date already exists<br>";
    }
}

// Show the current table structure
echo "<br>Current table structure:<br>";
$structure = "DESCRIBE borrowed_books";
$result = $conn->query($structure);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "<br>";
    }
}

$conn->close();
?> 