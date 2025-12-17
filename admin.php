<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

/* ---------- guard ---------- */
if (!isset($_SESSION['auth_admin_id'])) redirect('login.php');
$adminId = (int)$_SESSION['auth_admin_id'];
$stmt    = $pdo->prepare('SELECT * FROM admins WHERE id = ?');
$stmt->execute([$adminId]);
$admin   = $stmt->fetch();
if (!$admin) redirect('logout.php');

/* ---------- helpers ---------- */
function deleteRow(string $table, int $id): void {
    global $pdo;
    $pdo->prepare("DELETE FROM `$table` WHERE id = ? LIMIT 1")->execute([$id]);
}
function publishNote(int $id, bool $pub = true): void {
    global $pdo;
    $status = $pub ? 'published' : 'draft';
    $pdo->prepare("UPDATE notes SET status = ? WHERE id = ? LIMIT 1")->execute([$status, $id]);
}

/* ---------- actions ---------- */
$action = $_GET['action'] ?? '';
$id     = (int)($_GET['id']   ?? 0);

if ($action === 'del_user'   && $id) { deleteRow('users', $id); }
if ($action === 'del_note'   && $id) { deleteRow('notes', $id); }
if ($action === 'pub_note'   && $id) { publishNote($id, true);  }
if ($action === 'unpub_note' && $id) { publishNote($id, false); }
if ($action === 'resolve_report' && $id) {
    try {
        $pdo->prepare('UPDATE reports SET status = ? WHERE id = ?')->execute(['resolved', $id]);
        // Mark related notifications as read
        $pdo->prepare('UPDATE admin_notifications SET is_read = 1 WHERE admin_id = ? AND reference_id = (SELECT note_id FROM reports WHERE id = ?)')->execute([$adminId, $id]);
    } catch (Exception $e) {}
}

if ($action) redirect('admin.php?ok=1');

/* ---------- data ---------- */
$users = $pdo->query('SELECT * FROM users ORDER BY id DESC')->fetchAll();
$notes = $pdo->query('SELECT n.*, u.username FROM notes n JOIN users u ON u.id = n.user_id ORDER BY n.id DESC')->fetchAll();

