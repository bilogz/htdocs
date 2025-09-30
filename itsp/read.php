<?php
session_start();
// --- Setup Database Connection ---
// Use the existing config.php if available, otherwise keep the current connection setup
if (file_exists('config.php')) {
    require 'config.php';
    $pdo = $conn;  // Using the existing connection from config.php
} else {
    $host = 'localhost:3307';
    $db   = 'library_management';
    $user = 'root';
    $pass = '';
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ];

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
    } catch (\PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

// --- Validate ebook_id ---
if (!isset($_GET['ebook_id']) || !is_numeric($_GET['ebook_id'])) {
    die("Invalid eBook ID.");
}

$ebook_id = (int) $_GET['ebook_id'];

// --- Fetch eBook from database ---
if ($pdo instanceof PDO) {
    $stmt = $pdo->prepare("SELECT * FROM ebooks WHERE id = ?");
    $stmt->execute([$ebook_id]);
    $ebook = $stmt->fetch();
} else {
    // Using mysqli connection
    $stmt = $pdo->prepare("SELECT * FROM ebooks WHERE id = ?");
    $stmt->bind_param("i", $ebook_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $ebook = $result->fetch_assoc();
    $stmt->close();
}

if (!$ebook) {
    die("eBook not found.");
}

// Get file extension
$file_extension = '';
if (!empty($ebook['file_path'])) {
    $file_extension = strtolower(pathinfo($ebook['file_path'], PATHINFO_EXTENSION));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <title>Read: <?php echo htmlspecialchars($ebook['title']); ?></title>
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/fontawesome.css">
    <link rel="stylesheet" href="assets/css/templatemo-cyborg-gaming.css">
    <style>
        body {
            overflow: hidden;
            background-color: #1f2122;
            font-family: 'Poppins', sans-serif;
        }
        .ebook-reader-container {
            display: flex;
            flex-direction: column;
            height: 100vh;
            color: #fff;
        }
        .reader-header {
            background-color: #27292a;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .reader-header h1 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
            color: #fff;
        }
        .reader-body {
            display: flex;
            flex: 1;
            overflow: hidden;
        }
        .sidebar {
            width: 300px;
            background-color: #1f2122;
            padding: 20px;
            overflow-y: auto;
            border-right: 1px solid #27292a;
        }
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .viewer-container {
            flex: 1;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }
        .viewer {
            width: 100%;
            height: 100%;
            border: none;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .btn {
            border-radius: 25px;
            padding: 8px 20px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-primary {
            background-color: #e75e8d;
            border-color: #e75e8d;
            color: #fff;
        }
        .btn-primary:hover {
            background-color: #cf4c77;
            border-color: #cf4c77;
        }
        .btn-secondary {
            background-color: #60b8eb;
            border-color: #60b8eb;
            color: #fff;
        }
        .btn-secondary:hover {
            background-color: #4a9cd1;
            border-color: #4a9cd1;
        }
        .btn-back {
            background-color: #333539;
            border-color: #333539;
            color: #fff;
            padding: 5px 15px;
            font-size: 0.8rem;
        }
        .btn-back:hover {
            background-color: #444;
            border-color: #444;
        }
        .book-cover {
            width: 100%;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.3);
        }
        .book-info {
            margin-bottom: 25px;
        }
        .book-info h2 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            font-weight: 600;
            color: #fff;
        }
        .book-info p {
            color: #ccc;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }
        .book-meta {
            background-color: #27292a;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .book-meta p {
            margin-bottom: 8px;
            font-size: 0.9rem;
        }
        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 8px;
        }
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }
        .status-dot.available {
            background-color: #4CAF50;
            box-shadow: 0 0 8px rgba(76, 175, 80, 0.5);
        }
        .status-dot.unavailable {
            background-color: #f44336;
            box-shadow: 0 0 8px rgba(244, 67, 54, 0.5);
        }
        .book-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .book-actions a {
            text-align: center;
        }
        .section-title {
            font-size: 1.2rem;
            color: #e75e8d;
            margin-bottom: 12px;
        }
        .format-badge {
            display: inline-block;
            background-color: #2f3135;
            color: #60b8eb;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .unsupported-format {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100%;
            text-align: center;
            padding: 40px;
            background-color: #27292a;
            border-radius: 10px;
        }
        .unsupported-format i {
            font-size: 3rem;
            color: #60b8eb;
            margin-bottom: 20px;
        }
        .unsupported-format h3 {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: #fff;
        }
        .unsupported-format p {
            color: #ccc;
            margin-bottom: 20px;
        }
        @media (max-width: 992px) {
            .reader-body {
                flex-direction: column;
            }
            .sidebar {
                width: 100%;
                max-height: 350px;
                border-right: none;
                border-bottom: 1px solid #27292a;
            }
        }
        @media (max-width: 768px) {
            .reader-header h1 {
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>

<div class="ebook-reader-container">
    <div class="reader-header">
        <h1><?php echo htmlspecialchars($ebook['title']); ?></h1>
        <div>
            <a href="javascript:history.back()" class="btn btn-back">
                <i class="fa fa-arrow-left"></i> Back
            </a>
        </div>
    </div>
    
    <div class="reader-body">
        <div class="sidebar">
            <div class="text-center">
                <img src="assets/images/<?php echo htmlspecialchars($ebook['cover_image'] ?? ''); ?>" 
                     alt="<?php echo htmlspecialchars($ebook['title']); ?>" 
                     class="book-cover"
                     onerror="this.src='assets/images/default-book-cover.jpg'">
            </div>
            
            <div class="book-info">
                <h2><?php echo htmlspecialchars($ebook['title']); ?></h2>
                <p>By <?php echo htmlspecialchars($ebook['author']); ?></p>
                <span class="format-badge"><?php echo strtoupper($file_extension); ?> FILE</span>
                <div class="status-indicator">
                    <span class="status-dot <?php echo strtolower($ebook['status'] ?? 'available'); ?>"></span>
                    <span class="status-text"><?php echo htmlspecialchars($ebook['status'] ?? 'Available'); ?></span>
                </div>
            </div>
            
            <div class="book-meta">
                <p><strong>Category:</strong> <?php echo htmlspecialchars($ebook['category'] ?? 'N/A'); ?></p>
                <p><strong>Size:</strong> <?php echo file_exists($ebook['file_path']) ? round(filesize($ebook['file_path']) / (1024*1024), 2) . ' MB' : 'Unknown'; ?></p>
                <p><strong>Added:</strong> <?php echo date('M j, Y', strtotime($ebook['created_at'] ?? 'now')); ?></p>
            </div>
            
            <div class="book-description">
                <div class="section-title">Description</div>
                <p><?php echo nl2br(htmlspecialchars($ebook['description'])); ?></p>
            </div>
            
            <?php if (!empty($ebook['file_path']) && file_exists($ebook['file_path'])): ?>
                <div class="book-actions mt-4">
                    <a href="read_ebook.php?id=<?php echo $ebook_id; ?>" class="btn btn-primary" target="_blank">Open in New Tab</a>
                    <a href="download_ebook.php?id=<?php echo $ebook_id; ?>" class="btn btn-secondary">Download eBook</a>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="main-content">
            <div class="viewer-container">
                <?php if (!empty($ebook['file_path']) && file_exists($ebook['file_path'])): ?>
                    <?php if ($file_extension === 'pdf'): ?>
                        <iframe class="viewer" src="read_ebook.php?id=<?php echo $ebook_id; ?>"></iframe>
                    <?php elseif (in_array($file_extension, ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'])): ?>
                        <iframe class="viewer" src="https://view.officeapps.live.com/op/embed.aspx?src=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . '/' . $ebook['file_path']); ?>"></iframe>
                    <?php elseif (in_array($file_extension, ['txt', 'rtf', 'csv'])): ?>
                        <iframe class="viewer" src="read_ebook.php?id=<?php echo $ebook_id; ?>"></iframe>
                    <?php elseif (in_array($file_extension, ['epub'])): ?>
                        <iframe class="viewer" src="read_ebook.php?id=<?php echo $ebook_id; ?>"></iframe>
                    <?php else: ?>
                        <div class="unsupported-format">
                            <i class="fa fa-file-alt"></i>
                            <h3>File Format Not Supported</h3>
                            <p>This file format (<?php echo htmlspecialchars($file_extension); ?>) cannot be displayed directly in the browser.</p>
                            <a href="download_ebook.php?id=<?php echo $ebook_id; ?>" class="btn btn-primary">Download to View</a>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="unsupported-format">
                        <i class="fa fa-exclamation-triangle"></i>
                        <h3>File Not Available</h3>
                        <p>Sorry, the eBook file is missing or unavailable.</p>
                        <p>Please contact the library administrator for assistance.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.min.js"></script>
<script src="assets/js/isotope.min.js"></script>
<script src="assets/js/owl-carousel.js"></script>
<script src="assets/js/tabs.js"></script>
<script src="assets/js/popup.js"></script>
<script src="assets/js/custom.js"></script>

</body>
</html>
