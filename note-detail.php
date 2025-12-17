<?php
// Note detail page for Student Notes Hub
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
    '     SELECT n.*, c.slug AS category_slug, c.name AS cat_name, c.icon, u.id AS user_id, u.username, u.avatar_url
     FROM notes n
     JOIN categories c ON c.id = n.category_id
     JOIN users u ON u.id = n.user_id
     WHERE n.id = ? AND (n.status = "published" OR (n.status = "draft" AND n.user_id = ?))'
);
$note->execute([$noteId, $userId]);
$note = $note->fetch();

if (!$note) {
    http_response_code(404);
    exit('Note not found or you do not have permission to view it');
}

// Set page title
$pageTitle = htmlspecialchars($note['title']) . ' - Student Notes Hub';

// Record view
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$pdo->prepare(
    'INSERT INTO views (note_id, ip_address) VALUES (?, ?) 
     ON DUPLICATE KEY UPDATE created_at = NOW()'
)->execute([$noteId, $ip]);

// Get accurate total view count from views table
$viewCountStmt = $pdo->prepare('SELECT COUNT(*) FROM views WHERE note_id = ?');
$viewCountStmt->execute([$noteId]);
$totalViews = (int)$viewCountStmt->fetchColumn();

// Check if user liked
$isLiked = false;
if (isset($_SESSION['auth_user_id'])) {
    $liked = $pdo->prepare('SELECT id FROM likes WHERE note_id = ? AND user_id = ?');
    $liked->execute([$noteId, $_SESSION['auth_user_id']]);
    $isLiked = (bool)$liked->fetch();
}

// Get related notes with accurate view counts
$related = $pdo->prepare(
    'SELECT n.id, n.title, n.downloads, n.likes,
             COALESCE(COUNT(DISTINCT v.id), 0) AS views,
             c.name AS cat_name, c.icon, u.username
     FROM notes n
     JOIN categories c ON c.id = n.category_id
     JOIN users u ON u.id = n.user_id
     LEFT JOIN views v ON v.note_id = n.id
     WHERE n.category_id = ? AND n.id != ? AND n.status = "published"
     GROUP BY n.id, n.title, n.downloads, n.likes, c.name, c.icon, u.username
     ORDER BY n.created_at DESC
     LIMIT 4'
);
$related->execute([$note['category_id'], $noteId]);
$relatedNotes = $related->fetchAll();

// Include header
require __DIR__ . '/components/header.php';
?>

