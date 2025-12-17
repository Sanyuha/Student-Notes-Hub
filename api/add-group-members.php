<?php
header('Content-Type: application/json');
require __DIR__ . '/../bootstrap.php';
ensureLoggedIn();

$input = json_decode(file_get_contents('php://input'), true);
$groupId = isset($input['group_id']) ? (int)$input['group_id'] : 0;
$memberIds = isset($input['member_ids']) ? $input['member_ids'] : [];
$userId = (int)$_SESSION['auth_user_id'];

if ($groupId <= 0 || empty($memberIds)) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}

try {
    // Check if user is admin or moderator
    $stmt = $pdo->prepare("
        SELECT role FROM group_members
        WHERE group_id = ? AND user_id = ?
    ");
    $stmt->execute([$groupId, $userId]);
    $myRole = $stmt->fetchColumn();
    
    if (!$myRole || ($myRole !== 'admin' && $myRole !== 'moderator')) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }

    // Add members
    $stmt = $pdo->prepare("
        INSERT INTO group_members (group_id, user_id, role, joined_at)
        VALUES (?, ?, 'member', NOW())
        ON DUPLICATE KEY UPDATE role = 'member'
    ");

    $added = [];
    $errors = [];
    foreach ($memberIds as $memberId) {
        $memberId = (int)$memberId;
        if ($memberId > 0 && $memberId !== $userId) {
            try {
                $stmt->execute([$groupId, $memberId]);
                if ($stmt->rowCount() > 0) {
                    $added[] = $memberId;
                }
            } catch (PDOException $e) {
                // Log error but continue with other members
                $errors[] = "Failed to add user $memberId: " . $e->getMessage();
            }
        }
    }

    if (empty($added) && !empty($errors)) {
        echo json_encode([
            'success' => false,
            'error' => implode(', ', $errors)
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'added' => $added,
            'message' => count($added) > 0 ? 'Members added successfully' : 'No new members added'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
