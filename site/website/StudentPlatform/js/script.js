// Wait for the document to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Form validation for login
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            let isValid = true;
            
            // Clear previous error messages
            document.querySelectorAll('.help-block').forEach(el => el.textContent = '');
            
            // Validate username
            if (username === '') {
                document.getElementById('username_err').textContent = 'Please enter your username.';
                isValid = false;
            }
            
            // Validate password
            if (password === '') {
                document.getElementById('password_err').textContent = 'Please enter your password.';
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    }
    
    // Form validation for registration
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value.trim();
            const confirmPassword = document.getElementById('confirm_password').value.trim();
            let isValid = true;
            
            // Clear previous error messages
            document.querySelectorAll('.help-block').forEach(el => el.textContent = '');
            
            // Validate username
            if (username === '') {
                document.getElementById('username_err').textContent = 'Please enter a username.';
                isValid = false;
            } else if (username.length < 3) {
                document.getElementById('username_err').textContent = 'Username must be at least 3 characters.';
                isValid = false;
            }
            
            // Validate email
            if (email === '') {
                document.getElementById('email_err').textContent = 'Please enter your email.';
                isValid = false;
            } else if (!isValidEmail(email)) {
                document.getElementById('email_err').textContent = 'Please enter a valid email address.';
                isValid = false;
            }
            
            // Validate password
            if (password === '') {
                document.getElementById('password_err').textContent = 'Please enter a password.';
                isValid = false;
            } else if (password.length < 6) {
                document.getElementById('password_err').textContent = 'Password must be at least 6 characters.';
                isValid = false;
            }
            
            // Validate confirm password
            if (confirmPassword === '') {
                document.getElementById('confirm_password_err').textContent = 'Please confirm your password.';
                isValid = false;
            } else if (password !== confirmPassword) {
                document.getElementById('confirm_password_err').textContent = 'Passwords do not match.';
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    }
    
    // Verification code form
    const verifyForm = document.getElementById('verifyForm');
    if (verifyForm) {
        const codeInput = document.getElementById('verification_code');
        if (codeInput) {
            // Auto-submit when code length is reached
            codeInput.addEventListener('input', function() {
                if (this.value.length === 6) {
                    verifyForm.submit();
                }
            });
        }
    }
});

// Email validation helper function
function isValidEmail(email) {
    const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(String(email).toLowerCase());
}