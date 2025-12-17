<?php
// View other users' profiles with follow functionality
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

ensureLoggedIn();
$currentUserId = (int)$_SESSION['auth_user_id'];

// Get user ID from query parameter
$viewUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if ($viewUserId <= 0) {
    redirect('index.php');
}

// Can't view own profile here (use profile.php instead)
if ($viewUserId === $currentUserId) {
    redirect('profile.php');
}

/* ----------  Get User Data  ---------- */
$userStmt = $pdo->prepare('
    SELECT u.id, u.username, u.avatar_url, u.cover_url, u.bio, u.university, u.major, u.study_year, u.member_since,
           (SELECT COUNT(*) FROM notes WHERE notes.user_id = u.id AND notes.status = "published") as notes_count,
           (SELECT COUNT(*) FROM follows WHERE follows.follower_id = u.id) as following_count,
           (SELECT COUNT(*) FROM follows WHERE follows.following_id = u.id) as followers_count
    FROM users u
    WHERE u.id = ?
');
$userStmt->execute([$viewUserId]);
$user = $userStmt->fetch();

if (!$user) {
    redirect('index.php');
}

/* ----------  Check if current user is following this user  ---------- */
$followCheck = $pdo->prepare('SELECT follower_id FROM follows WHERE follower_id = ? AND following_id = ?');
$followCheck->execute([$currentUserId, $viewUserId]);
$isFollowing = (bool)$followCheck->fetch();

/* ----------  Get User's Published Notes  ---------- */
$noteStmt = $pdo->prepare('
    SELECT n.*, c.name AS cat_name, c.icon
    FROM notes n
    JOIN categories c ON c.id = n.category_id
    WHERE n.user_id = ? AND n.status = "published"
    ORDER BY n.created_at DESC
    LIMIT 12
');
$noteStmt->execute([$viewUserId]);
$notes = $noteStmt->fetchAll();

$pageTitle = htmlspecialchars($user['username']) . ' - Profile - Student Notes Hub';
require __DIR__ . '/components/header.php';
?>

<style>
    .profile-section {
        padding: 2rem 0;
        min-height: calc(100vh - var(--navbar-height) - 2rem);
    }

    .profile-card {
        background: var(--bg-primary);
        border-radius: 16px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, .1);
        padding: 2rem;
        margin-bottom: 2rem;
        animation: fadeIn 0.6s ease-out;
        border: 1px solid var(--border-color);
    }

    .profile-header {
        display: flex;
        align-items: center;
        gap: 2rem;
        margin-bottom: 2rem;
        flex-wrap: wrap;
    }

    .avatar-section {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1rem;
    }

    .avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        border: 5px solid var(--bg-primary);
        position: relative;
        overflow: hidden;
        background: var(--bg-light);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        color: var(--text-muted);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, .1);
    }

    .avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .avatar-placeholder {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, var(--primary-color), #8b5cf6);
        color: white;
        font-size: 3rem;
    }

    .profile-info {
        flex: 1;
    }

    .profile-info h1 {
        font-size: clamp(1.5rem, 4vw, 2rem);
        margin-bottom: 0.5rem;
        color: var(--text-primary);
    }

    .profile-info p {
        color: var(--text-secondary);
        margin-bottom: 1rem;
        line-height: 1.5;
    }

    .profile-stats {
        display: flex;
        gap: 1.5rem;
        margin-bottom: 1rem;
        flex-wrap: wrap;
    }

    .stat {
        text-align: center;
    }

    .stat b {
        font-size: 1.25rem;
        color: var(--primary-color);
        display: block;
        font-weight: 700;
    }

    .stat span {
        font-size: 0.9rem;
        color: var(--text-secondary);
    }

    .profile-actions {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .follow-btn {
        background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        color: white;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 1rem;
    }

    .follow-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    }

    .follow-btn.following {
        background: var(--bg-light);
        color: var(--text-primary);
        border: 2px solid var(--border-color);
    }

    .follow-btn.following:hover {
        background: var(--error-color);
        color: white;
        border-color: var(--error-color);
    }

    .message-btn {
        background: var(--bg-light);
        color: var(--text-primary);
        border: 2px solid var(--border-color);
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        text-decoration: none;
        font-size: 1rem;
    }

    .message-btn:hover {
        background: var(--primary-color);
        color: white;
        border-color: var(--primary-color);
        transform: translateY(-2px);
    }

    .notes-section {
        margin-top: 2rem;
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .section-title {
        font-size: 1.5rem;
        color: var(--text-primary);
        margin: 0;
    }

    .notes-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1.5rem;
        margin-top: 1rem;
    }

    .note-card {
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 1.5rem;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        animation: slideUp 0.6s ease-out;
    }

    .note-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--primary-color), var(--primary-dark));
        transform: scaleX(0);
        transition: transform 0.3s ease;
    }

    .note-card:hover::before {
        transform: scaleX(1);
    }

    .note-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 25px -3px rgba(0, 0, 0, .1);
        border-color: var(--primary-color);
    }

    .note-category {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 500;
        margin-bottom: 0.5rem;
        background: rgba(99, 102, 241, 0.1);
        color: var(--primary-color);
    }
    
    /* Dark Mode Support */
    [data-theme="dark"] .profile-section {
        background: var(--bg-secondary);
    }
    
    [data-theme="dark"] .profile-card {
        background: var(--bg-primary);
        border-color: var(--border-color);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
    }
    
    [data-theme="dark"] .avatar {
        border-color: var(--bg-primary);
        background: var(--bg-light);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
    }
    
    [data-theme="dark"] .note-card {
        background: var(--bg-primary);
        border-color: var(--border-color);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }
    
    [data-theme="dark"] .note-card:hover {
        box-shadow: 0 10px 25px -3px rgba(0, 0, 0, 0.5);
    }
    
    [data-theme="dark"] .note-category {
        background: rgba(99, 102, 241, 0.2);
        color: var(--primary-light);
    }
    
    [data-theme="dark"] .follow-btn.following {
        background: var(--bg-light);
        color: var(--text-primary);
        border-color: var(--border-color);
    }
    
    [data-theme="dark"] .message-btn {
        background: var(--bg-light);
        color: var(--text-primary);
        border-color: var(--border-color);
    }
    
    [data-theme="dark"] .message-btn:hover {
        background: var(--primary-color);
        color: white;
        border-color: var(--primary-color);
    }

    .note-title {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: var(--text-primary);
    }

    .note-description {
        color: var(--text-secondary);
        font-size: 0.9rem;
        line-height: 1.4;
        margin-bottom: 1rem;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .note-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid var(--border-color);
    }

    .note-stats {
        display: flex;
        gap: 1rem;
        font-size: 0.85rem;
        color: var(--text-secondary);
    }

    .note-date {
        font-size: 0.75rem;
        color: var(--text-muted);
    }

    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        color: var(--text-secondary);
    }

    .empty-state i {
        font-size: 4rem;
        margin-bottom: 1rem;
        color: var(--text-muted);
    }

    .empty-state h3 {
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
        color: var(--text-primary);
    }

    /* Animation Classes */
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

    .fade-in {
        animation: fadeIn 0.6s ease-out;
    }

    .slide-up {
        animation: slideUp 0.6s ease-out;
    }

    @media (max-width: 768px) {
        .profile-header {
            flex-direction: column;
            text-align: center;
        }

        .profile-stats {
            justify-content: center;
        }

        .profile-actions {
            justify-content: center;
            width: 100%;
        }

        .follow-btn,
        .message-btn {
            flex: 1;
            justify-content: center;
        }

        .notes-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<main>
    <section class="profile-section">
        <div class="container">
            <div class="profile-card fade-in">
                <div class="profile-header">
                    <div class="avatar-section">
                        <div class="avatar">
                            <?php if (!empty($user['avatar_url']) && $user['avatar_url'] !== 'https://via.placeholder.com/150'): ?>
                                <img src="<?= htmlspecialchars($user['avatar_url']) ?>" alt="avatar">
                            <?php else: ?>
                                <div class="avatar-placeholder"><i class="fas fa-user"></i></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="profile-info">
                        <h1><?= htmlspecialchars($user['username']) ?></h1>
                        <p><?= nl2br(htmlspecialchars($user['bio'] ?? 'No bio yet.')) ?></p>

                        <?php if ($user['university'] || $user['major'] || $user['study_year']): ?>
                            <div style="margin-bottom: 1rem; color: var(--text-secondary); font-size: 0.9rem;">
                                <?php if ($user['university']): ?>
                                    <div><i class="fas fa-university"></i> <?= htmlspecialchars($user['university']) ?></div>
                                <?php endif; ?>
                                <?php if ($user['major']): ?>
                                    <div><i class="fas fa-book"></i> <?= htmlspecialchars($user['major']) ?></div>
                                <?php endif; ?>
                                <?php if ($user['study_year']): ?>
                                    <div><i class="fas fa-calendar"></i> <?= htmlspecialchars($user['study_year']) ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="profile-stats">
                            <div class="stat">
                                <b id="notesCount"><?= (int)$user['notes_count'] ?></b>
                                <span>Notes</span>
                            </div>
                            <div class="stat">
                                <b id="followersCount"><?= (int)$user['followers_count'] ?></b>
                                <span>Followers</span>
                            </div>
                            <div class="stat">
                                <b><?= (int)$user['following_count'] ?></b>
                                <span>Following</span>
                            </div>
                        </div>

                        <div class="profile-actions">
                            <button class="follow-btn <?= $isFollowing ? 'following' : '' ?>" 
                                    id="followBtn" 
                                    data-user-id="<?= $viewUserId ?>"
                                    onclick="toggleFollow(<?= $viewUserId ?>)">
                                <i class="fas fa-<?= $isFollowing ? 'user-check' : 'user-plus' ?>"></i>
                                <span id="followBtnText"><?= $isFollowing ? 'Following' : 'Follow' ?></span>
                            </button>
                            <a href="chat.php" class="message-btn" onclick="startConversation(<?= $viewUserId ?>); return false;">
                                <i class="fas fa-envelope"></i>
                                Message
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes Section -->
            <div class="notes-section">
                <div class="section-header">
                    <h2 class="section-title">Published Notes</h2>
                </div>

                <?php if (empty($notes)): ?>
                    <div class="empty-state">
                        <i class="fas fa-file-alt"></i>
                        <h3>No notes yet</h3>
                        <p>This user hasn't published any notes yet.</p>
                    </div>
                <?php else: ?>
                    <div class="notes-grid">
                        <?php foreach ($notes as $index => $note): ?>
                            <div class="note-card slide-up" 
                                 style="animation-delay: <?= $index * 0.1 ?>s;"
                                 onclick="window.location.href='note-detail.php?id=<?= $note['id'] ?>'">
                                <div class="note-category">
                                    <i class="fas fa-<?= $note['icon'] ?? 'file-alt' ?>"></i>
                                    <?= htmlspecialchars($note['cat_name']) ?>
                                </div>
                                <h3 class="note-title"><?= htmlspecialchars($note['title']) ?></h3>
                                <p class="note-description"><?= htmlspecialchars($note['description'] ?? 'No description available.') ?></p>

                                <div class="note-meta">
                                    <div class="note-stats">
                                        <span><i class="fas fa-download"></i> <?= $note['downloads'] ?? 0 ?></span>
                                        <span><i class="fas fa-heart"></i> <?= $note['likes'] ?? 0 ?></span>
                                        <span><i class="fas fa-eye"></i> <?= $note['views'] ?? 0 ?></span>
                                    </div>
                                    <div class="note-date">
                                        <i class="fas fa-clock"></i>
                                        <?= date('M j, Y', strtotime($note['created_at'])) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<script>
let isFollowing = <?= $isFollowing ? 'true' : 'false' ?>;

async function toggleFollow(userId) {
    const btn = document.getElementById('followBtn');
    const btnText = document.getElementById('followBtnText');
    const followersCount = document.getElementById('followersCount');
    
    // Disable button during request
    btn.disabled = true;
    
    try {
        const response = await fetch('api/toggle-follow.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ user_id: userId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            isFollowing = data.is_following;
            
            // Update button
            const icon = btn.querySelector('i');
            if (isFollowing) {
                btn.classList.add('following');
                if (icon) icon.className = 'fas fa-user-check';
                btnText.textContent = 'Following';
            } else {
                btn.classList.remove('following');
                if (icon) icon.className = 'fas fa-user-plus';
                btnText.textContent = 'Follow';
            }
            
            // Update followers count
            if (followersCount) {
                followersCount.textContent = data.followers_count;
            }
            
            // Show notification
            showNotification(
                isFollowing ? 'You are now following this user' : 'You unfollowed this user',
                'success'
            );
        } else {
            showNotification(data.error || 'Failed to update follow status', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('An error occurred. Please try again.', 'error');
    } finally {
        btn.disabled = false;
    }
}

async function startConversation(userId) {
    try {
        const response = await fetch('api/start-conversation.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ user_id: userId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            window.location.href = 'chat.php';
        } else {
            showNotification('Failed to start conversation', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('An error occurred. Please try again.', 'error');
    }
}

function showNotification(message, type = 'info') {
    let container = document.getElementById('notificationContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'notificationContainer';
        document.body.appendChild(container);
    }
    
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
        <span>${message}</span>
    `;
    
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
</script>

<?php require __DIR__ . '/components/footer.php'; ?>

