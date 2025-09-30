<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_id'])) {
    $record_id = intval($_POST['record_id']);
    // Only set return_date, do not set admin_confirmed_return
    $stmt = $conn->prepare("UPDATE borrowed_books SET return_date = NOW() WHERE record_id = ?");
    if (!$stmt) {
        die("Error preparing statement: " . $conn->error);
    }
    $stmt->bind_param("i", $record_id);
    if ($stmt->execute()) {
        echo "Book marked as returned! Please wait for admin confirmation.";
    } else {
        echo "Error marking return: " . $stmt->error;
    }
    $stmt->close();
} else {
    echo "Invalid request.";
} 