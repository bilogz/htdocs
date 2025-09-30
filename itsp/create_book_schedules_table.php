<?php
require_once 'config.php';

// First, check if the table exists
$table_check = "SHOW TABLES LIKE 'book_schedules'";
$result = $conn->query($table_check);

if ($result->num_rows == 0) {
    // Create book_schedules table if it doesn't exist
    $sql = "CREATE TABLE book_schedules (
        id INT PRIMARY KEY AUTO_INCREMENT,
        student_id INT NOT NULL,
        book_id INT NOT NULL,
        schedule_date DATE NOT NULL,
        return_date DATE NOT NULL,
        purpose TEXT NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES users(student_id),
        FOREIGN KEY (book_id) REFERENCES books(book_id)
    )";

    if ($conn->query($sql) === TRUE) {
        echo "Table book_schedules created successfully<br>";
    } else {
        echo "Error creating table: " . $conn->error . "<br>";
    }
} else {
    // Table exists, check for return_date column
    $column_check = "SHOW COLUMNS FROM book_schedules LIKE 'return_date'";
    $result = $conn->query($column_check);
    
    if ($result->num_rows == 0) {
        // Add return_date column if it doesn't exist
        $alter_sql = "ALTER TABLE book_schedules ADD COLUMN return_date DATE NOT NULL AFTER schedule_date";
        if ($conn->query($alter_sql) === TRUE) {
            echo "Column return_date added successfully<br>";
        } else {
            echo "Error adding column: " . $conn->error . "<br>";
        }
    } else {
        echo "Column return_date already exists<br>";
    }
}

// Check for other required columns
$required_columns = ['purpose', 'status'];
foreach ($required_columns as $column) {
    $column_check = "SHOW COLUMNS FROM book_schedules LIKE '$column'";
    $result = $conn->query($column_check);
    
    if ($result->num_rows == 0) {
        $type = ($column == 'purpose') ? 'TEXT' : 'VARCHAR(20)';
        $default = ($column == 'status') ? "DEFAULT 'pending'" : '';
        $alter_sql = "ALTER TABLE book_schedules ADD COLUMN $column $type NOT NULL $default";
        if ($conn->query($alter_sql) === TRUE) {
            echo "Column $column added successfully<br>";
        } else {
            echo "Error adding column $column: " . $conn->error . "<br>";
        }
    } else {
        echo "Column $column already exists<br>";
    }
}

$conn->close();
?> 