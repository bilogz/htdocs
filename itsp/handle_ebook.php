<?php
session_start();
require_once 'config.php';

// Check if user is admin
if ((!isset($_SESSION['student_id']) || $_SESSION['user_type'] !== 'admin') && 
    (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];

    try {
        // Debug info
        $debug = [];
        $debug['post'] = $_POST;
        $debug['files'] = $_FILES;
        
        // Log debug information
        error_log("handle_ebook.php - POST data: " . json_encode($_POST));
        error_log("handle_ebook.php - FILES data: " . json_encode($_FILES));

        // Get the current eBook data
        $stmt = $conn->prepare("SELECT cover_image, file_path FROM ebooks WHERE id = ?");
        $stmt->bind_param("i", $_POST['ebook_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $current_ebook = $result->fetch_assoc();
        $stmt->close();

        if (!$current_ebook) {
            error_log("Ebook not found with ID: " . $_POST['ebook_id']);
            throw new Exception("Ebook with ID " . $_POST['ebook_id'] . " not found.");
        }

        // Handle cover image upload
        $cover_image = $_POST['current_cover'];
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === 0) {
            $cover_dir = 'assets/images/';
            
            // Ensure directory exists
            if (!file_exists($cover_dir)) {
                if (!mkdir($cover_dir, 0777, true)) {
                    throw new Exception("Failed to create directory for cover image: " . $cover_dir);
                }
            }
            
            // Generate a safe filename with original extension
            $original_filename = basename($_FILES['cover_image']['name']);
            $file_extension = pathinfo($original_filename, PATHINFO_EXTENSION);
            $cover_image = 'ebook_cover_' . $_POST['ebook_id'] . '_' . time() . '.' . $file_extension;
            
            if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $cover_dir . $cover_image)) {
                // Delete old cover if it exists and is not the default
                if (!empty($current_ebook['cover_image']) && 
                    file_exists($cover_dir . $current_ebook['cover_image']) && 
                    $current_ebook['cover_image'] != 'default-ebook-cover.jpg') {
                    @unlink($cover_dir . $current_ebook['cover_image']);
                }
                $debug['cover_image'] = 'Uploaded successfully to ' . $cover_dir . $cover_image;
            } else {
                throw new Exception("Failed to upload cover image: " . error_get_last()['message']);
            }
        }

        // Handle eBook file upload
        $file_path = $current_ebook['file_path'];
        if (isset($_FILES['ebook_file']) && $_FILES['ebook_file']['error'] === 0) {
            $ebook_dir = 'ebooks/';
            
            // Ensure directory exists
            if (!file_exists($ebook_dir)) {
                if (!mkdir($ebook_dir, 0777, true)) {
                    throw new Exception("Failed to create directory for ebook: " . $ebook_dir);
                }
            }
            
            // Generate a safe filename with original extension
            $original_filename = basename($_FILES['ebook_file']['name']);
            $file_extension = pathinfo($original_filename, PATHINFO_EXTENSION);
            $new_filename = 'ebook_' . $_POST['ebook_id'] . '_' . time() . '.' . $file_extension;
            $file_path = $ebook_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['ebook_file']['tmp_name'], $file_path)) {
                // Delete old file if exists and is different
                if (!empty($current_ebook['file_path']) && 
                    file_exists($current_ebook['file_path']) && 
                    $current_ebook['file_path'] != $file_path) {
                    @unlink($current_ebook['file_path']);
                }
                $debug['file_path'] = 'Uploaded successfully to ' . $file_path;
            } else {
                throw new Exception("Failed to upload ebook file: " . error_get_last()['message']);
            }
        }

        // Update the eBook in the database
        $stmt = $conn->prepare("UPDATE ebooks SET 
            title = ?, 
            author = ?, 
            category = ?, 
            description = ?, 
            cover_image = ?, 
            file_path = ?, 
            price = ?, 
            status = ?,
            download_status = ?,
            updated_at = NOW()
            WHERE id = ?");

        if (!$stmt) {
            error_log("SQL Prepare Error: " . $conn->error);
            throw new Exception("SQL Prepare Error: " . $conn->error);
        }

        $bind_result = $stmt->bind_param("ssssssdssi", 
            $_POST['title'],
            $_POST['author'],
            $_POST['category'],
            $_POST['description'],
            $cover_image,
            $file_path,
            $_POST['price'],
            $_POST['status'],
            $_POST['download_status'],
            $_POST['ebook_id']
        );

        if (!$bind_result) {
            error_log("Bind Param Error: " . $stmt->error);
            throw new Exception("Bind Param Error: " . $stmt->error);
        }

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'eBook updated successfully';
            $response['debug'] = $debug;
            
            // Add file info to response
            $response['file_info'] = [
                'cover_image' => $cover_image,
                'file_path' => $file_path
            ];
        } else {
            error_log("SQL Execute Error: " . $stmt->error);
            throw new Exception("Error updating eBook: " . $stmt->error);
        }
        $stmt->close();

    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
        $response['error'] = true;
        error_log("Error in handle_ebook.php: " . $e->getMessage());
    }

    echo json_encode($response);
    exit();
}

// If not POST request
echo json_encode(['success' => false, 'message' => 'Invalid request method']);
exit(); 