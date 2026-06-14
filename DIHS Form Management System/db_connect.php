<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "admindihs"; // Change this to your actual DB name

// Create connection with persistent connection and optimized settings
$conn = new mysqli('p:' . $servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("Connection failed: Please try again later. If the problem persists, contact support.");
}

// Set character set to ensure proper encoding
$conn->set_charset("utf8mb4");

// Set timezone
$conn->query("SET time_zone = '+08:00'");

// Optimize session handling
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cache_limiter', 'private_no_expire');
    ini_set('session.gc_maxlifetime', 14400); // 4 hours
    session_start();
}
?>