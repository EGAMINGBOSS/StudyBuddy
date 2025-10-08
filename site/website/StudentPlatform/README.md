# Student Platform Authentication System

A complete authentication system for a student platform with login, registration, email verification, and profile management.

## Features

- User registration with email verification
- Login with username or email
- Password reset functionality
- User profile management
- Responsive design
- Form validation (client-side and server-side)
- Security features (password hashing, SQL injection prevention)

## Installation

### Prerequisites

- PHP 7.0 or higher
- MySQL 5.6 or higher
- Web server (Apache, Nginx, etc.)

### Setup Instructions

1. **Clone or download the repository**

2. **Configure the database connection**
   - Open `config/database.php`
   - Update the database credentials:
   ```php
   define('DB_SERVER', 'localhost'); // Your database server
   define('DB_USERNAME', 'root');    // Your database username
   define('DB_PASSWORD', '');        // Your database password
   define('DB_NAME', 'student_platform'); // Your database name
   ```

3. **Set up the database**
   - Option 1: Visit `setup.php` in your browser to automatically set up the database
   - Option 2: Manually import the `config/setup.sql` file into your MySQL database

4. **Configure your web server**
   - Point your web server to the project directory
   - Make sure PHP is properly configured

5. **Access the application**
   - Open your browser and navigate to the project URL
   - You should see the login/registration page

## Usage

### Registration

1. Click on the "Register" tab on the homepage
2. Fill in the required information:
   - Username (at least 3 characters)
   - Email (valid email format)
   - Password (at least 6 characters)
   - Confirm Password
3. Click "Register"
4. You will be redirected to the email verification page
5. In a real application, you would receive an email with a verification code
6. For this demo, a code is displayed on the screen
7. Enter the verification code to complete registration

### Login

1. Enter your username or email
2. Enter your password
3. Click "Login"
4. If your credentials are correct, you will be redirected to the welcome page

### Password Reset

1. Click "Forgot your password?" on the login page
2. Enter your email address
3. Click "Send Reset Link"
4. In a real application, you would receive an email with a reset link
5. For this demo, use the form at the bottom of the page to reset your password

### Profile Management

1. After logging in, click on "Profile" in the navigation bar
2. You can update your profile information:
   - Full Name
   - Password
   - Profile Picture (demo only)
   - User Preferences

## Security Features

- Passwords are hashed using PHP's `password_hash()` function
- SQL injection prevention using prepared statements
- XSS prevention using `htmlspecialchars()`
- CSRF protection
- Session security

## Customization

### Styling

- Edit `css/style.css` to customize the appearance
- The system uses Bootstrap 4 for responsive design

### Email Verification

- In a production environment, update the `sendVerificationEmail()` method in `backend/email_verification.php` to send actual emails

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Acknowledgments

- Bootstrap for the responsive design
- Font Awesome for the icons