<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
$servername = "localhost:3307";
$username = "root";
$password = "";
$dbname = "library_management";

// Create connection with error handling
try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset to ensure proper encoding
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Create book_schedules table if it doesn't exist
$create_schedules_table = "
CREATE TABLE IF NOT EXISTS book_schedules (
    schedule_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    book_id INT NOT NULL,
    scheduled_date DATE NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(student_id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE
)";

try {
    if (!$conn->query($create_schedules_table)) {
        throw new Exception("Error creating book_schedules table: " . $conn->error);
    }
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

// Create borrowed_books table if it doesn't exist
$create_borrowed_books_table = "
CREATE TABLE IF NOT EXISTS borrowed_books (
    record_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    book_id INT NOT NULL,
    borrow_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    due_date DATETIME NOT NULL,
    return_date DATETIME DEFAULT NULL,
    admin_confirmed_return TINYINT(1) DEFAULT 0,
    status ENUM('borrowed', 'returned', 'completed') DEFAULT 'borrowed',
    purpose TEXT,
    FOREIGN KEY (student_id) REFERENCES users(student_id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE
)";

try {
    if (!$conn->query($create_borrowed_books_table)) {
        throw new Exception("Error creating borrowed_books table: " . $conn->error);
    }
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

// Create ebooks table if it doesn't exist
$create_ebooks_table = "CREATE TABLE IF NOT EXISTS ebooks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    category VARCHAR(100) DEFAULT 'General',
    description TEXT,
    cover_image VARCHAR(255) DEFAULT 'default_cover.jpg',
    file_path VARCHAR(255) NOT NULL,
    stock INT DEFAULT 10,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

try {
    if (!$conn->query($create_ebooks_table)) {
        throw new Exception("Error creating ebooks table: " . $conn->error);
    }

    // Check if table is empty
    $check_ebooks = $conn->query("SELECT COUNT(*) as count FROM ebooks");
    if (!$check_ebooks) {
        throw new Exception("Error checking ebooks table: " . $conn->error);
    }
    
    $ebook_count = $check_ebooks->fetch_assoc()['count'];

    if ($ebook_count == 0) {
        $sample_ebooks = [
            [
                'title' => 'Introduction to Programming',
                'author' => 'John Doe',
                'category' => 'Computer Science',
                'description' => 'A comprehensive guide to programming fundamentals.',
                'cover_image' => 'default_cover.jpg',
                'file_path' => 'sample1.pdf'
            ],
            [
                'title' => 'Web Development Basics',
                'author' => 'Jane Smith',
                'category' => 'Web Development',
                'description' => 'Learn the basics of web development including HTML, CSS, and JavaScript.',
                'cover_image' => 'default_cover.jpg',
                'file_path' => 'sample2.pdf'
            ],
            [
                'title' => 'Database Design',
                'author' => 'Mike Johnson',
                'category' => 'Database',
                'description' => 'Master the art of database design and management.',
                'cover_image' => 'default_cover.jpg',
                'file_path' => 'sample3.pdf'
            ]
        ];

        $stmt = $conn->prepare("INSERT INTO ebooks (title, author, category, description, cover_image, file_path) VALUES (?, ?, ?, ?, ?, ?)");
        
        if (!$stmt) {
            throw new Exception("Error preparing statement: " . $conn->error);
        }

        foreach ($sample_ebooks as $ebook) {
            if (!$stmt->bind_param("ssssss", 
                $ebook['title'],
                $ebook['author'],
                $ebook['category'],
                $ebook['description'],
                $ebook['cover_image'],
                $ebook['file_path']
            )) {
                throw new Exception("Error binding parameters: " . $stmt->error);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Error executing statement: " . $stmt->error);
            }
        }
        $stmt->close();
    }
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

// Create ebook_downloads table if it doesn't exist
$create_ebook_downloads_table = "CREATE TABLE IF NOT EXISTS ebook_downloads (
    download_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    ebook_id INT NOT NULL,
    download_date DATETIME NOT NULL,
    FOREIGN KEY (student_id) REFERENCES users(student_id),
    FOREIGN KEY (ebook_id) REFERENCES ebooks(id)
)";

try {
    if (!$conn->query($create_ebook_downloads_table)) {
        throw new Exception("Error creating ebook_downloads table: " . $conn->error);
    }
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}
?>