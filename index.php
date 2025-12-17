<?php
// Main index page for Student Notes Hub
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

$pageTitle = 'Student Notes Hub - Share & Learn Together';

/* ----------  Recent Notes  ---------- */
$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

$notes = $pdo->prepare(
    '     SELECT n.id, n.title, n.description, n.file_url, n.created_at,
            n.downloads, n.likes, 
            COALESCE(COUNT(DISTINCT v.id), 0) AS views,
            c.slug AS category,
            c.name AS cat_name, c.icon, u.id AS user_id, u.username, u.avatar_url
     FROM notes n
     JOIN categories c ON c.id = n.category_id
     JOIN users u ON u.id = n.user_id
     LEFT JOIN views v ON v.note_id = n.id
     WHERE n.status = "published"
     GROUP BY n.id, n.title, n.description, n.file_url, n.created_at,
              n.downloads, n.likes, c.slug, c.name, c.icon, u.id, u.username, u.avatar_url
     ORDER BY n.created_at DESC
     LIMIT :l OFFSET :o'
);
$notes->bindValue(':l', $limit, PDO::PARAM_INT);
$notes->bindValue(':o', $offset, PDO::PARAM_INT);
$notes->execute();
$notes = $notes->fetchAll();

$total = (int)$pdo->query('SELECT COUNT(*) FROM notes WHERE status = "published"')->fetchColumn();
$pages = ceil($total / $limit);

/* ----------  Categories / Stats / Featured  ---------- */
$cats            = $pdo->query('SELECT slug, name, icon FROM categories ORDER BY name')->fetchAll();
$totalNotes      = (int)$pdo->query('SELECT COUNT(*) FROM notes WHERE status = "published"')->fetchColumn();
$totalDownloads  = (int)$pdo->query('SELECT COUNT(*) FROM downloads')->fetchColumn();
$totalUsers      = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

$featuredNotes = $pdo->prepare(
    '     SELECT n.id, n.title, n.description, n.downloads, n.likes,
            COALESCE(COUNT(DISTINCT v.id), 0) AS views,
            c.name AS cat_name, c.icon, u.id AS user_id, u.username
     FROM notes n
     JOIN categories c ON c.id = n.category_id
     JOIN users u ON u.id = n.user_id
     LEFT JOIN views v ON v.note_id = n.id
     WHERE n.status = "published"
     GROUP BY n.id, n.title, n.description, n.downloads, n.likes,
              c.name, c.icon, u.id, u.username
     ORDER BY (n.downloads * 2 + n.likes * 1.5 + COALESCE(COUNT(DISTINCT v.id), 0) * 0.5) DESC
     LIMIT 6'
);
$featuredNotes->execute();
$featuredNotes = $featuredNotes->fetchAll();

require __DIR__ . '/components/header.php';
?>

<main>
<!-- Hero -->
<section class="hero">
  <div class="container">
    <div class="hero-content">
      <h1>Welcome to Student Notes Hub</h1>
      <p>Share knowledge, learn together, and excel in your studies with our community-driven platform for student notes and educational resources.</p>

      <!-- SINGLE, BIGGER BROWSE BUTTON -->
      <div class="hero-actions">
        <a href="#recent-notes" class="btn btn-primary btn-lg">
          <i class="fas fa-book-open"></i> Browse Notes
        </a>
      </div>
    </div>
  </div>
</section>

<!-- Stats -->
<section class="stats">
  <div class="container">
    <div class="stats-grid">
      <div class="stat-card"><div class="stat-number" data-count="<?= $totalNotes ?>"><?= $totalNotes ?></div><div class="stat-label">Total Notes</div><i class="fas fa-file-alt stat-icon"></i></div>
      <div class="stat-card"><div class="stat-number" data-count="<?= $totalDownloads ?>"><?= $totalDownloads ?></div><div class="stat-label">Downloads</div><i class="fas fa-download stat-icon"></i></div>
      <div class="stat-card"><div class="stat-number" data-count="<?= count($cats) ?>"><?= count($cats) ?></div><div class="stat-label">Categories</div><i class="fas fa-tags stat-icon"></i></div>
      <div class="stat-card"><div class="stat-number" data-count="<?= $totalUsers ?>"><?= $totalUsers ?></div><div class="stat-label">Total Users</div><i class="fas fa-users stat-icon"></i></div>
    </div>
  </div>
