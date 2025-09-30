<?php
session_start();
require 'config.php';

// Check if user is logged in
if (!isset($_SESSION['student_id']) && (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true)) {
    $_SESSION['error'] = "Please login to download ebooks.";
    header("Location: login.php?type=student");
    exit();
}

// Check if ebook ID is provided (accept both id and ebook_id parameters)
if (!isset($_GET['id']) && !isset($_GET['ebook_id'])) {
    $_SESSION['error'] = "Invalid ebook request.";
    header("Location: index.php");
    exit();
}

// Get the ebook ID from either parameter
$ebook_id = isset($_GET['id']) ? $_GET['id'] : $_GET['ebook_id'];

// Get ebook details
$query = "SELECT * FROM ebooks WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $ebook_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Ebook not found.";
    header("Location: index.php");
    exit();
}

$ebook = $result->fetch_assoc();
$file_path = $ebook['file_path'];

// Check if file exists
if (!file_exists($file_path)) {
    $_SESSION['error'] = "File not found.";
    header("Location: index.php");
    exit();
}

// Set headers for download
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Output file
readfile($file_path);
exit(); 