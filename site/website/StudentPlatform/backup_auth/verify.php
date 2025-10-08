<?php
// Initialize the session
session_start();

// Check if user is in verification process
if(!isset($_SESSION["verification_user_id"]) || !isset($_SESSION["verification_email"])) {
    header("location: login.php");
    exit;
}

// Include required files
require_once "config/database.php";
require_once "backend/email_verification.php";

// Define variables
$verification_code = "";
$verification_err = "";
$success_message = "";

// Get or generate the demo code for this session
if(!isset($_SESSION["demo_verification_code"])) {
    $emailVerification = new EmailVerification();
    $demoCode = $emailVerification->generateVerificationCode();
    $_SESSION["demo_verification_code"] = $demoCode;
    
    // Store the code in the database
    $emailVerification->storeVerificationCode(
        $_SESSION["verification_user_id"], 
        $_SESSION["verification_email"], 
        $demoCode
    );
} else {
    $demoCode = $_SESSION["demo_verification_code"];
}

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Check if verification code is empty
    if(empty(trim($_POST["verification_code"]))) {
        $verification_err = "Please enter verification code.";
    } else {
        $verification_code = trim($_POST["verification_code"]);
    }
    
    // If there are no errors, attempt to verify
    if(empty($verification_err)) {
        $emailVerification = new EmailVerification();
        
        // Attempt to verify the code
        if($emailVerification->verifyCode($_SESSION["verification_user_id"], $verification_code)) {
            // Verification successful
            $success_message = "Email verified successfully! You can now login.";
            
            // Clear verification session variables
            unset($_SESSION["verification_user_id"]);
            unset($_SESSION["verification_email"]);
            unset($_SESSION["verification_username"]);
            unset($_SESSION["demo_verification_code"]);
            
            // Redirect to login page after a delay
            header("refresh:3;url=login.php");
        } else {
            $verification_err = "Invalid or expired verification code.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - Student Platform</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header text-center">
                        <h2>Email Verification</h2>
                    </div>
                    <div class="card-body verification-container" style="display: block;">
                        <?php 
                        if(!empty($verification_err)){
                            echo '<div class="alert alert-danger">' . $verification_err . '</div>';
                        }
                        if(!empty($success_message)){
                            echo '<div class="alert alert-success">' . $success_message . '</div>';
                        }
                        ?>
                        
                        <?php if(empty($success_message)): ?>
                            <p>A verification code has been sent to <strong><?php echo htmlspecialchars($_SESSION["verification_email"]); ?></strong></p>
                            <p>Please enter the code below to verify your email address.</p>
                            
                            <!-- For demonstration purposes only - show the code on screen -->
                            <div class="alert alert-info">
                                <p><strong>Demo Mode:</strong> In a real application, the code would be sent via email.</p>
                                <p>For this demo, use this code: <strong><?php echo $demoCode; ?></strong></p>
                            </div>
                            
                            <form id="verifyForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                <div class="form-group">
                                    <input type="text" name="verification_code" id="verification_code" class="form-control verification-code" maxlength="6" placeholder="Enter 6-digit code">
                                </div>
                                <div class="form-group">
                                    <input type="submit" class="btn btn-primary btn-block" value="Verify">
                                </div>
                            </form>
                            <p class="text-center">Didn't receive the code? <a href="resend-code.php">Resend code</a></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="js/script.js"></script>
</body>
</html>