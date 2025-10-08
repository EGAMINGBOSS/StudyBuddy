<?php
// Include database configuration
require_once "config/database.php";

// Function to execute SQL from a file
function executeSQLFile($filename) {
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Read SQL file
    $sql = file_get_contents($filename);
    
    // Execute multi query
    if ($conn->multi_query($sql)) {
        echo "<p>SQL file executed successfully.</p>";
        
        // Consume all results to free the connection
        do {
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->more_results() && $conn->next_result());
    } else {
        echo "<p>Error executing SQL: " . $conn->error . "</p>";
    }
    
    $conn->close();
}

// Start HTML output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - Student Platform</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header text-center">
                <h2>Database Setup</h2>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <p><strong>Note:</strong> This script will set up the database for the Student Platform.</p>
                    <p>Make sure your MySQL server is running and the credentials in config/database.php are correct.</p>
                </div>
                
                <div class="setup-results">
                    <h4>Setup Results:</h4>
                    <?php
                    // Execute the SQL setup file
                    executeSQLFile("config/setup.sql");
                    ?>
                    
                    <div class="alert alert-success mt-3">
                        <p>Database setup completed!</p>
                        <p>You can now <a href="index.php">go to the homepage</a> and start using the application.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>