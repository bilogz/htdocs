<?php
session_start();
require 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['student_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$student_id = $_SESSION['student_id'];

if (!isset($_FILES['profile_picture'])) {
    echo json_encode(['status' => 'error', 'message' => 'No file uploaded']);
    exit;
}

$file = $_FILES['profile_picture'];
$target_dir = "assets/images/profile_pictures/";
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0777, true);
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid file type']);
    exit;
}

$new_filename = "profile_" . $student_id . "." . $ext;
$target_path = $target_dir . $new_filename;

if (move_uploaded_file($file['tmp_name'], $target_path)) {
    $relative_path = $target_path;
    $stmt = $conn->prepare("UPDATE users SET profile_pic = ? WHERE student_id = ?");
    $stmt->bind_param("si", $relative_path, $student_id);
    if ($stmt->execute()) {
        $timestamp = time();
        echo json_encode([
            'status' => 'success', 
            'new_path' => $relative_path . '?t=' . $timestamp
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'DB update failed']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to move file']);
}