</section>

<!-- Featured Notes -->
<?php if (!empty($featuredNotes)): ?>
<section class="featured-notes">
  <div class="container">
    <h2 class="section-title">Featured Notes</h2>
    <p class="section-subtitle">Most popular and highly-rated notes from our community</p>
    <div class="notes-grid featured-grid">
      <?php foreach ($featuredNotes as $note): ?>
        <div class="note-card featured-card" onclick="window.location.href='note-detail.php?id=<?= $note['id'] ?>'">
          <div class="note-image"><i class="fas fa-<?= $note['icon'] ?? 'file-alt' ?>"></i></div>
          <div class="note-content">
            <div class="note-category featured-category"><i class="fas fa-<?= $note['icon'] ?? 'file-alt' ?>"></i> <?= htmlspecialchars($note['cat_name']) ?></div>
            <h3 class="note-title"><?= htmlspecialchars($note['title']) ?></h3>
            <p class="note-description"><?= htmlspecialchars($note['description'] ?? '') ?></p>
            <div class="note-meta">
              <a href="profile_users.php?user_id=<?= $note['user_id'] ?>" class="note-author" onclick="event.stopPropagation();"><i class="fas fa-user"></i> <?= htmlspecialchars($note['username']) ?></a>
              <div class="note-stats">
                <span title="Downloads"><i class="fas fa-download"></i> <?= $note['downloads'] ?></span>
                <span title="Likes"><i class="fas fa-heart"></i> <?= $note['likes'] ?></span>
                <span title="Views"><i class="fas fa-eye"></i> <?= $note['views'] ?></span>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- Categories -->
<section class="categories" id="categories">
  <div class="container">
    <h2 class="section-title">Browse by Category</h2>
    <p class="section-subtitle">Find notes organized by subject and topic</p>
    <div class="categories-grid">
      <?php foreach ($cats as $cat):
        $catCount = $pdo->prepare('SELECT COUNT(*) FROM notes WHERE category_id = (SELECT id FROM categories WHERE slug = ?) AND status = "published"');
        $catCount->execute([$cat['slug']]);
        $count = $catCount->fetchColumn();
      ?>
        <div class="category-card" onclick="window.location.href='notes.php?category=<?= $cat['slug'] ?>'">
          <div class="category-icon"><i class="fas fa-<?= $cat['icon'] ?? 'folder' ?>"></i></div>
          <h3><?= htmlspecialchars($cat['name']) ?></h3>
          <p>Explore notes in <?= htmlspecialchars($cat['name']) ?></p>
          <div class="category-stats"><span><?= $count ?> notes</span></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Recent Notes -->
