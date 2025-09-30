<?php
session_start();
require_once 'config.php';

// Check if user is admin
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    echo "Unauthorized access. Please login as admin.";
    exit();
}

// Sample books data
$books = [
    [
        'title' => 'To Kill a Mockingbird',
        'author' => 'Harper Lee',
        'category' => 'Fiction',
        'description' => 'The unforgettable novel of a childhood in a sleepy Southern town and the crisis of conscience that rocked it.',
        'cover_image' => 'mockingbird.jpg',
        'available_stock' => 10,
        'status' => 'Available'
    ],
    [
        'title' => '1984',
        'author' => 'George Orwell',
        'category' => 'Science Fiction',
        'description' => 'A dystopian novel set in a totalitarian regime where Big Brother is always watching.',
        'cover_image' => 'default_cover.jpg',
        'available_stock' => 8,
        'status' => 'Available'
    ],
    [
        'title' => 'The Great Gatsby',
        'author' => 'F. Scott Fitzgerald',
        'category' => 'Fiction',
        'description' => 'A portrait of the Jazz Age in all its decadence and excess.',
        'cover_image' => 'default_cover.jpg',
        'available_stock' => 6,
        'status' => 'Available'
    ],
    [
        'title' => 'Pride and Prejudice',
        'author' => 'Jane Austen',
        'category' => 'Romance',
        'description' => 'A classic novel of manners, focusing on the Bennet family and their five unmarried daughters.',
        'cover_image' => 'default_cover.jpg',
        'available_stock' => 12,
        'status' => 'Available'
    ],
    [
        'title' => 'The Hobbit',
        'author' => 'J.R.R. Tolkien',
        'category' => 'Fantasy',
        'description' => 'The journey of Bilbo Baggins, who is swept into an epic quest to reclaim the lost Dwarf Kingdom of Erebor.',
        'cover_image' => 'default_cover.jpg',
        'available_stock' => 7,
        'status' => 'Available'
    ],
    [
        'title' => 'The Catcher in the Rye',
        'author' => 'J.D. Salinger',
        'category' => 'Fiction',
        'description' => 'The story of a teenage boy dealing with alienation in a world of adult hypocrisy.',
        'cover_image' => 'default_cover.jpg',
        'available_stock' => 9,
        'status' => 'Available'
    ],
    [
        'title' => 'Harry Potter and the Philosopher\'s Stone',
        'author' => 'J.K. Rowling',
        'category' => 'Fantasy',
        'description' => 'The first novel in the Harry Potter series, introducing a young wizard and his adventures at Hogwarts School of Witchcraft and Wizardry.',
        'cover_image' => 'default_cover.jpg',
        'available_stock' => 15,
        'status' => 'Available'
    ],
    [
        'title' => 'The Lord of the Rings',
        'author' => 'J.R.R. Tolkien',
        'category' => 'Fantasy',
        'description' => 'An epic trilogy that follows the quest to destroy the One Ring, a powerful artifact created by the Dark Lord Sauron.',
        'cover_image' => 'default_cover.jpg',
        'available_stock' => 5,
        'status' => 'Available'
    ],
    [
        'title' => 'Brave New World',
        'author' => 'Aldous Huxley',
        'category' => 'Science Fiction',
        'description' => 'A dystopian novel about a futuristic World State and its citizens, whose lives are carefully controlled.',
        'cover_image' => 'default_cover.jpg',
        'available_stock' => 6,
        'status' => 'Available'
    ],
    [
        'title' => 'The Alchemist',
        'author' => 'Paulo Coelho',
        'category' => 'Fiction',
        'description' => 'A philosophical novel about a young Andalusian shepherd named Santiago and his journey to find a treasure at the Egyptian pyramids.',
        'cover_image' => 'default_cover.jpg',
        'available_stock' => 8,
        'status' => 'Available'
    ],
    [
        'title' => 'The Little Prince',
        'author' => 'Antoine de Saint-Exupéry',
        'category' => 'Children\'s Literature',
        'description' => 'A poetic tale about a young prince who visits various planets in space, addressing themes of loneliness, friendship, love, and loss.',
        'cover_image' => 'default_cover.jpg',
        'available_stock' => 10,
        'status' => 'Available'
    ],
    [
        'title' => 'Crime and Punishment',
        'author' => 'Fyodor Dostoevsky',
        'category' => 'Psychological Fiction',
        'description' => 'A novel that focuses on the mental anguish and moral dilemmas of Rodion Raskolnikov, an impoverished ex-student in Saint Petersburg.',
        'cover_image' => 'default_cover.jpg',
        'available_stock' => 4,
        'status' => 'Available'
    ]
];

// Ensure assets/images directory exists
$dir = 'assets/images/';
if (!file_exists($dir)) {
    mkdir($dir, 0777, true);
}

// Create a default cover image file if it doesn't exist
$default_cover = $dir . 'default_cover.jpg';
if (!file_exists($default_cover)) {
    // Copy default image from a URL or create a simple text file
    $default_image_url = 'https://via.placeholder.com/400x600.jpg?text=No+Cover+Available';
    
    // Try to download the default image
    $image_data = @file_get_contents($default_image_url);
    if ($image_data !== false) {
        file_put_contents($default_cover, $image_data);
    } else {
        // If download fails, create a dummy text file with .jpg extension
        file_put_contents($default_cover, "This is a placeholder for a book cover image.");
    }
}

// Connect to the database
$success_count = 0;
$error_messages = [];

foreach ($books as $book) {
    try {
        // Insert book into database
        $stmt = $conn->prepare("INSERT INTO books (title, author, category, description, cover_image, available_stock, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", 
            $book['title'],
            $book['author'],
            $book['category'],
            $book['description'],
            $book['cover_image'],
            $book['available_stock'],
            $book['status']
        );
        
        if ($stmt->execute()) {
            $success_count++;
        } else {
            $error_messages[] = "Error adding {$book['title']}: " . $stmt->error;
        }
        
        $stmt->close();
    } catch (Exception $e) {
        $error_messages[] = "Exception for {$book['title']}: " . $e->getMessage();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Sample Books</title>
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
        <h1>Add Sample Books</h1>
        
        <div class="result">
            <h3>Results</h3>
            <p class="success">Successfully added <?php echo $success_count; ?> books.</p>
            
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