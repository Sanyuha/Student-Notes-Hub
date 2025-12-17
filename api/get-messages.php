<?php
header('Content-Type: application/json');
require __DIR__ . '/../bootstrap.php';
ensureLoggedIn();

$input = json_decode(file_get_contents('php://input'), true);
$conversationId = isset($input['id']) ? (int)$input['id'] : 0;
$userId = (int)$_SESSION['auth_user_id'];

if ($conversationId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid conversation ID']);
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

    // Get messages - optionally only new ones after a specific ID
    $afterId = isset($input['after_id']) ? (int)$input['after_id'] : 0;
    
    if ($afterId > 0) {
        // Only get new messages
        $stmt = $pdo->prepare("
            SELECT m.id, m.message, m.sender_id, m.created_at,
                   m.file_url, m.file_name, m.file_type,
                   u.username, u.avatar_url
            FROM messages m
            INNER JOIN users u ON u.id = m.sender_id
            WHERE m.conversation_id = ? AND m.id > ?
            ORDER BY m.created_at ASC
        ");
        $stmt->execute([$conversationId, $afterId]);
    } else {
        // Get all messages
        $stmt = $pdo->prepare("
            SELECT m.id, m.message, m.sender_id, m.created_at,
                   m.file_url, m.file_name, m.file_type,
                   u.username, u.avatar_url
            FROM messages m
            INNER JOIN users u ON u.id = m.sender_id
            WHERE m.conversation_id = ?
            ORDER BY m.created_at ASC
        ");
        $stmt->execute([$conversationId]);
    }
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Mark messages as read when user opens the conversation
    if (count($messages) > 0) {
        try {
            // Check if read timestamp columns exist
            $columnsExist = false;
            try {
                $checkCols = $pdo->query("SHOW COLUMNS FROM conversations LIKE 'user1_last_read'");
                $columnsExist = $checkCols->rowCount() > 0;
            } catch (Exception $e) {
                $columnsExist = false;
            }
            
            if ($columnsExist) {
                // Update the conversation's last_read timestamp for this user (new method)
                $convStmt = $pdo->prepare("
                    SELECT user1_id, user2_id FROM conversations WHERE id = ?
                ");
                $convStmt->execute([$conversationId]);
                $conv = $convStmt->fetch();
                
                if ($conv) {
                    $column = ($conv['user1_id'] == $userId) ? 'user1_last_read' : 'user2_last_read';
                    $pdo->prepare("
                        UPDATE conversations
                        SET $column = NOW()
                        WHERE id = ?
                    ")->execute([$conversationId]);
                }
            } else {
                // Fallback: mark messages as read in messages table (old method)
                $pdo->prepare("
                    UPDATE messages
                    SET is_read = 1
                    WHERE conversation_id = ?
                    AND sender_id != ?
                    AND (is_read = 0 OR is_read IS NULL)
                ")->execute([$conversationId, $userId]);
            }
            
            // Mark all message notifications from the other user as read
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
            
            if ($otherUser && $otherUser['other_user_id']) {
                // Mark all message notifications from this user as read
                $pdo->prepare("
                    UPDATE notifications 
                    SET is_read = 1 
                    WHERE user_id = ? 
                    AND type = 'message' 
                    AND from_id = ?
                    AND is_read = 0
                ")->execute([$userId, $otherUser['other_user_id']]);
            }
        } catch (Exception $e) {
            // Continue even if update fails
        }
    }

    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