<section class="recent-notes" id="recent-notes">
  <div class="container">
    <h2 class="section-title">Recent Notes</h2>
    <p class="section-subtitle">Latest notes uploaded by our community</p>

    <?php if (empty($notes)): ?>
      <div class="empty-state">
        <i class="fas fa-file-alt" style="font-size:4rem;color:var(--text-muted);margin-bottom:1rem;"></i>
        <h3>No notes found</h3>
        <p>Be the first to upload notes in our community!</p>
        <?php if (isset($_SESSION['auth_user_id'])): ?>
        <?php else: ?>
          <a href="login.php" class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> Join to Upload Notes</a>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="notes-grid">
        <?php foreach ($notes as $note): ?>
          <div class="note-card" onclick="window.location.href='note-detail.php?id=<?= $note['id'] ?>'">
            <div class="note-image"><i class="fas fa-<?= $note['icon'] ?? 'file-alt' ?>"></i></div>
            <div class="note-content">
              <div class="note-category"><i class="fas fa-<?= $note['icon'] ?? 'file-alt' ?>"></i> <?= htmlspecialchars($note['cat_name']) ?></div>
              <h3 class="note-title"><?= htmlspecialchars($note['title']) ?></h3>
              <p class="note-description"><?= htmlspecialchars($note['description'] ?? '') ?></p>
              <div class="note-meta">
                <a href="profile_users.php?user_id=<?= $note['user_id'] ?>" class="note-author" onclick="event.stopPropagation();">
                  <img src="<?= htmlspecialchars($note['avatar_url'] ?? 'https://via.placeholder.com/24') ?>" alt="<?= htmlspecialchars($note['username']) ?>" style="width:24px;height:24px;border-radius:50%;margin-right:0.5rem;">
                  <?= htmlspecialchars($note['username']) ?>
                </a>
                <div class="note-stats">
                  <span title="Downloads"><i class="fas fa-download"></i> <?= $note['downloads'] ?? 0 ?></span>
                  <span title="Likes"><i class="fas fa-heart"></i> <?= $note['likes'] ?? 0 ?></span>
                  <span title="Views"><i class="fas fa-eye"></i> <?= $note['views'] ?? 0 ?></span>
                </div>
              </div>
              <div class="note-date"><i class="fas fa-clock"></i> <?= date('M j, Y', strtotime($note['created_at'])) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Pagination -->
      <?php if ($pages > 1): ?>
        <div class="pagination">
          <?php if ($page > 1): ?>
            <a href="index.php?page=<?= $page - 1 ?>" class="pagination-link"><i class="fas fa-chevron-left"></i> Previous</a>
          <?php endif; ?>

          <?php for ($i = 1; $i <= $pages; $i++): ?>
            <?php if ($i === $page): ?>
              <span class="pagination-current"><?= $i ?></span>
            <?php else: ?>
              <a href="index.php?page=<?= $i ?>" class="pagination-link"><?= $i ?></a>
            <?php endif; ?>
          <?php endfor; ?>

          <?php if ($page < $pages): ?>
            <a href="index.php?page=<?= $page + 1 ?>" class="pagination-link">Next <i class="fas fa-chevron-right"></i></a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>

<!-- Call to Action -->
<section class="cta-section">
  <div class="container">
    <div class="cta-content">
      <h2>Ready to Share Your Knowledge?</h2>
      <p>Join thousands of students sharing notes and helping each other succeed.</p>
      <div class="cta-actions">
        <?php if (isset($_SESSION['auth_user_id'])): ?>
          <a href="profile.php" class="btn btn-outline btn-lg"><i class="fas fa-user"></i> View Profile</a>
        <?php else: ?>
          <a href="register.php" class="btn btn-primary btn-lg"><i class="fas fa-user-plus"></i> Join for Free</a>
          <a href="login.php" class="btn btn-outline btn-lg"><i class="fas fa-sign-in-alt"></i> Sign In</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
</main>

<?php require __DIR__ . '/components/footer.php'; ?>

<!--=====  STYLES  =====-->
<style>
/* ---- Hero ---- */
.hero{
  background:linear-gradient(135deg,var(--primary-color),var(--primary-dark));
  color:#fff;text-align:center;padding:4rem 0;position:relative;overflow:hidden;
}
.hero::before{content:'';position:absolute;inset:0;background:url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="g" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23g)"/></svg>');opacity:.3}
.hero-content{position:relative;z-index:1}
.hero h1{font-size:clamp(2rem,5vw,3.5rem);margin-bottom:1rem;font-weight:700}
.hero p{font-size:clamp(1rem,2.5vw,1.25rem);margin:0 auto 2rem;max-width:600px;opacity:.9}
.hero-actions{display:flex;gap:1rem;justify-content:center;flex-wrap:wrap}

