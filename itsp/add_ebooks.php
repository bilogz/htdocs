<?php
session_start();
require_once 'config.php';

// Check if user is admin
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    echo "Unauthorized access. Please login as admin.";
    exit();
}

// Sample eBooks data
$ebooks = [
    [
        'title' => 'Introduction to Computer Science',
        'author' => 'David Johnson',
        'category' => 'Education',
        'description' => 'A comprehensive guide to the fundamentals of computer science for beginners.',
        'cover_image' => 'cs_intro.jpg',
        'file_path' => 'ebooks/cs_intro.pdf',
        'price' => 0.00,
        'status' => 'Available',
        'download_status' => 'Enabled'
    ],
    [
        'title' => 'Web Development Bootcamp',
        'author' => 'Sarah Miller',
        'category' => 'Technology',
        'description' => 'Learn HTML, CSS, JavaScript, and modern web development frameworks in this comprehensive guide.',
        'cover_image' => 'webdev.jpg',
        'file_path' => 'ebooks/webdev.pdf',
        'price' => 5.99,
        'status' => 'Available',
        'download_status' => 'Enabled'
    ],
    [
        'title' => 'Data Structures and Algorithms',
        'author' => 'Robert Chen',
        'category' => 'Computer Science',
        'description' => 'An in-depth look at fundamental data structures and algorithms for computer scientists and programmers.',
        'cover_image' => 'dsa.jpg',
        'file_path' => 'ebooks/dsa.pdf',
        'price' => 9.99,
        'status' => 'Available',
        'download_status' => 'Enabled'
    ],
    [
        'title' => 'Artificial Intelligence: A Modern Approach',
        'author' => 'Michael Davis',
        'category' => 'Computer Science',
        'description' => 'Comprehensive guide to modern AI techniques, applications, and ethical considerations.',
        'cover_image' => 'ai.jpg',
        'file_path' => 'ebooks/ai.pdf',
        'price' => 12.99,
        'status' => 'Available',
        'download_status' => 'Enabled'
    ],
    [
        'title' => 'Python Programming for Beginners',
        'author' => 'Lisa Thompson',
        'category' => 'Programming',
        'description' => 'A step-by-step guide to Python programming language for complete beginners.',
        'cover_image' => 'python.jpg',
        'file_path' => 'ebooks/python.pdf',
        'price' => 0.00,
        'status' => 'Available',
        'download_status' => 'Enabled'
    ],
    [
        'title' => 'Database Design and SQL',
        'author' => 'James Wilson',
        'category' => 'Database',
        'description' => 'Learn the principles of database design and how to write effective SQL queries.',
        'cover_image' => 'sql.jpg',
        'file_path' => 'ebooks/sql.pdf',
        'price' => 7.99,
        'status' => 'Available',
        'download_status' => 'Enabled'
    ],
    [
        'title' => 'Machine Learning Fundamentals',
        'author' => 'Emily Zhang',
        'category' => 'Data Science',
        'description' => 'Introduction to machine learning algorithms, techniques, and practical applications.',
        'cover_image' => 'ml.jpg',
        'file_path' => 'ebooks/ml.pdf',
        'price' => 14.99,
        'status' => 'Available',
        'download_status' => 'Enabled'
    ],
    [
        'title' => 'Cybersecurity Essentials',
        'author' => 'Thomas Brown',
        'category' => 'Security',
        'description' => 'A comprehensive guide to cybersecurity principles, vulnerabilities, and protection strategies.',
        'cover_image' => 'security.jpg',
        'file_path' => 'ebooks/security.pdf',
        'price' => 11.99,
        'status' => 'Available',
        'download_status' => 'Enabled'
    ],
    [
        'title' => 'Cloud Computing Architecture',
        'author' => 'Alan Richards',
        'category' => 'Cloud Computing',
        'description' => 'Explore cloud infrastructure, services, and deployment models with practical examples.',
        'cover_image' => 'cloud.jpg',
        'file_path' => 'ebooks/cloud.pdf',
        'price' => 9.99,
        'status' => 'Available',
        'download_status' => 'Enabled'
    ],
    [
        'title' => 'Mobile App Development',
        'author' => 'Jennifer Lee',
        'category' => 'Programming',
        'description' => 'A guide to developing mobile applications for iOS and Android platforms.',
        'cover_image' => 'mobile.jpg',
        'file_path' => 'ebooks/mobile.pdf',
        'price' => 8.99,
        'status' => 'Available',
        'download_status' => 'Enabled'
    ]
];

