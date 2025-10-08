<?php
// Database configuration
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'student_user');
define('DB_PASSWORD', 'password123');
define('DB_NAME', 'student_platform');

// Connect to MySQL database
function getDBConnection() {
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}
?>