<!-- Main Content -->
<main>
    <!-- Note Header -->
    <section class="note-header">
        <div class="container">
            <div class="note-breadcrumb">
                <a href="index.php">Home</a>
                <i class="fas fa-chevron-right"></i>
                <a href="notes.php?category=<?= $note['category_slug'] ?>"><?= htmlspecialchars($note['cat_name']) ?></a>
                <i class="fas fa-chevron-right"></i>
                <span><?= htmlspecialchars($note['title']) ?></span>
            </div>
                
            <div class="note-title-section">
                <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                    <div class="note-category-badge">
                        <i class="fas fa-<?= $note['icon'] ?? 'file-alt' ?>"></i>
                        <?= htmlspecialchars($note['cat_name']) ?>
                    </div>
                    <?php if ($note['status'] === 'draft'): ?>
                        <div style="background: var(--warning-color); color: white; padding: 0.375rem 0.75rem; border-radius: var(--border-radius); font-size: 0.875rem; font-weight: 600; display: flex; align-items: center; gap: 0.375rem;">
                            <i class="fas fa-file-alt"></i>
                            Draft
                        </div>
                    <?php endif; ?>
                </div>
                <h1><?= htmlspecialchars($note['title']) ?></h1>
                <p class="note-description"><?= htmlspecialchars($note['description'] ?? '') ?></p>
            </div>
                
            <div class="note-meta-bar">
                <div class="author-info">
                    <?php if (!empty($note['avatar_url']) && $note['avatar_url'] !== 'https://via.placeholder.com/40' && $note['avatar_url'] !== 'https://via.placeholder.com/150'): ?>
                        <img src="<?= htmlspecialchars($note['avatar_url']) ?>" alt="<?= htmlspecialchars($note['username']) ?>" class="author-avatar">
                    <?php else: ?>
                        <div class="author-avatar-placeholder">
                            <i class="fas fa-user"></i>
                        </div>
                    <?php endif; ?>
                    <div class="author-details">
                        <?php if (isset($_SESSION['auth_user_id']) && $note['user_id'] != $_SESSION['auth_user_id']): ?>
                            <a href="profile_users.php?user_id=<?= $note['user_id'] ?>" class="author-name" style="text-decoration: none; color: inherit;">
                                <?= htmlspecialchars($note['username']) ?>
                            </a>
                        <?php elseif (isset($_SESSION['auth_user_id']) && $note['user_id'] == $_SESSION['auth_user_id']): ?>
                            <a href="profile.php" class="author-name" style="text-decoration: none; color: inherit;">
                                <?= htmlspecialchars($note['username']) ?>
                            </a>
                        <?php else: ?>
                            <span class="author-name"><?= htmlspecialchars($note['username']) ?></span>
                        <?php endif; ?>
                        <span class="upload-date"><?= date('M j, Y', strtotime($note['created_at'])) ?></span>
                    </div>
                </div>
                    
                <div class="note-stats-bar">
                    <span class="stat">
                        <i class="fas fa-download"></i>
                        <span id="downloadsCount"><?= realDownloadsForNote($noteId) ?></span> Downloads
                    </span>
                    <span class="stat">
                        <i class="fas fa-heart"></i>
                        <span id="likesCount"><?= $note['likes'] ?? 0 ?></span> Likes
                    </span>
                    <span class="stat">
                        <i class="fas fa-eye"></i>
                        <?= $totalViews ?> Views
                    </span>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Note Actions -->
    <section class="note-actions-bar">
        <div class="container">
            <div class="actions-container">
                <div class="primary-actions">
                    <button type="button" class="btn btn-primary btn-lg" onclick="viewNote()">
                        <i class="fas fa-eye"></i>
                        View Note
                    </button>
                    <button type="button" class="btn btn-outline btn-lg" onclick="downloadNote()">
                        <i class="fas fa-download"></i>
                        Download Notes
                    </button>
                    <?php if ($note['status'] === 'published'): ?>
                        <button type="button" class="btn btn-outline btn-lg like-btn" 
                                onclick="toggleLike()" data-liked="<?= $isLiked ? '1' : '0' ?>">
                            <i class="fas fa-heart"></i>
                            <span><?= $isLiked ? 'Liked' : 'Like' ?></span>
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="shareNote()">
                            <i class="fas fa-share"></i>
                            Share
                        </button>
                    <?php endif; ?>
                </div>
                    
                <div class="secondary-actions">
                    <?php if ($note['status'] === 'published'): ?>
                        <button type="button" class="btn btn-outline" onclick="reportNote()">
                            <i class="fas fa-flag"></i>
                            Report
                        </button>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['auth_user_id']) && $note['user_id'] == $_SESSION['auth_user_id']): ?>
                        <a href="edit-note.php?id=<?= $note['id'] ?>" class="btn btn-outline">
                            <i class="fas fa-edit"></i>
                            Edit
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Note Content -->
    <section class="note-content-section">
        <div class="container">
            <div class="content-grid">
                <div class="main-content">
                    <div class="file-info-card">
                        <h3>File Information</h3>
                        <div class="file-details">
                            <div class="detail-item">
                                <i class="fas fa-file"></i>
                                <span>Type:</span>
                                <strong><?= strtoupper($note['file_type'] ?? 'PDF') ?></strong>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-hdd"></i>
                                <span>Size:</span>
                                <strong><?php
                                    $fileSize = $note['file_size'] ?? null;
                                    // If file_size is not in database, try to get it from the actual file
                                    if ($fileSize === null || $fileSize === 0) {
                                        $filePath = __DIR__ . '/' . $note['file_url'];
                                        if (file_exists($filePath)) {
                                            $fileSize = filesize($filePath);
                                        } else {
                                            $fileSize = 0;
                                        }
                                    }
                                    echo formatFileSize($fileSize);
                                ?></strong>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-calendar"></i>
                                <span>Uploaded:</span>
                                <strong><?= date('F j, Y', strtotime($note['created_at'])) ?></strong>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-user"></i>
                                <span>Author:</span>
                                <?php if (isset($_SESSION['auth_user_id']) && $note['user_id'] != $_SESSION['auth_user_id']): ?>
                                    <strong><a href="profile_users.php?user_id=<?= $note['user_id'] ?>" style="color: var(--primary-color); text-decoration: none;"><?= htmlspecialchars($note['username']) ?></a></strong>
                                <?php elseif (isset($_SESSION['auth_user_id']) && $note['user_id'] == $_SESSION['auth_user_id']): ?>
                                    <strong><a href="profile.php" style="color: var(--primary-color); text-decoration: none;"><?= htmlspecialchars($note['username']) ?></a></strong>
                                <?php else: ?>
                                    <strong><?= htmlspecialchars($note['username']) ?></strong>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                        
                    <div class="description-card">
                        <h3>Description</h3>
                        <div class="description-content">
                            <?php if ($note['description']): ?>
                                <p><?= nl2br(htmlspecialchars($note['description'])) ?></p>
                            <?php else: ?>
                                <p class="text-muted">No description provided.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                        
                    <?php if ($note['status'] === 'published'): ?>
                        <?php if (isset($_SESSION['auth_user_id']) && $note['user_id'] != $_SESSION['auth_user_id']): ?>
                            <div class="feedback-section">
                                <h3>Rate This Note</h3>
                                <div class="rating-stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star" data-rating="<?= $i ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <button type="button" class="btn btn-primary" onclick="submitRating()">Submit Rating</button>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Rating Statistics Section -->
                        <div class="rating-stats-section">
                            <h3>Rating Statistics</h3>
                            <div id="ratingStatsContainer" class="rating-stats-content">
                                <div class="loading-rating-stats">
                                    <i class="fas fa-spinner fa-spin"></i> Loading ratings...
                                </div>
                            </div>
                        </div>
                        
                        <!-- Comments Section -->
                        <div class="comments-section">
                            <h3>Comments</h3>
                            <?php if (isset($_SESSION['auth_user_id'])): ?>
                                <div class="add-comment-form">
                                    <textarea id="newCommentText" placeholder="Write a comment..." rows="3"></textarea>
                                    <button type="button" class="btn btn-primary" onclick="addComment()">
                                        <i class="fas fa-paper-plane"></i> Post Comment
                                    </button>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">Please <a href="login.php">login</a> to leave a comment.</p>
                            <?php endif; ?>
                            <div id="commentsContainer" class="comments-list">
                                <div class="loading-comments">
                                    <i class="fas fa-spinner fa-spin"></i> Loading comments...
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Draft Notice -->
                        <div class="draft-notice" style="background: var(--bg-light); border: 2px dashed var(--border-color); border-radius: var(--border-radius); padding: 2rem; text-align: center; margin-top: 2rem;">
                            <i class="fas fa-file-alt" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
                            <h3 style="color: var(--text-primary); margin-bottom: 0.5rem;">This is a Draft</h3>
                            <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">This note is not published yet. Only you can see it. Publish it to make it visible to others.</p>
                            <a href="edit-note.php?id=<?= $note['id'] ?>" class="btn btn-primary">
                                <i class="fas fa-edit"></i> Edit & Publish
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                    
                <div class="sidebar">
                    <div class="author-card">
                        <div class="author-header">
                            <?php if (!empty($note['avatar_url']) && $note['avatar_url'] !== 'https://via.placeholder.com/60' && $note['avatar_url'] !== 'https://via.placeholder.com/150'): ?>
                                <img src="<?= htmlspecialchars($note['avatar_url']) ?>" alt="<?= htmlspecialchars($note['username']) ?>" class="author-avatar-large">
                            <?php else: ?>
                                <div class="author-avatar-large-placeholder">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                            <div class="author-info">
                                <?php if (isset($_SESSION['auth_user_id']) && $note['user_id'] != $_SESSION['auth_user_id']): ?>
                                    <a href="profile_users.php?user_id=<?= $note['user_id'] ?>" style="text-decoration: none; color: inherit;">
                                        <h4><?= htmlspecialchars($note['username']) ?></h4>
                                    </a>
                                <?php elseif (isset($_SESSION['auth_user_id']) && $note['user_id'] == $_SESSION['auth_user_id']): ?>
                                    <a href="profile.php" style="text-decoration: none; color: inherit;">
                                        <h4><?= htmlspecialchars($note['username']) ?></h4>
                                    </a>
                                <?php else: ?>
                                    <h4><?= htmlspecialchars($note['username']) ?></h4>
                                <?php endif; ?>
                                <span class="author-role">Student</span>
                            </div>
                        </div>
                        <div class="author-stats">
                            <div class="stat">
                                <span class="stat-number">
                                    <?php 
                                    $stmt = $pdo->prepare('SELECT COUNT(*) FROM notes WHERE user_id = ? AND status = "published"');
                                    $stmt->execute([$note['user_id']]);
                                    echo (int)$stmt->fetchColumn();
                                    ?>
                                </span>
                                <span class="stat-label">Notes</span>
                            </div>
                            <div class="stat">
                                <span class="stat-number">
                                    <?php 
                                    $stmt = $pdo->prepare('SELECT SUM(downloads) FROM notes WHERE user_id = ?');
                                    $stmt->execute([$note['user_id']]);
                                    echo (int)($stmt->fetchColumn() ?? 0);
                                    ?>
                                </span>
                                <span class="stat-label">Downloads</span>
                            </div>
                        </div>
                        <?php if (isset($_SESSION['auth_user_id']) && $note['user_id'] != $_SESSION['auth_user_id']): ?>
                            <a href="profile_users.php?user_id=<?= $note['user_id'] ?>" class="btn btn-primary btn-block" style="margin-top: 1rem;">
                                <i class="fas fa-user"></i>
                                View Profile
                            </a>
                        <?php elseif (isset($_SESSION['auth_user_id']) && $note['user_id'] == $_SESSION['auth_user_id']): ?>
                            <a href="profile.php" class="btn btn-primary btn-block" style="margin-top: 1rem;">
                                <i class="fas fa-user"></i>
                                My Profile
                            </a>
                        <?php endif; ?>
                    </div>
                        
                    <div class="category-card">
                        <h4>Category</h4>
                        <div class="category-info">
                            <i class="fas fa-<?= $note['icon'] ?? 'file-alt' ?>"></i>
                            <span><?= htmlspecialchars($note['cat_name']) ?></span>
                        </div>
                        <a href="notes.php?category=<?= $note['category_slug'] ?>" class="btn btn-outline btn-sm">
                            Browse Category
                        </a>
                    </div>
                        
                    <div class="share-card">
                        <h4>Share This Note</h4>
                        <div class="share-buttons">
                            <button type="button" class="share-btn facebook" onclick="shareOnFacebook()">
                                <i class="fab fa-facebook"></i>
                            </button>
                            <button type="button" class="share-btn twitter" onclick="shareOnTwitter()">
                                <i class="fab fa-twitter"></i>
                            </button>
                            <button type="button" class="share-btn linkedin" onclick="shareOnLinkedIn()">
                                <i class="fab fa-linkedin"></i>
                            </button>
                            <button type="button" class="share-btn copy" onclick="copyLink()">
                                <i class="fas fa-link"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Related Notes -->
        <?php if (!empty($relatedNotes)): ?>
        <section class="related-notes">
            <div class="container">
                <h2 class="section-title">Related Notes</h2>
                <p class="section-subtitle">More notes in <?= htmlspecialchars($note['cat_name']) ?></p>
                
                <div class="related-grid">
                    <?php foreach ($relatedNotes as $related): ?>
                        <div class="related-card" onclick="window.location.href='note-detail.php?id=<?= $related['id'] ?>'">
                            <div class="related-image">
                                <i class="fas fa-<?= $related['icon'] ?? 'file-alt' ?>"></i>
                            </div>
                            <div class="related-content">
                                <h4><?= htmlspecialchars($related['title']) ?></h4>
                                <div class="related-meta">
                                    <span class="author"><?= htmlspecialchars($related['username']) ?></span>
                                    <div class="stats">
                                        <span><i class="fas fa-download"></i> <?= $related['downloads'] ?></span>
                                        <span><i class="fas fa-heart"></i> <?= $related['likes'] ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>
    </main>

