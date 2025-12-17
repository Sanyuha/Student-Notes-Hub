<?php
// Email verification page for Student Notes Hub
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

// Set page title
$pageTitle = 'Verify Email - Student Notes Hub';

// Redirect if already logged in
if (isset($_SESSION['auth_user_id'])) {
    redirect('index.php');
}

// Get token from URL - try multiple methods
$token = '';
if (isset($_GET['token'])) {
    $token = urldecode(trim($_GET['token']));
} elseif (isset($_REQUEST['token'])) {
    $token = urldecode(trim($_REQUEST['token']));
}

$error = null;
$message = null;
$verified = false;
$registered = isset($_GET['registered']) && $_GET['registered'] == '1';
$registeredEmail = isset($_GET['email']) ? htmlspecialchars(urldecode($_GET['email'])) : '';

// Verify token
if ($token && strlen($token) > 0) {
    try {
        // Create email_verification_tokens table if it doesn't exist
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS email_verification_tokens (
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
        
        // Add email_verified column to users table if it doesn't exist
        try {
            $pdo->exec("ALTER TABLE users ADD COLUMN email_verified TINYINT(1) DEFAULT 0");
        } catch (Exception $e) {
            // Column might already exist
        }
        
        // Clean the token
        $token = trim($token);
        
        // Validate token format (should be 64 hex characters)
        if (strlen($token) !== 64 || !ctype_xdigit($token)) {
            $error = 'Invalid verification link. Please use the link from your email.';
        } else {
            // Check if token exists and is valid
            $stmt = $pdo->prepare('
                SELECT evt.user_id, u.email, u.username, evt.expires_at, evt.used
                FROM email_verification_tokens evt
                JOIN users u ON u.id = evt.user_id
                WHERE evt.token = ? 
            ');
            $stmt->execute([$token]);
            $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($tokenData) {
                // Check if token is used
                if ($tokenData['used']) {
                    $error = 'This verification link has already been used.';
                    // Check if email is already verified
                    $checkStmt = $pdo->prepare('SELECT email_verified FROM users WHERE id = ?');
                    $checkStmt->execute([$tokenData['user_id']]);
                    $userData = $checkStmt->fetch(PDO::FETCH_ASSOC);
                    if ($userData && $userData['email_verified']) {
                        $message = 'Your email is already verified. You can now log in.';
                        $verified = true;
                    }
                }
                // Check if token is expired
                elseif (strtotime($tokenData['expires_at']) <= time()) {
                    $error = 'This verification link has expired. Please request a new verification email.';
                } else {
                    // Verify the email
                    $pdo->beginTransaction();
                    
                    // Mark email as verified
                    $updateStmt = $pdo->prepare('UPDATE users SET email_verified = 1 WHERE id = ?');
                    $updateStmt->execute([$tokenData['user_id']]);
                    
                    // Mark token as used
                    $markStmt = $pdo->prepare('UPDATE email_verification_tokens SET used = 1 WHERE token = ?');
                    $markStmt->execute([$token]);
                    
                    $pdo->commit();
                    
                    $message = 'Your email has been verified successfully! You can now log in to your account.';
                    $verified = true;
                }
            } else {
                $error = 'Invalid verification link. Please check the link and try again, or request a new verification email.';
            }
        }
    } catch (Exception $e) {
        $error = 'Error verifying email. Please try again.';
    }
} elseif (!$registered) {
    // Only show error if user didn't come from registration page
    // If they came from registration, we'll show the success message instead
    $error = 'No verification token provided. Please use the link from your verification email.';
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
                    <h1><i class="fas fa-envelope-check"></i> Verify Email</h1>
                    <p>Confirm your email address to complete registration</p>
                </div>
            </div>
        </section>
        
        <!-- Verification Status -->
        <section class="verification-section">
            <div class="container">
                <div class="verification-container">
                    <div class="verification-card">
                        <div class="verification-header">
                            <div class="logo">
                                <i class="fas fa-graduation-cap"></i>
                                <span>Student Notes Hub</span>
                            </div>
                            <h2>Email Verification</h2>
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
                                <?php if (strpos($error, 'expired') !== false || strpos($error, 'Invalid') !== false): ?>
                                    <div style="margin-top: 1rem;">
                                        <a href="resend-verification.php" class="btn btn-outline">
                                            <i class="fas fa-envelope"></i>
                                            Resend Verification Email
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($registered && !$token): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i>
                                <p><strong>Registration successful!</strong></p>
                                <p>We've sent a verification email to <strong><?= $registeredEmail ?></strong>. Please check your inbox and click the verification link to activate your account.</p>
                                <p style="margin-top: 1rem; font-size: 0.875rem; color: var(--text-secondary);">
                                    Didn't receive the email? Check your spam folder or
                                    <a href="resend-verification.php" style="color: var(--primary-color); text-decoration: underline;">request a new verification email</a>.
                                </p>
                            </div>
                        <?php elseif (!$token && !$error && !$message && !$registered): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <p>Please check your email for the verification link. If you didn't receive it, you can request a new one.</p>
                                <div style="margin-top: 1rem;">
                                    <a href="resend-verification.php" class="btn btn-outline">
                                        <i class="fas fa-envelope"></i>
                                        Resend Verification Email
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="verification-footer">
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
        .verification-section {
            padding: 3rem 0;
            background: var(--bg-secondary);
        }
        
        .verification-container {
            max-width: 500px;
            margin: 0 auto;
        }
        
        .verification-card {
            background: var(--bg-primary);
            border-radius: var(--border-radius-lg);
            padding: 2.5rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
        }
        
        .verification-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .verification-header .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .verification-header h2 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }
        
        .alert {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            font-weight: 500;
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: var(--error-color);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
        
        .alert-info {
            background: rgba(59, 130, 246, 0.1);
            color: var(--info-color);
            border: 1px solid rgba(59, 130, 246, 0.2);
        }
        
        .verification-footer {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid var(--border-color);
        }
        
        @media (max-width: 480px) {
            .verification-card {
                padding: 1.5rem;
            }
        }
    </style>
<?php
unset($error, $message, $token, $verified);
?>

