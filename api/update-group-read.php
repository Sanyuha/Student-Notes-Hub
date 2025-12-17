<?php
require __DIR__ . '/../bootstrap.php';
ensureLoggedIn();

$data = json_decode(file_get_contents('php://input'), true);
$groupId = (int)($data['group_id'] ?? 0);
$userId = $_SESSION['auth_user_id'];

if ($groupId <= 0) {
    echo json_encode(['success' => false]);
    exit;
}

$stmt = $pdo->prepare("
    UPDATE group_members
    SET last_read_at = NOW()
    WHERE group_id = :gid AND user_id = :uid
");
$stmt->execute([
    ':gid' => $groupId,
    ':uid' => $userId
]);

echo json_encode(['success' => true]);
