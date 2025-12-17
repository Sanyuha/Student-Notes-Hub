<?php
header('Content-Type: application/json');
require __DIR__ . '/../bootstrap.php';

ob_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $type = trim($input['type'] ?? '');
    $subject = trim($input['subject'] ?? '');
    $description = trim($input['description'] ?? '');
    $email = trim($input['email'] ?? '');
    
    if (empty($type) || empty($subject) || empty($description) || empty($email)) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'All fields are required']);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Invalid email address']);
        exit;
    }
    
    // Create user_reports table if it doesn't exist
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS user_reports (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                type VARCHAR(50) NOT NULL,
                subject VARCHAR(255) NOT NULL,
                description TEXT NOT NULL,
                email VARCHAR(255) NOT NULL,
                user_id INT UNSIGNED NULL,
                status VARCHAR(20) DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_status (status),
                KEY idx_user (user_id),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    } catch (Exception $e) {
        // Table might already exist
    }
    
    $userId = isset($_SESSION['auth_user_id']) ? (int)$_SESSION['auth_user_id'] : null;
    
    $stmt = $pdo->prepare('
        INSERT INTO user_reports (type, subject, description, email, user_id) 
        VALUES (?, ?, ?, ?, ?)
    ');
    $stmt->execute([$type, $subject, $description, $email, $userId]);
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Thank you for your report. We will review it and get back to you soon.'
    ]);
    
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to submit report. Please try again later.'
    ]);
}



