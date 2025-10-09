document.addEventListener('DOMContentLoaded', function() {
    const tabBtns = document.querySelectorAll('.tab-btn');
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const tab = this.getAttribute('data-tab');
            
            // Remove active class from all tabs
            tabBtns.forEach(b => b.classList.remove('active'));
            
            // Add active class to clicked tab
            this.classList.add('active');
            
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
    function showError(form, message) {
        // Remove existing error messages
        const existingError = form.querySelector('.error-message');
        if (existingError) {
            existingError.remove();
        }

        // Create new error message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.textContent = message;
        
        // Insert error message at the top of the form
        form.insertBefore(errorDiv, form.firstChild);
    }

    // Form validation
    const registerFormElement = document.getElementById('registerForm');
    registerFormElement.addEventListener('submit', function(e) {
        const password = this.querySelector('input[name="password"]').value;
        const confirmPassword = this.querySelector('input[name="confirm_password"]').value;
        
        if (password !== confirmPassword) {
            e.preventDefault();
            showError(registerForm, 'Passwords do not match!');
        }
    });
});