<?php
// Forgot password page for Student Notes Hub
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

// Set page title
$pageTitle = 'Forgot Password - Student Notes Hub';

// Redirect if already logged in
if (isset($_SESSION['auth_user_id'])) {
    redirect('index.php');
}

$error = null;
$message = null;
$emailSent = false;

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
        // Use the API endpoint to send password reset email
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/api/send-password-reset.php');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['email' => $email]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            if ($result && $result['success']) {
                $emailSent = true;
                $message = 'If an account exists with that email, a password reset link has been sent. Please check your inbox.';
            } else {
                $error = $result['error'] ?? 'Failed to send password reset email';
            }
        } else {
            $error = 'Failed to send password reset email. Please try again later.';
        }
    }
}

require __DIR__ . '/components/header.php';
?>

<main style="min-height: calc(100vh - 200px); display: flex; align-items: center; justify-content: center; padding: 2rem;">
    <div style="max-width: 500px; width: 100%; background: var(--bg-primary); padding: 2rem; border-radius: var(--border-radius); box-shadow: var(--shadow-lg);">
        <h1 style="margin-bottom: 1.5rem; text-align: center; color: var(--text-primary);">Forgot Password</h1>
        
        <?php if ($error): ?>
            <div style="padding: 1rem; background: var(--error-color); color: white; border-radius: var(--border-radius); margin-bottom: 1rem;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div style="padding: 1rem; background: var(--success-color); color: white; border-radius: var(--border-radius); margin-bottom: 1rem;">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <?php if (!$emailSent): ?>
            <p style="margin-bottom: 1.5rem; color: var(--text-secondary); text-align: center;">
                Enter your email address and we'll send you a link to reset your password.
            </p>
            
            <form method="POST" style="display: flex; flex-direction: column; gap: 1rem;">
                <?= csrf_field() ?>
                
                <div>
                    <label for="email" style="display: block; margin-bottom: 0.5rem; color: var(--text-primary); font-weight: 500;">Email Address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        required 
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: var(--border-radius); font-size: 1rem; background: var(--bg-light); color: var(--text-primary);"
                        placeholder="your.email@example.com"
                    >
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.75rem; font-size: 1rem;">
                    <i class="fas fa-envelope"></i> Send Reset Link
                </button>
            </form>
            
            <div style="margin-top: 1.5rem; text-align: center;">
                <a href="login.php" style="color: var(--primary-color); text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Back to Login
                </a>
            </div>
        <?php else: ?>
            <div style="text-align: center;">
                <p style="margin-bottom: 1.5rem; color: var(--text-secondary);">
                    Please check your email for the password reset link. The link will expire in 1 hour.
                </p>
                <a href="login.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Back to Login
                </a>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require __DIR__ . '/components/footer.php'; ?>
