<?php
require 'config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Admin Account Fix</h2>";

// 1. Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "<p style='color: green;'>✓ Database connection successful</p>";

// 2. Check if users table exists
$table_check = $conn->query("SHOW TABLES LIKE 'users'");
if ($table_check->num_rows == 0) {
    // Create users table if it doesn't exist
    $create_table = "CREATE TABLE IF NOT EXISTS users (
        student_id VARCHAR(50) PRIMARY KEY,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        user_type ENUM('student', 'admin') NOT NULL DEFAULT 'student',
        profile_pic VARCHAR(255) DEFAULT 'assets/images/profile_pictures/default.png',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($create_table)) {
        echo "<p style='color: green;'>✓ Users table created successfully</p>";
    } else {
        die("Error creating users table: " . $conn->error);
    }
} else {
    echo "<p style='color: green;'>✓ Users table exists</p>";
}

// 3. Check for admin user
$check_admin = $conn->query("SELECT * FROM users WHERE student_id = 'admin'");
if ($check_admin->num_rows == 0) {
    // Create admin user
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $insert_admin = "INSERT INTO users (student_id, password, email, full_name, user_type) 
                     VALUES ('admin', ?, 'admin@library.com', 'System Administrator', 'admin')";
    
    $stmt = $conn->prepare($insert_admin);
    if (!$stmt) {
        die("Error preparing statement: " . $conn->error);
    }
    
    $stmt->bind_param("s", $admin_password);
    
    if ($stmt->execute()) {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; margin: 10px; border-radius: 5px;'>";
        echo "<h3>Admin account created successfully!</h3>";
        echo "<p><strong>Login Details:</strong></p>";
        echo "<ul>";
        echo "<li>Admin ID: admin</li>";
        echo "<li>Password: admin123</li>";
        echo "</ul>";
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; margin: 10px; border-radius: 5px;'>";
        echo "Error creating admin account: " . $stmt->error;
        echo "</div>";
    }
    $stmt->close();
} else {
    // Update admin password
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $update_admin = "UPDATE users SET password = ? WHERE student_id = 'admin'";
    
    $stmt = $conn->prepare($update_admin);
    if (!$stmt) {
        die("Error preparing statement: " . $conn->error);
    }
    
    $stmt->bind_param("s", $admin_password);
    
    if ($stmt->execute()) {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; margin: 10px; border-radius: 5px;'>";
        echo "<h3>Admin account updated successfully!</h3>";
        echo "<p><strong>Login Details:</strong></p>";
        echo "<ul>";
        echo "<li>Admin ID: admin</li>";
        echo "<li>Password: admin123</li>";
        echo "</ul>";
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; margin: 10px; border-radius: 5px;'>";
        echo "Error updating admin account: " . $stmt->error;
        echo "</div>";
    }
    $stmt->close();
}

// 4. Verify admin account
$verify = $conn->query("SELECT * FROM users WHERE student_id = 'admin' AND user_type = 'admin'");
if ($verify->num_rows > 0) {
    $admin = $verify->fetch_assoc();
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
} else {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; margin: 10px; border-radius: 5px;'>";
    echo "Error: Admin account verification failed!";
    echo "</div>";
}
?> 