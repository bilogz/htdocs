<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['admin_id'])) {
    die(json_encode(['success' => false, 'message' => 'Not authorized']));
}

if (!isset($_GET['id'])) {
    die(json_encode(['success' => false, 'message' => 'Ebook ID is required']));
}

$ebook_id = $_GET['id'];

// Fetch ebook details
$query = "SELECT * FROM ebooks WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $ebook_id);
$stmt->execute();
$result = $stmt->get_result();

if ($ebook = $result->fetch_assoc()) {
    echo json_encode([
        'success' => true,
        'data' => [
            'id' => $ebook['id'],
            'title' => $ebook['title'],
            'author' => $ebook['author'],
            'category' => $ebook['category'],
            'price' => $ebook['price'],
            'status' => $ebook['status'],
            'download_status' => $ebook['download_status'],
            'description' => $ebook['description'],
            'cover_image' => $ebook['cover_image'],
            'file_path' => $ebook['file_path']
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Ebook not found']);
}

$stmt->close(); 