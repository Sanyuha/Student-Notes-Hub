<?php
// API endpoint to get comments for a note
declare(strict_types=1);
require __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json');
ob_start();

$noteId = (int)($_GET['note_id'] ?? 0);

if ($noteId === 0) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Invalid note ID']);
    exit;
}

try {
    $commentsStmt = $pdo->prepare('
        SELECT c.id, c.body, c.created_at, c.user_id,
               u.username, u.avatar_url
        FROM comments c
        JOIN users u ON u.id = c.user_id
        WHERE c.note_id = ? AND c.parent_id IS NULL
        ORDER BY c.created_at ASC
    ');
    $commentsStmt->execute([$noteId]);
    $comments = $commentsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'comments' => $comments
    ]);
    
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}



