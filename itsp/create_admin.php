<?php
include 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Default admin credentials
$admin_id = 'admin';
$admin_password = 'admin123'; // Default password
$admin_email = 'admin@library.com';
$admin_name = 'System Administrator';

// Hash the password
$hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);

// First, delete any existing admin user to avoid conflicts
$delete_sql = "DELETE FROM users WHERE student_id = ? OR email = ?";
$delete_stmt = $conn->prepare($delete_sql);

if ($delete_stmt === false) {
    die("Error preparing delete statement: " . $conn->error);
}

$delete_stmt->bind_param("ss", $admin_id, $admin_email);
$delete_stmt->execute();
$delete_stmt->close();

// Create new admin account
$insert_sql = "INSERT INTO users (student_id, password, email, full_name, user_type, profile_pic) 
               VALUES (?, ?, ?, ?, 'admin', 'assets/images/profile_pictures/default.png')";
$insert_stmt = $conn->prepare($insert_sql);

if ($insert_stmt === false) {
    die("Error preparing insert statement: " . $conn->error);
}

$insert_stmt->bind_param("ssss", $admin_id, $hashed_password, $admin_email, $admin_name);

if ($insert_stmt->execute()) {
    // Verify the admin account was created
    $verify_sql = "SELECT * FROM users WHERE student_id = ? AND user_type = 'admin'";
    $verify_stmt = $conn->prepare($verify_sql);
    
    if ($verify_stmt === false) {
        die("Error preparing verify statement: " . $conn->error);
    }
    
    $verify_stmt->bind_param("s", $admin_id);
    $verify_stmt->execute();
    $result = $verify_stmt->get_result();
    
    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();
        echo "<div style='background-color: #d4edda; color: #155724; padding: 15px; margin: 20px; border-radius: 5px;'>";
        echo "<h3>Admin Account Created Successfully</h3>";
        echo "<p>Admin account has been created with the following credentials:</p>";
        echo "<ul>";
        echo "<li><strong>Admin ID:</strong> " . htmlspecialchars($admin_id) . "</li>";
        echo "<li><strong>Password:</strong> " . htmlspecialchars($admin_password) . "</li>";
        echo "<li><strong>Email:</strong> " . htmlspecialchars($admin_email) . "</li>";
        echo "<li><strong>Name:</strong> " . htmlspecialchars($admin_name) . "</li>";
        echo "<li><strong>User Type:</strong> " . htmlspecialchars($admin['user_type']) . "</li>";
        echo "</ul>";
        echo "<p><strong>Important:</strong> Please use these exact credentials to log in:</p>";
        echo "<ul>";
        echo "<li>Select 'Admin' from the login mode dropdown</li>";
        echo "<li>Enter Admin ID: <strong>admin</strong></li>";
        echo "<li>Enter Password: <strong>admin123</strong></li>";
        echo "</ul>";
        echo "<p><a href='login.php' style='color: #155724; text-decoration: underline;'>Go to Login Page</a></p>";
        echo "</div>";
    } else {
        echo "<div style='background-color: #f8d7da; color: #721c24; padding: 15px; margin: 20px; border-radius: 5px;'>";
        echo "<h3>Error: Admin Account Not Found After Creation</h3>";
        echo "<p>Please check your database connection and try again.</p>";
        echo "</div>";
    }
    
    $verify_stmt->close();
} else {
    echo "<div style='background-color: #f8d7da; color: #721c24; padding: 15px; margin: 20px; border-radius: 5px;'>";
    echo "<h3>Error Creating Admin Account</h3>";
    echo "<p>Error: " . $insert_stmt->error . "</p>";
    echo "</div>";
}

$insert_stmt->close();
$conn->close();
?> 