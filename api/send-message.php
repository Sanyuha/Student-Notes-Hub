<?php
header('Content-Type: application/json');
require __DIR__ . '/../bootstrap.php';
ensureLoggedIn();

$input = json_decode(file_get_contents('php://input'), true);
$conversationId = isset($input['id']) ? (int)$input['id'] : 0;
$message = isset($input['message']) ? trim($input['message']) : '';
$fileUrl = isset($input['file_url']) ? trim($input['file_url']) : null;
$fileName = isset($input['file_name']) ? trim($input['file_name']) : null;
$fileType = isset($input['file_type']) ? trim($input['file_type']) : null;
$userId = (int)$_SESSION['auth_user_id'];

if ($conversationId <= 0 || ($message === '' && !$fileUrl)) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}

try {
    // Verify user is part of conversation
    $checkStmt = $pdo->prepare("
        SELECT id FROM conversations 
        WHERE id = ? AND (user1_id = ? OR user2_id = ?)
    ");
    $checkStmt->execute([$conversationId, $userId, $userId]);
    
    if (!$checkStmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }

    // Get the other user in the conversation
    $otherUserStmt = $pdo->prepare("
        SELECT CASE 
            WHEN user1_id = ? THEN user2_id 
            ELSE user1_id 
        END AS other_user_id
        FROM conversations 
        WHERE id = ?
    ");
    $otherUserStmt->execute([$userId, $conversationId]);
    $otherUser = $otherUserStmt->fetch();
    $otherUserId = $otherUser ? (int)$otherUser['other_user_id'] : null;

    // Insert message
    $stmt = $pdo->prepare("
        INSERT INTO messages (conversation_id, sender_id, message, file_url, file_name, file_type, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$conversationId, $userId, $message ?: '', $fileUrl, $fileName, $fileType]);

    // Update conversation timestamp
    $updateStmt = $pdo->prepare("
        UPDATE conversations SET last_message_at = NOW() WHERE id = ?
    ");
    $updateStmt->execute([$conversationId]);
    
    // Create notification for the recipient
    if ($otherUserId) {
        try {
            $pdo->prepare('
                INSERT INTO notifications (user_id, type, from_id, created_at) 
                VALUES (?, ?, ?, NOW())
            ')->execute([$otherUserId, 'message', $userId]);
        } catch (Exception $e) {
            // Notification creation failed, but don't fail the message send
        }
    }

    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
