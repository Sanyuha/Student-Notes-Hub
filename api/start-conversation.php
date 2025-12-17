<?php
header('Content-Type: application/json');
require __DIR__ . '/../bootstrap.php';
ensureLoggedIn();

$input = json_decode(file_get_contents('php://input'), true);
$otherUserId = isset($input['user_id']) ? (int)$input['user_id'] : 0;
$userId = (int)$_SESSION['auth_user_id'];

if ($otherUserId <= 0 || $otherUserId === $userId) {
    echo json_encode(['success' => false, 'message' => 'Invalid user']);
    exit;
}

try {
    // Check if conversation already exists
    $stmt = $pdo->prepare("
        SELECT id FROM conversations
        WHERE (user1_id = ? AND user2_id = ?)
           OR (user1_id = ? AND user2_id = ?)
        LIMIT 1
    ");
    $stmt->execute([$userId, $otherUserId, $otherUserId, $userId]);
    $conversation = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($conversation) {
        echo json_encode([
            'success' => true,
            'conversation_id' => (int)$conversation['id']
        ]);
        exit;
    }

    // Create new conversation - ensure smaller user_id is always user1_id
    $user1Id = min($userId, $otherUserId);
    $user2Id = max($userId, $otherUserId);
    
    $stmt = $pdo->prepare("
        INSERT INTO conversations (user1_id, user2_id, created_at)
        VALUES (?, ?, NOW())
    ");
    $stmt->execute([$user1Id, $user2Id]);

    echo json_encode([
        'success' => true,
        'conversation_id' => (int)$pdo->lastInsertId()
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
