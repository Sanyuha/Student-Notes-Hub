<?php
// API endpoint for tracking downloads
declare(strict_types=1);
require __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$noteId = (int)($input['note_id'] ?? 0);

if ($noteId === 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid note ID']);
    exit;
}

try {
    // Check if note exists and is published (or draft owned by user)
    $userId = isset($_SESSION['auth_user_id']) ? (int)$_SESSION['auth_user_id'] : 0;
    $note = $pdo->prepare('SELECT id, status, user_id FROM notes WHERE id = ?');
    $note->execute([$noteId]);
    $noteData = $note->fetch();
    
    if (!$noteData) {
        http_response_code(404);
        echo json_encode(['error' => 'Note not found']);
        exit;
    }
    
    // Allow download if published, or if draft and user is the author
    if ($noteData['status'] !== 'published' && ($noteData['status'] !== 'draft' || $noteData['user_id'] != $userId)) {
        http_response_code(403);
        echo json_encode(['error' => 'You do not have permission to download this note']);
        exit;
    }
    
    // Get user ID if logged in
    $userId = isset($_SESSION['auth_user_id']) ? (int)$_SESSION['auth_user_id'] : null;
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    
    // Check for recent download from same user/IP to avoid duplicates
    $recentCheck = $pdo->prepare(
        'SELECT id FROM downloads 
         WHERE note_id = ? 
         AND (user_id = ? OR ip_address = ?)
         AND created_at > DATE_SUB(NOW(), INTERVAL 2 SECOND)'
    );
    $recentCheck->execute([$noteId, $userId, $ipAddress]);
    
    // Only record if no recent download found
    if (!$recentCheck->fetch()) {
        // Track download (trigger will update notes.downloads automatically)
        $pdo->prepare(
            'INSERT INTO downloads (note_id, user_id, ip_address) VALUES (?, ?, ?)'
        )->execute([$noteId, $userId, $ipAddress]);
        
        // Get actual count from downloads table
        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM downloads WHERE note_id = ?');
        $countStmt->execute([$noteId]);
        $actualCount = (int) $countStmt->fetchColumn();
        
        echo json_encode(['success' => true, 'downloads' => $actualCount]);
    } else {
        // Get current count even if we didn't insert
        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM downloads WHERE note_id = ?');
        $countStmt->execute([$noteId]);
        $actualCount = (int) $countStmt->fetchColumn();
        
        echo json_encode(['success' => true, 'downloads' => $actualCount, 'duplicate' => true]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}