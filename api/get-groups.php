<?php
header('Content-Type: application/json');
require __DIR__ . '/../bootstrap.php';
ensureLoggedIn();

$userId = (int)$_SESSION['auth_user_id'];

try {
    // Get all groups user is member of
    $stmt = $pdo->prepare("
        SELECT g.id, g.name, g.description, g.icon, g.created_at, g.creator_id,
               g.updated_at, gm.role AS my_role
        FROM chat_groups g
        INNER JOIN group_members gm ON gm.group_id = g.id
        WHERE gm.user_id = ?
        ORDER BY COALESCE(g.updated_at, g.created_at) DESC
    ");
    $stmt->execute([$userId]);
    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get last message and unread count for each group
    $result = [];
    foreach ($groups as $group) {
        $groupId = (int)$group['id'];
        
        // Get last message
        $msgStmt = $pdo->prepare("
            SELECT message, created_at 
            FROM group_messages 
            WHERE group_id = ? 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $msgStmt->execute([$groupId]);
        $lastMsg = $msgStmt->fetch(PDO::FETCH_ASSOC);
        
        // Get unread count
        $unreadStmt = $pdo->prepare("
            SELECT COUNT(*) as cnt
            FROM group_messages gm
            WHERE gm.group_id = ? 
            AND gm.created_at > COALESCE((SELECT last_read_at FROM group_members WHERE group_id = ? AND user_id = ?), '1970-01-01')
            AND gm.sender_id != ?
        ");
        $unreadStmt->execute([$groupId, $groupId, $userId, $userId]);
        $unread = $unreadStmt->fetch(PDO::FETCH_ASSOC);
        
        $result[] = [
            'id' => $groupId,
            'name' => $group['name'],
            'description' => $group['description'],
            'icon' => $group['icon'],
            'created_at' => $group['created_at'],
            'creator_id' => (int)$group['creator_id'],
            'my_role' => $group['my_role'],
            'last_message' => $lastMsg ? $lastMsg['message'] : null,
            'last_message_time' => $lastMsg ? $lastMsg['created_at'] : null,
            'unread_count' => (int)($unread['cnt'] ?? 0)
        ];
    }

    echo json_encode(['success' => true, 'groups' => $result]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
