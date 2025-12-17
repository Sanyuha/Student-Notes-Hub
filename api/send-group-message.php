<?php
header('Content-Type: application/json');
require __DIR__ . '/../bootstrap.php';
ensureLoggedIn();

$input = json_decode(file_get_contents('php://input'), true);
$groupId = isset($input['group_id']) ? (int)$input['group_id'] : 0;
$message = isset($input['message']) ? trim($input['message']) : '';
$fileUrl = isset($input['file_url']) ? trim($input['file_url']) : null;
$fileName = isset($input['file_name']) ? trim($input['file_name']) : null;
$fileType = isset($input['file_type']) ? trim($input['file_type']) : null;
$userId = (int)$_SESSION['auth_user_id'];

if ($groupId <= 0 || ($message === '' && !$fileUrl)) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
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

    // Insert message
    $stmt = $pdo->prepare("
        INSERT INTO group_messages (group_id, sender_id, message, file_url, file_name, file_type, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$groupId, $userId, $message ?: '', $fileUrl, $fileName, $fileType]);

    // Update group timestamp
    $updateStmt = $pdo->prepare("
        UPDATE chat_groups SET updated_at = NOW() WHERE id = ?
    ");
    $updateStmt->execute([$groupId]);

    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
