<?php
// Profile page for Student Notes Hub – built-in delete + clean header
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

ensureLoggedIn();
$uid = (int)$_SESSION['auth_user_id'];

/* ----------  fetch categories once  ---------- */
$cats = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();

/* ----------  BUILT-IN DELETE NOTE  ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['delete_note'], $_POST['note_id'], $_POST['csrf'])) {

    if (!csrf_ok($_POST['csrf'])) {
        http_response_code(403);
        exit('Bad CSRF token');
    }

    $noteId = (int)$_POST['note_id'];

    /* verify ownership */
    $check = $pdo->prepare('SELECT id FROM notes WHERE id = ? AND user_id = ?');
    $check->execute([$noteId, $uid]);
    if (!$check->fetch()) {
        http_response_code(404);
        exit('Note not found');
    }

    /* cascade cleans likes/downloads/views */
    $pdo->prepare('DELETE FROM notes WHERE id = ? AND user_id = ?')
        ->execute([$noteId, $uid]);

    /* ajax? plain text, otherwise redirect */
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        exit('success');
    }
    redirect('profile.php?deleted=1');
}

/* ----------  PROFILE TEXT-ONLY UPDATE  ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_FILES['avatar']) && !isset($_POST['upload_note'])) {

    if (!csrf_ok($_POST['csrf'] ?? '')) {
        http_response_code(403);
        exit('Bad CSRF token');
    }

    $_SESSION['tmp_profile'] = [
        'bio'        => trim($_POST['bio']    ?? ''),
        'university' => trim($_POST['university'] ?? ''),
        'major'      => trim($_POST['major']  ?? ''),
        'year'       => trim($_POST['year']   ?? ''),
    ];

    $pdo->prepare(
        'UPDATE users
         SET bio = :bio, university = :uni, major = :maj, study_year = :yr
         WHERE id = :id LIMIT 1'
    )->execute([
        ':bio' => $_SESSION['tmp_profile']['bio'],
        ':uni' => $_SESSION['tmp_profile']['university'],
        ':maj' => $_SESSION['tmp_profile']['major'],
        ':yr'  => $_SESSION['tmp_profile']['year'],
        ':id'  => $uid,
    ]);

    /* cover (base64) – kept from your old code */
    if (!empty($_POST['cover'])) {
        $path = saveBase64Image($_POST['cover'], "cover_$uid");
        if ($path) {
            $pdo->prepare('UPDATE users SET cover_url = ? WHERE id = ?')->execute([$path, $uid]);
        }
    }

    unset($_SESSION['tmp_profile']);
    redirect('profile.php?updated=1');
}

