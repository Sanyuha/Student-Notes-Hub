<?php
// API endpoint to add a comment
declare(strict_types=1);
require __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json');
ob_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['auth_user_id'])) {
    http_response_code(401);
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $noteId = (int)($input['note_id'] ?? 0);
    $body = trim($input['body'] ?? '');
    $parentId = isset($input['parent_id']) ? (int)$input['parent_id'] : null;
    $userId = (int)$_SESSION['auth_user_id'];

    if ($noteId === 0) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Invalid note ID']);
        exit;
    }

    if (empty($body)) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Comment cannot be empty']);
        exit;
    }

    // Check if note exists
    $noteStmt = $pdo->prepare('SELECT id FROM notes WHERE id = ? AND status = "published"');
    $noteStmt->execute([$noteId]);
    if (!$noteStmt->fetch()) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Note not found']);
        exit;
    }

    // Insert comment
    $insertStmt = $pdo->prepare(
        'INSERT INTO comments (note_id, user_id, body, parent_id) VALUES (?, ?, ?, ?)'
    );
    $insertStmt->execute([$noteId, $userId, $body, $parentId]);
    $commentId = $pdo->lastInsertId();

    // Get the created comment with user info
    $commentStmt = $pdo->prepare('
        SELECT c.id, c.body, c.created_at, c.user_id,
               u.username, u.avatar_url
        FROM comments c
        JOIN users u ON u.id = c.user_id
        WHERE c.id = ?
    ');
    $commentStmt->execute([$commentId]);
    $comment = $commentStmt->fetch(PDO::FETCH_ASSOC);

    ob_clean();
    echo json_encode([
        'success' => true,
        'comment' => $comment
    ]);
    
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}



