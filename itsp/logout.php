<?php
session_start();

// Store the role switch parameter
$role_switch = $_GET['role_switch'] ?? '';

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', [
        'expires' => time() - 3600,
        'path' => '/',
        'domain' => '',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
}

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect based on role switch
if ($role_switch === 'admin') {
    header('Location: login.php?reason=role_switch');
    exit();
} elseif ($role_switch === 'student') {
    header('Location: admin_login.php?reason=role_switch');
    exit();
} else {
    // Determine the appropriate login page based on the previous user type
    $was_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
    header('Location: ' . ($was_admin ? 'admin_login.php' : 'login.php'));
    exit();
}
?> 