<?php
// Include footer
require __DIR__ . '/components/footer.php';
?>

<!-- Additional Styles -->
<style>
/* Note Header */
.note-header {
    background: var(--bg-primary);
    padding: 2rem 0;
    border-bottom: 1px solid var(--border-color);
}

.note-breadcrumb {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    font-size: 0.875rem;
    color: var(--text-muted);
}

.note-breadcrumb a {
    color: var(--primary-color);
}

.note-breadcrumb a:hover {
    text-decoration: underline;
}

.note-title-section {
    margin-bottom: 1.5rem;
}

.note-category-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: rgba(99, 102, 241, 0.1);
    color: var(--primary-color);
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.note-title-section h1 {
    font-size: clamp(1.75rem, 4vw, 2.5rem);
    margin-bottom: 1rem;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1.2;
}

.note-description {
    font-size: 1.125rem;
    color: var(--text-secondary);
    line-height: 1.6;
}

.note-meta-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.author-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.author-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
}

.author-avatar-placeholder {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.25rem;
}

.author-details {
    display: flex;
    flex-direction: column;
}

.author-name {
    font-weight: 600;
    color: var(--text-primary);
}

.upload-date {
    font-size: 0.875rem;
    color: var(--text-muted);
}

.note-stats-bar {
    display: flex;
    gap: 2rem;
    flex-wrap: wrap;
}

