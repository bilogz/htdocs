<?php
session_name('student_session');
session_start();
include 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize error variable
$error = '';

// Check for error messages
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'unauthorized':
            $error = "Please log in as a student to access the system.";
            break;
        // Add more error cases as needed
    }
}

// Check for session error messages
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']); // Clear the error after displaying
}

// At the top, before any HTML output, check for a logout reason
$logout_reason = $_GET['reason'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = trim($_POST['student_id'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($student_id) && !empty($password)) {
        $stmt = $conn->prepare("SELECT student_id, password, full_name, user_type FROM users WHERE student_id = ? AND user_type = 'student'");
        if ($stmt === false) {
            die("Error preparing statement: " . $conn->error);
        }
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 1) {
            $stmt->bind_result($db_student_id, $hashed_password, $db_full_name, $user_type);
            $stmt->fetch();
            if (password_verify($password, $hashed_password)) {
                // Start a new session
                session_regenerate_id(true);
                
                // Set all necessary session variables
                $_SESSION['student_id'] = $db_student_id;
                $_SESSION['full_name'] = $db_full_name;
                $_SESSION['user_type'] = $user_type;
                $_SESSION['is_admin'] = false;
                
                // Set session cookie parameters for better security
                $params = session_get_cookie_params();
                setcookie(session_name(), session_id(), [
                    'expires' => time() + 3600,
                    'path' => '/',
                    'domain' => '',
                    'secure' => true,
                    'httponly' => true,
                    'samesite' => 'Strict'
                ]);
                
                header("Location: index.php");
                exit();
            } else {
                $error = "Incorrect ID or password.";
            }
        } else {
            $error = "Student account not found.";
        }
        $stmt->close();
    } else {
        $error = "All fields are required.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BCP Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f4f7fc;
            font-family: Arial, sans-serif;
        }
        .login-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 350px;
        }
        .login-container img {
            width: 100px;
            margin-bottom: 20px;
        }
        .login-container h2 {
            margin-bottom: 20px;
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        .form-control {
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 15px;
        }
        .btn-login {
            width: 100%;
            padding: 10px;
            background: #4f46e5;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
        }
        .btn-login:hover {
            background: #4338ca;
        }
        .btn-register {
            width: 100%;
            padding: 10px;
            background: #6366f1;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
        }
        .btn-register:hover {
            background: #4f46e5;
        }
        .error-message {
            color: #dc3545;
            margin-bottom: 15px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <img src="img\images.png" alt="Logo">
        <h2>Sign in</h2>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($logout_reason === 'role_switch'): ?>
            <div class="alert alert-info">You have been logged out as admin. Please log in again if you want to access the admin panel.</div>
        <?php endif; ?>
        
        <form action="login.php" method="POST">
            <div class="mb-3">
                <label for="student_id" class="form-label">Student ID</label>
                <input type="text" class="form-control" id="student_id" name="student_id" required>
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn btn-login">Sign in</button>
            <button type="button" class="btn btn-register" onclick="window.location.href='register.php'">Register</button>
        </form>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>