<?php
// API endpoint for reporting notes
declare(strict_types=1);
require __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json');
ob_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['auth_user_id'])) {
    http_response_code(401);
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $noteId = (int)($input['note_id'] ?? 0);
    $reason = trim($input['reason'] ?? 'Inappropriate content');
    $userId = (int)$_SESSION['auth_user_id'];

    if ($noteId === 0) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Invalid note ID']);
        exit;
    }

    // Check if note exists
    $noteStmt = $pdo->prepare('SELECT id, user_id, title FROM notes WHERE id = ?');
    $noteStmt->execute([$noteId]);
    $noteData = $noteStmt->fetch();
    
    if (!$noteData) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Note not found']);
        exit;
    }
    
    // Prevent users from reporting their own notes
    if ($noteData['user_id'] == $userId) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Cannot report your own notes']);
        exit;
    }
    
    // Create reports table if it doesn't exist (must be done before checking for existing reports)
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS reports (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                note_id INT UNSIGNED NOT NULL,
                reporter_id INT UNSIGNED NOT NULL,
                reason TEXT,
                status ENUM('pending', 'reviewed', 'resolved', 'dismissed') DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_note (note_id),
                KEY idx_reporter (reporter_id),
                KEY idx_status (status),
                FOREIGN KEY (note_id) REFERENCES notes(id) ON DELETE CASCADE,
                FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    } catch (Exception $e) {
        // Table might already exist or foreign key constraints might fail, continue anyway
    }
    
    // Check if user already reported
    try {
        $existingStmt = $pdo->prepare('SELECT id FROM reports WHERE note_id = ? AND reporter_id = ?');
        $existingStmt->execute([$noteId, $userId]);
        
        if ($existingStmt->fetch()) {
            ob_clean();
            echo json_encode(['success' => true, 'message' => 'Note already reported']);
            exit;
        }
    } catch (Exception $e) {
        // If table doesn't exist yet, continue to create it
    }
    
    // Add report
    $insertStmt = $pdo->prepare(
        'INSERT INTO reports (note_id, reporter_id, reason, status) VALUES (?, ?, ?, ?)'
    );
    $insertStmt->execute([$noteId, $userId, $reason, 'pending']);
    
    // Get reporter username
    $reporterStmt = $pdo->prepare('SELECT username FROM users WHERE id = ?');
    $reporterStmt->execute([$userId]);
    $reporter = $reporterStmt->fetch();
    $reporterName = $reporter ? $reporter['username'] : 'Unknown';
    
    // Create admin_notifications table if it doesn't exist (before notifying admins)
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS admin_notifications (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                admin_id INT NOT NULL,
                type VARCHAR(50) NOT NULL,
                message TEXT NOT NULL,
                reference_id INT UNSIGNED,
                is_read TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_admin (admin_id),
                KEY idx_read (is_read),
                FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    } catch (Exception $e) {
        // Table might already exist, continue anyway
    }
    
    // Notify all admins
    try {
        $adminsStmt = $pdo->query('SELECT id FROM admins');
        $admins = $adminsStmt->fetchAll();
        
        foreach ($admins as $admin) {
            // Insert notification
            $notifStmt = $pdo->prepare(
                'INSERT INTO admin_notifications (admin_id, type, message, reference_id) VALUES (?, ?, ?, ?)'
            );
            $message = "Note '{$noteData['title']}' reported by {$reporterName}. Reason: {$reason}";
            $notifStmt->execute([$admin['id'], 'report', $message, $noteId]);
        }
    } catch (Exception $e) {
        // If notification fails, continue - report was still created
    }
    
    ob_clean();
    echo json_encode(['success' => true, 'message' => 'Note reported successfully. Admin will review it.']);
    
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}

