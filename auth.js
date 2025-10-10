document.addEventListener('DOMContentLoaded', function() {
    const tabBtns = document.querySelectorAll('.tab-btn');
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');

    // Tab switching functionality
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const tab = this.getAttribute('data-tab');
            
            // Remove active class from all tabs
            tabBtns.forEach(b => b.classList.remove('active'));
            
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Clear all error messages when switching tabs
            clearErrors();
            
            // Show/hide forms
            if (tab === 'login') {
                loginForm.classList.remove('hidden');
                registerForm.classList.add('hidden');
            } else {
                loginForm.classList.add('hidden');
                registerForm.classList.remove('hidden');
            }
        });
    });

    // Function to show error message
    function showError(messageId, message) {
        const errorElement = document.getElementById(messageId);
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.style.display = 'block';
        }
    }

    // Function to clear error messages
    function clearErrors() {
        const errorMessages = document.querySelectorAll('.error-message');
        errorMessages.forEach(error => {
            error.style.display = 'none';
            error.textContent = '';
        });
    }

    // Email validation
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    // Username validation
    function validateUsername(username) {
        const errors = [];
        
        if (username.length < 3) {
            errors.push('Username must be at least 3 characters long');
        }
        if (username.length > 20) {
            errors.push('Username must be no more than 20 characters long');
        }
        if (!/^[a-zA-Z0-9_]+$/.test(username)) {
            errors.push('Username can only contain letters, numbers, and underscores');
        }
        if (/\s/.test(username)) {
            errors.push('Username cannot contain spaces');
        }
        
        return errors;
    }

    // Password validation
    function validatePassword(password) {
        const errors = [];
        
        if (password.length < 8) {
            errors.push('Password must be at least 8 characters long');
        }
        if (!/[A-Z]/.test(password)) {
            errors.push('Password must contain at least one uppercase letter');
        }
        if (!/[a-z]/.test(password)) {
            errors.push('Password must contain at least one lowercase letter');
        }
        if (!/[0-9]/.test(password)) {
            errors.push('Password must contain at least one number');
        }
        if (/\s/.test(password)) {
            errors.push('Password cannot contain spaces');
        }
        if (!/^[a-zA-Z0-9!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]+$/.test(password)) {
            errors.push('Password contains invalid characters');
        }
        
        return errors;
    }

    // Login form validation
    const loginFormElement = document.getElementById('loginForm');
    loginFormElement.addEventListener('submit', function(e) {
        clearErrors();
        
        const email = this.querySelector('input[name="email"]').value.trim();
        const password = this.querySelector('input[name="password"]').value;
        
        let hasErrors = false;
        
        // Email validation
        if (!email) {
            showError('error-message', 'Email is required');
            hasErrors = true;
        } else if (!isValidEmail(email)) {
            showError('error-message', 'Please enter a valid email address');
            hasErrors = true;
        }
        
        // Password validation
        if (!password) {
            showError('error-message', 'Password is required');
            hasErrors = true;
        }
        
        if (hasErrors) {
            e.preventDefault();
        }
    });

    // Register form validation
    const registerFormElement = document.getElementById('registerForm');
    registerFormElement.addEventListener('submit', function(e) {
        clearErrors();
        
        const username = this.querySelector('input[name="username"]').value.trim();
        const email = this.querySelector('input[name="email"]').value.trim();
        const password = this.querySelector('input[name="password"]').value;
        const confirmPassword = this.querySelector('input[name="confirm_password"]').value;
        
        let hasErrors = false;
        let errorMessages = [];
        
        // Username validation
        const usernameErrors = validateUsername(username);
        if (usernameErrors.length > 0) {
            errorMessages.push(...usernameErrors);
            hasErrors = true;
        }
        
        // Email validation
        if (!email) {
            errorMessages.push('Email is required');
            hasErrors = true;
        } else if (!isValidEmail(email)) {
            errorMessages.push('Please enter a valid email address');
            hasErrors = true;
        }
        
        // Password validation
        if (!password) {
            errorMessages.push('Password is required');
            hasErrors = true;
        } else {
            const passwordErrors = validatePassword(password);
            if (passwordErrors.length > 0) {
                errorMessages.push(...passwordErrors);
                hasErrors = true;
            }
        }
        
        // Confirm password validation
        if (!confirmPassword) {
            errorMessages.push('Please confirm your password');
            hasErrors = true;
        } else if (password !== confirmPassword) {
            errorMessages.push('Passwords do not match');
            hasErrors = true;
        }
        
        // Display errors
        if (hasErrors) {
            e.preventDefault();
            showError('register-error-message', errorMessages.join('\n'));
        }
    });

    // Real-time validation feedback
    const inputs = document.querySelectorAll('input[type="text"], input[type="email"], input[type="password"]');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            const value = this.value.trim();
            const name = this.name;
            
            // Clear previous individual field errors (if any)
            this.classList.remove('input-error');
            
            // Validate individual fields
            if (name === 'email' && value && !isValidEmail(value)) {
                this.classList.add('input-error');
            } else if (name === 'username' && value) {
                const errors = validateUsername(value);
                if (errors.length > 0) {
                    this.classList.add('input-error');
                }
            } else if (name === 'password' && value) {
                const errors = validatePassword(value);
                if (errors.length > 0) {
                    this.classList.add('input-error');
                }
            }
        });
        
        // Remove error styling when user starts typing
        input.addEventListener('input', function() {
            this.classList.remove('input-error');
        });

        // Real-time input filtering for username and password
        input.addEventListener('input', function() {
            const name = this.name;
            const value = this.value;
            
            if (name === 'username') {
                // Remove any characters that aren't letters, numbers, or underscores
                this.value = value.replace(/[^a-zA-Z0-9_]/g, '');
            } else if (name === 'password' || name === 'confirm_password') {
                // Remove spaces and invalid characters from password
                this.value = value.replace(/\s/g, '').replace(/[^a-zA-Z0-9!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/g, '');
            }
        });

        // Prevent pasting invalid content
        input.addEventListener('paste', function(e) {
            const name = this.name;
            
            setTimeout(() => {
                if (name === 'username') {
                    // Clean username field after paste
                    this.value = this.value.replace(/[^a-zA-Z0-9_]/g, '');
                } else if (name === 'password' || name === 'confirm_password') {
                    // Clean password field after paste
                    this.value = this.value.replace(/\s/g, '').replace(/[^a-zA-Z0-9!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/g, '');
                }
            }, 10);
        });
    });
});