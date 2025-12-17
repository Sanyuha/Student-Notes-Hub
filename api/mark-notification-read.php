<?php
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
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = (int)$_SESSION['auth_user_id'];
    
    if (isset($input['id'])) {
        // Mark single notification as read
        $notificationId = (int)$input['id'];
        $stmt = $pdo->prepare('
            UPDATE notifications 
            SET is_read = 1 
            WHERE id = ? AND user_id = ?
        ');
        $stmt->execute([$notificationId, $userId]);
    } else {
        // Mark all notifications as read
        $stmt = $pdo->prepare('
            UPDATE notifications 
            SET is_read = 1 
            WHERE user_id = ? AND is_read = 0
        ');
        $stmt->execute([$userId]);
    }
    
    ob_clean();
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to update notification'
    ]);
}



