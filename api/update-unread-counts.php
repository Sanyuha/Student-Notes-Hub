<?php
require __DIR__ . '/../bootstrap.php';
ensureLoggedIn();

$userId = $_SESSION['auth_user_id'];

// Conversations unread count
$conversations = $pdo->prepare("
    SELECT c.id,
    COUNT(m.id) AS unread_count
    FROM conversations c
    LEFT JOIN messages m
      ON m.conversation_id = c.id
     AND m.sender_id != :uid
     AND m.created_at >
        CASE
          WHEN c.user1_id = :uid THEN COALESCE(c.user1_last_read, '1970-01-01')
          ELSE COALESCE(c.user2_last_read, '1970-01-01')
        END
    WHERE c.user1_id = :uid OR c.user2_id = :uid
    GROUP BY c.id
");
$conversations->execute([':uid' => $userId]);

echo json_encode([
    'success' => true,
    'conversations' => $conversations->fetchAll()
]);
