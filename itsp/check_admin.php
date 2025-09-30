<?php
require 'config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Connection Test</h2>";

// Test database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    echo "<p style='color: green;'>✓ Database connection successful</p>";
}

// Check if users table exists
$table_check = $conn->query("SHOW TABLES LIKE 'users'");
if ($table_check->num_rows == 0) {
    die("Error: 'users' table does not exist!");
} else {
    echo "<p style='color: green;'>✓ 'users' table exists</p>";
}

// Check table structure
$columns = $conn->query("SHOW COLUMNS FROM users");
echo "<h3>Table Structure:</h3>";
echo "<ul>";
while ($column = $columns->fetch_assoc()) {
    echo "<li>" . $column['Field'] . " - " . $column['Type'] . "</li>";
}
echo "</ul>";

// Check for admin user
$sql = "SELECT * FROM users WHERE student_id = 'admin' AND user_type = 'admin'";
$result = $conn->query($sql);

echo "<h3>Admin User Check:</h3>";
if ($result->num_rows > 0) {
    $admin = $result->fetch_assoc();
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
    echo "<p style='color: green;'>✓ Admin user found!</p>";
    echo "<ul>";
    echo "<li>Student ID: " . htmlspecialchars($admin['student_id']) . "</li>";
    echo "<li>Email: " . htmlspecialchars($admin['email']) . "</li>";
    echo "<li>Full Name: " . htmlspecialchars($admin['full_name']) . "</li>";
    echo "<li>User Type: " . htmlspecialchars($admin['user_type']) . "</li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "<p style='color: red;'>✗ Admin user not found!</p>";
    echo "<p>Please run <a href='add_admin.php'>add_admin.php</a> to create the admin user.</p>";
    echo "</div>";
}

// Check all users in the database
echo "<h3>All Users in Database:</h3>";
$all_users = $conn->query("SELECT student_id, email, full_name, user_type FROM users");
if ($all_users->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Student ID</th><th>Email</th><th>Full Name</th><th>User Type</th></tr>";
    while ($user = $all_users->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($user['student_id']) . "</td>";
        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
        echo "<td>" . htmlspecialchars($user['full_name']) . "</td>";
        echo "<td>" . htmlspecialchars($user['user_type']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No users found in the database.</p>";
}
?> 