.stat {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: var(--text-secondary);
}

.stat i {
    color: var(--primary-color);
}

/* Note Actions */
.note-actions-bar {
    background: var(--bg-secondary);
    padding: 1.5rem 0;
    border-bottom: 1px solid var(--border-color);
}

.actions-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.primary-actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.secondary-actions {
    display: flex;
    gap: 0.5rem;
}

.like-btn[data-liked="1"] {
    background: var(--error-color);
    color: white;
    border-color: var(--error-color);
}

.like-btn[data-liked="1"]:hover {
    background: #dc2626;
}

/* Content Section */
.note-content-section {
    padding: 3rem 0;
}

.content-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 3rem;
}

.main-content {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.file-info-card,
.description-card {
    background: var(--bg-primary);
    border-radius: var(--border-radius-lg);
    padding: 2rem;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border-color);
}

.file-info-card h3,
.description-card h3,
.feedback-section h3 {
    font-size: 1.25rem;
    margin-bottom: 1.5rem;
    color: var(--text-primary);
}

.file-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    background: var(--bg-secondary);
    border-radius: var(--border-radius);
}

.detail-item i {
    color: var(--primary-color);
    width: 16px;
}

.detail-item span {
    color: var(--text-secondary);
    font-size: 0.875rem;
}

.detail-item strong {
    color: var(--text-primary);
}

.description-content {
    line-height: 1.7;
    color: var(--text-primary);
}

.description-content p {
    margin-bottom: 1rem;
}

.text-muted {
    color: var(--text-muted);
    font-style: italic;
}

/* Feedback Section */
.feedback-section {
    background: var(--bg-primary);
    border-radius: var(--border-radius-lg);
    padding: 2rem;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border-color);
}

.rating-stars {
    display: flex;
    gap: 0.25rem;
    margin-bottom: 1rem;
}

.rating-stars i {
    font-size: 1.5rem;
    color: var(--text-muted);
    cursor: pointer;
    transition: color var(--transition-fast);
}

.rating-stars i:hover,
.rating-stars i.active {
    color: #fbbf24;
}

.feedback-section textarea {
    width: 100%;
    padding: 1rem;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    font-family: inherit;
    resize: vertical;
    margin-bottom: 1rem;
}

.feedback-section textarea:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

/* Rating Statistics Section */
.rating-stats-section {
    background: var(--bg-primary);
    border-radius: var(--border-radius-lg);
    padding: 2rem;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border-color);
    margin-top: 2rem;
}

