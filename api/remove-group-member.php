<?php
header('Content-Type: application/json');
require __DIR__ . '/../bootstrap.php';
ensureLoggedIn();

$input = json_decode(file_get_contents('php://input'), true);
$groupId = isset($input['group_id']) ? (int)$input['group_id'] : 0;
$memberId = isset($input['member_id']) ? (int)$input['member_id'] : 0;
$userId = (int)$_SESSION['auth_user_id'];

if ($groupId <= 0 || $memberId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}

try {
    // Check permissions
    $canRemove = false;
    
    if ($memberId === $userId) {
        // Users can always remove themselves
        $canRemove = true;
    } else {
        // Check if user is admin or moderator
        $stmt = $pdo->prepare("
            SELECT role FROM group_members
            WHERE group_id = ? AND user_id = ?
        ");
        $stmt->execute([$groupId, $userId]);
        $myRole = $stmt->fetchColumn();
        
        if ($myRole === 'admin' || $myRole === 'moderator') {
            // Check target member role
            $stmt = $pdo->prepare("
                SELECT role FROM group_members
                WHERE group_id = ? AND user_id = ?
            ");
            $stmt->execute([$groupId, $memberId]);
            $targetRole = $stmt->fetchColumn();
            
            // Only admins can remove other admins
            if ($targetRole !== 'admin' || $myRole === 'admin') {
                $canRemove = true;
            }
        }
    }

    if (!$canRemove) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }

    // Remove member
    $stmt = $pdo->prepare("
        DELETE FROM group_members
        WHERE group_id = ? AND user_id = ?
    ");
    $stmt->execute([$groupId, $memberId]);

    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
