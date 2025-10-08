<?php
// Initialize the session
session_start();

// Include required files
require_once "config/database.php";
require_once "backend/email_verification.php";

// Get the user's verification code from the database
$conn = getDBConnection();
$stmt = $conn->prepare("SELECT user_id, email, code FROM verification_codes WHERE user_id = 1 ORDER BY created_at DESC LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $user_id = $row['user_id'];
    $email = $row['email'];
    $code = $row['code'];
    
    echo "<h2>Verification Information</h2>";
    echo "<p>User ID: " . $user_id . "</p>";
    echo "<p>Email: " . $email . "</p>";
    echo "<p>Verification Code: <strong>" . $code . "</strong></p>";
    
    // If form is submitted, verify the code
    if($_SERVER["REQUEST_METHOD"] == "POST") {
        $submitted_code = trim($_POST["verification_code"]);
        
        $emailVerification = new EmailVerification();
        if($emailVerification->verifyCode($user_id, $submitted_code)) {
            echo "<div style='color: green; font-weight: bold;'>Email verified successfully! You can now login.</div>";
            echo "<p><a href='login.php'>Go to Login</a></p>";
        } else {
            echo "<div style='color: red; font-weight: bold;'>Invalid or expired verification code.</div>";
        }
    }
    
    echo "<form method='post'>";
    echo "<input type='text' name='verification_code' placeholder='Enter code' maxlength='6' required>";
    echo "<button type='submit'>Verify</button>";
    echo "</form>";
} else {
    echo "<p>No verification code found.</p>";
}

$stmt->close();
$conn->close();
?>