<?php
require_once __DIR__ . '/../config/database.php';

class Auth {
    private $conn;
    
    public function __construct() {
        $this->conn = getDBConnection();
    }
    
    // Register new user
    public function register($username, $email, $password, $full_name = '') {
        // Validate input
        if (empty($username) || empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'All fields are required'];
        }
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }
        
        // Check if username exists
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            return ['success' => false, 'message' => 'Username already exists'];
        }
        
        // Check if email exists
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            return ['success' => false, 'message' => 'Email already registered'];
        }
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user
        $stmt = $this->conn->prepare("INSERT INTO users (username, email, password, full_name, email_verified) VALUES (?, ?, ?, ?, FALSE)");
        $stmt->bind_param("ssss", $username, $email, $hashed_password, $full_name);
        
        if ($stmt->execute()) {
            $user_id = $stmt->insert_id;
            
            // Create user preferences
            $stmt = $this->conn->prepare("INSERT INTO user_preferences (user_id) VALUES (?)");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
            // Generate and send verification code
            require_once __DIR__ . '/email_verification.php';
            $emailVerification = new EmailVerification();
            $code = $emailVerification->generateVerificationCode();
            $emailVerification->storeVerificationCode($user_id, $email, $code);
            $emailVerification->sendVerificationEmail($email, $username, $code);
            
            return [
                'success' => true, 
                'message' => 'Registration successful. Please check your email for verification code.',
                'user_id' => $user_id,
                'requires_verification' => true
            ];
        }
        
        return ['success' => false, 'message' => 'Registration failed'];
    }
    
    // Login user
    public function login($username, $password) {
        if (empty($username) || empty($password)) {
            return ['success' => false, 'message' => 'Username and password are required'];
        }
        
        $stmt = $this->conn->prepare("SELECT id, username, email, password, full_name, profile_picture, email_verified FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                // Check if email is verified
                if (!$user['email_verified']) {
                    return [
                        'success' => false, 
                        'message' => 'Please verify your email before logging in',
                        'requires_verification' => true,
                        'user_id' => $user['id'],
                        'email' => $user['email'],
                        'username' => $user['username']
                    ];
                }
                
                // Update online status
                $stmt = $this->conn->prepare("UPDATE users SET is_online = TRUE, last_seen = NOW() WHERE id = ?");
                $stmt->bind_param("i", $user['id']);
                $stmt->execute();
                
                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['profile_picture'] = $user['profile_picture'];
                $_SESSION['logged_in'] = true;
                
                return [
                    'success' => true, 
                    'message' => 'Login successful',
                    'user' => [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'email' => $user['email'],
                        'full_name' => $user['full_name'],
                        'profile_picture' => $user['profile_picture']
                    ]
                ];
            }
        }
        
        return ['success' => false, 'message' => 'Invalid username or password'];
    }
    
    // Logout user
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $stmt = $this->conn->prepare("UPDATE users SET is_online = FALSE, last_seen = NOW() WHERE id = ?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
        }
        
        session_destroy();
        return ['success' => true, 'message' => 'Logged out successfully'];
    }
    
    // Check if user is logged in
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    // Get current user
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'email' => $_SESSION['email'],
            'full_name' => $_SESSION['full_name'],
            'profile_picture' => $_SESSION['profile_picture']
        ];
    }
    
    // Password reset
    public function resetPassword($email, $new_password) {
        if (empty($email) || empty($new_password)) {
            return ['success' => false, 'message' => 'Email and new password are required'];
        }
        
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $stmt = $this->conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashed_password, $email);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            return ['success' => true, 'message' => 'Password reset successful'];
        }
        
        return ['success' => false, 'message' => 'Email not found'];
    }
}
?>