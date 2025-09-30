<?php
require_once 'config.php';

// Drop the table if it exists
$drop_table = "DROP TABLE IF EXISTS borrowed_books";
if ($conn->query($drop_table) === TRUE) {
    echo "Old table dropped successfully<br>";
} else {
    echo "Error dropping table: " . $conn->error . "<br>";
}

// Create the table with all required columns
$create_table = "CREATE TABLE borrowed_books (
    record_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT,
    book_id INT,
    borrow_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    due_date DATETIME,
    return_date DATETIME NULL,
    admin_confirmed_return TINYINT(1) DEFAULT 0,
    user_confirmed_return TINYINT(1) DEFAULT 0,
    status ENUM('borrowed', 'returned', 'overdue') DEFAULT 'borrowed',
    FOREIGN KEY (student_id) REFERENCES users(student_id),
    FOREIGN KEY (book_id) REFERENCES books(book_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($create_table) === TRUE) {
    echo "Table borrowed_books created successfully with all required columns<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Verify the table structure
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