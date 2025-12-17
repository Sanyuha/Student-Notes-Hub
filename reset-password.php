<?php
// Reset password page for Student Notes Hub
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

// Set page title
$pageTitle = 'Reset Password - Student Notes Hub';

// Redirect if already logged in
if (isset($_SESSION['auth_user_id'])) {
    redirect('index.php');
}

// Get and decode the token from URL
$token = isset($_GET['token']) ? urldecode(trim($_GET['token'])) : '';
$error = null;
$message = null;
$validToken = false;
$userId = null;

// Verify token
if ($token) {
    try {
        // Create password_reset_tokens table if it doesn't exist
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS password_reset_tokens (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id INT UNSIGNED NOT NULL,
                token VARCHAR(64) NOT NULL,
                expires_at TIMESTAMP NOT NULL,
                used TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY unique_token (token),
                KEY idx_user (user_id),
                KEY idx_expires (expires_at),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Clean the token (remove any whitespace)
        $token = trim($token);
        
        // Validate token format (should be 64 hex characters)
        if (strlen($token) !== 64 || !ctype_xdigit($token)) {
            $error = 'Invalid token format. Please use the link from your email.';
        } else {
            // Check if token exists and is valid
            $stmt = $pdo->prepare('
                SELECT prt.user_id, u.email, u.username, prt.expires_at, prt.used
                FROM password_reset_tokens prt
                JOIN users u ON u.id = prt.user_id
                WHERE prt.token = ? 
            ');
            $stmt->execute([$token]);
            $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($tokenData) {
                // Check if token is used
                if ($tokenData['used']) {
                    $error = 'This reset link has already been used. Please request a new password reset link.';
                }
                // Check if token is expired
                elseif (strtotime($tokenData['expires_at']) <= time()) {
                    $error = 'This reset link has expired. Please request a new password reset link.';
                } else {
                    $validToken = true;
                    $userId = $tokenData['user_id'];
                }
            } else {
                $error = 'Invalid reset token. Please check the link and try again, or request a new password reset link.';
            }
        }
    } catch (Exception $e) {
        $error = 'Error verifying reset token. Please try again.';
    }
} else {
    $error = 'No reset token provided.';
}

// Handle password reset form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    if (!csrf_ok($_POST['csrf'] ?? '')) {
        http_response_code(403);
        exit('Bad CSRF token');
    }

    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($password === '' || $confirmPassword === '') {
        $error = 'Please fill in all fields';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Update password
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $updateStmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $updateStmt->execute([$passwordHash, $userId]);
            
            // Mark token as used
            $markStmt = $pdo->prepare('UPDATE password_reset_tokens SET used = 1 WHERE token = ?');
            $markStmt->execute([$token]);
            
            $pdo->commit();
            
            $message = 'Password has been reset successfully! You can now login with your new password.';
            $validToken = false; // Hide form after success
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Failed to reset password. Please try again.';
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
                    <h1><i class="fas fa-key"></i> Reset Password</h1>
                    <p>Enter your new password</p>
                </div>
            </div>
        </section>
        
        <!-- Reset Password Form -->
        <section class="reset-password-section">
            <div class="container">
                <div class="reset-password-container">
                    <div class="reset-password-card">
                        <div class="reset-password-header">
                            <div class="logo">
                                <i class="fas fa-graduation-cap"></i>
                                <span>Student Notes Hub</span>
                            </div>
                            <h2>Set New Password</h2>
                            <p>Choose a strong password for your account</p>
                        </div>
                        
                        <?php if ($message): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i>
                                <?= htmlspecialchars($message) ?>
                                <div style="margin-top: 1rem;">
                                    <a href="login.php" class="btn btn-primary">
                                        <i class="fas fa-sign-in-alt"></i>
                                        Go to Login
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-error">
                                <i class="fas fa-exclamation-circle"></i>
                                <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($validToken && !$message): ?>
                            <form method="post" class="reset-password-form">
                                <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
                                
                                <div class="form-group">
                                    <label for="password">
                                        <i class="fas fa-lock"></i>
                                        New Password
                                    </label>
                                    <div class="password-input">
                                        <input type="password" id="password" name="password" required 
                                               placeholder="Enter your new password"
                                               minlength="6">
                                        <button type="button" class="toggle-password" onclick="togglePassword('password')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <small class="form-help">At least 6 characters</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="confirm_password">
                                        <i class="fas fa-lock"></i>
                                        Confirm New Password
                                    </label>
                                    <div class="password-input">
                                        <input type="password" id="confirm_password" name="confirm_password" required 
                                               placeholder="Confirm your new password"
                                               minlength="6">
                                        <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-save"></i>
                                    Reset Password
                                </button>
                            </form>
                        <?php elseif (!$validToken && !$message): ?>
                            <div class="alert alert-error">
                                <i class="fas fa-exclamation-circle"></i>
                                Invalid or expired reset token. Please <a href="forgot-password.php">request a new password reset link</a>.
                            </div>
                        <?php endif; ?>
                        
                        <div class="reset-password-footer">
                            <a href="login.php" class="btn btn-outline">
                                <i class="fas fa-arrow-left"></i>
                                Back to Login
                            </a>
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
        
        /* Reset Password Section */
        .reset-password-section {
            padding: 3rem 0;
            background: var(--bg-secondary);
        }
        
        .reset-password-container {
            max-width: 500px;
            margin: 0 auto;
        }
        
        .reset-password-card {
            background: var(--bg-primary);
            border-radius: var(--border-radius-lg);
            padding: 2.5rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
        }
        
        .reset-password-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .reset-password-header .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .reset-password-header h2 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }
        
        .reset-password-header p {
            color: var(--text-secondary);
        }
        
        /* Alerts */
        .alert {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
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
        
        .alert a {
            color: inherit;
            text-decoration: underline;
        }
        
        /* Form Styles */
        .reset-password-form {
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
        
        .form-group input[type="password"] {
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
        
        .form-help {
            display: block;
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
        }
        
        .btn-block {
            width: 100%;
        }
        
        /* Footer */
        .reset-password-footer {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid var(--border-color);
        }
        
        /* Responsive Design */
        @media (max-width: 480px) {
            .reset-password-card {
                padding: 1.5rem;
            }
            
            .reset-password-header .logo {
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
    </script>
<?php
// Reset variables to avoid conflicts
unset($error, $message, $token, $validToken, $userId);
?>