/* ----------  AVATAR FILE  ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {

    if (!csrf_ok($_POST['csrf'] ?? '')) {
        http_response_code(403);
        exit('Bad CSRF token');
    }

    $file = $_FILES['avatar'];
    $ok   = false;
    if ($file['error'] === 0 && strpos($file['type'], 'image/') === 0) {
        $dir = 'uploads/';
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        $name = 'avatar_' . $uid . '_' . uniqid() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
        $path = $dir . $name;

        if (move_uploaded_file($file['tmp_name'], $path)) {
            $pdo->prepare('UPDATE users SET avatar_url = ? WHERE id = ?')->execute([$path, $uid]);
            $ok = true;
        }
    }
    redirect('profile.php?avatar=' . ($ok ? '1' : '0'));
}

/* ----------  NEW NOTE UPLOAD  ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_note'])) {

    if (!csrf_ok($_POST['csrf'] ?? '')) {
        http_response_code(403);
        exit('Bad CSRF token');
    }

    $title       = trim($_POST['title']       ?? '');
    $description = trim($_POST['description'] ?? '');
    $categoryId  = (int)($_POST['category']  ?? 0);
    $status      = in_array($_POST['status'] ?? '', ['published','draft'], true)
                   ? $_POST['status'] : 'draft';

    if ($title === '' || $categoryId === 0 || !isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        redirect('profile.php?note_error=1');
    }

    $file    = $_FILES['file'];
    $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['pdf','doc','docx','zip'];
    if (!in_array($ext, $allowed, true)) {
        redirect('profile.php?note_error=1');
    }

    $dir = 'uploads/';
    if (!is_dir($dir)) mkdir($dir, 0777, true);

    $fileName = uniqid() . '_' . basename($file['name']);
    $filePath = $dir . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        redirect('profile.php?note_error=1&debug=move_failed');
    }

    try {
        // Get file size
        $fileSize = file_exists($filePath) ? filesize($filePath) : 0;
        
        $stmt = $pdo->prepare(
            'INSERT INTO notes (user_id, category_id, title, description, file_url, file_size, file_type, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$uid, $categoryId, $title, $description, $filePath, $fileSize, $ext, $status]);
        $newNoteId = (int)$pdo->lastInsertId();
        
        redirect('profile.php?note_ok=1');
    } catch (Exception $e) {
        redirect('profile.php?note_error=1&debug=' . urlencode($e->getMessage()));
    }
}

/* ----------  user data  ---------- */
$userStmt = $pdo->prepare('
    SELECT u.id, u.username, u.avatar_url, u.cover_url, u.bio, u.university, u.major, u.study_year, u.member_since,
           (SELECT COUNT(*) FROM notes WHERE notes.user_id = u.id) as notes_count,
           (SELECT COUNT(*) FROM follows WHERE follows.follower_id = u.id) as following_count,
           (SELECT COUNT(*) FROM follows WHERE follows.following_id = u.id) as followers_count
    FROM users u
    WHERE u.id = ?
');
$userStmt->execute([$uid]);
$user = $userStmt->fetch();
if (!$user) redirect('logout.php');

$noteStmt = $pdo->prepare(
    'SELECT n.*, c.name AS cat
     FROM notes n
     JOIN categories c ON c.id = n.category_id
     WHERE n.user_id = ?
     ORDER BY n.created_at DESC
     LIMIT 6'
);
$noteStmt->execute([$uid]);
$notes = $noteStmt->fetchAll();

/* ----------  form sticky on error  ---------- */
if (isset($_SESSION['tmp_profile'])) {
    foreach ($_SESSION['tmp_profile'] as $k => $v) $user[$k] = $v;
    unset($_SESSION['tmp_profile']);
}

$_SESSION['csrf'] = csrf();
$pageTitle = 'Profile - ' . htmlspecialchars($user['username']) . ' - Student Notes Hub';

// Include header
require __DIR__ . '/components/header.php';
?>

<style>
        /* ----  identical CSS you already had  ---- */
        :root{--primary-color:#6366f1;--primary-dark:#4f46e5;--text-primary:#1f2937;--text-secondary:#6b7280;--text-muted:#9ca3af;--bg-secondary:#f9fafb;--border-color:#e5e7eb;--success-color:#10b981;--error-color:#ef4444;--warning-color:#f59e0b;}
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:'Inter',-apple-system,BlinkMacSystemFont,sans-serif;background:var(--bg-secondary);color:var(--text-primary);line-height:1.6;}
        /* ----------  NEW CLEAN HEADER  ---------- */
        .main-header{background:var(--bg-primary);border-bottom:1px solid var(--border-color);padding:.75rem 0;position:sticky;top:0;z-index:1000;}
        .header-container{max-width:1200px;margin:0 auto;padding:0 1rem;display:flex;align-items:center;justify-content:space-between;}
        .logo{display:flex;align-items:center;gap:.5rem;font-size:1.25rem;font-weight:700;color:var(--primary-color);text-decoration:none;}
        .logo i{font-size:1.5rem;}
        .user-area{display:flex;align-items:center;gap:.75rem;}
        .user-name{font-size:.875rem;color:var(--text-secondary);}
        .user-avatar{width:32px;height:32px;border-radius:50%;object-fit:cover;border:1px solid var(--border-color);}
        .logout-form{margin:0;}
        .logout-btn{background:none;border:none;color:var(--text-muted);font-size:1.1rem;cursor:pointer;padding:.25rem .4rem;border-radius:4px;transition:color .2s;}
        .logout-btn:hover{color:var(--error-color);}
        @media(max-width:768px){.user-name{display:none;}}
        /* ----------  REFINED PROFILE SECTION  ---------- */
        .profile-section{
            padding:2rem 0 4rem;
            min-height:calc(100vh - 70px);
            background:linear-gradient(180deg, rgba(99,102,241,0.03) 0%, transparent 50%);
        }
        .container{
            max-width:1200px;
            margin:0 auto;
            padding:0 1.5rem;
        }
        .profile-card{
            background:var(--bg-primary);
            border-radius:24px;
            box-shadow:0 10px 40px rgba(0,0,0,0.08);
            padding:3rem;
            margin-bottom:2rem;
            border:1px solid var(--border-color);
            position:relative;
            overflow:hidden;
        }
        .profile-card::before{
            content:'';
            position:absolute;
            top:0;
            left:0;
            right:0;
            height:4px;
            background:linear-gradient(90deg,var(--primary-color),#8b5cf6,var(--primary-color));
            background-size:200% 100%;
            animation:gradientShift 3s ease infinite;
        }
        @keyframes gradientShift{
            0%,100%{background-position:0% 50%;}
            50%{background-position:100% 50%;}
        }
        .profile-header{
            display:flex;
            align-items:flex-start;
            gap:3rem;
            margin-bottom:2.5rem;
            flex-wrap:wrap;
        }
        .avatar-section{
            display:flex;
            flex-direction:column;
            align-items:center;
            gap:1rem;
            position:relative;
        }
        .avatar-section .edit-profile-btn{
            width:100%;
            justify-content:center;
        }
        .avatar{
            width:140px;
            height:140px;
            border-radius:50%;
            border:4px solid var(--bg-primary);
            position:relative;
            overflow:hidden;
            background:var(--bg-light);
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:3rem;
            color:var(--text-muted);
            box-shadow:0 8px 24px rgba(99,102,241,0.2);
            transition:transform .3s ease;
        }
        .avatar:hover{
            transform:scale(1.05);
        }
        .avatar img{
            width:100%;
            height:100%;
            object-fit:cover;
        }
        .avatar-placeholder{
            width:100%;
            height:100%;
            display:flex;
            align-items:center;
            justify-content:center;
            background:linear-gradient(135deg,var(--primary-color),#8b5cf6);
            color:white;
            font-size:3.5rem;
        }
        .avatar-edit-btn{
            background:var(--primary-color);
            color:white;
            border:none;
            padding:.5rem 1rem;
            border-radius:8px;
            cursor:pointer;
            font-weight:500;
            font-size:.9rem;
            transition:all .3s cubic-bezier(0.4, 0, 0.2, 1);
            display:flex;
            align-items:center;
            gap:.5rem;
        }
        .avatar-edit-btn:hover{
            background:var(--primary-dark);
            transform:translateY(-2px) scale(1.05);
            box-shadow:0 4px 8px rgba(99,102,241,.3);
        }
        .avatar-edit-btn:active{
            transform:translateY(0) scale(1);
        }
        .avatar-edit-btn i{
            transition:transform .3s ease;
        }
        .avatar-edit-btn:hover i{
            transform:rotate(15deg);
        }
        .profile-info{
            flex:1;
            min-width:0;
        }
        .profile-info h1{
            font-size:clamp(1.75rem,4vw,2.5rem);
            margin-bottom:.75rem;
            color:var(--text-primary);
            font-weight:700;
            letter-spacing:-0.02em;
            background:linear-gradient(135deg,var(--primary-color),#8b5cf6);
            -webkit-background-clip:text;
            -webkit-text-fill-color:transparent;
            background-clip:text;
        }
        .profile-info p{
            color:var(--text-secondary);
            margin-bottom:1.5rem;
            line-height:1.7;
            font-size:1.05rem;
            max-width:600px;
        }
        .profile-stats{
            display:flex;
            gap:2rem;
            margin-bottom:1.5rem;
            flex-wrap:wrap;
        }
        .stat{
            text-align:left;
            padding:1rem 1.5rem;
            background:var(--bg-secondary);
            border-radius:12px;
            border:1px solid var(--border-color);
            min-width:100px;
        }
        .stat.clickable-stat{
            transition:all .2s ease;
        }
        .stat.clickable-stat:hover{
            background:var(--bg-light);
            border-color:var(--primary-color);
        }
        .stat b{
            font-size:1.5rem;
            color:var(--primary-color);
            display:block;
            font-weight:700;
            margin-bottom:.25rem;
        }
        .stat span{
            font-size:.875rem;
            color:var(--text-secondary);
            font-weight:500;
            text-transform:uppercase;
            letter-spacing:0.5px;
        }
        .edit-profile-btn{
            background:var(--primary-color);
            color:white;
            border:none;
            padding:.6rem 1.2rem;
            border-radius:8px;
            cursor:pointer;
            font-weight:500;
            transition:all .3s cubic-bezier(0.4, 0, 0.2, 1);
            display:inline-flex;
            align-items:center;
            gap:.5rem;
            position:relative;
            overflow:hidden;
        }
        .edit-profile-btn::before{
            content:'';
            position:absolute;
            top:0;
            left:-100%;
            width:100%;
            height:100%;
            background:linear-gradient(90deg,transparent,rgba(255,255,255,.2),transparent);
            transition:left .5s;
        }
        .edit-profile-btn:hover::before{
            left:100%;
        }
        .edit-profile-btn:hover{
            background:var(--primary-dark);
            transform:translateY(-2px);
            box-shadow:0 4px 12px rgba(99,102,241,.3);
        }
        .edit-profile-btn:active{
            transform:translateY(0);
        }
        .notes-section{
            margin-top:3rem;
        }
        .section-header{
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:2rem;
            flex-wrap:wrap;
            gap:1rem;
            padding-bottom:1.5rem;
            border-bottom:2px solid var(--border-color);
        }
        .section-title{
            font-size:1.75rem;
            color:var(--text-primary);
            margin:0;
            font-weight:700;
            display:flex;
            align-items:center;
            gap:.75rem;
        }
        .section-title::before{
            content:'';
            width:4px;
            height:24px;
            background:linear-gradient(180deg,var(--primary-color),#8b5cf6);
            border-radius:2px;
        }
        .upload-note-btn{
            background:linear-gradient(135deg,var(--primary-color),var(--primary-dark));
            color:white;
            border:none;
            padding:.75rem 1.5rem;
            border-radius:8px;
            cursor:pointer;
            font-weight:500;
            transition:all .3s cubic-bezier(0.4, 0, 0.2, 1);
            display:inline-flex;
            align-items:center;
            gap:.5rem;
            position:relative;
            overflow:hidden;
        }
        .upload-note-btn::before{
            content:'';
            position:absolute;
            top:50%;
            left:50%;
            width:0;
            height:0;
            border-radius:50%;
            background:rgba(255,255,255,.2);
            transform:translate(-50%,-50%);
            transition:width .6s,height .6s;
        }
        .upload-note-btn:hover::before{
            width:300px;
            height:300px;
        }
        .upload-note-btn:hover{
            transform:translateY(-2px) scale(1.02);
            box-shadow:0 8px 16px rgba(99,102,241,.4);
        }
        .upload-note-btn:active{
            transform:translateY(0) scale(0.98);
        }
        .upload-note-btn i{
            transition:transform .3s ease;
        }
        .upload-note-btn:hover i{
            transform:rotate(90deg) scale(1.1);
        }
        .notes-grid{
            display:grid;
            grid-template-columns:repeat(auto-fill,minmax(320px,1fr));
            gap:2rem;
            margin-top:1.5rem;
        }
        .note-card{
            background:var(--bg-primary);
            border:2px solid var(--border-color);
            border-radius:16px;
            padding:2rem;
            cursor:pointer;
            transition:all .4s cubic-bezier(0.4, 0, 0.2, 1);
            position:relative;
            overflow:hidden;
            box-shadow:0 2px 8px rgba(0,0,0,0.04);
        }
        .note-card::before{
            content:'';
            position:absolute;
            top:0;
            left:0;
            right:0;
            height:4px;
            background:linear-gradient(90deg,var(--primary-color),#8b5cf6);
            transform:scaleX(0);
            transform-origin:left;
            transition:transform .4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .note-card:hover::before{
            transform:scaleX(1);
        }
        .note-card::after{
            content:'';
            position:absolute;
            top:50%;
            left:50%;
            width:0;
            height:0;
            border-radius:50%;
            background:rgba(99,102,241,0.05);
            transform:translate(-50%,-50%);
            transition:width .6s,height .6s;
        }
        .note-card:hover::after{
            width:300px;
            height:300px;
        }
        .note-card:hover{
            transform:translateY(-8px);
            box-shadow:0 20px 40px rgba(99,102,241,0.15);
            border-color:var(--primary-color);
        }
        .note-status{
            display:inline-block;
            padding:.4rem 1rem;
            border-radius:20px;
            font-size:.75rem;
            font-weight:600;
            margin-bottom:1rem;
            text-transform:uppercase;
            letter-spacing:0.5px;
            position:relative;
            z-index:1;
        }
        .note-status.published{
            background:linear-gradient(135deg,rgba(16,185,129,.15),rgba(16,185,129,.1));
            color:var(--success-color);
            border:1px solid rgba(16,185,129,.2);
        }
        .note-status.draft{
            background:linear-gradient(135deg,rgba(245,158,11,.15),rgba(245,158,11,.1));
            color:var(--warning-color);
            border:1px solid rgba(245,158,11,.2);
        }
        .note-title{
            font-size:1.25rem;
            font-weight:700;
            margin-bottom:.75rem;
            color:var(--text-primary);
            line-height:1.3;
            position:relative;
            z-index:1;
        }
        .note-description{
            color:var(--text-secondary);
            font-size:.95rem;
            line-height:1.6;
            margin-bottom:1.5rem;
            display:-webkit-box;
            -webkit-line-clamp:3;
            -webkit-box-orient:vertical;
            overflow:hidden;
            position:relative;
            z-index:1;
        }
        .note-meta{
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-top:1.5rem;
            padding-top:1.5rem;
            border-top:2px solid var(--border-color);
            position:relative;
            z-index:1;
        }
        .note-stats{
            display:flex;
            gap:1.5rem;
            font-size:.875rem;
            color:var(--text-secondary);
            font-weight:500;
        }
        .note-stats span{
            display:flex;
            align-items:center;
            gap:.4rem;
        }
        .note-stats i{
            color:var(--primary-color);
        }
        .note-actions{display:flex;gap:.5rem;}
        .action-btn{
            padding:.4rem .8rem;
            border:none;
            border-radius:6px;
            font-size:.8rem;
            cursor:pointer;
            transition:all .3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration:none;
            display:inline-flex;
            align-items:center;
            gap:.25rem;
            position:relative;
            overflow:hidden;
        }
        .action-btn::after{
            content:'';
            position:absolute;
            top:50%;
            left:50%;
            width:0;
            height:0;
            border-radius:50%;
            background:rgba(255,255,255,.3);
            transform:translate(-50%,-50%);
            transition:width .4s,height .4s;
        }
        .action-btn:hover::after{
            width:200px;
            height:200px;
        }
        .action-btn.primary{background:var(--primary-color);color:white;}
        .action-btn.secondary{background:var(--text-secondary);color:white;}
        .action-btn.success{background:var(--success-color);color:white;}
        .action-btn.danger{background:var(--error-color);color:white;}
        .action-btn:hover{
            transform:translateY(-2px) scale(1.1);
            box-shadow:0 4px 8px rgba(0,0,0,.2);
            z-index:1;
        }
        .action-btn:active{
            transform:translateY(0) scale(1);
        }
        .action-btn i{
            transition:transform .3s ease;
            position:relative;
            z-index:1;
        }
        .action-btn:hover i{
            transform:scale(1.2);
        }
        .empty-state{
            grid-column:1/-1;
            text-align:center;
            padding:5rem 2rem;
            color:var(--text-secondary);
            background:var(--bg-primary);
            border:2px dashed var(--border-color);
            border-radius:20px;
            transition:all .3s ease;
        }
        .empty-state:hover{
            border-color:var(--primary-color);
            background:var(--bg-secondary);
        }
        .empty-state i{
            font-size:5rem;
            color:var(--text-muted);
            margin-bottom:1.5rem;
            opacity:.4;
            display:inline-block;
            animation:float 3s ease-in-out infinite;
        }
        @keyframes float{
            0%,100%{transform:translateY(0);}
            50%{transform:translateY(-10px);}
        }
        .empty-state h3{
            font-size:1.75rem;
            margin-bottom:.75rem;
            color:var(--text-primary);
            font-weight:700;
        }
        .empty-state p{
            color:var(--text-secondary);
            font-size:1.05rem;
        }
        .modal{
            display:none;
            position:fixed;
            top:0;
            left:0;
            right:0;
            bottom:0;
            background:rgba(0,0,0,0.6);
            z-index:1000;
            backdrop-filter:blur(8px);
            opacity:0;
            transition:opacity .3s ease;
        }
        .modal.active{
            display:flex;
            align-items:center;
            justify-content:center;
            opacity:1;
            animation:fadeIn .3s ease;
        }
        @keyframes fadeIn{
            from{opacity:0;}
            to{opacity:1;}
        }
        .modal-content{
            background:var(--bg-primary);
            border-radius:24px;
            padding:2.5rem;
            width:90%;
            max-width:600px;
            max-height:90vh;
            overflow-y:auto;
            border:1px solid var(--border-color);
            box-shadow:0 20px 60px rgba(0,0,0,0.3);
            transform:scale(0.9);
            transition:transform .3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .modal.active .modal-content{
            transform:scale(1);
        }
        .modal-header{
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:2rem;
            padding-bottom:1.5rem;
            border-bottom:2px solid var(--border-color);
        }
        .modal-header h2{
            color:var(--text-primary);
            margin:0;
            font-size:1.75rem;
            font-weight:700;
            background:linear-gradient(135deg,var(--primary-color),#8b5cf6);
            -webkit-background-clip:text;
            -webkit-text-fill-color:transparent;
            background-clip:text;
        }
        .close-modal{
            background:none;
            border:none;
            font-size:1.75rem;
            cursor:pointer;
            color:var(--text-secondary);
            padding:.5rem;
            width:40px;
            height:40px;
            display:flex;
            align-items:center;
            justify-content:center;
            border-radius:8px;
            transition:all .3s ease;
        }
        .close-modal:hover{
            background:var(--bg-secondary);
            color:var(--error-color);
            transform:rotate(90deg);
        }
        /* Followers Modal Styles */
        #followersList .user-item{
            display:flex;
            align-items:center;
            gap:1rem;
            padding:1rem;
            border-bottom:1px solid var(--border-color);
            cursor:pointer;
            transition:background .2s ease;
        }
        #followersList .user-item:hover{
            background:var(--bg-secondary);
        }
        #followersList .user-item:last-child{
            border-bottom:none;
        }
        #followersList img{
            width:50px;
            height:50px;
            border-radius:50%;
            object-fit:cover;
            border:2px solid var(--border-color);
        }
        .form-group{
            margin-bottom:1.75rem;
        }
        .form-group label{
            display:block;
            margin-bottom:.75rem;
            font-weight:600;
            color:var(--text-primary);
            font-size:.95rem;
        }
        .form-group input,
        .form-group textarea,
        .form-group select{
            width:100%;
            padding:1rem;
            border:2px solid var(--border-color);
            border-radius:12px;
            font-size:1rem;
            transition:all .3s ease;
            background:var(--bg-primary);
            color:var(--text-primary);
            font-family:inherit;
        }
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus{
            outline:none;
            border-color:var(--primary-color);
            box-shadow:0 0 0 4px rgba(99,102,241,.1);
            transform:translateY(-1px);
        }
        .form-group textarea{
            resize:vertical;
            min-height:120px;
            line-height:1.6;
        }
        .form-actions{display:flex;gap:1rem;justify-content:flex-end;margin-top:2rem;}
        .btn{padding:.75rem 1.5rem;border:none;border-radius:8px;font-weight:500;cursor:pointer;transition:all .2s ease;text-decoration:none;display:inline-flex;align-items:center;gap:.5rem;}
        .btn-primary{background:var(--primary-color);color:white;}
        .btn-primary:hover{background:var(--primary-dark);transform:translateY(-1px);box-shadow:0 2px 4px rgba(0,0,0,.1);}
        .btn-secondary{background:var(--text-secondary);color:white;}
        .btn-secondary:hover{background:#4b5563;transform:translateY(-1px);box-shadow:0 2px 4px rgba(0,0,0,.1);}
        .notification{position:fixed;top:20px;right:20px;padding:1rem 1.5rem;border-radius:8px;color:white;font-weight:500;z-index:10001;transform:translateX(100%);transition:transform .3s ease;}
        .notification.show{transform:translateX(0);}
        .notification.success{background:var(--success-color);}
        .notification.error{background:var(--error-color);}
        .notification.info{background:var(--primary-color);}
        .btn{
            padding:.75rem 1.5rem;
            border:none;
            border-radius:8px;
            font-weight:500;
            cursor:pointer;
            transition:all .3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration:none;
            display:inline-flex;
            align-items:center;
            gap:.5rem;
            position:relative;
            overflow:hidden;
        }
        .btn::before{
            content:'';
            position:absolute;
            top:0;
            left:-100%;
            width:100%;
            height:100%;
            background:linear-gradient(90deg,transparent,rgba(255,255,255,.2),transparent);
            transition:left .5s;
        }
        .btn:hover::before{
            left:100%;
        }
        .btn-primary{background:var(--primary-color);color:white;}
        .btn-primary:hover{
            background:var(--primary-dark);
            transform:translateY(-2px);
            box-shadow:0 4px 12px rgba(99,102,241,.3);
        }
        .btn-primary:active{
            transform:translateY(0);
        }
        .btn-secondary{background:var(--text-secondary);color:white;}
        .btn-secondary:hover{
            background:#4b5563;
            transform:translateY(-2px);
            box-shadow:0 4px 12px rgba(0,0,0,.2);
        }
        .btn-secondary:active{
            transform:translateY(0);
        }
        @media (max-width:768px){
            .profile-section{
                padding:1.5rem 0 3rem;
            }
            .container{
                padding:0 1rem;
            }
            .profile-card{
                padding:2rem 1.5rem;
                border-radius:20px;
            }
            .profile-header{
                flex-direction:column;
                text-align:center;
                gap:2rem;
            }
            .avatar{
                width:120px;
                height:120px;
            }
            .profile-stats{
                justify-content:center;
                gap:1rem;
            }
            .stat{
                padding:.75rem 1rem;
                min-width:80px;
            }
            .stat b{
                font-size:1.25rem;
            }
            .notes-grid{
                grid-template-columns:1fr;
                gap:1.5rem;
            }
            .section-header{
                flex-direction:column;
                align-items:flex-start;
            }
            .form-actions{
                flex-direction:column;
            }
            .form-actions .btn{
                width:100%;
                justify-content:center;
            }
            .upload-note-btn,
            .edit-profile-btn,
            .avatar-edit-btn{
                width:100%;
                justify-content:center;
            }
        }
        @media (max-width:480px){
            .profile-card{
                padding:1.5rem 1rem;
            }
            .profile-stats{
                grid-template-columns:repeat(3,1fr);
                gap:.75rem;
            }
            .stat{
                padding:.5rem .75rem;
                min-width:auto;
            }
            .stat b{
                font-size:1.1rem;
            }
            .stat span{
                font-size:.75rem;
            }
        }
        
        /* Dark Mode Support */
        [data-theme="dark"] {
            --bg-primary: #1f2937;
            --bg-secondary: #111827;
            --bg-light: #374151;
            --text-primary: #f9fafb;
            --text-secondary: #d1d5db;
            --text-muted: #9ca3af;
            --border-color: #374151;
        }
        
        [data-theme="dark"] body {
            background: var(--bg-secondary);
            color: var(--text-primary);
        }
        
        [data-theme="dark"] .profile-section {
            background: var(--bg-secondary);
        }
        
        [data-theme="dark"] .profile-card,
        [data-theme="dark"] .note-card,
        [data-theme="dark"] .modal-content {
            background: var(--bg-primary);
            border-color: var(--border-color);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
        }
        
        [data-theme="dark"] .profile-card h1,
        [data-theme="dark"] .note-card h3,
        [data-theme="dark"] .note-title,
        [data-theme="dark"] .section-title {
            color: var(--text-primary);
        }
        
        [data-theme="dark"] .note-description,
        [data-theme="dark"] .profile-info p {
            color: var(--text-secondary);
        }
        
        [data-theme="dark"] .form-group input,
        [data-theme="dark"] .form-group textarea,
        [data-theme="dark"] .form-group select {
            background: var(--bg-light);
            border-color: var(--border-color);
            color: var(--text-primary);
        }
        
        [data-theme="dark"] .form-group input:focus,
        [data-theme="dark"] .form-group textarea:focus,
        [data-theme="dark"] .form-group select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
        }
        
        [data-theme="dark"] .form-group label {
            color: var(--text-primary);
        }
        
        [data-theme="dark"] .modal {
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(4px);
        }
        
        [data-theme="dark"] .close-modal {
            color: var(--text-secondary);
        }
        
        [data-theme="dark"] .close-modal:hover {
            color: var(--text-primary);
        }
        
        [data-theme="dark"] .avatar {
            border-color: var(--bg-primary);
            background: var(--bg-light);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
        }
        
        [data-theme="dark"] .stat b {
            color: var(--primary-light);
        }
        
        [data-theme="dark"] .stat span {
            color: var(--text-secondary);
        }
        
        [data-theme="dark"] .note-stats {
            color: var(--text-secondary);
        }
        
        [data-theme="dark"] .note-status {
            background: rgba(99, 102, 241, 0.2);
            color: var(--primary-light);
        }
        
        [data-theme="dark"] .action-btn {
            background: var(--bg-light);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }
        
        [data-theme="dark"] .action-btn:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        [data-theme="dark"] .action-btn.secondary {
            background: var(--bg-light);
            color: var(--text-primary);
        }
        
        [data-theme="dark"] .action-btn.secondary:hover {
            background: var(--text-secondary);
            color: white;
        }
        
        [data-theme="dark"] .action-btn.danger {
            background: rgba(239, 68, 68, 0.2);
            color: var(--error-color);
        }
        
        [data-theme="dark"] .action-btn.danger:hover {
            background: var(--error-color);
            color: white;
        }
        
        [data-theme="dark"] .section-header {
            border-bottom-color: var(--border-color);
        }
        
        [data-theme="dark"] .upload-card {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border-color: rgba(255, 255, 255, 0.2);
        }
        
        [data-theme="dark"] .modal-header h2 {
            color: var(--primary-light);
        }
        
        [data-theme="dark"] .btn-secondary {
            background: var(--bg-light);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }
        
        [data-theme="dark"] .btn-secondary:hover {
            background: var(--text-secondary);
            color: white;
            border-color: var(--text-secondary);
        }
        
        [data-theme="dark"] .main-header {
            background: var(--bg-primary);
            border-bottom-color: var(--border-color);
        }
        
        [data-theme="dark"] .logo {
            color: var(--primary-light);
        }
        
        [data-theme="dark"] .user-name {
            color: var(--text-secondary);
        }
        
        [data-theme="dark"] .user-avatar {
            border-color: var(--border-color);
        }
        
        [data-theme="dark"] .logout-btn {
            color: var(--text-muted);
        }
        
        [data-theme="dark"] .logout-btn:hover {
            color: var(--error-color);
        }
        
        [data-theme="dark"] .note-card:hover {
            box-shadow: 0 10px 25px -3px rgba(0, 0, 0, 0.5);
        }
        
        [data-theme="dark"] .note-meta {
            border-top-color: var(--border-color);
        }
        
        [data-theme="dark"] .profile-section {
            background: var(--bg-secondary);
        }
        
        [data-theme="dark"] .container {
            background: transparent;
        }
    </style>
</head>
<body>

    <!-- ----------  PROFILE SECTION  ---------- -->
    <section class="profile-section">
        <div class="container">
            <div class="profile-card">
                <div class="profile-header">
                    <div class="avatar-section">
                        <div class="avatar">
                            <?php if (!empty($user['avatar_url']) && $user['avatar_url'] !== 'https://via.placeholder.com/150'): ?>
                                <img id="avatarImg" src="<?= e($user['avatar_url']) ?>" alt="avatar">
                            <?php else: ?>
                                <div class="avatar-placeholder"><i class="fas fa-user"></i></div>
                            <?php endif; ?>
                        </div>
                        <button class="avatar-edit-btn" onclick="changeAvatar()">
                            <i class="fas fa-camera"></i> Edit Avatar
                        </button>
                        <button class="edit-profile-btn" onclick="openEdit()">
                            <i class="fas fa-edit"></i> Edit Profile
                        </button>
                    </div>

                    <div class="profile-info">
                        <h1><?= e($user['username']) ?></h1>
                        <p><?= nl2br(e($user['bio'] ?? 'No bio yet.')) ?></p>

                        <div class="profile-stats">
                            <div class="stat"><b><?= (int)$user['notes_count'] ?></b><span>Notes</span></div>
                            <div class="stat clickable-stat" onclick="showFollowers()" style="cursor:pointer;">
                                <b><?= (int)$user['followers_count'] ?></b><span>Followers</span>
                            </div>
                            <div class="stat clickable-stat" onclick="showFollowing()" style="cursor:pointer;">
                                <b><?= (int)$user['following_count'] ?></b><span>Following</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ----------  NOTES SECTION  ---------- -->
            <div class="notes-section">
                <div class="section-header">
                    <h2 class="section-title">My Recent Notes</h2>
                    <button class="upload-note-btn" onclick="openUpload()">
                        <i class="fas fa-plus"></i> Upload Note
                    </button>
                </div>

                <div class="notes-grid">
                    <?php if (empty($notes)): ?>
                        <div class="empty-state">
                            <i class="fas fa-file-alt"></i>
                            <h3>No notes yet</h3>
                            <p>Start sharing your knowledge with the community</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($notes as $note): ?>
                            <div class="note-card" onclick="viewNote(<?= $note['id'] ?>)">
                                <div class="note-status <?= $note['status'] ?>"><?= ucfirst($note['status']) ?></div>
                                <h3 class="note-title"><?= e($note['title']) ?></h3>
                                <p class="note-description"><?= e($note['description'] ?? 'No description available.') ?></p>

                                <div class="note-meta">
                                    <div class="note-stats">
                                        <span><i class="fas fa-download"></i> <?= $note['downloads'] ?? 0 ?></span>
                                        <span><i class="fas fa-heart"></i> <?= $note['likes'] ?? 0 ?></span>
                                        <span><i class="fas fa-eye"></i> <?= $note['views'] ?? 0 ?></span>
                                    </div>
                                    <div class="note-actions">
                                        <button class="action-btn primary" onclick="event.stopPropagation(); viewNote(<?= $note['id'] ?>)" title="View"><i class="fas fa-eye"></i></button>
                                        <button class="action-btn secondary" onclick="event.stopPropagation(); editNote(<?= $note['id'] ?>)" title="Edit"><i class="fas fa-edit"></i></button>
                                        <button class="action-btn success" onclick="event.stopPropagation(); downloadNote(<?= $note['id'] ?>)" title="Download"><i class="fas fa-download"></i></button>
                                        <button class="action-btn danger" onclick="event.stopPropagation(); deleteNote(<?= $note['id'] ?>)" title="Delete"><i class="fas fa-trash"></i></button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- ==========  MODALS  ========== -->

    <!-- EDIT PROFILE (plain POST) -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Profile</h2>
                <button type="button" class="close-modal" onclick="closeEdit()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
                <div class="form-group">
                    <label for="bio">Bio</label>
                    <textarea id="bio" name="bio" placeholder="Tell us about yourself..."><?= e($user['bio'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label for="university">University</label>
                    <input type="text" id="university" name="university" placeholder="Your university" value="<?= e($user['university'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="major">Major</label>
                    <input type="text" id="major" name="major" placeholder="Your major" value="<?= e($user['major'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="year">Study Year</label>
                    <input type="text" id="year" name="year" placeholder="e.g., 3rd Year" value="<?= e($user['study_year'] ?? '') ?>">
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeEdit()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- UPLOAD NOTE (multipart) -->
    <div id="uploadModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Upload New Note</h2>
                <button type="button" class="close-modal" onclick="closeUpload()">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data" onsubmit="return validateUploadForm(this);">
                <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
                <input type="hidden" name="upload_note" value="1">

                <div class="form-group">
                    <label for="title">Note Title</label>
                    <input type="text" id="title" name="title" placeholder="Enter note title" required>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" placeholder="Describe your note..." rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category" name="category" required>
                        <option value="">Select a category</option>
                        <?php foreach ($cats as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="file">File (PDF, DOC, DOCX, ZIP)</label>
                    <input type="file" id="file" name="file" accept=".pdf,.doc,.docx,.zip" required>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeUpload()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload Note</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Followers/Following Modal -->
    <div id="followersModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2 id="followersModalTitle">Followers</h2>
                <button type="button" class="close-modal" onclick="closeFollowersModal()">&times;</button>
            </div>
            <div id="followersList" style="max-height: 500px; overflow-y: auto;">
                <div style="text-align: center; padding: 2rem;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--text-muted);"></i>
                    <p style="margin-top: 1rem; color: var(--text-secondary);">Loading...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- hidden avatar form -->
    <form id="avatarForm" method="post" enctype="multipart/form-data" style="display:none">
        <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
        <input type="file" name="avatar" accept="image/*" onchange="this.form.submit()">
    </form>

    <!-- ----------  JS  ---------- -->
    <script>
        /* modal helpers */
        function openEdit()   { document.getElementById('editModal').classList.add('active');   }
        function closeEdit()  { document.getElementById('editModal').classList.remove('active'); }
        function openUpload() { document.getElementById('uploadModal').classList.add('active'); }
        function closeUpload(){ document.getElementById('uploadModal').classList.remove('active');}

        /* upload validation */
        function validateUploadForm(form){
            const title   = form.title.value.trim();
            const cat     = form.category.value;
            const file    = form.file.files[0];
            if(!title){showNotification('Please enter a title','error');return false}
            if(!cat){showNotification('Please select a category','error');return false}
            if(!file){showNotification('Please choose a file','error');return false}
            if(file.size > 50*1024*1024){showNotification('File must be ≤ 50 MB','error');return false}
            return true;
        }

        /* avatar */
        function changeAvatar(){ document.getElementById('avatarForm').querySelector('input[name="avatar"]').click(); }

        /* note actions */
        function viewNote(id){window.location.href=`note-detail.php?id=${id}`;}
        function editNote(id) {window.location.href=`edit-note.php?id=${id}`;}
        function downloadNote(id){
            fetch('api/download.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({note_id:id})})
            .then(r=>r.json()).then(d=>showNotification(d.success?'Download started':'Download failed',d.success?'success':'error'));
        }
        function deleteNote(id){
            if(!confirm('Delete this note?'))return;
            fetch('profile.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
                  body:`csrf=<?= $_SESSION['csrf'] ?>&delete_note=1&note_id=${id}`})
            .then(r=>r.text()).then(t=>{if(t.includes('success'))location.reload();else showNotification('Delete failed','error');});
        }

        /* followers/following modal */
        function showFollowers(){
            document.getElementById('followersModalTitle').textContent = 'Followers';
            document.getElementById('followersModal').classList.add('active');
            loadFollowersList('followers');
        }
        function showFollowing(){
            document.getElementById('followersModalTitle').textContent = 'Following';
            document.getElementById('followersModal').classList.add('active');
            loadFollowersList('following');
        }
        function closeFollowersModal(){
            document.getElementById('followersModal').classList.remove('active');
        }
        async function loadFollowersList(type){
            const listDiv = document.getElementById('followersList');
            listDiv.innerHTML = '<div style="text-align: center; padding: 2rem;"><i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--text-muted);"></i><p style="margin-top: 1rem; color: var(--text-secondary);">Loading...</p></div>';
            
            try{
                const response = await fetch(`api/get-followers.php?type=${type}`);
                const data = await response.json();
                
                if(data.success && data.users.length > 0){
                    listDiv.innerHTML = data.users.map(user => {
                        const avatar = user.avatar_url && user.avatar_url !== 'https://via.placeholder.com/150' 
                            ? `<img src="${escapeHtml(user.avatar_url)}" alt="${escapeHtml(user.username)}" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid var(--border-color);">`
                            : `<div style="width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, var(--primary-color), #8b5cf6); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem;"><i class="fas fa-user"></i></div>`;
                        
                        return `
                            <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; border-bottom: 1px solid var(--border-color); cursor: pointer; transition: background .2s;" 
                                 onmouseover="this.style.background='var(--bg-secondary)'" 
                                 onmouseout="this.style.background=''" 
                                 onclick="window.location.href='profile_users.php?user_id=${user.id}'">
                                ${avatar}
                                <div style="flex: 1;">
                                    <div style="font-weight: 600; color: var(--text-primary); margin-bottom: 0.25rem;">${escapeHtml(user.username)}</div>
                                    <div style="font-size: 0.875rem; color: var(--text-secondary);">${user.notes_count || 0} notes</div>
                                </div>
                                <i class="fas fa-chevron-right" style="color: var(--text-muted);"></i>
                            </div>
                        `;
                    }).join('');
                } else {
                    listDiv.innerHTML = '<div style="text-align: center; padding: 3rem; color: var(--text-secondary);"><i class="fas fa-users" style="font-size: 3rem; opacity: 0.3; margin-bottom: 1rem;"></i><p>No ' + type + ' yet</p></div>';
                }
            } catch(error){
                listDiv.innerHTML = '<div style="text-align: center; padding: 2rem; color: var(--error-color);">Error loading ' + type + '</div>';
            }
        }
        function escapeHtml(text){
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        /* notification helper */
        function showNotification(msg,type='info'){
            let container = document.getElementById('notificationContainer');
            if (!container) {
                container = document.createElement('div');
                container.id = 'notificationContainer';
                document.body.appendChild(container);
            }
            const n=document.createElement('div');
            n.className=`notification notification-${type}`;
            n.innerHTML=`<i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i><span>${msg}</span>`;
            container.appendChild(n);
            setTimeout(()=>n.classList.add('show'),100);
            setTimeout(()=>{n.classList.remove('show');n.classList.add('removing');setTimeout(()=>{if(n.parentNode)n.remove();if(container&&container.children.length===0)container.remove();},300)},3000);
        }

        /* toast on page load */
        document.addEventListener('DOMContentLoaded',()=>{
            <?php if(isset($_GET['updated'])):   ?> showNotification('Profile updated successfully!','success'); <?php endif; ?>
            <?php if(isset($_GET['avatar'])):    ?> showNotification('Avatar <?= $_GET['avatar']==='1'?'updated':'failed to update' ?>!',<?= $_GET['avatar']==='1' ?>'success':'error'?>); <?php endif; ?>
            <?php if(isset($_GET['note_ok'])):   ?> showNotification('Note uploaded successfully!','success');   <?php endif; ?>
            <?php if(isset($_GET['note_error'])):?> showNotification('Upload failed – please try again.','error');<?php endif; ?>
            <?php if(isset($_GET['deleted'])):   ?> showNotification('Note deleted.','success');                <?php endif; ?>

            /* click outside modal to close */
            document.addEventListener('click',e=>{
                if(e.target.classList.contains('modal')){
                    e.target.classList.remove('active');
                }
            });
            
            // Close followers modal when clicking outside
            document.getElementById('followersModal')?.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeFollowersModal();
                }
            });
        });
    </script>
<?php require __DIR__ . '/components/footer.php'; ?>