.rating-stats-section h3 {
    font-size: 1.25rem;
    margin-bottom: 1.5rem;
    color: var(--text-primary);
}

.rating-stats-content {
    min-height: 100px;
}

.loading-rating-stats {
    text-align: center;
    padding: 2rem;
    color: var(--text-secondary);
}

.loading-rating-stats i {
    margin-right: 0.5rem;
}

.rating-summary {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.rating-average {
    text-align: center;
    padding: 1.5rem;
    background: var(--bg-secondary);
    border-radius: var(--border-radius);
}

.average-number {
    font-size: 3rem;
    font-weight: 700;
    color: var(--primary-color);
    margin-bottom: 0.5rem;
}

.average-stars {
    font-size: 1.5rem;
    color: #fbbf24;
    margin-bottom: 0.5rem;
}

.average-text {
    color: var(--text-secondary);
    font-size: 0.9375rem;
}

.rating-distribution {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.distribution-row {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.distribution-label {
    width: 60px;
    font-weight: 600;
    color: var(--text-primary);
    font-size: 0.9375rem;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.distribution-label i {
    color: #fbbf24;
    font-size: 0.75rem;
}

.distribution-bar {
    flex: 1;
    height: 24px;
    background: var(--bg-secondary);
    border-radius: 12px;
    overflow: hidden;
    position: relative;
}

.distribution-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--primary-color), var(--primary-dark));
    border-radius: 12px;
    transition: width 0.3s ease;
}

.distribution-count {
    width: 40px;
    text-align: right;
    font-weight: 600;
    color: var(--text-secondary);
    font-size: 0.875rem;
}

/* Comments Section */
.comments-section {
    background: var(--bg-primary);
    border-radius: var(--border-radius-lg);
    padding: 2rem;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border-color);
    margin-top: 2rem;
}

.comments-section h3 {
    font-size: 1.25rem;
    margin-bottom: 1.5rem;
    color: var(--text-primary);
}

.add-comment-form {
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid var(--border-color);
}

.add-comment-form textarea {
    width: 100%;
    padding: 1rem;
    border: 2px solid var(--border-color);
    border-radius: var(--border-radius);
    font-family: inherit;
    resize: vertical;
    margin-bottom: 1rem;
    min-height: 80px;
}

.add-comment-form textarea:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.comments-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.comment-item {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    background: var(--bg-secondary);
    border-radius: var(--border-radius);
    transition: all var(--transition-fast);
}

.comment-item:hover {
    background: var(--bg-light);
}

.comment-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    flex-shrink: 0;
    overflow: hidden;
}

.comment-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.comment-content {
    flex: 1;
}

.comment-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
}

.comment-author {
    font-weight: 600;
    color: var(--text-primary);
    font-size: 0.9375rem;
}

.comment-date {
    font-size: 0.8125rem;
    color: var(--text-muted);
}

.comment-body {
    color: var(--text-primary);
    line-height: 1.6;
    white-space: pre-wrap;
    word-wrap: break-word;
}

.loading-comments {
    text-align: center;
    padding: 2rem;
    color: var(--text-secondary);
}

.loading-comments i {
    margin-right: 0.5rem;
}

/* Sidebar */
.sidebar {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.author-card,
.category-card,
.share-card {
    background: var(--bg-primary);
    border-radius: var(--border-radius-lg);
    padding: 2rem;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border-color);
}

.author-card h4,
.category-card h4,
.share-card h4 {
    font-size: 1.125rem;
    margin-bottom: 1.5rem;
    color: var(--text-primary);
}

.author-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.author-avatar-large {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    object-fit: cover;
}

.author-avatar-large-placeholder {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.author-info h4 {
    margin-bottom: 0.25rem;
}

.author-role {
    font-size: 0.875rem;
    color: var(--text-muted);
}

.author-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.author-stats .stat {
    text-align: center;
    padding: 1rem;
    background: var(--bg-secondary);
    border-radius: var(--border-radius);
}

.author-stats .stat-number {
    display: block;
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--primary-color);
    margin-bottom: 0.25rem;
}

.author-stats .stat-label {
    font-size: 0.75rem;
    color: var(--text-muted);
    text-transform: uppercase;
}

.btn-block {
    width: 100%;
}

.category-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
    padding: 1rem;
    background: var(--bg-secondary);
    border-radius: var(--border-radius);
}

.category-info i {
    font-size: 1.5rem;
    color: var(--primary-color);
}

.share-buttons {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0.5rem;
}

.share-btn {
    width: 40px;
    height: 40px;
    border: none;
    border-radius: var(--border-radius);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    cursor: pointer;
    transition: all var(--transition-fast);
}

.share-btn:hover {
    transform: translateY(-2px);
}

.share-btn.facebook {
    background: #1877f2;
}

.share-btn.twitter {
    background: #1da1f2;
}

.share-btn.linkedin {
    background: #0a66c2;
}

.share-btn.copy {
    background: var(--text-muted);
}

.share-btn.copy:hover {
    background: var(--text-primary);
}

/* Related Notes */
.related-notes {
    background: var(--bg-secondary);
    padding: 4rem 0;
}

.section-title {
    font-size: clamp(1.5rem, 3vw, 2rem);
    text-align: center;
    margin-bottom: 0.5rem;
    font-weight: 700;
    color: var(--text-primary);
}

