<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_id'])) {
    $record_id = intval($_POST['record_id']);
    // Ensure the column exists
    $conn->query("ALTER TABLE borrowed_books ADD COLUMN IF NOT EXISTS admin_confirmed_return TINYINT(1) DEFAULT 0");
    // Get the book_id for this record
    $stmt = $conn->prepare("SELECT book_id FROM borrowed_books WHERE record_id = ?");
    $stmt->bind_param("i", $record_id);
    $stmt->execute();
    $stmt->bind_result($book_id);
    $stmt->fetch();
    $stmt->close();

    // Confirm the return
    $stmt = $conn->prepare("UPDATE borrowed_books SET return_date = NOW(), admin_confirmed_return = 1 WHERE record_id = ?");
    if (!$stmt) {
        die("Error preparing statement: " . $conn->error);
    }
    $stmt->bind_param("i", $record_id);
    if ($stmt->execute()) {
        // Increase book stock by 1
        $update_stock = $conn->prepare("UPDATE books SET stock = stock + 1 WHERE book_id = ?");
        $update_stock->bind_param("i", $book_id);
        $update_stock->execute();
        $update_stock->close();

        // Update book status if stock > 0
        $conn->query("UPDATE books SET status = 'Available' WHERE book_id = $book_id AND stock > 0");

        echo "Book return confirmed!";
    } else {
        echo "Error confirming return: " . $stmt->error;
    }
    $stmt->close();
} else {
    echo "Invalid request.";
} 