// Function to create dummy cover images
function create_dummy_cover($filename, $title, $author) {
    // Create directory if it doesn't exist
    $dir = 'assets/images/';
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
    
    // Check if file already exists
    if (file_exists($dir . $filename)) {
        return;
    }
    
    // Create a simple image with text
    $image = imagecreate(400, 600);
    $bg_color = imagecolorallocate($image, rand(100, 200), rand(100, 200), rand(100, 200));
    $text_color = imagecolorallocate($image, 0, 0, 0);
    
    // Add title and author text
    imagettftext($image, 20, 0, 20, 300, $text_color, 'C:\Windows\Fonts\arial.ttf', wordwrap($title, 20, "\n"));
    imagettftext($image, 16, 0, 20, 400, $text_color, 'C:\Windows\Fonts\arial.ttf', "by " . $author);
    
    // Save the image
    imagejpeg($image, $dir . $filename, 90);
    imagedestroy($image);
}

// Function to create dummy PDF files
function create_dummy_pdf($filepath, $title, $author) {
    // Create directory if it doesn't exist
    $dir = dirname($filepath);
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
    
    // Check if file already exists
    if (file_exists($filepath)) {
        return;
    }
    
    // Create a simple text file with PDF extension (for demonstration)
    $content = "Title: {$title}\nAuthor: {$author}\n\nThis is a sample ebook content for demonstration purposes.\n";
    $content .= "The actual content would be much longer and formatted as a proper PDF document.\n";
    $content .= "This is just a placeholder file created by the sample data script.\n";
    
    file_put_contents($filepath, $content);
}

// Connect to the database
$success_count = 0;
$error_messages = [];

foreach ($ebooks as $ebook) {
    try {
        // Generate cover image if it doesn't exist
        create_dummy_cover($ebook['cover_image'], $ebook['title'], $ebook['author']);
        
        // Generate dummy PDF file if it doesn't exist
        create_dummy_pdf($ebook['file_path'], $ebook['title'], $ebook['author']);
        
        // Insert ebook into database
        $stmt = $conn->prepare("INSERT INTO ebooks (title, author, category, description, cover_image, file_path, price, status, download_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssdss", 
            $ebook['title'],
            $ebook['author'],
            $ebook['category'],
            $ebook['description'],
            $ebook['cover_image'],
            $ebook['file_path'],
            $ebook['price'],
            $ebook['status'],
            $ebook['download_status']
        );
        
        if ($stmt->execute()) {
            $success_count++;
        } else {
            $error_messages[] = "Error adding {$ebook['title']}: " . $stmt->error;
        }
        
        $stmt->close();
    } catch (Exception $e) {
        $error_messages[] = "Exception for {$ebook['title']}: " . $e->getMessage();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Sample eBooks</title>
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
            background-color: #1f2122;
            color: #fff;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #23272b;
            padding: 20px;
            border-radius: 10px;
        }
        .success {
            color: #4CAF50;
        }
        .error {
            color: #f44336;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Add Sample eBooks</h1>
        
        <div class="result">
            <h3>Results</h3>
            <p class="success">Successfully added <?php echo $success_count; ?> eBooks.</p>
            
            <?php if (!empty($error_messages)): ?>
                <div class="errors">
                    <h4>Errors:</h4>
                    <ul>
                        <?php foreach ($error_messages as $error): ?>
                            <li class="error"><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
        
        <a href="admin_page.php" class="btn btn-primary">Return to Admin Page</a>
    </div>
</body>
</html> 