<?php
require_once 'config.php';

// Set up error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Open file for writing
$log_file = fopen('schedule_debug.txt', 'w');

function log_message($message) {
    global $log_file;
    fwrite($log_file, $message . "\n");
}

log_message("Book Schedules Table Check - " . date('Y-m-d H:i:s'));

// Check if table exists
$check_table = $conn->query("SHOW TABLES LIKE 'book_schedules'");
if ($check_table->num_rows == 0) {
    log_message("Table book_schedules does not exist!");
    fclose($log_file);
    echo "Table does not exist. See schedule_debug.txt for details.";
    exit;
}

log_message("Table exists.");

// Check structure
$structure = $conn->query("DESCRIBE book_schedules");
if (!$structure) {
    log_message("Error checking structure: " . $conn->error);
} else {
    log_message("\nTABLE STRUCTURE:");
    log_message(str_pad("Field", 20) . str_pad("Type", 20) . str_pad("Null", 10) . str_pad("Key", 10) . str_pad("Default", 15) . "Extra");
    log_message(str_repeat("-", 100));
    
    while ($row = $structure->fetch_assoc()) {
        log_message(
            str_pad($row['Field'], 20) . 
            str_pad($row['Type'], 20) . 
            str_pad($row['Null'], 10) . 
            str_pad($row['Key'], 10) . 
            str_pad($row['Default'] ?? "NULL", 15) . 
            $row['Extra']
        );
    }
}

// Count records
$count = $conn->query("SELECT COUNT(*) as total FROM book_schedules");
$count_row = $count->fetch_assoc();
log_message("\nTotal records: " . $count_row['total']);

// Check for pending requests
$pending = $conn->query("SELECT COUNT(*) as pending FROM book_schedules WHERE status = 'pending'");
$pending_row = $pending->fetch_assoc();
log_message("Pending requests: " . $pending_row['pending']);

// Show sample data
log_message("\nSAMPLE DATA (up to 10 rows):");
$data = $conn->query("SELECT * FROM book_schedules LIMIT 10");
if (!$data || $data->num_rows == 0) {
    log_message("No data in the table or error: " . $conn->error);
} else {
    // Get column names
    $row = $data->fetch_assoc();
    $columns = array_keys($row);
    
    // Create header
    $header = "";
    foreach ($columns as $column) {
        $header .= str_pad($column, 20);
    }
    log_message($header);
    log_message(str_repeat("-", 20 * count($columns)));
    
    // Reset and print data
    $data->data_seek(0);
    while ($row = $data->fetch_assoc()) {
        $line = "";
        foreach ($row as $value) {
            $line .= str_pad(substr($value ?? "NULL", 0, 19), 20);
        }
        log_message($line);
    }
}

// Check the WHERE clause for pending status
log_message("\nCHECKING STATUS VALUES:");
$status_values = $conn->query("SELECT DISTINCT status FROM book_schedules");
log_message("Status values in the table:");
while ($status_row = $status_values->fetch_assoc()) {
    log_message("- " . ($status_row['status'] ?? "NULL"));
}

// Check all statuses with counts
log_message("\nSTATUS COUNTS:");
$status_counts = $conn->query("SELECT status, COUNT(*) as count FROM book_schedules GROUP BY status");
while ($status_row = $status_counts->fetch_assoc()) {
    log_message("- " . ($status_row['status'] ?? "NULL") . ": " . $status_row['count']);
}

// Check for any issues with joining the tables
log_message("\nCHECKING JOIN CONDITION:");
$join_check = $conn->query("
    SELECT 
        bs.schedule_id,
        bs.book_id,
        bs.student_id,
        bs.status,
        (SELECT COUNT(*) FROM books b WHERE b.book_id = bs.book_id) as book_exists,
        (SELECT COUNT(*) FROM users u WHERE u.student_id = bs.student_id) as user_exists
    FROM 
        book_schedules bs
    LIMIT 10
");

if (!$join_check) {
    log_message("Error checking join: " . $conn->error);
} else {
    log_message(str_pad("schedule_id", 15) . str_pad("book_id", 15) . str_pad("student_id", 15) . str_pad("status", 15) . str_pad("book_exists", 15) . "user_exists");
    log_message(str_repeat("-", 90));
    
    while ($row = $join_check->fetch_assoc()) {
        log_message(
            str_pad($row['schedule_id'], 15) . 
            str_pad($row['book_id'], 15) . 
            str_pad($row['student_id'], 15) . 
            str_pad($row['status'], 15) . 
            str_pad($row['book_exists'], 15) . 
            $row['user_exists']
        );
    }
}

// Check if there are actually any pending requests that would match the query
log_message("\nFINDING PENDING REQUESTS:");
$pending_check = $conn->query("
    SELECT 
        bs.schedule_id,
        bs.book_id,
        bs.student_id,
        bs.status,
        b.title as book_title,
        u.full_name as student_name
    FROM 
        book_schedules bs
    LEFT JOIN 
        books b ON bs.book_id = b.book_id
    LEFT JOIN 
        users u ON bs.student_id = u.student_id
    WHERE 
        bs.status = 'pending'
    LIMIT 10
");

if (!$pending_check) {
    log_message("Error checking pending requests: " . $conn->error);
} else if ($pending_check->num_rows == 0) {
    log_message("No pending requests found. This explains why the main page doesn't show any.");
} else {
    log_message("Found " . $pending_check->num_rows . " pending request(s):");
    log_message(str_pad("schedule_id", 15) . str_pad("book_id", 15) . str_pad("student_id", 15) . str_pad("status", 15) . str_pad("book_title", 25) . "student_name");
    log_message(str_repeat("-", 100));
    
    while ($row = $pending_check->fetch_assoc()) {
        log_message(
            str_pad($row['schedule_id'], 15) . 
            str_pad($row['book_id'], 15) . 
            str_pad($row['student_id'], 15) . 
            str_pad($row['status'], 15) . 
            str_pad(substr($row['book_title'] ?? "NULL", 0, 24), 25) . 
            ($row['student_name'] ?? "NULL")
        );
    }
}

// Check if schedule_date column exists and has correct format
log_message("\nCHECKING SCHEDULE_DATE COLUMN:");
$date_check = $conn->query("SHOW COLUMNS FROM book_schedules LIKE 'schedule_date'");
if ($date_check->num_rows == 0) {
    log_message("schedule_date column does not exist in the book_schedules table!");
    
    // Check if there's a similarly named column
    $columns = $conn->query("SHOW COLUMNS FROM book_schedules");
    log_message("Available columns:");
    while ($col = $columns->fetch_assoc()) {
        log_message("- " . $col['Field']);
    }
} else {
    $date_col = $date_check->fetch_assoc();
    log_message("schedule_date column exists with type: " . $date_col['Type']);
    
    // Check actual date values
    $dates = $conn->query("SELECT schedule_id, schedule_date FROM book_schedules LIMIT 10");
    log_message("Sample date values:");
    while ($date_row = $dates->fetch_assoc()) {
        log_message("ID: " . $date_row['schedule_id'] . ", Date: " . ($date_row['schedule_date'] ?? "NULL"));
    }
}

// Close file
fclose($log_file);

// Output confirmation to browser
echo "Diagnostic complete. Check schedule_debug.txt for results.";
?> 