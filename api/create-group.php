<?php
header('Content-Type: application/json');
require __DIR__ . '/../bootstrap.php';
ensureLoggedIn();

$input = json_decode(file_get_contents('php://input'), true);
$name = isset($input['name']) ? trim($input['name']) : '';
$members = isset($input['members']) ? $input['members'] : [];
$userId = (int)$_SESSION['auth_user_id'];

if ($name === '' || empty($members)) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Create group
    $stmt = $pdo->prepare("
        INSERT INTO chat_groups (name, creator_id, created_at)
        VALUES (?, ?, NOW())
    ");
    $stmt->execute([$name, $userId]);
    $groupId = (int)$pdo->lastInsertId();

    // Add creator as admin
    $stmt = $pdo->prepare("
        INSERT INTO group_members (group_id, user_id, role, joined_at)
        VALUES (?, ?, 'admin', NOW())
    ");
    $stmt->execute([$groupId, $userId]);

    // Add members
    $stmt = $pdo->prepare("
        INSERT INTO group_members (group_id, user_id, role, joined_at)
        VALUES (?, ?, 'member', NOW())
    ");

    foreach ($members as $memberId) {
        $memberId = (int)$memberId;
        if ($memberId > 0 && $memberId !== $userId) {
            $stmt->execute([$groupId, $memberId]);
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'group_id' => $groupId
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
