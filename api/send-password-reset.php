<?php
// API endpoint to send password reset email
declare(strict_types=1);
require __DIR__ . '/../bootstrap.php';

// Load PHPMailer
$phpmailerLoaded = false;
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    $phpmailerLoaded = true;
} elseif (file_exists(__DIR__ . '/../vendor/PHPMailer/src/PHPMailer.php')) {
    // Manual installation path (vendor/PHPMailer/src/)
    require_once __DIR__ . '/../vendor/PHPMailer/src/Exception.php';
    require_once __DIR__ . '/../vendor/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/../vendor/PHPMailer/src/SMTP.php';
    $phpmailerLoaded = true;
} elseif (file_exists(__DIR__ . '/../vendor/PHPMailer/PHPMailer/src/PHPMailer.php')) {
    // Alternative manual installation path (vendor/PHPMailer/PHPMailer/src/)
    require_once __DIR__ . '/../vendor/PHPMailer/PHPMailer/src/Exception.php';
    require_once __DIR__ . '/../vendor/PHPMailer/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/../vendor/PHPMailer/PHPMailer/src/SMTP.php';
    $phpmailerLoaded = true;
}

header('Content-Type: application/json');
ob_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $email = trim($input['email'] ?? '');

    if ($email === '') {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Email is required']);
        exit;
    }

    // Check if user exists
    $stmt = $pdo->prepare('SELECT id, username, email FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        // Don't reveal if email exists (security best practice)
        ob_clean();
        echo json_encode(['success' => true, 'message' => 'If an account exists with that email, a reset link has been sent.']);
        exit;
    }

    // Create password_reset_tokens table if it doesn't exist
    try {
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
    } catch (Exception $e) {
        // Table might already exist
    }

    // Generate secure token
    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Invalidate any existing tokens for this user
    $invalidateStmt = $pdo->prepare('UPDATE password_reset_tokens SET used = 1 WHERE user_id = ? AND used = 0');
    $invalidateStmt->execute([$user['id']]);

    // Insert new token
    $insertStmt = $pdo->prepare('
        INSERT INTO password_reset_tokens (user_id, token, expires_at) 
        VALUES (?, ?, ?)
    ');
    $insertStmt->execute([$user['id'], $token, $expiresAt]);

    // Check if PHPMailer is loaded
    if (!$phpmailerLoaded) {
        ob_clean();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'PHPMailer is not installed. Please install it using Composer or manually. See INSTALL_PHPMAILER.md for instructions.'
        ]);
        exit;
    }

    // Load email configuration
    $emailConfig = [];
    if (file_exists(__DIR__ . '/../email-config.php')) {
        $emailConfig = require __DIR__ . '/../email-config.php';
    } else {
        ob_clean();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Email configuration not found. Please copy email-config.example.php to email-config.php and configure your email settings.'
        ]);
        exit;
    }

    // Send email using PHPMailer
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $emailConfig['smtp_host'] ?? 'smtp.gmail.com';
        $mail->SMTPAuth   = $emailConfig['smtp_auth'] ?? true;
        $mail->Username   = $emailConfig['smtp_username'] ?? '';
        $mail->Password   = $emailConfig['smtp_password'] ?? '';
        $mail->SMTPSecure = ($emailConfig['smtp_secure'] ?? 'tls') === 'ssl' 
            ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS 
            : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $emailConfig['smtp_port'] ?? 587;
        $mail->CharSet    = 'UTF-8';
        $mail->SMTPDebug  = 0; // Set to 2 for debugging
        $mail->Debugoutput = function($str, $level) {
            // Log debug output if needed
        };

        // Recipients
        $mail->setFrom($emailConfig['from_email'] ?? $emailConfig['smtp_username'], $emailConfig['from_name'] ?? 'Student Notes Hub');
        $mail->addAddress($user['email'], $user['username']);
        
        // Reply-to
        $mail->addReplyTo($emailConfig['reply_to_email'] ?? $emailConfig['smtp_username'], $emailConfig['reply_to_name'] ?? 'Student Notes Hub Support');

        // Content
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'];
        $scriptPath = dirname($_SERVER['PHP_SELF']);
        if ($scriptPath === '/' || $scriptPath === '\\') {
            $scriptPath = '';
        }
        // URL encode the token to ensure it's properly handled
        $resetLink = $baseUrl . $scriptPath . '/reset-password.php?token=' . urlencode($token);
        
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Request - Student Notes Hub';
        $mail->Body    = '
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: linear-gradient(135deg, #6366f1, #4f46e5); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                    .content { background: #f9fafb; padding: 30px; border-radius: 0 0 8px 8px; }
                    .button { display: inline-block; padding: 12px 24px; background: #6366f1; color: white; text-decoration: none; border-radius: 6px; margin: 20px 0; }
                    .footer { text-align: center; margin-top: 20px; color: #6b7280; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h1>Password Reset Request</h1>
                    </div>
                    <div class="content">
                        <p>Hello ' . htmlspecialchars($user['username']) . ',</p>
                        <p>We received a request to reset your password for your Student Notes Hub account.</p>
                        <p>Click the button below to reset your password:</p>
                        <p style="text-align: center;">
                            <a href="' . htmlspecialchars($resetLink) . '" class="button">Reset Password</a>
                        </p>
                        <p>Or copy and paste this link into your browser:</p>
                        <p style="word-break: break-all; color: #6366f1;">' . htmlspecialchars($resetLink) . '</p>
                        <p><strong>This link will expire in 1 hour.</strong></p>
                        <p>If you did not request a password reset, please ignore this email. Your password will remain unchanged.</p>
                        <p>Best regards,<br>Student Notes Hub Team</p>
                    </div>
                    <div class="footer">
                        <p>This is an automated email. Please do not reply to this message.</p>
                    </div>
                </div>
            </body>
            </html>
        ';
        
        $mail->AltBody = "Hello {$user['username']},\n\nWe received a request to reset your password.\n\nClick this link to reset your password: {$resetLink}\n\nThis link will expire in 1 hour.\n\nIf you did not request this, please ignore this email.\n\nBest regards,\nStudent Notes Hub Team";

        $mail->send();
        
        ob_clean();
        echo json_encode(['success' => true, 'message' => 'Password reset email sent successfully']);
        
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        ob_clean();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to send email: ' . $mail->ErrorInfo
        ]);
    }

} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
