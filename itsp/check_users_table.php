<?php
include 'config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Connection Test</h2>";
if ($conn) {
    echo "<div style='color: green;'>Database connection successful</div>";
} else {
    echo "<div style='color: red;'>Database connection failed</div>";
    die();
}

// Check users table structure
echo "<h2>Users Table Structure</h2>";
$result = $conn->query("DESCRIBE users");
if ($result) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div style='color: red;'>Error getting table structure: " . $conn->error . "</div>";
}

// Check users table content
echo "<h2>Users Table Content</h2>";
$result = $conn->query("SELECT * FROM users");
if ($result) {
    if ($result->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr>";
        // Get column names
        $fields = $result->fetch_fields();
        foreach ($fields as $field) {
            echo "<th>" . htmlspecialchars($field->name) . "</th>";
        }
        echo "</tr>";
        
        // Reset result pointer
        $result->data_seek(0);
        
        // Get data
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div style='color: orange;'>No users found in the database</div>";
    }
} else {
    echo "<div style='color: red;'>Error getting table content: " . $conn->error . "</div>";
}

// Check specifically for admin user
echo "<h2>Admin User Check</h2>";
$stmt = $conn->prepare("SELECT * FROM users WHERE student_id = ? AND user_type = 'admin'");
if ($stmt) {
    $admin_id = 'admin';
    $stmt->bind_param("s", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "<div style='color: green;'>Admin user found:</div>";
        $admin = $result->fetch_assoc();
        echo "<pre>";
        print_r($admin);
        echo "</pre>";
    } else {
        echo "<div style='color: red;'>Admin user not found</div>";
    }
    $stmt->close();
} else {
    echo "<div style='color: red;'>Error preparing admin check statement: " . $conn->error . "</div>";
}

$conn->close();
?> 