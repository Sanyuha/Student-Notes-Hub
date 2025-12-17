<?php
// View file handler - serves files for inline viewing in browser
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

// Get note ID from URL
$noteId = (int)($_GET['id'] ?? 0);

if ($noteId === 0) {
    http_response_code(404);
    exit('Note not found');
}

// Get note details - allow authors to view their own drafts
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
    exit('Note not found or you do not have permission to view it');
}

// Get file path
$filePath = __DIR__ . '/' . $note['file_url'];

if (!file_exists($filePath)) {
    http_response_code(404);
    exit('File not found on server');
}

// Security: Prevent directory traversal
$realPath = realpath($filePath);
$basePath = realpath(__DIR__);
if (!$realPath || strpos($realPath, $basePath) !== 0) {
    http_response_code(403);
    exit('Access denied');
}

// Get file info
$fileName = basename($filePath);
$fileSize = filesize($filePath);
$fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

// Determine MIME type based on extension
$mimeTypes = [
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xls' => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'ppt' => 'application/vnd.ms-powerpoint',
    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'txt' => 'text/plain',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'webp' => 'image/webp',
];

$mimeType = $mimeTypes[$fileExtension] ?? 'application/octet-stream';

// Set headers for inline viewing
header('Content-Type: ' . $mimeType);
header('Content-Disposition: inline; filename="' . basename($fileName) . '"');
header('Content-Length: ' . $fileSize);
header('Cache-Control: public, max-age=3600');
header('X-Content-Type-Options: nosniff');

// Output file content
readfile($filePath);
exit;
