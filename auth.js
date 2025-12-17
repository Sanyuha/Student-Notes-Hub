// Authentication JavaScript
document.addEventListener('DOMContentLoaded', function() {
    initAuthTabs();
    initAuthForms();
    initSocialLogin();
    initPasswordToggle();
});

function initAuthTabs() {
    const authTabs = document.querySelectorAll('.auth-tab');
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    
    authTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const tabType = this.dataset.tab;
            
            // Update active tab
            authTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            // Show/hide forms
            if (tabType === 'login') {
                loginForm.classList.add('active');
                registerForm.classList.remove('active');
            } else {
                loginForm.classList.remove('active');
                registerForm.classList.add('active');
            }
        });
    });
}

function initAuthForms() {
    // Login form
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handleLogin();
        });
    }
    
    // Register form
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handleRegister();
        });
    }
}

function handleLogin() {
    const email = document.getElementById('loginEmail').value;
    const password = document.getElementById('loginPassword').value;
    const rememberMe = document.getElementById('rememberMe').checked;
    
    // Simulate login
    const user = {
        id: 1,
        username: email.split('@')[0],
        email: email,
        avatar: 'https://via.placeholder.com/150',
        bio: 'Student at Student Notes Hub'
    };
    
    localStorage.setItem('user', JSON.stringify(user));
    
    // Show success message
    showNotification('Login successful! Redirecting...', 'success');
    
    setTimeout(() => {
        window.location.href = 'index.html';
    }, 1500);
}

function handleRegister() {
    const username = document.getElementById('registerUsername').value;
    const email = document.getElementById('registerEmail').value;
    const password = document.getElementById('registerPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    const userType = document.getElementById('userType').value;
    
    // Validate passwords
    if (password !== confirmPassword) {
        showNotification('Passwords do not match!', 'error');
        return;
    }
    
    if (password.length < 6) {
        showNotification('Password must be at least 6 characters!', 'error');
        return;
    }
    
    // Simulate registration
    const user = {
        id: Date.now(),
        username: username,
        email: email,
        userType: userType,
        avatar: 'https://via.placeholder.com/150',
        bio: 'New student at Student Notes Hub'
    };
    
    localStorage.setItem('user', JSON.stringify(user));
    
    // Show success message
    showNotification('Registration successful! Welcome to Student Notes Hub!', 'success');
    
    setTimeout(() => {
        window.location.href = 'index.html';
    }, 2000);
}

function initSocialLogin() {
    const socialButtons = document.querySelectorAll('.social-btn');
    socialButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const provider = this.classList.contains('google') ? 'Google' : 'GitHub';
            showNotification(`Logging in with ${provider}...`, 'info');
            
            // Simulate social login
            setTimeout(() => {
                const user = {
                    id: Date.now(),
                    username: `${provider.toLowerCase()}_user`,
                    email: `user@${provider.toLowerCase()}.com`,
                    avatar: 'https://via.placeholder.com/150',
                    bio: `Student logged in via ${provider}`
                };
                
                localStorage.setItem('user', JSON.stringify(user));
                window.location.href = 'index.html';
            }, 1500);
        });
    });
}

function initPasswordToggle() {
    const toggleButtons = document.querySelectorAll('.toggle-password');
    
    toggleButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const input = this.previousElementSibling;
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });
}

function showNotification(message, type) {
    // Get or create notification container
    let container = document.getElementById('notificationContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'notificationContainer';
        document.body.appendChild(container);
    }
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
        <span>${message}</span>
    `;
    
    // Add to container
    container.appendChild(notification);
    
    // Remove after 5 seconds with animation
    setTimeout(() => {
        notification.classList.add('removing');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
            // Remove container if empty
            if (container && container.children.length === 0) {
                container.remove();
            }
        }, 300);
    }, 5000);
}