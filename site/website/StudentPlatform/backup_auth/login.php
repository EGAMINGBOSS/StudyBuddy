<?php
// Initialize the session
session_start();

// Check if the user is already logged in, if yes then redirect to welcome page
if(isset($_SESSION["logged_in"]) && $_SESSION["logged_in"] === true) {
    header("location: welcome.php");
    exit;
}

// Include database connection
require_once "config/database.php";
require_once "backend/auth.php";

// Define variables and initialize with empty values
$username = $password = "";
$username_err = $password_err = $login_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Check if username is empty
    if(empty(trim($_POST["username"]))) {
        $username_err = "Please enter username or email.";
    } else {
        $username = trim($_POST["username"]);
    }
    
    // Check if password is empty
    if(empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Validate credentials
    if(empty($username_err) && empty($password_err)) {
        // Create Auth instance
        $auth = new Auth();
        
        // Attempt to login
        $result = $auth->login($username, $password);
        
        if($result['success']) {
            // Set logged in session variable
            $_SESSION["logged_in"] = true;
            // Redirect to welcome page
            header("location: welcome.php");
        } elseif(isset($result['requires_verification']) && $result['requires_verification']) {
            // Store user info for verification
            $_SESSION["verification_user_id"] = $result['user_id'];
            $_SESSION["verification_email"] = $result['email'];
            $_SESSION["verification_username"] = $result['username'];
            
            // Get the existing verification code from database
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
            $login_err = $result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Student Platform</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header text-center">
                        <h2>Login</h2>
                    </div>
                    <div class="card-body">
                        <?php 
                        if(!empty($login_err)){
                            echo '<div class="alert alert-danger">' . $login_err . '</div>';
                        }        
                        ?>
                        <form id="loginForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="auth-form">
                            <div class="form-group">
                                <label for="username">Username or Email</label>
                                <input type="text" name="username" id="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
                                <span id="username_err" class="help-block"><?php echo $username_err; ?></span>
                            </div>    
                            <div class="form-group">
                                <label for="password">Password</label>
                                <input type="password" name="password" id="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                                <span id="password_err" class="help-block"><?php echo $password_err; ?></span>
                            </div>
                            <div class="form-group">
                                <input type="submit" class="btn btn-primary btn-block" value="Login">
                            </div>
                            <p class="text-center">Don't have an account? <a href="register.php">Sign up now</a>.</p>
                            <p class="text-center"><a href="reset-password.php">Forgot your password?</a></p>
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