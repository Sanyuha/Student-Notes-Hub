<?php
header('Content-Type: application/json');
require __DIR__ . '/../bootstrap.php';
ensureLoggedIn();

$userId = (int)$_SESSION['auth_user_id'];

try {
    // Get conversations where user is user1
    $stmt1 = $pdo->prepare("
        SELECT c.id, c.last_message_at, c.user2_id AS other_user_id,
               u.username AS other_username, u.avatar_url AS other_avatar_url
        FROM conversations c
        INNER JOIN users u ON u.id = c.user2_id
        WHERE c.user1_id = ?
    ");
    $stmt1->execute([$userId]);
    $convs1 = $stmt1->fetchAll(PDO::FETCH_ASSOC);

    // Get conversations where user is user2
    $stmt2 = $pdo->prepare("
        SELECT c.id, c.last_message_at, c.user1_id AS other_user_id,
               u.username AS other_username, u.avatar_url AS other_avatar_url
        FROM conversations c
        INNER JOIN users u ON u.id = c.user1_id
        WHERE c.user2_id = ?
    ");
    $stmt2->execute([$userId]);
    $convs2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    // Merge results
    $allConversations = array_merge($convs1, $convs2);

    // Get last message and unread count for each
    $result = [];
    foreach ($allConversations as $conv) {
        $convId = (int)$conv['id'];
        
        // Get last message
        $msgStmt = $pdo->prepare("
            SELECT message, created_at 
            FROM messages 
            WHERE conversation_id = ? 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $msgStmt->execute([$convId]);
        $lastMsg = $msgStmt->fetch(PDO::FETCH_ASSOC);
        
        // Get unread count
        // First check if read timestamp columns exist
        $columnsExist = false;
        try {
            $checkCols = $pdo->query("SHOW COLUMNS FROM conversations LIKE 'user1_last_read'");
            $columnsExist = $checkCols->rowCount() > 0;
        } catch (Exception $e) {
            $columnsExist = false;
        }
        
        if ($columnsExist) {
            // Use last_read timestamp method (new method)
            $lastReadStmt = $pdo->prepare("
                SELECT 
                    CASE 
                        WHEN user1_id = ? THEN user1_last_read 
                        ELSE user2_last_read 
                    END AS last_read
                FROM conversations 
                WHERE id = ?
            ");
            $lastReadStmt->execute([$userId, $convId]);
            $lastRead = $lastReadStmt->fetch(PDO::FETCH_ASSOC);
            $lastReadTime = $lastRead && $lastRead['last_read'] ? $lastRead['last_read'] : '1970-01-01';
            
            // Count messages after last_read time
            $unreadStmt = $pdo->prepare("
                SELECT COUNT(*) as cnt
                FROM messages 
                WHERE conversation_id = ? 
                AND sender_id != ? 
                AND created_at > ?
            ");
            $unreadStmt->execute([$convId, $userId, $lastReadTime]);
            $unread = $unreadStmt->fetch(PDO::FETCH_ASSOC);
        } else {
            // Fallback: use is_read field in messages table (old method)
            $unreadStmt = $pdo->prepare("
                SELECT COUNT(*) as cnt
                FROM messages 
                WHERE conversation_id = ? 
                AND sender_id != ? 
                AND (is_read = 0 OR is_read IS NULL)
            ");
            $unreadStmt->execute([$convId, $userId]);
            $unread = $unreadStmt->fetch(PDO::FETCH_ASSOC);
        }
        
        $result[] = [
            'id' => $convId,
            'last_message_at' => $conv['last_message_at'],
            'other_user_id' => (int)$conv['other_user_id'],
            'other_username' => $conv['other_username'],
            'other_avatar_url' => $conv['other_avatar_url'] ?: 'https://via.placeholder.com/150',
            'last_message' => $lastMsg ? $lastMsg['message'] : null,
            'last_message_time' => $lastMsg ? $lastMsg['created_at'] : null,
            'unread_count' => (int)($unread['cnt'] ?? 0)
        ];
    }

    // Sort by last message time
    usort($result, function($a, $b) {
        $timeA = $a['last_message_time'] ?? $a['last_message_at'] ?? '1970-01-01';
        $timeB = $b['last_message_time'] ?? $b['last_message_at'] ?? '1970-01-01';
        return strtotime($timeB) - strtotime($timeA);
    });

    echo json_encode(['success' => true, 'conversations' => $result]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
