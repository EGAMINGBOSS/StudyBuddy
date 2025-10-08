<?php
// Initialize the session
session_start();

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("location: login.php");
    exit;
}

// Include database connection
require_once "config/database.php";
require_once "backend/auth.php";

// Get current user info
$auth = new Auth();
$current_user = $auth->getCurrentUser();

// Get additional user info from database
$conn = getDBConnection();
$stmt = $conn->prepare("SELECT u.full_name, u.email, u.profile_picture, u.created_at, p.theme, p.language, p.notifications_enabled FROM users u LEFT JOIN user_preferences p ON u.id = p.user_id WHERE u.id = ?");
$stmt->bind_param("i", $current_user['id']);
$stmt->execute();
$result = $stmt->get_result();
$user_details = $result->fetch_assoc();
$stmt->close();
$conn->close();

// Default values if preferences don't exist
$theme = $user_details['theme'] ?? 'light';
$language = $user_details['language'] ?? 'en';
$notifications_enabled = $user_details['notifications_enabled'] ?? 1;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Student Platform</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Student Platform</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="welcome.php">Home</a>
                    </li>
                    <li class="nav-item active">
                        <a class="nav-link" href="profile.php">Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Profile Picture</h5>
                    </div>
                    <div class="card-body text-center">
                        <img src="uploads/profile_pictures/<?php echo htmlspecialchars($user_details['profile_picture']); ?>" alt="Profile Picture" class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                        <button class="btn btn-primary btn-sm">Change Picture</button>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header">
                        <h5>Account Information</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                <strong>Member Since:</strong> 
                                <span><?php echo date("F j, Y", strtotime($user_details['created_at'])); ?></span>
                            </li>
                            <li class="list-group-item">
                                <strong>Last Login:</strong> 
                                <span>Just now</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5>Profile Details</h5>
                    </div>
                    <div class="card-body">
                        <form id="profileForm">
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($current_user['username']); ?>" readonly>
                            </div>
                            
                            <div class="form-group">
                                <label for="full_name">Full Name</label>
                                <input type="text" class="form-control" id="full_name" value="<?php echo htmlspecialchars($user_details['full_name']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($user_details['email']); ?>">
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </form>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header">
                        <h5>Preferences</h5>
                    </div>
                    <div class="card-body">
                        <form id="preferencesForm">
                            <div class="form-group">
                                <label for="theme">Theme</label>
                                <select class="form-control" id="theme">
                                    <option value="light" <?php echo ($theme == 'light') ? 'selected' : ''; ?>>Light</option>
                                    <option value="dark" <?php echo ($theme == 'dark') ? 'selected' : ''; ?>>Dark</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="language">Language</label>
                                <select class="form-control" id="language">
                                    <option value="en" <?php echo ($language == 'en') ? 'selected' : ''; ?>>English</option>
                                    <option value="es" <?php echo ($language == 'es') ? 'selected' : ''; ?>>Spanish</option>
                                    <option value="fr" <?php echo ($language == 'fr') ? 'selected' : ''; ?>>French</option>
                                </select>
                            </div>
                            
                            <div class="form-group form-check">
                                <input type="checkbox" class="form-check-input" id="notifications" <?php echo ($notifications_enabled) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="notifications">Enable Notifications</label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Save Preferences</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-light py-3 mt-5">
        <div class="container text-center">
            <p>&copy; <?php echo date("Y"); ?> Student Platform. All rights reserved.</p>
        </div>
    </footer>
    
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="js/script.js"></script>
</body>
</html>