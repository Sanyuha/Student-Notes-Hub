<?php
header('Content-Type: application/json');
require __DIR__ . '/../bootstrap.php';
ensureLoggedIn();

$input = json_decode(file_get_contents('php://input'), true);
$targetUserId = isset($input['user_id']) ? (int)$input['user_id'] : 0;
$currentUserId = (int)$_SESSION['auth_user_id'];

if ($targetUserId <= 0 || $targetUserId === $currentUserId) {
    echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
    exit;
}

try {
    // Check if user exists
    $userCheck = $pdo->prepare('SELECT id FROM users WHERE id = ?');
    $userCheck->execute([$targetUserId]);
    if (!$userCheck->fetch()) {
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit;
    }

    // Check if already following
    $followCheck = $pdo->prepare('SELECT follower_id FROM follows WHERE follower_id = ? AND following_id = ?');
    $followCheck->execute([$currentUserId, $targetUserId]);
    $isFollowing = $followCheck->fetch();

    if ($isFollowing) {
        // Unfollow
        $pdo->prepare('DELETE FROM follows WHERE follower_id = ? AND following_id = ?')
            ->execute([$currentUserId, $targetUserId]);
        $action = 'unfollowed';
    } else {
        // Follow - use INSERT IGNORE to handle duplicate key errors gracefully
        try {
            $pdo->prepare('INSERT INTO follows (follower_id, following_id, created_at) VALUES (?, ?, NOW())')
                ->execute([$currentUserId, $targetUserId]);
            $action = 'followed';
            
            // Create notification for the user being followed
            try {
                $pdo->prepare('
                    INSERT INTO notifications (user_id, type, from_id, created_at) 
                    VALUES (?, ?, ?, NOW())
                ')->execute([$targetUserId, 'follow', $currentUserId]);
            } catch (Exception $e) {
                // Notification creation failed, but don't fail the follow action
            }
        } catch (PDOException $e) {
            // If duplicate key error, user is already following (race condition)
            if ($e->getCode() == 23000) {
                // Check again to get current state
                $followCheck = $pdo->prepare('SELECT follower_id FROM follows WHERE follower_id = ? AND following_id = ?');
                $followCheck->execute([$currentUserId, $targetUserId]);
                $isFollowing = $followCheck->fetch();
                $action = $isFollowing ? 'followed' : 'unfollowed';
            } else {
                throw $e;
            }
        }
    }

    // Get updated counts
    $followersCount = $pdo->prepare('SELECT COUNT(*) FROM follows WHERE following_id = ?');
    $followersCount->execute([$targetUserId]);
    $followers = (int)$followersCount->fetchColumn();

    echo json_encode([
        'success' => true,
        'action' => $action,
        'is_following' => !$isFollowing,
        'followers_count' => $followers
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

