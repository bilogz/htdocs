<?php
session_start();
require 'config.php';

// Only allow logged-in users (student or admin)
if (!isset($_SESSION['student_id']) && (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true)) {
    $redirect_url = 'read_ebook.php?id=' . urlencode($_GET['id'] ?? $_GET['ebook_id'] ?? '');
    header('Location: login.php?redirect=' . urlencode($redirect_url));
    exit();
}

// Check if ebook ID is provided (accept both id and ebook_id parameters)
if (!isset($_GET['id']) && !isset($_GET['ebook_id'])) {
    header('Location: index.php');
    exit();
}

// Get the ebook ID from either parameter
$ebook_id = isset($_GET['id']) ? intval($_GET['id']) : intval($_GET['ebook_id']);

// Get ebook details
$stmt = $conn->prepare("SELECT * FROM ebooks WHERE id = ? AND status = 'Available'");
if (!$stmt) {
    die("Error preparing statement: " . $conn->error);
}
if (!$stmt->bind_param("i", $ebook_id)) {
    die("Error binding parameters: " . $stmt->error);
}
if (!$stmt->execute()) {
    die("Error executing statement: " . $stmt->error);
}
$result = $stmt->get_result();
$ebook = $result->fetch_assoc();
$stmt->close();

if (!$ebook) {
    header('Location: index.php');
    exit();
}

// Check if file exists
if (!file_exists($ebook['file_path'])) {
    die("Ebook file not found.");
}

// Get file extension
$file_extension = strtolower(pathinfo($ebook['file_path'], PATHINFO_EXTENSION));

// Log access to the ebook (optional, uncomment if you want to track ebook reads)
/*
$user_id = isset($_SESSION['student_id']) ? $_SESSION['student_id'] : (isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : null);
if ($user_id) {
    $log_stmt = $conn->prepare("INSERT INTO ebook_access_logs (ebook_id, user_id, access_time) VALUES (?, ?, NOW())");
    $log_stmt->bind_param("ii", $ebook_id, $user_id);
    $log_stmt->execute();
    $log_stmt->close();
}
*/

// Set appropriate headers based on file type
switch ($file_extension) {
    case 'pdf':
        header('Content-Type: application/pdf');
        break;
    case 'epub':
        header('Content-Type: application/epub+zip');
        break;
    case 'mobi':
        header('Content-Type: application/x-mobipocket-ebook');
        break;
    case 'txt':
        header('Content-Type: text/plain');
        break;
    case 'rtf':
        header('Content-Type: application/rtf');
        break;
    case 'doc':
    case 'docx':
        header('Content-Type: application/msword');
        break;
    case 'xls':
    case 'xlsx':
        header('Content-Type: application/vnd.ms-excel');
        break;
    case 'ppt':
    case 'pptx':
        header('Content-Type: application/vnd.ms-powerpoint');
        break;
    default:
        header('Content-Type: application/octet-stream');
}

// Set filename for download
header('Content-Disposition: inline; filename="' . basename($ebook['file_path']) . '"');
header('Content-Length: ' . filesize($ebook['file_path']));

// Output file
readfile($ebook['file_path']);
exit(); 