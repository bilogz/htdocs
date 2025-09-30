<?php
// Set up error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once 'config.php';

// Check if connection is successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h1>Database Debug Information</h1>";

// Get list of tables
$tables_query = "SHOW TABLES";
$tables_result = $conn->query($tables_query);

if (!$tables_result) {
    die("Error fetching tables: " . $conn->error);
}

echo "<h2>Database Tables</h2>";
echo "<ul>";
$tables = [];

while ($table = $tables_result->fetch_row()) {
    $tables[] = $table[0];
    echo "<li>{$table[0]}</li>";
}
echo "</ul>";

// Check key tables
$key_tables = [
    'books', 
    'borrowed_books', 
    'book_schedules', 
    'ebooks', 
    'users', 
    'notifications'
];

foreach ($key_tables as $table) {
    if (in_array($table, $tables)) {
        echo "<h2>Table: {$table}</h2>";
        
        // Show table structure
        $structure_query = "DESCRIBE {$table}";
        $structure_result = $conn->query($structure_query);
        
        if (!$structure_result) {
            echo "<p>Error fetching structure: " . $conn->error . "</p>";
            continue;
        }
        
        echo "<h3>Structure</h3>";
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        while ($row = $structure_result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['Field']}</td>";
            echo "<td>{$row['Type']}</td>";
            echo "<td>{$row['Null']}</td>";
            echo "<td>{$row['Key']}</td>";
            echo "<td>{$row['Default']}</td>";
            echo "<td>{$row['Extra']}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // Show sample data (first 5 rows)
        $data_query = "SELECT * FROM {$table} LIMIT 5";
        $data_result = $conn->query($data_query);
        
        if (!$data_result) {
            echo "<p>Error fetching data: " . $conn->error . "</p>";
            continue;
        }
        
        if ($data_result->num_rows > 0) {
            echo "<h3>Sample Data (up to 5 rows)</h3>";
            echo "<table border='1'>";
            
            // Table header
            $header_printed = false;
            
            while ($row = $data_result->fetch_assoc()) {
                if (!$header_printed) {
                    echo "<tr>";
                    foreach ($row as $key => $value) {
                        echo "<th>{$key}</th>";
                    }
                    echo "</tr>";
                    $header_printed = true;
                }
                
                echo "<tr>";
                foreach ($row as $value) {
                    echo "<td>" . (is_null($value) ? "NULL" : htmlspecialchars($value)) . "</td>";
                }
                echo "</tr>";
            }
            
            echo "</table>";
            
            // Count records
            $count_query = "SELECT COUNT(*) as count FROM {$table}";
            $count_result = $conn->query($count_query);
            $count = $count_result->fetch_assoc()['count'];
            
            echo "<p>Total records: {$count}</p>";
        } else {
            echo "<p>No data in this table.</p>";
        }
    } else {
        echo "<h2>Table: {$table} - NOT FOUND</h2>";
    }
}

// Check specific pending requests
echo "<h2>Pending Borrow Requests</h2>";
$pending_query = "SELECT bs.*, b.title as book_title, b.cover_image, u.full_name as student_name, u.email as student_email
    FROM book_schedules bs
    JOIN books b ON bs.book_id = b.book_id
    JOIN users u ON bs.student_id = u.student_id
    WHERE bs.status = 'pending'
    ORDER BY bs.schedule_date DESC";

$pending_result = $conn->query($pending_query);

if (!$pending_result) {
    echo "<p>Error querying pending requests: " . $conn->error . "</p>";
} else {
    if ($pending_result->num_rows > 0) {
        echo "<table border='1'>";
        
        // Table header
        $header_printed = false;
        
        while ($row = $pending_result->fetch_assoc()) {
            if (!$header_printed) {
                echo "<tr>";
                foreach ($row as $key => $value) {
                    echo "<th>{$key}</th>";
                }
                echo "</tr>";
                $header_printed = true;
            }
            
            echo "<tr>";
            foreach ($row as $key => $value) {
                echo "<td>" . (is_null($value) ? "NULL" : htmlspecialchars($value)) . "</td>";
            }
            echo "</tr>";
        }
        
        echo "</table>";
        echo "<p>Total pending requests: {$pending_result->num_rows}</p>";
    } else {
        echo "<p>No pending borrow requests found.</p>";
    }
}

// Close the connection
$conn->close();
?> 