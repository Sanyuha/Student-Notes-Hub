<?php
// Resend verification email page for Student Notes Hub
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

// Set page title
$pageTitle = 'Resend Verification Email - Student Notes Hub';

// Redirect if already logged in
if (isset($_SESSION['auth_user_id'])) {
    redirect('index.php');
}

$error = null;
$message = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok($_POST['csrf'] ?? '')) {
        http_response_code(403);
        exit('Bad CSRF token');
    }

    $email = trim($_POST['email'] ?? '');

    if ($email === '') {
        $error = 'Please enter your email address';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        try {
            // Check if user exists
            $stmt = $pdo->prepare('SELECT id, username, email, email_verified FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                // Don't reveal if email exists (security best practice)
                $message = 'If an account exists with that email and is not verified, a verification email has been sent.';
            } else {
                // Check if already verified
                if ($user['email_verified']) {
                    $message = 'This email is already verified. You can log in to your account.';
                } else {
                    // Send verification email
                    require_once __DIR__ . '/api/send-verification-email.php';
                    $result = sendVerificationEmail($pdo, $user['id'], $user['email'], $user['username']);
                    
                    if ($result['success']) {
                        $message = 'A verification email has been sent to ' . htmlspecialchars($email) . '. Please check your inbox.';
                    } else {
                        $error = $result['error'] ?? 'Failed to send verification email. Please try again.';
                    }
                }
            }
        } catch (Exception $e) {
            $error = 'An error occurred. Please try again later.';
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
                    <h1><i class="fas fa-envelope"></i> Resend Verification Email</h1>
                    <p>Request a new verification email</p>
                </div>
            </div>
        </section>
        
        <!-- Resend Verification Form -->
        <section class="resend-verification-section">
            <div class="container">
                <div class="resend-verification-container">
                    <div class="resend-verification-card">
                        <div class="resend-verification-header">
                            <div class="logo">
                                <i class="fas fa-graduation-cap"></i>
                                <span>Student Notes Hub</span>
                            </div>
                            <h2>Resend Verification Email</h2>
                            <p>Enter your email address to receive a new verification link</p>
                        </div>
                        
                        <?php if ($message): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i>
                                <?= $message ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-error">
                                <i class="fas fa-exclamation-circle"></i>
                                <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="post" class="resend-verification-form">
                            <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
                            
                            <div class="form-group">
                                <label for="email">
                                    <i class="fas fa-envelope"></i>
                                    Email Address
                                </label>
                                <input type="email" id="email" name="email" required 
                                       placeholder="Enter your email address"
                                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-paper-plane"></i>
                                Send Verification Email
                            </button>
                        </form>
                        
                        <div class="resend-verification-footer">
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
        .resend-verification-section {
            padding: 3rem 0;
            background: var(--bg-secondary);
        }
        
        .resend-verification-container {
            max-width: 500px;
            margin: 0 auto;
        }
        
        .resend-verification-card {
            background: var(--bg-primary);
            border-radius: var(--border-radius-lg);
            padding: 2.5rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
        }
        
        .resend-verification-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .resend-verification-header .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .resend-verification-header h2 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }
        
        .resend-verification-header p {
            color: var(--text-secondary);
        }
        
        .resend-verification-form {
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
        
        .form-group input {
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
        
        .resend-verification-footer {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid var(--border-color);
        }
        
        @media (max-width: 480px) {
            .resend-verification-card {
                padding: 1.5rem;
            }
        }
    </style>
<?php
unset($error, $message);
?>

