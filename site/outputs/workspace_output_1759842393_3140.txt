<?php
// Initialize the session
session_start();

// Include database connection
require_once "config/database.php";
require_once "backend/auth.php";

// Define variables and initialize with empty values
$email = "";
$email_err = "";
$success_message = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Check if email is empty
    if(empty(trim($_POST["email"]))) {
        $email_err = "Please enter your email address.";
    } elseif(!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
        $email_err = "Please enter a valid email address.";
    } else {
        $email = trim($_POST["email"]);
    }
    
    // If there are no errors, proceed with password reset
    if(empty($email_err)) {
        // For demonstration purposes, we'll just show a success message
        // In a real application, you would:
        // 1. Check if the email exists in the database
        // 2. Generate a password reset token
        // 3. Send an email with a link to reset the password
        
        $success_message = "If your email address exists in our database, you will receive a password recovery link at your email address in a few minutes.";
        
        // Clear the email field after submission
        $email = "";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Student Platform</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header text-center">
                        <h2>Reset Password</h2>
                    </div>
                    <div class="card-body">
                        <?php 
                        if(!empty($success_message)){
                            echo '<div class="alert alert-success">' . $success_message . '</div>';
                        }        
                        ?>
                        <p class="text-center">Enter your email address and we'll send you a link to reset your password.</p>
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="auth-form">
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" name="email" id="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>">
                                <span class="help-block"><?php echo $email_err; ?></span>
                            </div>
                            <div class="form-group">
                                <input type="submit" class="btn btn-primary btn-block" value="Send Reset Link">
                            </div>
                            <p class="text-center"><a href="login.php">Back to Login</a></p>
                        </form>
                    </div>
                </div>
                
                <!-- For demonstration purposes only -->
                <div class="card mt-4">
                    <div class="card-header text-center">
                        <h4>Demo Mode</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <p><strong>Note:</strong> In a real application, an email would be sent with a password reset link.</p>
                            <p>For this demo, you can use the form below to simulate resetting your password:</p>
                        </div>
                        <form action="demo-reset.php" method="post">
                            <div class="form-group">
                                <label for="demo_email">Email</label>
                                <input type="email" name="demo_email" id="demo_email" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" name="new_password" id="new_password" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <input type="submit" class="btn btn-warning btn-block" value="Demo Reset Password">
                            </div>
                        </form>
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