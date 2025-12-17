<?php
declare(strict_types=1);

// Prevent multiple includes
if (defined('BOOTSTRAP_LOADED')) {
    return;
}
define('BOOTSTRAP_LOADED', true);

/*  session  */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*  PDO  */
$dsn  = 'mysql:host=localhost;dbname=student_notes_hub;charset=utf8mb4';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    exit('Database connection failed: ' . $e->getMessage());
}

/*  mini-helpers  */
function ensureLoggedIn(): void
{
    if (!isset($_SESSION['auth_user_id'])) {
        header('Location: login.php');
        exit;
    }
    
    // Check if email is verified - block access if not verified
    global $pdo;
    try {
        // Add email_verified column if it doesn't exist
        try {
            $pdo->exec("ALTER TABLE users ADD COLUMN email_verified TINYINT(1) DEFAULT 0");
        } catch (Exception $e) {
            // Column might already exist
        }
        
        $stmt = $pdo->prepare('SELECT email_verified FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['auth_user_id']]);
        $user = $stmt->fetch();
        
        if ($user && !$user['email_verified']) {
            // User is logged in but email is not verified - log them out and redirect
            $userEmail = $_SESSION['email'] ?? '';
            session_destroy();
            session_start();
            header('Location: login.php?verification_required=1' . ($userEmail ? '&email=' . urlencode($userEmail) : ''));
            exit;
        }
    } catch (Exception $e) {
        // If there's an error checking verification, allow access (fail open for existing users)
        // This prevents breaking the site if there's a database issue
    }
}

function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): void
{
    header("Location: $url");
    exit;
}

function csrf(): string
{
    return $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

function csrf_ok(string $token): bool
{
    return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

/*  NEW – honest counters  */
function realViews(int $noteId): int
{
    global $pdo;
    return (int) $pdo->query(
        "SELECT COUNT(DISTINCT ip_address) FROM views 
         WHERE note_id = $noteId AND created_at >= NOW() - INTERVAL 24 HOUR"
    )->fetchColumn();
}

function realLikes(int $noteId): int
{
    global $pdo;
    return (int) $pdo->query("SELECT COUNT(*) FROM likes WHERE note_id = $noteId")->fetchColumn();
}

function realDownloads(int $noteId): int
{
    global $pdo;
    return (int) $pdo->query("SELECT COUNT(*) FROM downloads WHERE note_id = $noteId")->fetchColumn();
}

function realDownloadsForNote(int $noteId): int
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM downloads WHERE note_id = ?");
    $stmt->execute([$noteId]);
    return (int) $stmt->fetchColumn();
}
/*  save base-64 image (avatar / cover / etc)  */
function saveBase64Image(string $base64, string $name): ?string
{
    if (!preg_match('/^data:image\/(\w+);base64,/', $base64, $matches)) return null;

    $ext = $matches[1];
    if (!in_array($ext, ['jpg','jpeg','png','gif'])) return null;

    $data = substr($base64, strpos($base64, ',') + 1);
    $data = base64_decode($data);
    if ($data === false) return null;

    $fileName = $name . '.' . $ext;
    $dir = __DIR__ . '/uploads';
    if (!is_dir($dir)) mkdir($dir, 0777, true);

    $path = $dir . '/' . $fileName;
    return file_put_contents($path, $data) ? 'uploads/' . $fileName : null;
}