// Get reports
$reports = [];
try {
    $reports = $pdo->query('
        SELECT r.*, n.title as note_title, u.username as reporter_name, n.user_id as note_author_id
        FROM reports r
        JOIN notes n ON n.id = r.note_id
        JOIN users u ON u.id = r.reporter_id
        ORDER BY r.created_at DESC
    ')->fetchAll();
} catch (Exception $e) {
    // Reports table might not exist yet
}

// Get unread admin notifications count
$unreadCount = 0;
try {
    $notifStmt = $pdo->prepare('SELECT COUNT(*) FROM admin_notifications WHERE admin_id = ? AND is_read = 0');
    $notifStmt->execute([$adminId]);
    $unreadCount = (int)$notifStmt->fetchColumn();
} catch (Exception $e) {
    // Notifications table might not exist yet
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Admin – Student Notes Hub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root{--p:#6366f1;--pd:#4f46e5;--t:#1f2937;--ts:#6b7280;--bg:#f9fafb;--b:#e5e7eb;--ok:#10b981;--bad:#ef4444;}
        body{font-family:Inter,sans-serif;background:var(--bg);color:var(--t);margin:0;}
        .nav{position:sticky;top:0;background:#fff;box-shadow:0 2px 6px rgba(0,0,0,.05);z-index:1000;}
        .nav .cnt{max-width:1200px;margin:auto;padding:0 1rem;display:flex;align-items:center;justify-content:space-between;height:70px;}
        .nav .logo{font-weight:700;color:var(--p);text-decoration:none;font-size:1.25rem;}
        .btn-logout{background:var(--bad);color:#fff;border:none;padding:.5rem 1rem;border-radius:8px;font-weight:500;}
        .admin{padding:2rem;max-width:1200px;margin:auto;}
        .card{background:#fff;border-radius:16px;box-shadow:0 4px 6px -1px rgba(0,0,0,.1);padding:2rem;margin-bottom:2rem;}
        h2{margin-bottom:1rem;color:var(--p);}
        table{width:100%;border-collapse:collapse;font-size:.9rem;}
        th,td{padding:.75rem;text-align:left;border-bottom:1px solid var(--b);}
        th{color:var(--ts);}
        .badge{padding:.25rem .5rem;border-radius:12px;font-size:.75rem;font-weight:500;}
        .badge-published{background:rgba(16,185,129,.1);color:var(--ok);}
        .badge-draft{background:rgba(245,158,11,.1);color:#f59e0b;}
        .btn-sm{padding:.35rem .75rem;border:none;border-radius:6px;font-size:.75rem;font-weight:500;cursor:pointer;}
        .btn-danger{background:var(--bad);color:#fff;}
        .btn-info{background:var(--p);color:#fff;}
        .notification{position:fixed;top:20px;right:20px;padding:1rem 1.5rem;border-radius:8px;color:#fff;font-weight:500;z-index:10001;}
        .notification.success{background:var(--ok);}
    </style>
</head>
<body>

<nav class="nav">
    <div class="cnt">
        <a href="admin.php" class="logo"><i class="fas fa-shield-alt"></i> Admin Panel</a>
        <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</nav>

<div class="admin">
    <div class="card">
        <h2>Welcome, <?= e($admin['username']) ?></h2>
        <p>Role: <strong><?= e($admin['role']) ?></strong></p>
    </div>

    <div class="card">
        <h2>Users</h2>
        <table>
            <thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Action</th></tr></thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= $u['id'] ?></td><td><?= e($u['username']) ?></td><td><?= e($u['email']) ?></td><td><?= e($u['role']) ?></td>
                    <td><a href="?action=del_user&id=<?= $u['id'] ?>" class="btn-sm btn-danger" onclick="return confirm('Delete user?')">Delete</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h2>Notes</h2>
        <table>
            <thead><tr><th>ID</th><th>Title</th><th>Author</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($notes as $n): ?>
                <tr>
                    <td><?= $n['id'] ?></td><td><?= e($n['title']) ?></td><td><?= e($n['username']) ?></td>
                    <td><span class="badge badge-<?= e($n['status']) ?>"><?= ucfirst($n['status']) ?></span></td>
                    <td>
                        <?php if ($n['status']==='published'): ?>
                            <a href="?action=unpub_note&id=<?= $n['id'] ?>" class="btn-sm btn-info">Unpublish</a>
                        <?php else: ?>
                            <a href="?action=pub_note&id=<?= $n['id'] ?>" class="btn-sm btn-info">Publish</a>
                        <?php endif; ?>
                        <a href="?action=del_note&id=<?= $n['id'] ?>" class="btn-sm btn-danger" onclick="return confirm('Delete note?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if (!empty($reports)): ?>
    <div class="card">
        <h2>Reports <?php if ($unreadCount > 0): ?><span class="badge" style="background: var(--bad); color: white; margin-left: 0.5rem;"><?= $unreadCount ?> New</span><?php endif; ?></h2>
        <table>
            <thead><tr><th>ID</th><th>Note</th><th>Reporter</th><th>Reason</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($reports as $r): ?>
                <tr>
                    <td><?= $r['id'] ?></td>
                    <td><a href="note-detail.php?id=<?= $r['note_id'] ?>" target="_blank"><?= e($r['note_title']) ?></a></td>
                    <td><?= e($r['reporter_name']) ?></td>
                    <td><?= e($r['reason']) ?></td>
                    <td><span class="badge badge-<?= $r['status'] === 'pending' ? 'draft' : 'published' ?>"><?= ucfirst($r['status']) ?></span></td>
                    <td><?= date('M j, Y H:i', strtotime($r['created_at'])) ?></td>
                    <td>
                        <a href="note-detail.php?id=<?= $r['note_id'] ?>" class="btn-sm btn-info" target="_blank">View Note</a>
                        <?php if ($r['status'] === 'pending'): ?>
                            <a href="?action=resolve_report&id=<?= $r['id'] ?>" class="btn-sm btn-info">Mark Resolved</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php if (isset($_GET['ok'])): ?>
<div class="notification success"><i class="fas fa-check-circle"></i> Action completed.</div>
<script>setTimeout(()=>document.querySelector('.notification')?.remove(),3000);</script>
<?php endif; ?>

</body>
</html>