.section-subtitle {
    text-align: center;
    color: var(--text-secondary);
    margin-bottom: 3rem;
    font-size: 1.125rem;
}

.related-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
}

.related-card {
    background: var(--bg-primary);
    border-radius: var(--border-radius-lg);
    overflow: hidden;
    cursor: pointer;
    transition: all var(--transition-normal);
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border-color);
}

.related-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
    border-color: var(--primary-color);
}

.related-image {
    height: 120px;
    background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: white;
}

.related-content {
    padding: 1.5rem;
}

.related-content h4 {
    font-size: 1rem;
    margin-bottom: 0.75rem;
    color: var(--text-primary);
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.related-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.75rem;
    color: var(--text-muted);
}

.related-meta .author {
    font-weight: 500;
}

.related-meta .stats {
    display: flex;
    gap: 0.75rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .content-grid {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    
    .note-meta-bar {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .actions-container {
        flex-direction: column;
        align-items: stretch;
    }
    
    .primary-actions,
    .secondary-actions {
        justify-content: center;
    }
    
    .file-details {
        grid-template-columns: 1fr;
    }
    
    .related-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .note-header {
        padding: 1.5rem 0;
    }
    
    .note-title-section h1 {
        font-size: 1.5rem;
    }
    
    .note-actions-bar {
        padding: 1rem 0;
    }
    
    .note-content-section {
        padding: 2rem 0;
    }
    
    .file-info-card,
    .description-card,
    .feedback-section {
        padding: 1.5rem;
    }
    
}
</style>

<script>
let currentRating = 0;

// Helper function to escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// View note in new tab
function viewNote() {
    window.open('view-file.php?id=<?= $note['id'] ?>', '_blank');
}

// Download note
function downloadNote() {
    // Track download first
    fetch('api/download.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ note_id: <?= $note['id'] ?> })
    })
    .then(response => response.json())
    .then(data => {
        // Update download count if provided
        if (data.success && data.downloads !== undefined) {
            const downloadsCount = document.getElementById('downloadsCount');
            if (downloadsCount) {
                downloadsCount.textContent = data.downloads;
            }
        }
        // Open download regardless of tracking success
        window.location.href = 'download.php?id=<?= $note['id'] ?>';
    })
    .catch(error => {
        console.error('Error tracking download:', error);
        // Still allow download even if tracking fails
        window.location.href = 'download.php?id=<?= $note['id'] ?>';
    });
}

// Toggle like
function toggleLike() {
    <?php if (!isset($_SESSION['auth_user_id'])): ?>
        showNotification('Please login to like notes', 'error');
        return;
    <?php endif; ?>
    const btn = document.querySelector('.like-btn');
    const isCurrentlyLiked = btn.dataset.liked === '1';
    
    // Optimistically update UI
    btn.disabled = true;
    
    fetch('api/toggle-like.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ note_id: <?= $note['id'] ?> })
    }).then(response => response.json())
      .then(data => {
          if (data.success) {
              // Update button state
              btn.dataset.liked = data.liked ? '1' : '0';
              if (data.liked) {
                  btn.classList.add('liked');
                  btn.querySelector('span').textContent = 'Liked';
              } else {
                  btn.classList.remove('liked');
                  btn.querySelector('span').textContent = 'Like';
              }
              
              // Update like count
              document.getElementById('likesCount').textContent = data.likes;
              
              showNotification(data.liked ? 'Added like' : 'Removed like', 'success');
          } else {
              showNotification('Failed to update like: ' + (data.error || 'Unknown error'), 'error');
          }
      })
      .catch(error => {
          console.error('Error toggling like:', error);
          showNotification('Error updating like. Please try again.', 'error');
      })
      .finally(() => {
          btn.disabled = false;
      });
}

// Share note
function shareNote() {
    if (navigator.share) {
        navigator.share({
            title: '<?= htmlspecialchars($note['title']) ?>',
            text: '<?= htmlspecialchars($note['description'] ?? '') ?>',
            url: window.location.href
        });
    } else {
        copyLink();
    }
}

// Share on social media
function shareOnFacebook() {
    window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(window.location.href)}`, '_blank');
}

function shareOnTwitter() {
    window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent('Check out these notes: ' + document.title)}&url=${encodeURIComponent(window.location.href)}`, '_blank');
}

function shareOnLinkedIn() {
    window.open(`https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(window.location.href)}`, '_blank');
}

// Copy link to clipboard
function copyLink() {
    navigator.clipboard.writeText(window.location.href).then(() => {
        showNotification('Link copied to clipboard!', 'success');
    });
}

// Report note
function reportNote() {
    <?php if (!isset($_SESSION['auth_user_id'])): ?>
        showNotification('Please login to report notes', 'error');
        return;
    <?php endif; ?>
    const reason = prompt('Please provide a reason for reporting this note:');
    if (!reason || reason.trim() === '') {
        showNotification('Please provide a reason for reporting', 'error');
        return;
    }
    if (!confirm('Report this note for: ' + reason + '?')) return;
    fetch('api/report-note.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ 
            note_id: <?= $note['id'] ?>,
            reason: reason.trim()
        })
    }).then(response => response.json())
      .then(data => {
          if (data.success) {
              showNotification(data.message || 'Note reported successfully. Admin will review it.', 'success');
          } else {
              showNotification('Failed to report note: ' + (data.error || 'Unknown error'), 'error');
          }
      })
      .catch(error => {
          console.error('Error reporting note:', error);
          showNotification('Error reporting note. Please try again.', 'error');
      });
}

