<?php
session_start();
require_once 'config.php';

// Check if user is admin
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
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

        // Validate required fields
        $required_fields = ['title', 'author', 'category', 'description'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Field '{$field}' is required.");
            }
        }

        // Check if ebook file was uploaded
        if (!isset($_FILES['ebook_file']) || $_FILES['ebook_file']['error'] !== 0) {
            throw new Exception("Please upload a valid eBook file.");
        }

        // Handle cover image upload
        $cover_image = 'default-ebook-cover.jpg'; // Default image if no cover is uploaded
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
            
            if (!in_array(strtolower($file_extension), ['jpg', 'jpeg', 'png', 'gif'])) {
                throw new Exception("Invalid cover image format. Please use JPG, PNG, or GIF.");
            }
            
            $cover_image = 'ebook_cover_' . time() . '_' . uniqid() . '.' . $file_extension;
            
            if (!move_uploaded_file($_FILES['cover_image']['tmp_name'], $cover_dir . $cover_image)) {
                throw new Exception("Failed to upload cover image: " . error_get_last()['message']);
            }
            
            $debug['cover_image'] = 'Uploaded successfully to ' . $cover_dir . $cover_image;
        }

        // Handle eBook file upload
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
        
        // Validate file extension
        $allowed_extensions = ['pdf', 'epub', 'mobi', 'doc', 'docx', 'txt', 'rtf'];
        if (!in_array(strtolower($file_extension), $allowed_extensions)) {
            throw new Exception("Invalid eBook format. Allowed formats: " . implode(', ', $allowed_extensions));
        }
        
        $new_filename = 'ebook_' . time() . '_' . uniqid() . '.' . $file_extension;
        $file_path = $ebook_dir . $new_filename;
        
        if (!move_uploaded_file($_FILES['ebook_file']['tmp_name'], $file_path)) {
            throw new Exception("Failed to upload ebook file: " . error_get_last()['message']);
        }
        
        $debug['file_path'] = 'Uploaded successfully to ' . $file_path;

        // Set default values for optional fields
        $price = !empty($_POST['price']) ? floatval($_POST['price']) : 0.00;
        $status = !empty($_POST['status']) ? $_POST['status'] : 'Available';
        $download_status = !empty($_POST['download_status']) ? $_POST['download_status'] : 'Enabled';
        
        // Insert the eBook into the database
        $stmt = $conn->prepare("INSERT INTO ebooks (
            title, 
            author, 
            category, 
            description, 
            cover_image, 
            file_path, 
            price, 
            status,
            download_status,
            created_at,
            updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");

        $stmt->bind_param("ssssssdss", 
            $_POST['title'],
            $_POST['author'],
            $_POST['category'],
            $_POST['description'],
            $cover_image,
            $file_path,
            $price,
            $status,
            $download_status
        );

        if ($stmt->execute()) {
            $ebook_id = $conn->insert_id;
            $response['success'] = true;
            $response['message'] = 'eBook added successfully';
            $response['ebook_id'] = $ebook_id;
            $response['debug'] = $debug;
            
            // Add file info to response
            $response['file_info'] = [
                'cover_image' => $cover_image,
                'file_path' => $file_path
            ];
        } else {
            // If database insertion fails, delete the uploaded files
            @unlink($file_path);
            if ($cover_image !== 'default-ebook-cover.jpg') {
                @unlink($cover_dir . $cover_image);
            }
            throw new Exception("Error adding eBook: " . $stmt->error);
        }
        $stmt->close();

    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
        $response['error'] = true;
        error_log("Error in add_new_ebook.php: " . $e->getMessage());
    }

    echo json_encode($response);
    exit();
}

// If not POST request
echo json_encode(['success' => false, 'message' => 'Invalid request method']);
exit(); 