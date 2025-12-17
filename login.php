<?php
// Login page for Student Notes Hub
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

// Set page title
$pageTitle = 'Login - Student Notes Hub';

// Redirect if already logged in
if (isset($_SESSION['auth_user_id'])) {
    redirect('index.php');
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok($_POST['csrf'] ?? '')) {
        http_response_code(403);
        exit('Bad CSRF token');
    }

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $verificationError = false;
    $userEmail = '';

    if ($email === '' || $password === '') {
        $error = 'Please fill in all fields';
    } else {
        // 1. Try USERS table first
        // Add email_verified column check (handle case where column doesn't exist yet)
        try {
            $pdo->exec("ALTER TABLE users ADD COLUMN email_verified TINYINT(1) DEFAULT 0");
        } catch (Exception $e) {
            // Column might already exist
        }
        
        $stmt = $pdo->prepare('SELECT id, username, email, password_hash, avatar_url, role, COALESCE(email_verified, 0) as email_verified FROM users WHERE email = ? OR username = ?');
        $stmt->execute([$email, $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Check if email is verified - BLOCK LOGIN if not verified
            if (!$user['email_verified']) {
                $error = 'Email verification required';
                $verificationError = true;
                $userEmail = $user['email'];
            } else {
                // Regular user login - only allow if verified
                $_SESSION['auth_user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['avatar_url'] = $user['avatar_url'];
                $_SESSION['role'] = $user['role'];
                $pdo->prepare('UPDATE users SET last_activity = NOW() WHERE id = ?')->execute([$user['id']]);
                redirect('index.php?login=success');
            }
        }

        // 2. If not found in users, try ADMINS table
        $stmt = $pdo->prepare('SELECT id, username, email, password_hash, role FROM admins WHERE email = ? OR username = ?');
        $stmt->execute([$email, $email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            // Admin login
            $_SESSION['auth_admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['admin_role'] = $admin['role'];
            $pdo->prepare('UPDATE admins SET last_login = NOW() WHERE id = ?')->execute([$admin['id']]);
            redirect('admin.php?login=success');
        }

        // 3. Neither table matched
        $error = 'Invalid email/username or password';
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
                    <h1><i class="fas fa-sign-in-alt"></i> Login</h1>
                    <p>Welcome back! Please sign in to your account</p>
                </div>
            </div>
        </section>
        
        <!-- Login Form -->
        <section class="login-section">
            <div class="container">
                <div class="login-container">
                    <div class="login-card">
                        <div class="login-header">
                            <div class="logo">
                                <i class="fas fa-graduation-cap"></i>
                                <span>Student Notes Hub</span>
                            </div>
                            <h2>Sign In</h2>
                            <p>Access your notes and share knowledge</p>
                        </div>
                        
                        <?php if (isset($error)): ?>
                            <?php if (isset($verificationError) && $verificationError): ?>
                                <div class="alert alert-warning" style="background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); color: #d97706;">
                                    <div style="display: flex; align-items: flex-start; gap: 1rem;">
                                        <i class="fas fa-envelope-circle-check" style="font-size: 1.5rem; flex-shrink: 0;"></i>
                                        <div style="flex: 1;">
                                            <h3 style="margin: 0 0 0.5rem 0; font-size: 1.125rem; font-weight: 600;">Email Verification Required</h3>
                                            <p style="margin: 0 0 1rem 0; line-height: 1.6;">
                                                Your account has not been verified yet. Please check your email (<strong><?= htmlspecialchars($userEmail ?? $email) ?></strong>) and click the verification link to activate your account.
                                            </p>
                                            <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                                                <a href="resend-verification.php" class="btn btn-primary btn-sm" style="background: var(--warning-color); border: none; color: white;">
                                                    <i class="fas fa-paper-plane"></i>
                                                    Resend Verification Email
                                                </a>
                                                <a href="verify-email.php" class="btn btn-outline btn-sm">
                                                    <i class="fas fa-envelope"></i>
                                                    Check Verification Status
                                                </a>
                                            </div>
                                            <p style="margin: 1rem 0 0 0; font-size: 0.875rem; color: var(--text-secondary);">
                                                <i class="fas fa-info-circle"></i>
                                                Didn't receive the email? Check your spam folder or contact support.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-error">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <?= htmlspecialchars($error) ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php if (isset($_GET['registered'])): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i>
                                Registration successful! Please login.
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($_GET['verification_required'])): ?>
                            <div class="alert alert-warning" style="background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); color: #d97706;">
                                <div style="display: flex; align-items: flex-start; gap: 1rem;">
                                    <i class="fas fa-shield-exclamation" style="font-size: 1.5rem; flex-shrink: 0;"></i>
                                    <div style="flex: 1;">
                                        <h3 style="margin: 0 0 0.5rem 0; font-size: 1.125rem; font-weight: 600;">Email Verification Required</h3>
                                        <p style="margin: 0 0 1rem 0; line-height: 1.6;">
                                            You must verify your email address before accessing your account. Please check your email and click the verification link.
                                        </p>
                                        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                                            <a href="resend-verification.php" class="btn btn-primary btn-sm" style="background: var(--warning-color); border: none; color: white;">
                                                <i class="fas fa-paper-plane"></i>
                                                Resend Verification Email
                                            </a>
                                            <a href="verify-email.php" class="btn btn-outline btn-sm">
                                                <i class="fas fa-envelope"></i>
                                                Check Verification Status
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($_GET['logout'])): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                You have been logged out successfully.
                            </div>
                        <?php endif; ?>
                        
                        <form method="post" class="login-form">
                            <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
                            
                            <div class="form-group">
                                <label for="email">
                                    <i class="fas fa-envelope"></i>
                                    Email or Username
                                </label>
                                <input type="text" id="email" name="email" required 
                                       placeholder="Enter your email or username"
                                       value="<?= isset($email) ? htmlspecialchars($email) : '' ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="password">
                                    <i class="fas fa-lock"></i>
                                    Password
                                </label>
                                <div class="password-input">
                                    <input type="password" id="password" name="password" required 
                                           placeholder="Enter your password">
                                    <button type="button" class="toggle-password" onclick="togglePassword()">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="form-options">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="remember" value="1">
                                    <span class="checkmark"></span>
                                    Remember me
                                </label>
                                <a href="forgot-password.php" class="forgot-link">Forgot password?</a>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-sign-in-alt"></i>
                                Sign In
                            </button>
                        </form>
                        
                        <div class="login-footer">
                            <p>Don't have an account?</p>
                            <a href="register.php" class="btn btn-outline">
                                <i class="fas fa-user-plus"></i>
                                Create Account
                            </a>
                        </div>
                    </div>
                    
                    <div class="login-info">
                        <h3>Why Join Student Notes Hub?</h3>
                        <ul class="feature-list">
                            <li>
                                <i class="fas fa-upload"></i>
                                <span>Share your notes with the community</span>
                            </li>
                            <li>
                                <i class="fas fa-download"></i>
                                <span>Access notes from other students</span>
                            </li>
                            <li>
                                <i class="fas fa-users"></i>
                                <span>Connect with fellow learners</span>
                            </li>
                            <li>
                                <i class="fas fa-award"></i>
                                <span>Build your academic reputation</span>
                            </li>
                            <li>
                                <i class="fas fa-search"></i>
                                <span>Find notes by category and subject</span>
                            </li>
                            <li>
                                <i class="fas fa-star"></i>
                                <span>Rate and review notes</span>
                            </li>
                        </ul>
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
        
        /* Login Section */
        .login-section {
            padding: 3rem 0;
            background: var(--bg-secondary);
        }
        
        .login-container {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 3rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .login-card {
            background: var(--bg-primary);
            border-radius: var(--border-radius-lg);
            padding: 2.5rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .login-header h2 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }
        
        .login-header p {
            color: var(--text-secondary);
        }
        
        /* Alerts */
        .alert {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: var(--error-color);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        
        .alert-info {
            background: rgba(59, 130, 246, 0.1);
            color: var(--info-color);
            border: 1px solid rgba(59, 130, 246, 0.2);
        }
        
        /* Form Styles */
        .login-form {
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
        
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group input[type="text"] {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            font-size: 1rem;
            background: var(--bg-primary);
            transition: all var(--transition-fast);
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
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
        
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
        
        .checkbox-label input[type="checkbox"] {
            margin: 0;
        }
        
        .forgot-link {
            font-size: 0.875rem;
            color: var(--primary-color);
        }
        
        .forgot-link:hover {
            text-decoration: underline;
        }
        
        .btn-block {
            width: 100%;
        }
        
        /* Login Footer */
        .login-footer {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid var(--border-color);
        }
        
        .login-footer p {
            color: var(--text-secondary);
            margin-bottom: 1rem;
        }
        
        /* Login Info */
        .login-info {
            background: var(--bg-primary);
            border-radius: var(--border-radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
        }
        
        .login-info h3 {
            font-size: 1.25rem;
            margin-bottom: 1.5rem;
            color: var(--text-primary);
        }
        
        .feature-list {
            list-style: none;
            margin-bottom: 2rem;
        }
        
        .feature-list li {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
            color: var(--text-secondary);
        }
        
        .feature-list li i {
            color: var(--primary-color);
            width: 16px;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .login-container {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            
            .login-info {
                order: -1;
            }
            
            .login-card {
                padding: 2rem;
            }
            
            .form-options {
                flex-direction: column;
                align-items: stretch;
                text-align: center;
            }
        }
        
        @media (max-width: 480px) {
            .login-card {
                padding: 1.5rem;
            }
            
            .login-header .logo {
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
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleBtn = document.querySelector('.toggle-password i');
            
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
        
        // Form validation
        document.querySelector('.login-form').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            
            if (!email || !password) {
                e.preventDefault();
                showNotification('Please fill in all fields', 'error');
                return false;
            }
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + Enter to submit form
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                document.querySelector('.login-form').submit();
            }
        });
    </script>
<?php
// Reset variables to avoid conflicts
unset($error, $email);
?>