<?php
include 'config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

$new_id = 'admin';
$old_id = 1; // The current admin's student_id

$conn->begin_transaction();

try {
    // Update admin_users first
    $update_admin_users = $conn->prepare("UPDATE admin_users SET student_id = ? WHERE student_id = ?");
    $update_admin_users->bind_param("si", $new_id, $old_id);
    if (!$update_admin_users->execute()) {
        throw new Exception("Failed to update admin_users: " . $update_admin_users->error);
    }
    $update_admin_users->close();

    // Update users table
    $update_users = $conn->prepare("UPDATE users SET student_id = ? WHERE student_id = ?");
    $update_users->bind_param("si", $new_id, $old_id);
    if (!$update_users->execute()) {
        throw new Exception("Failed to update users: " . $update_users->error);
    }
    $update_users->close();

    $conn->commit();
    echo "<div style='color: green;'>Admin student_id updated to 'admin' in both users and admin_users tables. You can now log in using Admin ID: admin</div>";
} catch (Exception $e) {
    $conn->rollback();
    echo "<div style='color: red;'>Error: " . $e->getMessage() . "</div>";
}

$conn->close();
?> 