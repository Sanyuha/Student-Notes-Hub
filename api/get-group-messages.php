<?php
header('Content-Type: application/json');
require __DIR__ . '/../bootstrap.php';
ensureLoggedIn();

$input = json_decode(file_get_contents('php://input'), true);
$groupId = isset($input['group_id']) ? (int)$input['group_id'] : 0;
$userId = (int)$_SESSION['auth_user_id'];

if ($groupId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid group ID']);
    exit;
}

try {
    // Verify membership
    $checkStmt = $pdo->prepare("
        SELECT group_id FROM group_members 
        WHERE group_id = ? AND user_id = ?
    ");
    $checkStmt->execute([$groupId, $userId]);
    
    if (!$checkStmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }

    // Get messages - optionally only new ones after a specific ID
    $afterId = isset($input['after_id']) ? (int)$input['after_id'] : 0;
    
    if ($afterId > 0) {
        // Only get new messages
        $stmt = $pdo->prepare("
            SELECT 
                gm.id AS id,
                gm.message AS message,
                gm.created_at AS created_at,
                gm.sender_id AS sender_id,
                gm.file_url AS file_url,
                gm.file_name AS file_name,
                gm.file_type AS file_type,
                u.username AS username,
                u.avatar_url AS avatar_url
            FROM group_messages gm
            INNER JOIN users u ON u.id = gm.sender_id
            WHERE gm.group_id = ? AND gm.id > ?
            ORDER BY gm.created_at ASC
        ");
        $stmt->execute([$groupId, $afterId]);
    } else {
        // Get all messages
        $stmt = $pdo->prepare("
            SELECT 
                gm.id AS id,
                gm.message AS message,
                gm.created_at AS created_at,
                gm.sender_id AS sender_id,
                gm.file_url AS file_url,
                gm.file_name AS file_name,
                gm.file_type AS file_type,
                u.username AS username,
                u.avatar_url AS avatar_url
            FROM group_messages gm
            INNER JOIN users u ON u.id = gm.sender_id
            WHERE gm.group_id = ?
            ORDER BY gm.created_at ASC
        ");
        $stmt->execute([$groupId]);
    }
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Mark messages as read when user opens the group chat
    if (count($messages) > 0) {
        try {
            // Update the group member's last_read_at timestamp
            $pdo->prepare("
                UPDATE group_members
                SET last_read_at = NOW()
                WHERE group_id = ? AND user_id = ?
            ")->execute([$groupId, $userId]);
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
