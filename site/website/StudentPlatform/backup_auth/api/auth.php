<?php
// Initialize session
session_start();

// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Include database connection
require_once "../config/database.php";
require_once "../backend/auth.php";

// Create Auth instance
$auth = new Auth();

// Handle different request methods
$method = $_SERVER['REQUEST_METHOD'];

// Handle preflight OPTIONS request
if ($method === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Handle POST requests
if ($method === 'POST') {
    // Get JSON data from request body
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    // If JSON parsing failed, try to get data from POST
    if ($data === null) {
        $data = $_POST;
    }
    
    // Check action parameter
    $action = isset($data['action']) ? $data['action'] : '';
    
    switch ($action) {
        case 'login':
            // Validate required fields
            if (!isset($data['username']) || !isset($data['password'])) {
                echo json_encode(['success' => false, 'message' => 'Username and password are required']);
                exit();
            }
            
            // Attempt to login
            $result = $auth->login($data['username'], $data['password']);
            echo json_encode($result);
            break;
            
        case 'register':
            // Validate required fields
            if (!isset($data['username']) || !isset($data['email']) || !isset($data['password'])) {
                echo json_encode(['success' => false, 'message' => 'Username, email, and password are required']);
                exit();
            }
            
            // Get optional full name
            $full_name = isset($data['full_name']) ? $data['full_name'] : '';
            
            // Attempt to register
            $result = $auth->register($data['username'], $data['email'], $data['password'], $full_name);
            echo json_encode($result);
            break;
            
        case 'verify':
            // Validate required fields
            if (!isset($data['user_id']) || !isset($data['code'])) {
                echo json_encode(['success' => false, 'message' => 'User ID and verification code are required']);
                exit();
            }
            
            // Attempt to verify
            require_once "../backend/email_verification.php";
            $emailVerification = new EmailVerification();
            $result = $emailVerification->verifyCode($data['user_id'], $data['code']);
            
            echo json_encode(['success' => $result, 'message' => $result ? 'Email verified successfully' : 'Invalid or expired verification code']);
            break;
            
        case 'logout':
            // Logout user
            $result = $auth->logout();
            echo json_encode($result);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} 
// Handle GET requests
else if ($method === 'GET') {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    switch ($action) {
        case 'check':
            // Check if user is logged in
            $isLoggedIn = $auth->isLoggedIn();
            $user = $auth->getCurrentUser();
            
            echo json_encode([
                'success' => true,
                'logged_in' => $isLoggedIn,
                'user' => $user
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} 
// Handle unsupported methods
else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>