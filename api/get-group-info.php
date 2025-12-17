<?php
header('Content-Type: application/json');
require __DIR__ . '/../bootstrap.php';
ensureLoggedIn();

$groupId = isset($_GET['group_id']) ? (int)$_GET['group_id'] : 0;
$userId = (int)$_SESSION['auth_user_id'];

if ($groupId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid group ID']);
    exit;
}

try {
    // Check membership
    $stmt = $pdo->prepare("
        SELECT role FROM group_members
        WHERE group_id = ? AND user_id = ?
    ");
    $stmt->execute([$groupId, $userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }
    
    $myRole = $result['role'];

    // Get group info
    $stmt = $pdo->prepare("
        SELECT id, name, creator_id, created_at
        FROM chat_groups WHERE id = ?
    ");
    $stmt->execute([$groupId]);
    $group = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get members
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.avatar_url, gm.role
        FROM group_members gm
        INNER JOIN users u ON u.id = gm.user_id
        WHERE gm.group_id = ?
    ");
    $stmt->execute([$groupId]);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'group' => $group,
        'members' => $members,
        'my_role' => $myRole
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
