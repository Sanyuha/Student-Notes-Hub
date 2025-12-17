<?php
/**
 * Email Configuration Template
 * 
 * Copy this file to email-config.php and update with your email credentials
 * The email-config.php file is already in .gitignore and will not be committed
 */

return [
    // SMTP Server Settings
    'smtp_host' => 'smtp.gmail.com',        // Gmail: smtp.gmail.com
    'smtp_port' => 587,                     // Gmail: 587 (TLS) or 465 (SSL)
    'smtp_secure' => 'tls',                 // 'tls' or 'ssl'
    'smtp_auth' => true,
    
    // Email Account Credentials
    'smtp_username' => 'your-email@gmail.com',  // Your Gmail address
    'smtp_password' => 'your-app-password',      // Gmail App Password (not your regular password)
    
    // Email Sender Information
    'from_email' => 'your-email@gmail.com',     // Usually same as smtp_username
    'from_name' => 'Student Notes Hub',
    'reply_to_email' => 'your-email@gmail.com',  // Usually same as smtp_username
    'reply_to_name' => 'Student Notes Hub Support',
];

/**
 * How to get a Gmail App Password:
 * 
 * 1. Go to your Google Account: https://myaccount.google.com/
 * 2. Click on "Security" in the left sidebar
 * 3. Under "Signing in to Google", enable "2-Step Verification" if not already enabled
 * 4. After enabling 2-Step Verification, go back to Security
 * 5. Under "Signing in to Google", click "App passwords"
 * 6. Select "Mail" as the app and "Other" as the device
 * 7. Enter "Student Notes Hub" as the name
 * 8. Click "Generate"
 * 9. Copy the 16-character password (spaces don't matter)
 * 10. Paste it in the 'smtp_password' field above
 * 
 * For other email providers (Outlook, Yahoo, etc.):
 * - Check their SMTP settings documentation
 * - Update smtp_host, smtp_port, and smtp_secure accordingly
 */

