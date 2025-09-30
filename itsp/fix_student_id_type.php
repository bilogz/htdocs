<?php
require_once 'config.php';

$errors = [];
$success = [];

// Alter users table
$sql1 = "ALTER TABLE users MODIFY student_id INT";
if ($conn->query($sql1) === TRUE) {
    $success[] = "users.student_id set to INT";
} else {
    $errors[] = "Error altering users.student_id: " . $conn->error;
}

// Alter book_schedules table
$sql2 = "ALTER TABLE book_schedules MODIFY student_id INT";
if ($conn->query($sql2) === TRUE) {
    $success[] = "book_schedules.student_id set to INT";
} else {
    $errors[] = "Error altering book_schedules.student_id: " . $conn->error;
}

// Output results
if ($success) {
    echo "<b>Success:</b><br>" . implode('<br>', $success) . "<br>";
}
if ($errors) {
    echo "<b>Errors:</b><br>" . implode('<br>', $errors) . "<br>";
}

$conn->close();
?> 