/* ---- bigger / brighter button ---- */
.btn-lg{padding:1rem 2rem;font-size:1.1rem;border-radius:12px}
.btn-primary{background:#fff;color:var(--primary-color);font-weight:600;box-shadow:0 4px 14px rgba(0,0,0,.25)}
.btn-primary:hover{background:#f1f5f9;transform:translateY(-2px)}

/* ---- Stats ---- */
.stats{background:var(--bg-primary);padding:3rem 0}
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:2rem;text-align:center}
.stat-card{background:var(--bg-secondary);padding:2rem;border-radius:1rem;box-shadow:var(--shadow-sm);position:relative;overflow:hidden}
.stat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:linear-gradient(90deg,var(--primary-color),var(--primary-light))}
.stat-number{font-size:clamp(2rem,5vw,2.5rem);font-weight:700;color:var(--primary-color);margin-bottom:.5rem}
.stat-label{color:var(--text-secondary);font-weight:500;margin-bottom:1rem}
.stat-icon{font-size:1.5rem;color:var(--primary-light)}

/* ---- Categories ---- */
.categories{background:var(--bg-primary);padding:4rem 0}
.section-title{text-align:center;font-size:clamp(1.75rem,4vw,2.5rem);margin-bottom:1rem;font-weight:700}
.section-subtitle{text-align:center;color:var(--text-secondary);margin-bottom:3rem;font-size:1.125rem}
.categories-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:2rem}
.category-card{background:var(--bg-primary);border-radius:1rem;padding:2rem;text-align:center;cursor:pointer;transition:all .3s;box-shadow:var(--shadow-sm);border:1px solid var(--border-color)}
.category-card:hover{transform:translateY(-5px);box-shadow:var(--shadow-xl);border-color:var(--primary-color)}
.category-icon{font-size:clamp(2.5rem,6vw,3rem);color:var(--primary-color);margin-bottom:1rem}
.category-card h3{font-size:1.25rem;margin-bottom:.5rem}
.category-card p{color:var(--text-secondary);margin-bottom:1rem}
.category-stats{display:flex;justify-content:center;gap:.5rem;color:var(--primary-color);font-weight:600;font-size:.875rem}

