<?php
header('Content-Type: application/json');
require __DIR__ . '/../bootstrap.php';
ensureLoggedIn();

ob_start();

try {
    $userId = (int)$_SESSION['auth_user_id'];
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $limit = min(max(1, $limit), 50); // Between 1 and 50
    
    // Get unread count
    $unreadStmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $unreadStmt->execute([$userId]);
    $unreadCount = (int)$unreadStmt->fetchColumn();
    
    // Get notifications with user info - show all notifications (read and unread)
    $stmt = $pdo->prepare("
        SELECT 
            n.id,
            n.type,
            n.from_id,
            n.note_id,
            n.is_read,
            n.created_at,
            u.username AS from_username,
            u.avatar_url AS from_avatar,
            n2.title AS note_title
        FROM notifications n
        LEFT JOIN users u ON u.id = n.from_id
        LEFT JOIN notes n2 ON n2.id = n.note_id
        WHERE n.user_id = ?
        ORDER BY n.is_read ASC, n.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$userId, $limit]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format notifications
    $formatted = [];
    foreach ($notifications as $notif) {
        $message = '';
        $icon = 'fa-bell';
        
        switch ($notif['type']) {
            case 'like':
                $message = $notif['from_username'] . ' liked your note "' . ($notif['note_title'] ?? 'Untitled') . '"';
                $icon = 'fa-heart';
                break;
            case 'follow':
                $message = $notif['from_username'] . ' started following you';
                $icon = 'fa-user-plus';
                break;
            case 'message':
                $message = $notif['from_username'] . ' sent you a message';
                $icon = 'fa-comment';
                break;
            default:
                $message = 'You have a new notification';
        }
        
        $formatted[] = [
            'id' => (int)$notif['id'],
            'type' => $notif['type'],
            'message' => $message,
            'icon' => $icon,
            'from_id' => $notif['from_id'] ? (int)$notif['from_id'] : null,
            'from_username' => $notif['from_username'],
            'from_avatar' => $notif['from_avatar'],
            'note_id' => $notif['note_id'] ? (int)$notif['note_id'] : null,
            'note_title' => $notif['note_title'],
            'is_read' => (bool)$notif['is_read'],
            'created_at' => $notif['created_at'],
            'time_ago' => timeAgo($notif['created_at'])
        ];
    }
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'notifications' => $formatted,
        'unread_count' => $unreadCount
    ]);
    
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch notifications'
    ]);
}

function timeAgo($datetime) {
    if (empty($datetime)) {
        return 'just now';
    }
    
    // Create DateTime objects for proper timezone handling
    try {
        $date = new DateTime($datetime);
        $now = new DateTime();
        $diff = $now->getTimestamp() - $date->getTimestamp();
        
        // Handle negative differences (future dates)
        if ($diff < 0) {
            return 'just now';
        }
        
        // Less than 1 minute
        if ($diff < 60) {
            return 'just now';
        }
        
        // Less than 1 hour
        if ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes . 'm ago';
        }
        
        // Less than 24 hours
        if ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . 'h ago';
        }
        
        // Less than 7 days
        if ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . 'd ago';
        }
        
        // Less than 30 days
        if ($diff < 2592000) {
            $weeks = floor($diff / 604800);
            return $weeks . 'w ago';
        }
        
        // Less than 1 year
        if ($diff < 31536000) {
            $months = floor($diff / 2592000);
            return $months . 'mo ago';
        }
        
        // More than 1 year
        return $date->format('M j, Y');
        
    } catch (Exception $e) {
        // Fallback if datetime parsing fails
        return 'recently';
    }
}


