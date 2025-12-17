<?php
header('Content-Type: application/json');
require __DIR__ . '/../bootstrap.php';
ensureLoggedIn();

$userId = (int)$_SESSION['auth_user_id'];
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    // Build query
    if ($search !== '') {
        $stmt = $pdo->prepare("
            SELECT id, username, avatar_url, email
            FROM users
            WHERE id != ? AND (username LIKE ? OR email LIKE ?)
            ORDER BY username ASC
            LIMIT 50
        ");
        $searchTerm = '%' . $search . '%';
        $stmt->execute([$userId, $searchTerm, $searchTerm]);
    } else {
        $stmt = $pdo->prepare("
            SELECT id, username, avatar_url, email
            FROM users
            WHERE id != ?
            ORDER BY username ASC
            LIMIT 50
        ");
        $stmt->execute([$userId]);
    }

    echo json_encode([
        'success' => true,
        'users' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
