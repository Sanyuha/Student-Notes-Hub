<?php
// Registration page for Student Notes Hub
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

// Set page title
$pageTitle = 'Register - Student Notes Hub';

// Redirect if already logged in
if (isset($_SESSION['auth_user_id'])) {
    redirect('index.php');
}

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok($_POST['csrf'] ?? '')) {
        http_response_code(403);
        exit('Bad CSRF token');
    }

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'student';

    // Validation
    $errors = [];

    if ($username === '') {
        $errors[] = 'Username is required';
    } elseif (strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters long';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = 'Username can only contain letters, numbers, and underscores';
    }

    if ($email === '') {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    }

    if ($password === '') {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long';
    } elseif ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match';
    }

    if (!in_array($role, ['student', 'teacher'])) {
        $role = 'student';
    }

    // Check if username or email already exists
    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
        $stmt->execute([$username, $email]);
        $existingUser = $stmt->fetch();

        if ($existingUser) {
            if ($existingUser['username'] === $username) {
                $errors[] = 'Username already taken';
            } else {
                $errors[] = 'Email already registered';
            }
        }
    }

    // Create user if no errors
    if (empty($errors)) {
        try {
            // Add email_verified column if it doesn't exist
            try {
                $pdo->exec("ALTER TABLE users ADD COLUMN email_verified TINYINT(1) DEFAULT 0");
            } catch (Exception $e) {
                // Column might already exist
            }
            
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user with email_verified = 0
            $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, role, member_since, email_verified) VALUES (?, ?, ?, ?, CURDATE(), 0)');
            $stmt->execute([$username, $email, $passwordHash, $role]);
            
            $userId = $pdo->lastInsertId();
            
            // Send verification email
            require_once __DIR__ . '/api/send-verification-email.php';
            $result = sendVerificationEmail($pdo, $userId, $email, $username);
            
            if ($result['success']) {
                // Redirect to verification page with success message
                redirect('verify-email.php?registered=1&email=' . urlencode($email));
            } else {
                // Registration succeeded but email failed - still show success but warn about email
                $errors[] = 'Account created, but verification email could not be sent. Please use the resend verification link.';
            }
        } catch (Exception $e) {
            $errors[] = 'Registration failed. Please try again.';
        }
    }
}

$_SESSION['csrf'] = csrf();

