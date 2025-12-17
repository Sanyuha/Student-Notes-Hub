<?php
// Function to send verification email
// This file can be included or called directly

function sendVerificationEmail($pdo, $userId, $email, $username) {
    // Load PHPMailer
    $phpmailerLoaded = false;
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        require_once __DIR__ . '/../vendor/autoload.php';
        $phpmailerLoaded = true;
    } elseif (file_exists(__DIR__ . '/../vendor/PHPMailer/src/PHPMailer.php')) {
        require_once __DIR__ . '/../vendor/PHPMailer/src/Exception.php';
        require_once __DIR__ . '/../vendor/PHPMailer/src/PHPMailer.php';
        require_once __DIR__ . '/../vendor/PHPMailer/src/SMTP.php';
        $phpmailerLoaded = true;
    } elseif (file_exists(__DIR__ . '/../vendor/PHPMailer/PHPMailer/src/PHPMailer.php')) {
        require_once __DIR__ . '/../vendor/PHPMailer/PHPMailer/src/Exception.php';
        require_once __DIR__ . '/../vendor/PHPMailer/PHPMailer/src/PHPMailer.php';
        require_once __DIR__ . '/../vendor/PHPMailer/PHPMailer/src/SMTP.php';
        $phpmailerLoaded = true;
    }

    if (!$phpmailerLoaded) {
        return ['success' => false, 'error' => 'PHPMailer is not installed'];
    }

    // Load email configuration
    $emailConfig = [];
    if (file_exists(__DIR__ . '/../email-config.php')) {
        $emailConfig = require __DIR__ . '/../email-config.php';
    } else {
        return ['success' => false, 'error' => 'Email configuration not found. Please copy email-config.example.php to email-config.php and configure your email settings.'];
    }

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

        // Generate secure token
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours')); // 24 hours expiry

        // Invalidate any existing unused tokens for this user
        $invalidateStmt = $pdo->prepare('UPDATE email_verification_tokens SET used = 1 WHERE user_id = ? AND used = 0');
        $invalidateStmt->execute([$userId]);

        // Insert new token
        $insertStmt = $pdo->prepare('
            INSERT INTO email_verification_tokens (user_id, token, expires_at) 
            VALUES (?, ?, ?)
        ');
        $insertStmt->execute([$userId, $token, $expiresAt]);

        // Send email using PHPMailer
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

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

        // Recipients
        $mail->setFrom($emailConfig['from_email'] ?? $emailConfig['smtp_username'], $emailConfig['from_name'] ?? 'Student Notes Hub');
        $mail->addAddress($email, $username);
        $mail->addReplyTo($emailConfig['reply_to_email'] ?? $emailConfig['smtp_username'], $emailConfig['reply_to_name'] ?? 'Student Notes Hub Support');

        // Content
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'];
        $scriptPath = dirname($_SERVER['PHP_SELF']);
        if ($scriptPath === '/' || $scriptPath === '\\') {
            $scriptPath = '';
        }
        $verifyLink = $baseUrl . $scriptPath . '/verify-email.php?token=' . urlencode($token);
        
        $mail->isHTML(true);
        $mail->Subject = 'Verify Your Email - Student Notes Hub';
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
                        <h1>Verify Your Email Address</h1>
                    </div>
                    <div class="content">
                        <p>Hello ' . htmlspecialchars($username) . ',</p>
                        <p>Thank you for registering with Student Notes Hub! Please verify your email address to complete your registration.</p>
                        <p>Click the button below to verify your email:</p>
                        <p style="text-align: center;">
                            <a href="' . htmlspecialchars($verifyLink, ENT_QUOTES, 'UTF-8') . '" class="button" style="color: white; text-decoration: none;">Verify Email Address</a>
                        </p>
                        <p>Or copy and paste this link into your browser:</p>
                        <p style="word-break: break-all; color: #6366f1; font-family: monospace; font-size: 0.875rem;">' . htmlspecialchars($verifyLink, ENT_QUOTES, 'UTF-8') . '</p>
                        <p><strong>This link will expire in 24 hours.</strong></p>
                        <p>If you did not create an account, please ignore this email.</p>
                        <p>Best regards,<br>Student Notes Hub Team</p>
                    </div>
                    <div class="footer">
                        <p>This is an automated email. Please do not reply to this message.</p>
                    </div>
                </div>
            </body>
            </html>
        ';
        
        $mail->AltBody = "Hello {$username},\n\nThank you for registering with Student Notes Hub! Please verify your email address by clicking this link:\n\n{$verifyLink}\n\nThis link will expire in 24 hours.\n\nIf you did not create an account, please ignore this email.\n\nBest regards,\nStudent Notes Hub Team";

        $mail->send();
        return ['success' => true];
        
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        return ['success' => false, 'error' => 'Failed to send email: ' . $mail->ErrorInfo];
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'An error occurred: ' . $e->getMessage()];
    }
}

// If called directly as API endpoint
if (basename($_SERVER['PHP_SELF']) === 'send-verification-email.php') {
    header('Content-Type: application/json');
    require __DIR__ . '/../bootstrap.php';
    ensureLoggedIn();
    
    ob_start();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }
    
    try {
        $userId = (int)$_SESSION['auth_user_id'];
        $stmt = $pdo->prepare('SELECT id, email, username, email_verified FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'User not found']);
            exit;
        }
        
        if ($user['email_verified']) {
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'Email is already verified']);
            exit;
        }
        
        $result = sendVerificationEmail($pdo, $user['id'], $user['email'], $user['username']);
        
        ob_clean();
        echo json_encode($result);
        
    } catch (Exception $e) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'An error occurred']);
    }
}
