<?php
// Initialize the session
session_start();

// Check if the user is already logged in, if yes then redirect to welcome page
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: welcome.php");
    exit;
}

// Include database connection
require_once "config/database.php";
require_once "backend/auth.php";

// Define variables and initialize with empty values
$username = $email = $password = $confirm_password = $full_name = "";
$username_err = $email_err = $password_err = $confirm_password_err = $full_name_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validate username
    if(empty(trim($_POST["username"]))) {
        $username_err = "Please enter a username.";
    } elseif(strlen(trim($_POST["username"])) < 3) {
        $username_err = "Username must have at least 3 characters.";
    } else {
        $username = trim($_POST["username"]);
    }
    
    // Validate email
    if(empty(trim($_POST["email"]))) {
        $email_err = "Please enter an email.";
    } elseif(!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
        $email_err = "Please enter a valid email address.";
    } else {
        $email = trim($_POST["email"]);
    }
    
    // Validate password
    if(empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";     
    } elseif(strlen(trim($_POST["password"])) < 6) {
        $password_err = "Password must have at least 6 characters.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Validate confirm password
    if(empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Please confirm password.";     
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if(empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = "Password did not match.";
        }
    }
    
    // Get full name (optional)
    $full_name = trim($_POST["full_name"] ?? "");
    
    // Check input errors before inserting in database
    if(empty($username_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err)) {
        
        // Create Auth instance
        $auth = new Auth();
        
        // Attempt to register the user
        $result = $auth->register($username, $email, $password, $full_name);
        
        if($result['success']) {
            // Store user info for verification
            $_SESSION["verification_user_id"] = $result['user_id'];
            $_SESSION["verification_email"] = $email;
            $_SESSION["verification_username"] = $username;
            
            // Generate and store verification code in session for demo
            require_once "backend/email_verification.php";
            $emailVerification = new EmailVerification();
            
            // Get the code that was just stored in the database
            $conn = getDBConnection();
            $stmt = $conn->prepare("SELECT code FROM verification_codes WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
            $stmt->bind_param("i", $result['user_id']);
            $stmt->execute();
            $codeResult = $stmt->get_result();
            if($codeResult->num_rows > 0) {
                $row = $codeResult->fetch_assoc();
                $_SESSION["demo_verification_code"] = $row['code'];
            }
            $stmt->close();
            $conn->close();
            
            // Redirect to verification page
            header("location: verify.php");
        } else {
            // Registration failed, display error message
            $register_err = $result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Student Platform</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header text-center">
                        <h2>Sign Up</h2>
                    </div>
                    <div class="card-body">
                        <?php 
                        if(isset($register_err)){
                            echo '<div class="alert alert-danger">' . $register_err . '</div>';
                        }        
                        ?>
                        <form id="registerForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="auth-form">
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input type="text" name="username" id="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
                                <span id="username_err" class="help-block"><?php echo $username_err; ?></span>
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" name="email" id="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>">
                                <span id="email_err" class="help-block"><?php echo $email_err; ?></span>
                            </div>
                            <div class="form-group">
                                <label for="full_name">Full Name (Optional)</label>
                                <input type="text" name="full_name" id="full_name" class="form-control" value="<?php echo $full_name; ?>">
                            </div>    
                            <div class="form-group">
                                <label for="password">Password</label>
                                <input type="password" name="password" id="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                                <span id="password_err" class="help-block"><?php echo $password_err; ?></span>
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Confirm Password</label>
                                <input type="password" name="confirm_password" id="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>">
                                <span id="confirm_password_err" class="help-block"><?php echo $confirm_password_err; ?></span>
                            </div>
                            <div class="form-group">
                                <input type="submit" class="btn btn-success btn-block" value="Sign Up">
                            </div>
                            <p class="text-center">Already have an account? <a href="login.php">Login here</a>.</p>
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