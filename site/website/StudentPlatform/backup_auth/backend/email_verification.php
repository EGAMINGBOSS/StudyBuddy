<?php
require_once __DIR__ . '/../config/database.php';

class EmailVerification {
    private $conn;
    
    public function __construct() {
        $this->conn = getDBConnection();
    }
    
    // Generate a random verification code
    public function generateVerificationCode($length = 6) {
        return substr(str_shuffle("0123456789"), 0, $length);
    }
    
    // Store verification code in database
    public function storeVerificationCode($user_id, $email, $code) {
        // Set expiration time (24 hours from now)
        $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        // Delete any existing codes for this user
        $stmt = $this->conn->prepare("DELETE FROM verification_codes WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        // Insert new code
        $stmt = $this->conn->prepare("INSERT INTO verification_codes (user_id, email, code, expires_at) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $email, $code, $expires_at);
        return $stmt->execute();
    }
    
    // Verify code
    public function verifyCode($user_id, $code) {
        $stmt = $this->conn->prepare("SELECT * FROM verification_codes WHERE user_id = ? AND code = ? AND expires_at > NOW()");
        $stmt->bind_param("is", $user_id, $code);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            // Mark email as verified
            $stmt = $this->conn->prepare("UPDATE users SET email_verified = TRUE WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
            // Delete the verification code
            $stmt = $this->conn->prepare("DELETE FROM verification_codes WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
            return true;
        }
        
        return false;
    }
    
    // Send verification email (simulated for this example)
    public function sendVerificationEmail($email, $username, $code) {
        // In a real application, you would send an actual email here
        // For this example, we'll just return true to simulate success
        return true;
    }
}
?>