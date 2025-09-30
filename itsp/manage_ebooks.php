<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['student_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                // Handle file uploads
                $cover_image = '';
                $file_path = '';
                
                // Upload cover image
                if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === 0) {
                    $cover_dir = 'assets/images/';
                    $cover_image = time() . '_' . basename($_FILES['cover_image']['name']);
                    move_uploaded_file($_FILES['cover_image']['tmp_name'], $cover_dir . $cover_image);
                }
                
                // Upload ebook file
                if (isset($_FILES['ebook_file']) && $_FILES['ebook_file']['error'] === 0) {
                    $ebook_dir = 'ebooks/';
                    $file_path = time() . '_' . basename($_FILES['ebook_file']['name']);
                    move_uploaded_file($_FILES['ebook_file']['tmp_name'], $ebook_dir . $file_path);
                }
                
                // Insert into database
                $stmt = $conn->prepare("INSERT INTO ebooks (title, author, category, description, cover_image, file_path) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssss", 
                    $_POST['title'],
                    $_POST['author'],
                    $_POST['category'],
                    $_POST['description'],
                    $cover_image,
                    $file_path
                );
                $stmt->execute();
                $stmt->close();
                break;
                
            case 'edit':
                $stmt = $conn->prepare("UPDATE ebooks SET title = ?, author = ?, category = ?, description = ?, status = ? WHERE id = ?");
                $stmt->bind_param("sssssi", 
                    $_POST['title'],
                    $_POST['author'],
                    $_POST['category'],
                    $_POST['description'],
                    $_POST['status'],
                    $_POST['ebook_id']
                );
                $stmt->execute();
                $stmt->close();
                break;
                
            case 'delete':
                // Get file paths before deleting
                $stmt = $conn->prepare("SELECT cover_image, file_path FROM ebooks WHERE id = ?");
                $stmt->bind_param("i", $_POST['ebook_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                $ebook = $result->fetch_assoc();
                
                // Delete from database
                $delete_stmt = $conn->prepare("DELETE FROM ebooks WHERE id = ?");
                $delete_stmt->bind_param("i", $_POST['ebook_id']);
                $delete_stmt->execute();
                $delete_stmt->close();
                
                // Delete files
                if ($ebook) {
                    @unlink('assets/images/' . $ebook['cover_image']);
                    @unlink('ebooks/' . $ebook['file_path']);
                }
                break;
        }
    }
}

// Get all ebooks
$ebooks_query = "SELECT * FROM ebooks ORDER BY id DESC";
$ebooks_result = $conn->query($ebooks_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage E-Books - Library Management System</title>
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/fontawesome.css">
    <link rel="stylesheet" href="assets/css/templatemo-cyborg-gaming.css">
    <style>
        .ebook-form {
            background: #1f2122;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .ebook-list {
            background: #1f2122;
            padding: 20px;
            border-radius: 10px;
        }
        .ebook-item {
            border-bottom: 1px solid #2a2d2e;
            padding: 15px 0;
        }
        .ebook-item:last-child {
            border-bottom: none;
        }
        .ebook-cover {
            width: 100px;
            height: 150px;
            object-fit: cover;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header-area header-sticky">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <nav class="main-nav">
                        <a href="index.php" class="logo">
                            <img src="img\libmsLOGO.png" alt="">
                        </a>
                        <ul class="nav">
                            <li><a href="index.php">Home</a></li>
                            <li><a href="browse.php">Browse</a></li>
                            <li><a href="streams.php">Books</a></li>
                            <li><a href="ebook.php">E-Books</a></li>
                            <li><a href="profile.php">Profile</a></li>
                            <li><a href="logout.php">Logout</a></li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container mt-5">
        <div class="row">
            <div class="col-lg-12">
                <div class="heading-section">
                    <h4><em>Manage</em> E-Books</h4>
                </div>
            </div>
            
            <!-- Add New E-Book Form -->
            <div class="col-lg-12">
                <div class="ebook-form">
                    <h5>Add New E-Book</h5>
                    <form action="" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="add">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Title</label>
                                    <input type="text" name="title" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Author</label>
                                    <input type="text" name="author" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Category</label>
                                    <input type="text" name="category" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Cover Image</label>
                                    <input type="file" name="cover_image" class="form-control" accept="image/*" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label>E-Book File (PDF)</label>
                            <input type="file" name="ebook_file" class="form-control" accept=".pdf" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Add E-Book</button>
                    </form>
                </div>
            </div>
            
            <!-- E-Books List -->
            <div class="col-lg-12">
                <div class="ebook-list">
                    <h5>E-Books List</h5>
                    <?php if ($ebooks_result && $ebooks_result->num_rows > 0): ?>
                        <?php while ($ebook = $ebooks_result->fetch_assoc()): ?>
                            <div class="ebook-item">
                                <div class="row align-items-center">
                                    <div class="col-md-2">
                                        <img src="assets/images/<?php echo htmlspecialchars($ebook['cover_image']); ?>" alt="" class="ebook-cover">
                                    </div>
                                    <div class="col-md-7">
                                        <h5><?php echo htmlspecialchars($ebook['title']); ?></h5>
                                        <p>Author: <?php echo htmlspecialchars($ebook['author']); ?></p>
                                        <p>Category: <?php echo htmlspecialchars($ebook['category']); ?></p>
                                        <p>Status: <?php echo htmlspecialchars($ebook['status']); ?></p>
                                    </div>
                                    <div class="col-md-3">
                                        <a href="admin_page.php#ebooks" class="btn btn-warning btn-sm">Edit in Admin Panel</a>
                                        <form action="" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this e-book?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="ebook_id" value="<?php echo $ebook['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>No e-books found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
</body>
</html> 