// Rating functionality
document.addEventListener('DOMContentLoaded', function() {
    const stars = document.querySelectorAll('.rating-stars i');
    
    stars.forEach((star, index) => {
        star.addEventListener('mouseenter', () => {
            stars.forEach((s, i) => {
                if (i <= index) {
                    s.classList.add('active');
                    } else {
                    s.classList.remove('active');
                    }
                });
            });
            
        star.addEventListener('click', () => {
            currentRating = index + 1;
            stars.forEach((s, i) => {
                if (i < currentRating) {
                    s.classList.add('active');
                    } else {
                    s.classList.remove('active');
                    }
                });
        });
    });
    
    document.querySelector('.rating-stars').addEventListener('mouseleave', () => {
        stars.forEach((s, i) => {
            if (i < currentRating) {
                s.classList.add('active');
                } else {
                s.classList.remove('active');
                }
        });
    });
});

function submitRating() {
    <?php if (!isset($_SESSION['auth_user_id'])): ?>
        showNotification('Please login to rate notes', 'error');
        return;
    <?php endif; ?>
    if (currentRating === 0) {
        showNotification('Please select a rating', 'error');
        return;
    }
    
    const submitBtn = document.querySelector('.feedback-section button');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting...';
    }
    
    fetch('api/submit-rating.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            note_id: <?= $note['id'] ?>,
            rating: currentRating,
            comment: ''
        })
    }).then(response => response.json())
      .then(data => {
          if (data.success) {
              showNotification(data.message || 'Rating submitted successfully', 'success');
              currentRating = 0;
              document.querySelectorAll('.rating-stars i').forEach(s => s.classList.remove('active'));
              
              // Reload rating statistics
              loadRatingStats();
          } else {
              showNotification('Failed to submit rating: ' + (data.error || 'Unknown error'), 'error');
          }
      })
      .catch(error => {
          console.error('Error submitting rating:', error);
          showNotification('Error submitting rating. Please try again.', 'error');
      })
      .finally(() => {
          if (submitBtn) {
              submitBtn.disabled = false;
              submitBtn.textContent = 'Submit Rating';
          }
      });
}

// Load comments
function loadComments() {
    const container = document.getElementById('commentsContainer');
    if (!container) return;
    
    container.innerHTML = '<div class="loading-comments"><i class="fas fa-spinner fa-spin"></i> Loading comments...</div>';
    
    fetch(`api/get-comments.php?note_id=<?= $note['id'] ?>`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.comments.length === 0) {
                    container.innerHTML = '<p class="text-muted">No comments yet. Be the first to comment!</p>';
                } else {
                    container.innerHTML = data.comments.map(comment => renderComment(comment)).join('');
                }
            } else {
                container.innerHTML = '<p class="text-muted">Error loading comments.</p>';
            }
        })
        .catch(error => {
            console.error('Error loading comments:', error);
            container.innerHTML = '<p class="text-muted">Error loading comments.</p>';
        });
}

// Render a single comment
function renderComment(comment) {
    const avatar = comment.avatar_url && comment.avatar_url !== 'https://via.placeholder.com/150' && comment.avatar_url !== 'https://via.placeholder.com/40';
    const avatarHtml = avatar 
        ? `<img src="${escapeHtml(comment.avatar_url)}" alt="${escapeHtml(comment.username)}" onerror="this.parentElement.innerHTML='<i class=\\'fas fa-user\\'></i>'">`
        : '<i class="fas fa-user"></i>';
    
    const date = new Date(comment.created_at);
    const timeAgo = getTimeAgo(date);
    
    return `
        <div class="comment-item">
            <div class="comment-avatar">
                ${avatarHtml}
            </div>
            <div class="comment-content">
                <div class="comment-header">
                    <span class="comment-author">${escapeHtml(comment.username)}</span>
                    <span class="comment-date">${timeAgo}</span>
                </div>
                <div class="comment-body">${escapeHtml(comment.body)}</div>
            </div>
        </div>
    `;
}

// Add a new comment
function addComment() {
    <?php if (!isset($_SESSION['auth_user_id'])): ?>
        showNotification('Please login to leave a comment', 'error');
        return;
    <?php endif; ?>
    
    const textarea = document.getElementById('newCommentText');
    if (!textarea) return;
    
    const comment = textarea.value.trim();
    
    if (!comment) {
        showNotification('Please enter a comment', 'error');
        return;
    }
    
    const btn = document.querySelector('.add-comment-form button');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Posting...';
    }
    
    fetch('api/add-comment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            note_id: <?= $note['id'] ?>,
            body: comment
        })
    }).then(response => response.json())
      .then(data => {
          if (data.success) {
              textarea.value = '';
              showNotification('Comment posted successfully', 'success');
              loadComments();
          } else {
              showNotification('Failed to post comment: ' + (data.error || 'Unknown error'), 'error');
          }
      })
      .catch(error => {
          console.error('Error posting comment:', error);
          showNotification('Error posting comment. Please try again.', 'error');
      })
      .finally(() => {
          if (btn) {
              btn.disabled = false;
              btn.innerHTML = '<i class="fas fa-paper-plane"></i> Post Comment';
          }
      });
}

