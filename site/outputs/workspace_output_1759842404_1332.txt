<?php
// Initialize the session
session_start();

// Include database connection
require_once "config/database.php";
require_once "backend/auth.php";

// Define variables and initialize with empty values
$email = $new_password = "";
$email_err = $password_err = "";
$success_message = $error_message = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Check if email is empty
    if(empty(trim($_POST["demo_email"]))) {
        $email_err = "Please enter your email address.";
    } elseif(!filter_var(trim($_POST["demo_email"]), FILTER_VALIDATE_EMAIL)) {
        $email_err = "Please enter a valid email address.";
    } else {
        $email = trim($_POST["demo_email"]);
    }
    
    // Check if password is empty
    if(empty(trim($_POST["new_password"]))) {
        $password_err = "Please enter a new password.";     
    } elseif(strlen(trim($_POST["new_password"])) < 6) {
        $password_err = "Password must have at least 6 characters.";
    } else {
        $new_password = trim($_POST["new_password"]);
    }
    
    // If there are no errors, proceed with password reset
    if(empty($email_err) && empty($password_err)) {
        // Create Auth instance
        $auth = new Auth();
        
        // Attempt to reset password
        $result = $auth->resetPassword($email, $new_password);
        
        if($result['success']) {
            $success_message = "Password has been reset successfully. You can now login with your new password.";
        } else {
            $error_message = $result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo Reset Password - Student Platform</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header text-center">
                        <h2>Demo Password Reset</h2>
                    </div>
                    <div class="card-body">
                        <?php 
                        if(!empty($success_message)){
                            echo '<div class="alert alert-success">' . $success_message . '</div>';
                        }
                        if(!empty($error_message)){
                            echo '<div class="alert alert-danger">' . $error_message . '</div>';
                        }
                        ?>
                        
                        <?php if(empty($success_message)): ?>
                            <div class="alert alert-warning">
                                <p><strong>Demo Mode:</strong> This is a simplified password reset for demonstration purposes.</p>
                            </div>
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="auth-form">
                                <div class="form-group">
                                    <label for="demo_email">Email</label>
                                    <input type="email" name="demo_email" id="demo_email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>">
                                    <span class="help-block"><?php echo $email_err; ?></span>
                                </div>
                                <div class="form-group">
                                    <label for="new_password">New Password</label>
                                    <input type="password" name="new_password" id="new_password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                                    <span class="help-block"><?php echo $password_err; ?></span>
                                </div>
                                <div class="form-group">
                                    <input type="submit" class="btn btn-primary btn-block" value="Reset Password">
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="text-center mt-3">
                                <a href="login.php" class="btn btn-primary">Go to Login</a>
                            </div>
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