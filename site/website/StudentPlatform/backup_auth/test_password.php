<?php
$hash = '$2y$10$O86RtMO3DzioRO5./4EF0uB9ZrIeZrB8Dx/BsFmm5GtW/C7JHtaii';
$passwords_to_test = ['password123', 'Password123', 'admin123', 'khenjie'];

foreach ($passwords_to_test as $password) {
    if (password_verify($password, $hash)) {
        echo "Password '$password' matches!\n";
    } else {
        echo "Password '$password' does not match.\n";
    }
}
?>