<?php
require 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Default admin credentials
$student_id = 'admin';
$password = 'admin123'; // Changed to a more secure default password
$email = 'admin@library.com';
$full_name = 'System Administrator';

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// First, check if admin already exists
$check_sql = "SELECT student_id FROM users WHERE student_id = ? OR email = ?";
$check_stmt = $conn->prepare($check_sql);

if (!$check_stmt) {
    die("Error preparing check statement: " . $conn->error);
}

$check_stmt->bind_param("ss", $student_id, $email);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows > 0) {
    // Admin exists, update the password
    $update_sql = "UPDATE users SET password = ?, full_name = ? WHERE student_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    
    if (!$update_stmt) {
        die("Error preparing update statement: " . $conn->error);
    }
    
    $update_stmt->bind_param("sss", $hashed_password, $full_name, $student_id);
    
    if ($update_stmt->execute()) {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; margin: 10px; border-radius: 5px;'>";
        echo "<h3>Admin account updated successfully!</h3>";
        echo "<p><strong>Login Details:</strong></p>";
        echo "<ul>";
        echo "<li>Admin ID: " . htmlspecialchars($student_id) . "</li>";
        echo "<li>Password: " . htmlspecialchars($password) . "</li>";
        echo "</ul>";
        echo "<p><a href='admin_login.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Admin Login</a></p>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; margin: 10px; border-radius: 5px;'>";
        echo "Error updating admin account: " . $update_stmt->error;
        echo "</div>";
    }
    
    $update_stmt->close();
} else {
    // Admin doesn't exist, create new admin
    $insert_sql = "INSERT INTO users (student_id, password, email, full_name, user_type, profile_pic) 
                   VALUES (?, ?, ?, ?, 'admin', 'assets/images/profile_pictures/default.png')";
    $insert_stmt = $conn->prepare($insert_sql);
    
    if (!$insert_stmt) {
        die("Error preparing insert statement: " . $conn->error);
    }
    
    $insert_stmt->bind_param("ssss", $student_id, $hashed_password, $email, $full_name);
    
    if ($insert_stmt->execute()) {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; margin: 10px; border-radius: 5px;'>";
        echo "<h3>Admin account created successfully!</h3>";
        echo "<p><strong>Login Details:</strong></p>";
        echo "<ul>";
        echo "<li>Admin ID: " . htmlspecialchars($student_id) . "</li>";
        echo "<li>Password: " . htmlspecialchars($password) . "</li>";
        echo "</ul>";
        echo "<p><a href='admin_login.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Admin Login</a></p>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; margin: 10px; border-radius: 5px;'>";
        echo "Error creating admin account: " . $insert_stmt->error;
        echo "</div>";
    }
    
    $insert_stmt->close();
}

$check_stmt->close();

// Verify the admin account
$verify_sql = "SELECT * FROM users WHERE student_id = ? AND user_type = 'admin'";
$verify_stmt = $conn->prepare($verify_sql);
$verify_stmt->bind_param("s", $student_id);
$verify_stmt->execute();
$verify_result = $verify_stmt->get_result();

if ($verify_result->num_rows > 0) {
    $admin = $verify_result->fetch_assoc();
    echo "<div style='background: #e2e3e5; color: #383d41; padding: 15px; margin: 10px; border-radius: 5px;'>";
    echo "<h4>Account Verification:</h4>";
    echo "<p>Admin account details:</p>";
    echo "<ul>";
    echo "<li>Student ID: " . htmlspecialchars($admin['student_id']) . "</li>";
    echo "<li>Email: " . htmlspecialchars($admin['email']) . "</li>";
    echo "<li>Full Name: " . htmlspecialchars($admin['full_name']) . "</li>";
    echo "<li>User Type: " . htmlspecialchars($admin['user_type']) . "</li>";
    echo "</ul>";
    echo "</div>";
}

$verify_stmt->close();
?> 