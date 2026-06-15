<?php
// Aiven MySQL Cloud Configuration
define('DB_HOST', 'mysql-135acaf0-student-5765.e.aivencloud.com');
define('DB_PORT', '22332');
define('DB_USER', 'avnadmin');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', 'salon_booking');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function generateBookingNo() {
    return 'BK-' . date('Ymd') . '-' . rand(100, 999);
}
?>