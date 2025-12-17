<?php
// API endpoint for submitting ratings and comments
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
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $noteId = (int)($input['note_id'] ?? 0);
    $rating = (int)($input['rating'] ?? 0);
    $comment = trim($input['comment'] ?? '');
    $userId = (int)$_SESSION['auth_user_id'];

    if ($noteId === 0) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Invalid note ID']);
        exit;
    }

    if ($rating < 1 || $rating > 5) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Rating must be between 1 and 5']);
        exit;
    }

    // Check if note exists
    $noteStmt = $pdo->prepare('SELECT id, user_id FROM notes WHERE id = ?');
    $noteStmt->execute([$noteId]);
    $noteData = $noteStmt->fetch();
    
    if (!$noteData) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Note not found']);
        exit;
    }
    
    // Create ratings table if it doesn't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ratings (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            note_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            rating TINYINT UNSIGNED NOT NULL,
            comment TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_rating (note_id, user_id),
            KEY idx_note (note_id),
            KEY idx_user (user_id),
            FOREIGN KEY (note_id) REFERENCES notes(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Check if user already rated
    $existingStmt = $pdo->prepare('SELECT id FROM ratings WHERE note_id = ? AND user_id = ?');
    $existingStmt->execute([$noteId, $userId]);
    $existing = $existingStmt->fetch();
    
    if ($existing) {
        // Update existing rating
        $updateStmt = $pdo->prepare(
            'UPDATE ratings SET rating = ?, comment = ?, updated_at = NOW() WHERE note_id = ? AND user_id = ?'
        );
        $updateStmt->execute([$rating, $comment ?: null, $noteId, $userId]);
    } else {
        // Insert new rating
        $insertStmt = $pdo->prepare(
            'INSERT INTO ratings (note_id, user_id, rating, comment) VALUES (?, ?, ?, ?)'
        );
        $insertStmt->execute([$noteId, $userId, $rating, $comment ?: null]);
    }
    
    // If comment provided, also add to comments table
    if ($comment) {
        $commentStmt = $pdo->prepare(
            'INSERT INTO comments (note_id, user_id, body) VALUES (?, ?, ?)'
        );
        $commentStmt->execute([$noteId, $userId, $comment]);
    }
    
    // Calculate average rating
    $avgStmt = $pdo->prepare('SELECT AVG(rating) as avg_rating, COUNT(*) as count FROM ratings WHERE note_id = ?');
    $avgStmt->execute([$noteId]);
    $avgData = $avgStmt->fetch();
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Rating submitted successfully',
        'average_rating' => round((float)$avgData['avg_rating'], 2),
        'rating_count' => (int)$avgData['count']
    ]);
    
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}