// Utility functions
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function getTimeAgo(date) {
    const now = new Date();
    const diff = now - date;
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);
    
    if (minutes < 1) return 'Just now';
    if (minutes < 60) return `${minutes}m ago`;
    if (hours < 24) return `${hours}h ago`;
    if (days < 7) return `${days}d ago`;
    return date.toLocaleDateString();
}

// Utility function to show notifications
function showNotification(message, type = 'info') {
    // Get or create notification container
    let container = document.getElementById('notificationContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'notificationContainer';
        document.body.appendChild(container);
    }
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
        <span>${message}</span>
    `;
    
    // Add to container
    container.appendChild(notification);
    
    // Remove after 5 seconds with animation
    setTimeout(() => {
        notification.classList.add('removing');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
            // Remove container if empty
            if (container && container.children.length === 0) {
                container.remove();
            }
        }, 300);
    }, 5000);
}

// Load rating statistics
function loadRatingStats() {
    const container = document.getElementById('ratingStatsContainer');
    if (!container) return;
    
    container.innerHTML = '<div class="loading-rating-stats"><i class="fas fa-spinner fa-spin"></i> Loading ratings...</div>';
    
    fetch(`api/get-rating-stats.php?note_id=<?= $note['id'] ?>`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                container.innerHTML = renderRatingStats(data);
            } else {
                container.innerHTML = '<p class="text-muted">Error loading rating statistics.</p>';
            }
        })
        .catch(error => {
            console.error('Error loading rating stats:', error);
            container.innerHTML = '<p class="text-muted">Error loading rating statistics.</p>';
        });
}

// Render rating statistics
function renderRatingStats(stats) {
    if (stats.total_ratings === 0) {
        return '<p class="text-muted">No ratings yet. Be the first to rate this note!</p>';
    }
    
    const avgRating = stats.average_rating;
    const fullStars = Math.floor(avgRating);
    const hasHalfStar = (avgRating - fullStars) >= 0.5;
    const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);
    
    let starsHtml = '';
    for (let i = 0; i < fullStars; i++) {
        starsHtml += '<i class="fas fa-star"></i>';
    }
    if (hasHalfStar) {
        starsHtml += '<i class="fas fa-star-half-alt"></i>';
    }
    for (let i = 0; i < emptyStars; i++) {
        starsHtml += '<i class="far fa-star"></i>';
    }
    
    const totalRatings = stats.total_ratings;
    const distribution = stats.distribution;
    
    // Calculate percentages for distribution bars
    const getPercentage = (count) => totalRatings > 0 ? (count / totalRatings) * 100 : 0;
    
    return `
        <div class="rating-summary">
            <div class="rating-average">
                <div class="average-number">${avgRating.toFixed(1)}</div>
                <div class="average-stars">${starsHtml}</div>
                <div class="average-text">Based on ${totalRatings} ${totalRatings === 1 ? 'rating' : 'ratings'}</div>
            </div>
            <div class="rating-distribution">
                ${[5, 4, 3, 2, 1].map(rating => {
                    const count = distribution[rating] || 0;
                    const percentage = getPercentage(count);
                    return `
                        <div class="distribution-row">
                            <span class="distribution-label">${rating} <i class="fas fa-star"></i></span>
                            <div class="distribution-bar">
                                <div class="distribution-fill" style="width: ${percentage}%"></div>
                            </div>
                            <span class="distribution-count">${count}</span>
                        </div>
                    `;
                }).join('')}
            </div>
        </div>
    `;
}

// Load comments and rating stats on page load (only for published notes)
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($note['status'] === 'published'): ?>
    loadComments();
    loadRatingStats();
    <?php endif; ?>
});
</script>
<?php
// Helper function to format file size
function formatFileSize($bytes) {
    // Handle null, empty, or non-numeric values
    if ($bytes === null || $bytes === '' || !is_numeric($bytes)) {
        return 'Unknown';
    }
    
    $bytes = (int)$bytes;
    
    if ($bytes === 0) {
        return '0 Bytes';
    }
    
    // Handle negative values
    if ($bytes < 0) {
        return 'Unknown';
    }
    
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    
    // Calculate the appropriate unit
    $i = floor(log($bytes) / log($k));
    
    // Ensure index is within bounds
    if ($i < 0) {
        $i = 0;
    }
    if ($i >= count($sizes)) {
        $i = count($sizes) - 1;
    }
    
    $size = round($bytes / pow($k, $i), 2);
    
    // Remove unnecessary decimal places for whole numbers
    if ($size == (int)$size) {
        $size = (int)$size;
    }
    
    return $size . ' ' . $sizes[$i];
}

// Reset variables to avoid conflicts
unset($note, $relatedNotes, $isLiked);
?>