// Include header
require __DIR__ . '/components/header.php';
?>

    <!-- Main Content -->
    <main>
        <!-- Page Header -->
        <section class="page-header">
            <div class="container">
                <div class="header-content">
                    <h1><i class="fas fa-user-plus"></i> Create Account</h1>
                    <p>Join our community of students and educators</p>
                </div>
            </div>
        </section>
        
        <!-- Registration Form -->
        <section class="register-section">
            <div class="container">
                <div class="register-container">
                    <div class="register-card">
                        <div class="register-header">
                            <div class="logo">
                                <i class="fas fa-graduation-cap"></i>
                                <span>Student Notes Hub</span>
                            </div>
                            <h2>Create Your Account</h2>
                            <p>Start sharing knowledge and learning together</p>
                        </div>
                        
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-error">
                                <i class="fas fa-exclamation-circle"></i>
                                <ul>
                                    <?php foreach ($errors as $error): ?>
                                        <li><?= htmlspecialchars($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <form method="post" class="register-form">
                            <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
                            
                            <div class="form-group">
                                <label for="username">
                                    <i class="fas fa-user"></i>
                                    Username
                                </label>
                                <input type="text" id="username" name="username" required 
                                       placeholder="Choose a username"
                                       value="<?= isset($username) ? htmlspecialchars($username) : '' ?>">
                                <small class="form-help">3+ characters, letters, numbers, and underscores only</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">
                                    <i class="fas fa-envelope"></i>
                                    Email Address
                                </label>
                                <input type="email" id="email" name="email" required 
                                       placeholder="Enter your email address"
                                       value="<?= isset($email) ? htmlspecialchars($email) : '' ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="password">
                                    <i class="fas fa-lock"></i>
                                    Password
                                </label>
                                <div class="password-input">
                                    <input type="password" id="password" name="password" required 
                                           placeholder="Create a password">
                                    <button type="button" class="toggle-password" onclick="togglePassword('password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <small class="form-help">At least 6 characters</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">
                                    <i class="fas fa-lock"></i>
                                    Confirm Password
                                </label>
                                <div class="password-input">
                                    <input type="password" id="confirm_password" name="confirm_password" required 
                                           placeholder="Confirm your password">
                                    <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="role">
                                    <i class="fas fa-graduation-cap"></i>
                                    I Am A
                                </label>
                                <select id="role" name="role" required>
                                    <option value="student" <?= (isset($role) && $role === 'student') ? 'selected' : '' ?>>Student</option>
                                    <option value="teacher" <?= (isset($role) && $role === 'teacher') ? 'selected' : '' ?>>Teacher</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="checkbox-label terms-label">
                                    <input type="checkbox" name="terms" value="1" required>
                                    <span class="checkmark"></span>
                                    I agree to the <a href="terms.php" target="_blank">Terms of Service</a> 
                                    and <a href="privacy.php" target="_blank">Privacy Policy</a>
                                </label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-user-plus"></i>
                                Create Account
                            </button>
                        </form>
                        
                        <div class="register-footer">
                            <p>Already have an account?</p>
                            <a href="login.php" class="btn btn-outline">
                                <i class="fas fa-sign-in-alt"></i>
                                Sign In
                            </a>
                        </div>
                    </div>
                    
                    <div class="register-info">
                        <h3>Why Join Our Community?</h3>
                        <div class="benefits-grid">
                            <div class="benefit">
                                <div class="benefit-icon">
                                    <i class="fas fa-upload"></i>
                                </div>
                                <h4>Share Your Notes</h4>
                                <p>Upload and share your study notes with students worldwide</p>
                            </div>
                            
                            <div class="benefit">
                                <div class="benefit-icon">
                                    <i class="fas fa-download"></i>
                                </div>
                                <h4>Access Resources</h4>
                                <p>Download high-quality notes from other students and educators</p>
                            </div>
                            
                            <div class="benefit">
                                <div class="benefit-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <h4>Connect & Collaborate</h4>
                                <p>Join study groups and connect with like-minded learners</p>
                            </div>
                            
                            <div class="benefit">
                                <div class="benefit-icon">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <h4>Track Progress</h4>
                                <p>Monitor your learning progress and see your contributions</p>
                            </div>
                            
                            <div class="benefit">
                                <div class="benefit-icon">
                                    <i class="fas fa-award"></i>
                                </div>
                                <h4>Build Reputation</h4>
                                <p>Earn points and badges for quality contributions</p>
                            </div>
                            
                            <div class="benefit">
                                <div class="benefit-icon">
                                    <i class="fas fa-mobile-alt"></i>
                                </div>
                                <h4>Mobile Friendly</h4>
                                <p>Access your notes anywhere, anytime on any device</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

<?php
// Include footer
require __DIR__ . '/components/footer.php';
?>

    <!-- Additional Styles -->
    <style>
        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 3rem 0 2rem;
            text-align: center;
        }
        
        .header-content h1 {
            font-size: clamp(1.75rem, 4vw, 2.5rem);
            margin-bottom: 0.5rem;
            font-weight: 700;
        }
        
        .header-content p {
            font-size: 1.125rem;
            opacity: 0.9;
        }
        
        /* Register Section */
        .register-section {
            padding: 3rem 0;
            background: var(--bg-secondary);
        }
        
        .register-container {
            display: grid;
            grid-template-columns: 1fr 500px;
            gap: 3rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .register-card {
            background: var(--bg-primary);
            border-radius: var(--border-radius-lg);
            padding: 2.5rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .register-header .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .register-header h2 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }
        
        .register-header p {
            color: var(--text-secondary);
        }
        
        /* Alert Styles */
        .alert {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            border: 1px solid;
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: var(--error-color);
            border-color: rgba(239, 68, 68, 0.2);
        }
        
        .alert ul {
            margin: 0;
            padding-left: 1.5rem;
        }
        
        .alert li {
            margin-bottom: 0.25rem;
        }
        
        /* Form Styles */
        .register-form {
            margin-bottom: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            font-size: 1rem;
            background: var(--bg-primary);
            transition: all var(--transition-fast);
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .form-help {
            display: block;
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
        }
        
        .password-input {
            position: relative;
        }
        
        .toggle-password {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 0.25rem;
        }
        
        .toggle-password:hover {
            color: var(--primary-color);
        }
        
        .terms-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
        
        .terms-label a {
            color: var(--primary-color);
        }
        
        .terms-label a:hover {
            text-decoration: underline;
        }
        
        .btn-block {
            width: 100%;
        }
        
        /* Register Footer */
        .register-footer {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid var(--border-color);
        }
        
        .register-footer p {
            color: var(--text-secondary);
            margin-bottom: 1rem;
        }
        
        /* Register Info */
        .register-info {
            background: var(--bg-primary);
            border-radius: var(--border-radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
        }
        
        .register-info h3 {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            color: var(--text-primary);
        }
        
        .benefits-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .benefit {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1rem;
            background: var(--bg-secondary);
            border-radius: var(--border-radius);
            transition: all var(--transition-normal);
        }
        
        .benefit:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .benefit-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1rem;
            flex-shrink: 0;
        }
        
        .benefit h4 {
            font-size: 1rem;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }
        
        .benefit p {
            font-size: 0.875rem;
            color: var(--text-secondary);
            line-height: 1.4;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .register-container {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            
            .register-info {
                order: -1;
            }
            
            .register-card {
                padding: 2rem;
            }
        }
        
        @media (max-width: 480px) {
            .register-card {
                padding: 1.5rem;
            }
            
            .register-header .logo {
                font-size: 1.25rem;
            }
            
            .page-header {
                padding: 2rem 0 1rem;
            }
            
            .header-content h1 {
                font-size: 1.5rem;
            }
        }
    </style>
    
    <script>
        // Toggle password visibility
        function togglePassword(fieldId) {
            const passwordInput = document.getElementById(fieldId);
            const toggleBtn = passwordInput.parentElement.querySelector('.toggle-password i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleBtn.classList.remove('fa-eye');
                toggleBtn.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleBtn.classList.remove('fa-eye-slash');
                toggleBtn.classList.add('fa-eye');
            }
        }
        
        // Password strength indicator
        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthIndicator = document.getElementById('password-strength');
            
            if (!password) {
                strengthIndicator.style.display = 'none';
                return;
            }
            
            strengthIndicator.style.display = 'block';
            let strength = 0;
            
            if (password.length >= 6) strength++;
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            const strengthLabels = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong', 'Very Strong'];
            const strengthColors = ['#ef4444', '#f59e0b', '#f59e0b', '#10b981', '#10b981', '#059669'];
            
            strengthIndicator.textContent = `Password strength: ${strengthLabels[strength] || 'Very Weak'}`;
            strengthIndicator.style.color = strengthColors[strength] || '#ef4444';
        }
        
        // Add password strength checker
        document.getElementById('password').addEventListener('input', checkPasswordStrength);
        
        // Form validation
        document.querySelector('.register-form').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const terms = document.querySelector('input[name="terms"]').checked;
            
            if (!username || !email || !password || !confirmPassword) {
                e.preventDefault();
                showNotification('Please fill in all required fields', 'error');
                return false;
            }
            
            if (password !== confirmPassword) {
                e.preventDefault();
                showNotification('Passwords do not match', 'error');
                return false;
            }
            
            if (!terms) {
                e.preventDefault();
                showNotification('Please agree to the Terms of Service and Privacy Policy', 'error');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                showNotification('Password must be at least 6 characters long', 'error');
                return false;
            }
            
            if (!isValidEmail(email)) {
                e.preventDefault();
                showNotification('Please enter a valid email address', 'error');
                return false;
            }
        });
        
        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }
        
        // Username availability check
        function checkUsernameAvailability() {
            const username = document.getElementById('username').value.trim();
            if (username.length < 3) return;
            
            // Simulate username check (in real app, this would be an AJAX call)
            const unavailableUsernames = ['admin', 'root', 'system', 'user'];
            const availabilityIndicator = document.getElementById('username-availability');
            
            if (unavailableUsernames.includes(username.toLowerCase())) {
                availabilityIndicator.textContent = 'Username not available';
                availabilityIndicator.style.color = 'var(--error-color)';
                availabilityIndicator.style.display = 'block';
            } else {
                availabilityIndicator.textContent = 'Username available';
                availabilityIndicator.style.color = 'var(--success-color)';
                availabilityIndicator.style.display = 'block';
            }
        }
        
        // Add username availability check
        document.getElementById('username').addEventListener('blur', checkUsernameAvailability);
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + Enter to submit form
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                document.querySelector('.register-form').submit();
            }
        });
    </script>
<?php
// Reset variables to avoid conflicts
unset($errors, $username, $email, $role);
?>