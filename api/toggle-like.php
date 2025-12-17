<?php
// API endpoint for toggling likes
declare(strict_types=1);
require __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['auth_user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$noteId = (int)($input['note_id'] ?? 0);
$userId = (int)$_SESSION['auth_user_id'];

if ($noteId === 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid note ID']);
    exit;
}

try {
    // Check if note exists and is published
    $note = $pdo->prepare('SELECT id, status FROM notes WHERE id = ?');
    $note->execute([$noteId]);
    $noteData = $note->fetch();
    
    if (!$noteData || $noteData['status'] !== 'published') {
        http_response_code(404);
        echo json_encode(['error' => 'Note not found']);
        exit;
    }
    
    // Check if user already liked
    $like = $pdo->prepare('SELECT id FROM likes WHERE note_id = ? AND user_id = ?');
    $like->execute([$noteId, $userId]);
    $existingLike = $like->fetch();
    
    if ($existingLike) {
        // Remove like
        $pdo->prepare('DELETE FROM likes WHERE note_id = ? AND user_id = ?')->execute([$noteId, $userId]);
        $isLiked = false;
    } else {
        // Add like
        $pdo->prepare('INSERT INTO likes (note_id, user_id) VALUES (?, ?)')->execute([$noteId, $userId]);
        $isLiked = true;
    }
    
    // Get updated like count
    $likes = $pdo->prepare('SELECT COUNT(*) FROM likes WHERE note_id = ?');
    $likes->execute([$noteId]);
    $likeCount = $likes->fetchColumn();
    
    // Update note likes count
    $pdo->prepare('UPDATE notes SET likes = ? WHERE id = ?')->execute([$likeCount, $noteId]);
    
    echo json_encode([
        'success' => true,
        'likes' => $likeCount,
        'liked' => $isLiked
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}