<?php
// Download handler for Student Notes Hub
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

// Get note ID from URL
$noteId = (int)($_GET['id'] ?? 0);

if ($noteId === 0) {
    http_response_code(404);
    exit('Note not found');
}

// Get note details - allow authors to download their own drafts
$userId = isset($_SESSION['auth_user_id']) ? (int)$_SESSION['auth_user_id'] : 0;
$note = $pdo->prepare(
    'SELECT n.*, u.username 
     FROM notes n
     JOIN users u ON u.id = n.user_id
     WHERE n.id = ? AND (n.status = "published" OR (n.status = "draft" AND n.user_id = ?))'
);
$note->execute([$noteId, $userId]);
$note = $note->fetch();

if (!$note) {
    http_response_code(404);
    exit('Note not found or you do not have permission to download it');
}

// Get file path
$filePath = __DIR__ . '/' . $note['file_url'];

if (!file_exists($filePath)) {
    http_response_code(404);
    exit('File not found on server');
}

// Record download (only if not already tracked by API)
// Check if download was already tracked in the last second to avoid duplicates
$userId = $_SESSION['auth_user_id'] ?? null;
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

try {
    // Check for recent download from same user/IP
    $recentCheck = $pdo->prepare(
        'SELECT id FROM downloads 
         WHERE note_id = ? 
         AND (user_id = ? OR ip_address = ?)
         AND created_at > DATE_SUB(NOW(), INTERVAL 2 SECOND)'
    );
    $recentCheck->execute([$noteId, $userId, $ipAddress]);
    
    // Only record if no recent download found
    // The database trigger will automatically update notes.downloads
    if (!$recentCheck->fetch()) {
        $pdo->prepare(
            'INSERT INTO downloads (note_id, user_id, ip_address) VALUES (?, ?, ?)'
        )->execute([$noteId, $userId, $ipAddress]);
    }
} catch (Exception $e) {
    // Continue with download even if tracking fails
}

// Set headers for file download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Output file content
readfile($filePath);
exit;