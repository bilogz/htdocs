<?php
include 'config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

$admin_id = 'admin';
$admin_password = 'admin';
$admin_email = 'admin@library.com';
$admin_name = 'System Administrator';
$hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);

// Find the current admin's student_id (by email or user_type)
$result = $conn->query("SELECT student_id FROM users WHERE (student_id = '$admin_id' OR email = '$admin_email') AND user_type = 'admin' LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    $old_id = $row['student_id'];
} else {
    // If not found, insert a new admin
    $insert = $conn->prepare("INSERT INTO users (student_id, password, email, full_name, user_type, profile_pic) VALUES (?, ?, ?, ?, 'admin', 'assets/images/profile_pictures/default.png')");
    $insert->bind_param("ssss", $admin_id, $hashed_password, $admin_email, $admin_name);
    if ($insert->execute()) {
        echo "<div style='color: green;'>Admin account created. ID: admin, Password: admin</div>";
    } else {
        echo "<div style='color: red;'>Failed to create admin: " . $insert->error . "</div>";
    }
    $insert->close();
    $conn->close();
    exit;
}

$conn->begin_transaction();
try {
    // Update admin_users first
    $update_admin_users = $conn->prepare("UPDATE admin_users SET student_id = ? WHERE student_id = ?");
    $update_admin_users->bind_param("ss", $admin_id, $old_id);
    if (!$update_admin_users->execute()) {
        throw new Exception("Failed to update admin_users: " . $update_admin_users->error);
    }
    $update_admin_users->close();

    // Update users table
    $update_users = $conn->prepare("UPDATE users SET student_id = ?, password = ?, email = ?, full_name = ?, user_type = 'admin' WHERE student_id = ?");
    $update_users->bind_param("sssss", $admin_id, $hashed_password, $admin_email, $admin_name, $old_id);
    if (!$update_users->execute()) {
        throw new Exception("Failed to update users: " . $update_users->error);
    }
    $update_users->close();

    $conn->commit();
    echo "<div style='color: green;'>Admin account updated. ID: admin, Password: admin</div>";
} catch (Exception $e) {
    $conn->rollback();
    echo "<div style='color: red;'>Error: " . $e->getMessage() . "</div>";
}
$conn->close();
?> 