/* ---- Recent Notes ---- */
.recent-notes{background:var(--bg-secondary);padding:4rem 0}
.notes-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:2rem;margin-bottom:3rem}
.note-card{background:var(--bg-primary);border-radius:1rem;overflow:hidden;cursor:pointer;transition:all .3s;box-shadow:var(--shadow-sm);border:1px solid var(--border-color)}
.note-card:hover{transform:translateY(-5px);box-shadow:var(--shadow-xl);border-color:var(--primary-color)}
.note-image{height:160px;background:linear-gradient(135deg,var(--primary-color),var(--primary-light));display:flex;align-items:center;justify-content:center;font-size:3rem;color:#fff}
.note-content{padding:1.5rem}
.note-category{display:inline-flex;align-items:center;gap:.5rem;background:rgba(99,102,241,.1);color:var(--primary-color);padding:.25rem .75rem;border-radius:20px;font-size:.75rem;font-weight:600;margin-bottom:1rem}
.note-title{font-size:1.125rem;font-weight:600;margin-bottom:.5rem;line-height:1.3}
.note-description{color:var(--text-secondary);font-size:.875rem;line-height:1.5;margin-bottom:1rem;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden}
.note-meta{display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;padding-bottom:1rem;border-bottom:1px solid var(--border-color)}
.note-author{display:flex;align-items:center;gap:.5rem;font-size:.875rem;color:var(--text-secondary);text-decoration:none;transition:color .2s}
.note-author:hover{color:var(--primary-color)}
.note-author img{width:24px;height:24px;border-radius:50%;object-fit:cover;margin-right:.5rem}
.note-stats{display:flex;gap:1rem;font-size:.75rem;color:var(--text-muted)}
.note-date{display:flex;align-items:center;gap:.5rem;font-size:.75rem;color:var(--text-muted)}
.empty-state{text-align:center;padding:4rem 2rem;color:var(--text-secondary)}
.empty-state i{font-size:4rem;margin-bottom:1rem;color:var(--text-muted)}
.empty-state h3{font-size:1.5rem;margin-bottom:.5rem}

/* ---- Pagination ---- */
.pagination{display:flex;justify-content:center;gap:.5rem;margin:3rem 0;flex-wrap:wrap}
.pagination-link,.pagination-current{display:inline-flex;align-items:center;justify-content:center;padding:.5rem .75rem;border:1px solid var(--border-color);border-radius:8px;font-weight:500;min-width:40px;height:40px;transition:all .2s}
.pagination-link{color:var(--text-secondary);background:var(--bg-primary)}
.pagination-link:hover{background:var(--bg-light);border-color:var(--primary-color);color:var(--primary-color)}
.pagination-current{background:var(--primary-color);color:#fff;border-color:var(--primary-color)}

/* ---- CTA ---- */
.cta-section{background:linear-gradient(135deg,var(--primary-color),var(--primary-dark));color:#fff;padding:4rem 0;text-align:center}
.cta-content h2{font-size:clamp(1.75rem,4vw,2.5rem);margin-bottom:1rem;font-weight:700}
.cta-content p{font-size:1.125rem;margin-bottom:2rem;opacity:.9;max-width:600px;margin-left:auto;margin-right:auto}
.cta-actions{display:flex;gap:1rem;justify-content:center;flex-wrap:wrap}
.cta-section .btn-outline{background:#fff;color:var(--primary-color);border:2px solid #fff;font-weight:600;box-shadow:0 4px 14px rgba(0,0,0,.25)}
.cta-section .btn-outline:hover{background:#f1f5f9;border-color:#f1f5f9;transform:translateY(-2px);color:var(--primary-color)}

/* ---- small-screen tweaks ---- */
@media(max-width:768px){
  .hero-actions{flex-direction:column;align-items:center}
  .stats-grid{grid-template-columns:repeat(2,1fr);gap:1rem}
  .categories-grid,.notes-grid{grid-template-columns:1fr;gap:1.5rem}
}
@media(max-width:480px){
  .stats-grid,.categories-grid,.notes-grid{grid-template-columns:1fr}
  .hero h1{font-size:1.75rem}
  .section-title{font-size:1.5rem}
}
</style>

<style>
/* Animation Classes */
.fade-in {
    animation: fadeIn 0.6s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.slide-up {
    animation: slideUp 0.6s ease-out;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

<script>
// ----- animate counters on scroll -----
const obsOps={threshold:.1,rootMargin:'0px 0px -50px 0px'};
const observer=new IntersectionObserver((entries)=>{
  entries.forEach(entry=>{
    if(entry.isIntersecting){
      if(entry.target.classList.contains('stats')) animateCounters();
      entry.target.classList.add('fade-in');
    }
  });
},obsOps);

function animateCounters(){
  document.querySelectorAll('.stat-number[data-count]').forEach(counter=>{
    const target=parseInt(counter.dataset.count),inc=target/100; let cur=0;
    const upd=()=>{if(cur<target){cur+=inc;counter.textContent=Math.ceil(cur);requestAnimationFrame(upd);}else counter.textContent=target;};
    upd();
  });
}

document.addEventListener('DOMContentLoaded',()=>{
  // Observe sections for fade-in animation
  document.querySelectorAll('section').forEach(s=>observer.observe(s));
  
  // Add stagger animation to note cards
  document.querySelectorAll('.note-card').forEach((card,i)=>{
    card.style.animationDelay=`${i*.1}s`; 
    card.classList.add('slide-up');
  });
  
  // Add stagger animation to category cards
  document.querySelectorAll('.category-card').forEach((card,i)=>{
    card.style.animationDelay=`${i*.1}s`; 
    card.classList.add('slide-up');
  });
  
  // Add stagger animation to stat cards
  document.querySelectorAll('.stat-card').forEach((card,i)=>{
    card.style.animationDelay=`${i*.1}s`; 
    card.classList.add('slide-up');
  });
});
</script>