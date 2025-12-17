<?php
header('Content-Type: application/json');
require __DIR__ . '/../bootstrap.php';
ensureLoggedIn();

$userId = (int)$_SESSION['auth_user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['file'])) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded']);
    exit;
}

$file = $_FILES['file'];
$conversationId = isset($_POST['conversation_id']) ? (int)$_POST['conversation_id'] : 0;
$groupId = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;

// Validate file
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'File upload error']);
    exit;
}

// Check file size (max 10MB)
if ($file['size'] > 10 * 1024 * 1024) {
    echo json_encode(['success' => false, 'error' => 'File size exceeds 10MB limit']);
    exit;
}

// Validate file type (allow common file types)
$allowedTypes = [
    // Images
    'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp', 'image/svg+xml',
    // Documents
    'application/pdf', 'application/msword', 
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-powerpoint',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    // Text files
    'text/plain', 'text/csv', 'text/html', 'text/css', 'text/javascript',
    // Archives
    'application/zip', 'application/x-zip-compressed', 'application/x-rar-compressed',
    'application/x-7z-compressed', 'application/gzip', 'application/x-tar',
    // Audio
    'audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/mp4', 'audio/webm',
    // Video
    'video/mp4', 'video/mpeg', 'video/quicktime', 'video/x-msvideo', 'video/webm',
    // Other common types
    'application/json', 'application/xml', 'application/octet-stream'
];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

// Allow if type is in allowed list, or if it's a generic binary/octet-stream (for unknown types)
if (!in_array($mimeType, $allowedTypes) && $mimeType !== 'application/octet-stream') {
    // For unknown types, check file extension as fallback
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'pdf', 'doc', 'docx', 
                         'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip', 'rar', '7z', 'gz', 
                         'tar', 'mp3', 'wav', 'ogg', 'mp4', 'avi', 'mov', 'webm', 'json', 'xml'];
    
    if (!in_array($extension, $allowedExtensions)) {
        echo json_encode(['success' => false, 'error' => 'File type not allowed. Allowed types: images, documents, archives, audio, video, and text files']);
        exit;
    }
}

try {
    // Verify authorization
    if ($conversationId > 0) {
        $checkStmt = $pdo->prepare("
            SELECT id FROM conversations 
            WHERE id = ? AND (user1_id = ? OR user2_id = ?)
        ");
        $checkStmt->execute([$conversationId, $userId, $userId]);
        if (!$checkStmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }
    } elseif ($groupId > 0) {
        $checkStmt = $pdo->prepare("
            SELECT group_id FROM group_members 
            WHERE group_id = ? AND user_id = ?
        ");
        $checkStmt->execute([$groupId, $userId]);
        if (!$checkStmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid conversation or group ID']);
        exit;
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'chat_' . $userId . '_' . time() . '_' . uniqid() . '.' . $extension;
    $uploadDir = __DIR__ . '/../uploads/chat/';
    
    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $filePath = $uploadDir . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        echo json_encode(['success' => false, 'error' => 'Failed to save file']);
        exit;
    }
    
    $fileUrl = 'uploads/chat/' . $filename;
    
    echo json_encode([
        'success' => true,
        'file_url' => $fileUrl,
        'file_name' => $file['name'],
        'file_type' => $mimeType,
        'file_size' => $file['size']
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

