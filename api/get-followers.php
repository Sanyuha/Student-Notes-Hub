<?php
header('Content-Type: application/json');
require __DIR__ . '/../bootstrap.php';
ensureLoggedIn();

$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : (int)$_SESSION['auth_user_id'];
$type = $_GET['type'] ?? 'followers'; // 'followers' or 'following'

try {
    if ($type === 'followers') {
        // Get users who follow this user
        $stmt = $pdo->prepare('
            SELECT u.id, u.username, u.avatar_url, u.bio,
                   (SELECT COUNT(*) FROM notes WHERE user_id = u.id AND status = "published") as notes_count
            FROM users u
            INNER JOIN follows f ON f.follower_id = u.id
            WHERE f.following_id = ?
            ORDER BY u.username ASC
        ');
    } else {
        // Get users this user is following
        $stmt = $pdo->prepare('
            SELECT u.id, u.username, u.avatar_url, u.bio,
                   (SELECT COUNT(*) FROM notes WHERE user_id = u.id AND status = "published") as notes_count
            FROM users u
            INNER JOIN follows f ON f.following_id = u.id
            WHERE f.follower_id = ?
            ORDER BY u.username ASC
        ');
    }
    
    $stmt->execute([$userId]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'users' => $users,
        'count' => count($users)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}


