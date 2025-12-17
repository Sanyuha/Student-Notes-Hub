<?php
// my-notes.php  –  design = profile.php  (PDO fixed)
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

ensureLoggedIn();
$uid       = (int)$_SESSION['auth_user_id'];
$pageTitle = 'My Notes - Student Notes Hub';

/* ----------  Pagination  ---------- */
$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

/* ----------  Count & Fetch  ---------- */
$total = (int)$pdo->prepare("SELECT COUNT(*) FROM notes WHERE user_id = ?")
                  ->execute([$uid])
                  ->fetchColumn();

$notes = $pdo->prepare(
    'SELECT n.*, c.name AS cat_name, c.icon
     FROM notes n
     JOIN categories c ON c.id = n.category_id
     WHERE n.user_id = ?
     ORDER BY n.created_at DESC
     LIMIT :l OFFSET :o'
);
$notes->bindValue(1, $uid, PDO::PARAM_INT);
$notes->bindValue(':l', $limit,  PDO::PARAM_INT);
$notes->bindValue(':o', $offset, PDO::PARAM_INT);
$notes->execute();
$notes = $notes->fetchAll();

$pages = $total ? ceil($total / $limit) : 1;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= e($pageTitle) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* =====  SAME DESIGN TOKENS AS PROFILE.PHP  ===== */
        :root{
            --primary-color:#6366f1;--primary-dark:#4f46e5;--text-primary:#1f2937;--text-secondary:#6b7280;
            --text-muted:#9ca3af;--bg-secondary:#f9fafb;--border-color:#e5e7eb;--success-color:#10b981;
            --error-color:#ef4444;--warning-color:#f59e0b;--border-radius:8px;--shadow-sm:0 1px 2px 0 rgba(0,0,0,.05);
            --shadow:0 4px 6px -1px rgba(0,0,0,.1);--shadow-lg:0 10px 15px -3px rgba(0,0,0,.1);
        }
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:'Inter',-apple-system,BlinkMacSystemFont,sans-serif;background:var(--bg-secondary);color:var(--text-primary);line-height:1.6;}
        /* -----  CLEAN HEADER (identical to profile.php)  ----- */
        .main-header{background:#fff;border-bottom:1px solid var(--border-color);padding:.75rem 0;position:sticky;top:0;z-index:1000;}
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

        /* -----  PAGE HEADER  ----- */
        .page-header{background:linear-gradient(135deg,var(--primary-color),var(--primary-dark));color:white;padding:3rem 0 2rem;text-align:center;}
        .page-header h1{font-size:clamp(1.75rem,4vw,2.5rem);margin-bottom:.5rem;font-weight:700;}
        .page-header p{font-size:1.125rem;opacity:.9;}

        /* -----  TOOL BAR  ----- */
        .tool-bar{background:#fff;border-bottom:1px solid var(--border-color);padding:1.5rem 0;}
        .tool-container{max-width:1200px;margin:0 auto;padding:0 1rem;display:flex;flex-wrap:wrap;gap:1rem;align-items:center;}
        .tool-left{display:flex;gap:1rem;align-items:center;}
        .btn{background:none;border:1px solid var(--border-color);color:var(--text-secondary);padding:.5rem 1rem;border-radius:var(--border-radius);font-size:.875rem;cursor:pointer;transition:all .2s;display:inline-flex;align-items:center;gap:.5rem;text-decoration:none;}
        .btn-primary{background:var(--primary-color);color:white;border-color:var(--primary-color);}
        .btn-primary:hover{background:var(--primary-dark);}
        .search-input{position:relative;}
        .search-input input{padding:.5rem 2.5rem .5rem 1rem;border:1px solid var(--border-color);border-radius:var(--border-radius);font-size:.875rem;width:220px;}
        .search-input input:focus{outline:none;border-color:var(--primary-color);}
        .search-input i{position:absolute;right:.75rem;top:50%;transform:translateY(-50%);color:var(--text-muted);}

        /* -----  NOTES GRID  ----- */
        .notes-section{padding:3rem 0;}
        .container{max-width:1200px;margin:0 auto;padding:0 1rem;}
        .notes-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:2rem;margin-bottom:3rem;}
        .note-card{background:#fff;border:1px solid var(--border-color);border-radius:var(--border-radius);overflow:hidden;cursor:pointer;transition:all .3s ease;box-shadow:var(--shadow-sm);}
        .note-card:hover{transform:translateY(-4px);box-shadow:var(--shadow-lg);border-color:var(--primary-color);}
        .note-img{height:160px;background:linear-gradient(135deg,var(--primary-color),var(--primary-light));display:flex;align-items:center;justify-content:center;font-size:3rem;color:white;}
        .note-body{padding:1.5rem;display:flex;flex-direction:column;height:100%;}
        .note-cat{display:inline-flex;align-items:center;gap:.5rem;background:rgba(99,102,241,.1);color:var(--primary-color);padding:.25rem .75rem;border-radius:20px;font-size:.75rem;font-weight:600;margin-bottom:1rem;}
        .note-title{font-size:1.125rem;font-weight:600;margin-bottom:.5rem;color:var(--text-primary);}
        .note-desc{color:var(--text-secondary);font-size:.875rem;line-height:1.5;margin-bottom:1rem;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;flex:1;}
        .note-foot{display:flex;justify-content:space-between;align-items:center;margin-top:auto;padding-top:1rem;border-top:1px solid var(--border-color);}
        .note-author{display:flex;align-items:center;gap:.5rem;font-size:.75rem;color:var(--text-secondary);}
        .note-author img{width:24px;height:24px;border-radius:50%;object-fit:cover;}
        .note-stats{display:flex;gap:1rem;font-size:.75rem;color:var(--text-muted);}
        .note-stats span{display:flex;align-items:center;gap:.25rem;}
        .note-date{font-size:.75rem;color:var(--text-muted);}
        .empty-state{text-align:center;padding:4rem 2rem;color:var(--text-secondary);}
        .empty-state i{font-size:4rem;margin-bottom:1rem;color:var(--text-muted);}
        .empty-state h3{font-size:1.5rem;margin-bottom:.5rem;color:var(--text-primary);}
        .empty-state p{margin-bottom:2rem;font-size:1.125rem;}

        /* ----------  PAGINATION  ---------- */
        .pagination{display:flex;justify-content:center;align-items:center;gap:.5rem;margin:3rem 0;flex-wrap:wrap;}
        .pagination a,.pagination span{display:inline-flex;align-items:center;justify-content:center;padding:.5rem .75rem;border:1px solid var(--border-color);border-radius:var(--border-radius);font-weight:500;min-width:40px;height:40px;text-decoration:none;color:var(--text-secondary);background:#fff;transition:all .2s;}
        .pagination a:hover{background:var(--bg-light);border-color:var(--primary-color);color:var(--primary-color);}
        .pagination .current{background:var(--primary-color);color:white;border-color:var(--primary-color);}
        @media(max-width:768px){
            .tool-container{flex-direction:column;align-items:stretch;}
            .search-input input{width:100%;}
            .notes-grid{grid-template-columns:1fr;gap:1.5rem;}
        }
    </style>
</head>
<body>
    <!-- ----------  CLEAN HEADER  ---------- -->
    <header class="main-header">
        <div class="header-container">
            <a href="index.php" class="logo">
                <i class="fas fa-graduation-cap"></i>
                <span>Student Notes Hub</span>
            </a>

            <div class="user-area">
                <span class="user-name"><?= e($_SESSION['auth_user']['username'] ?? 'User') ?></span>
                <img src="<?= e($_SESSION['auth_user']['avatar_url'] ?? 'https://via.placeholder.com/32') ?>" alt="avatar" class="user-avatar">
                <form method="post" action="logout.php" class="logout-form">
                    <button type="submit" class="logout-btn" title="Logout"><i class="fas fa-sign-out-alt"></i></button>
                </form>
            </div>
        </div>
    </header>

    <!-- ----------  PAGE HEADER  ---------- -->
    <section class="page-header">
        <div class="container">
            <h1><i class="fas fa-file-alt"></i> My Notes</h1>
            <p>Manage and view all your uploaded notes</p>
        </div>
    </section>

    <!-- ----------  TOOL BAR  ---------- -->
    <section class="tool-bar">
        <div class="container">
            <div class="tool-container">
                <div class="tool-left">
                    <a href="profile.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Back to Profile
                    </a>
                    <a href="upload.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Upload New Note
                    </a>
                </div>

                <div class="search-input">
                    <input type="text" id="searchInput" placeholder="Search your notes..." onkeyup="filterNotes(this.value)">
                    <i class="fas fa-search"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- ----------  NOTES GRID  ---------- -->
    <section class="notes-section">
        <div class="container">
            <?php if (empty($notes)): ?>
                <div class="empty-state">
                    <i class="fas fa-file-alt"></i>
                    <h3>No notes uploaded yet</h3>
                    <p>You haven't uploaded any notes yet. Start sharing your knowledge!</p>
                    <a href="upload.php" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Upload Your First Note
                    </a>
                </div>
            <?php else: ?>
                <div class="notes-grid" id="notesGrid">
                    <?php foreach ($notes as $n): ?>
                        <div class="note-card" data-note-id="<?= $n['id'] ?>">
                            <div class="note-img">
                                <i class="fas fa-<?= $n['icon'] ?? 'file-alt' ?>"></i>
                            </div>
                            <div class="note-body">
                                <div class="note-cat">
                                    <i class="fas fa-<?= $n['icon'] ?? 'file-alt' ?>"></i>
                                    <?= e($n['cat_name']) ?>
                                </div>
                                <h3 class="note-title"><?= e($n['title']) ?></h3>
                                <p class="note-desc"><?= e($n['description'] ?? '') ?></p>

                                <div class="note-foot">
                                    <div class="note-author">
                                        <img src="<?= e($n['avatar_url'] ?? 'https://via.placeholder.com/24') ?>" alt="<?= e($n['username']) ?>">
                                        <span><?= e($n['username']) ?></span>
                                    </div>
                                    <div class="note-stats">
                                        <span><i class="fas fa-download"></i> <?= $n['downloads'] ?></span>
                                        <span><i class="fas fa-heart"></i> <?= $n['likes'] ?></span>
                                        <span><i class="fas fa-eye"></i> <?= $n['views'] ?></span>
                                    </div>
                                </div>

                                <div class="note-date">
                                    <span><?= date('M j, Y', strtotime($n['created_at'])) ?></span>
                                    <span class="note-status status-<?= $n['status'] ?>"><?= ucfirst($n['status']) ?></span>
                                </div>

                                <!-- owner actions -->
                                <div class="note-actions" style="margin-top:1rem;display:flex;gap:.5rem;">
                                    <a href="note-detail.php?id=<?= $n['id'] ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="edit-note.php?id=<?= $n['id'] ?>" class="btn btn-outline btn-sm">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <button class="btn btn-danger btn-sm" onclick="deleteNote(<?= $n['id'] ?>)">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- ----------  PAGINATION  ---------- -->
                <?php if ($pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?>" class="pagination-link">
                                <i class="fas fa-chevron-left"></i> Prev
                            </a>
                        <?php endif; ?>

                        <?php for ($p = max(1, $page - 2); $p <= min($pages, $page + 2); $p++): ?>
                            <?php if ($p === $page): ?>
                                <span class="current"><?= $p ?></span>
                            <?php else: ?>
                                <a href="?page=<?= $p ?>" class="pagination-link"><?= $p ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $pages): ?>
                            <a href="?page=<?= $page + 1 ?>" class="pagination-link">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- ----------  JS  ---------- -->
    <script>
        /* quick client-side search (optional) */
        function filterNotes(q){
            document.querySelectorAll('.note-card').forEach(card=>{
                const txt=card.textContent.toLowerCase();
                card.style.display= txt.includes(q.toLowerCase()) ? '' : 'none';
            });
        }
        /* delete with ajax */
        function deleteNote(id){
            if(!confirm('Delete this note?'))return;
            fetch('api/delete-note.php',{
                method:'POST',
                headers:{'Content-Type':'application/json'},
                body:JSON.stringify({note_id:id})
            }).then(r=>r.json()).then(d=>{
                if(d.success){
                    document.querySelector(`[data-note-id="${id}"]`).remove();
                }else{
                    alert('Delete failed');
                }
            });
        }
    </script>
</body>
</html>