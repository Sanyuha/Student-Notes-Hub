<?php
require __DIR__ . '/../bootstrap.php';
ensureLoggedIn();

$data = json_decode(file_get_contents('php://input'), true);
$conversationId = (int)($data['conversation_id'] ?? 0);
$userId = $_SESSION['auth_user_id'];

if ($conversationId <= 0) {
    echo json_encode(['success' => false]);
    exit;
}

// Determine which read column to update
$stmt = $pdo->prepare("
    SELECT user1_id, user2_id FROM conversations WHERE id = :cid
");
$stmt->execute([':cid' => $conversationId]);
$conv = $stmt->fetch();

if (!$conv) {
    echo json_encode(['success' => false]);
    exit;
}

$column = ($conv['user1_id'] === $userId) ? 'user1_last_read' : 'user2_last_read';

$pdo->prepare("
    UPDATE conversations
    SET $column = NOW()
    WHERE id = :cid
")->execute([':cid' => $conversationId]);

echo json_encode(['success' => true]);
