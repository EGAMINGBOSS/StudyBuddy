<?php
// Test login script
require_once "config/database.php";
require_once "backend/auth.php";

// Create Auth instance
$auth = new Auth();

// Test login with existing user
echo "Testing login for user: khenjie\n";
echo "Email: khenjiehbo@gmail.com\n";

$result = $auth->login('khenjie', 'password123'); // Assuming this was the password used
print_r($result);

if ($result['success']) {
    echo "Login successful!\n";
    echo "User data:\n";
    print_r($result['user']);
} else {
    echo "Login failed: " . $result['message'] . "\n";
    
    // Let's also try with email
    echo "\nTrying with email...\n";
    $result2 = $auth->login('khenjiehbo@gmail.com', 'password123');
    print_r($result2);
}
?>