<?php
// Unified notes page for browsing all notes or user's notes
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

// Set page title
$pageTitle = 'Browse Notes - Student Notes Hub';

// Check if we're viewing a specific user's notes
$viewUserNotes = isset($_GET['user']) && $_GET['user'] === 'me' && isset($_SESSION['auth_user_id']);
$viewCategory = $_GET['category'] ?? null;
$searchQuery = $_GET['q'] ?? null;

/* ----------  Pagination Setup  ---------- */
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

/* ----------  Build Query Based on View  ---------- */
if ($viewUserNotes) {
    // Viewing logged-in user's notes
    $baseQuery = 'FROM notes n 
                  JOIN categories c ON c.id = n.category_id 
                  JOIN users u ON u.id = n.user_id 
                  WHERE n.user_id = :user_id';
    $countQuery = 'SELECT COUNT(*) FROM notes WHERE user_id = :user_id';
    $params = [':user_id' => $_SESSION['auth_user_id']];
    $pageTitle = 'My Notes - Student Notes Hub';
} elseif ($viewCategory) {
    // Viewing notes by category
    $baseQuery = 'FROM notes n 
                  JOIN categories c ON c.id = n.category_id 
                  JOIN users u ON u.id = n.user_id 
                  WHERE n.status = "published" AND c.slug = :category';
    $countQuery = 'SELECT COUNT(*) FROM notes n 
                   JOIN categories c ON c.id = n.category_id 
                   WHERE n.status = "published" AND c.slug = :category';
    $params = [':category' => $viewCategory];
    $pageTitle = 'Notes - ' . ucfirst($viewCategory) . ' - Student Notes Hub';
} elseif ($searchQuery) {
    // Search results
    $baseQuery = 'FROM notes n 
                  JOIN categories c ON c.id = n.category_id 
                  JOIN users u ON u.id = n.user_id 
                  WHERE n.status = "published" AND 
                        (n.title LIKE :search OR n.description LIKE :search)';
    $countQuery = 'SELECT COUNT(*) FROM notes 
                   WHERE status = "published" AND 
                         (title LIKE :search OR description LIKE :search)';
    $params = [':search' => '%' . $searchQuery . '%'];
    $pageTitle = 'Search Results - ' . htmlspecialchars($searchQuery) . ' - Student Notes Hub';
} else {
    // Viewing all published notes
    $baseQuery = 'FROM notes n 
                  JOIN categories c ON c.id = n.category_id 
                  JOIN users u ON u.id = n.user_id 
                  WHERE n.status = "published"';
    $countQuery = 'SELECT COUNT(*) FROM notes WHERE status = "published"';
    $params = [];
}

/* ----------  Get Total Count  ---------- */
$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pages = ceil($total / $limit);

/* ----------  Get Notes  ---------- */
$query = 'SELECT n.id, n.title, n.description, n.file_url, n.created_at, n.downloads, n.likes, n.views,
                 n.status, c.slug AS category, c.name AS cat_name, c.icon, u.id AS user_id, u.username, u.avatar_url ' . 
         $baseQuery . ' ORDER BY n.created_at DESC LIMIT :l OFFSET :o';

$notesStmt = $pdo->prepare($query);
foreach ($params as $key => $value) {
    $notesStmt->bindValue($key, $value);
}
$notesStmt->bindValue(':l', $limit, PDO::PARAM_INT);
$notesStmt->bindValue(':o', $offset, PDO::PARAM_INT);
$notesStmt->execute();
$notes = $notesStmt->fetchAll();

/* ----------  Get Categories for Filter  ---------- */
$categories = $pdo->query('SELECT slug, name, icon FROM categories ORDER BY name')->fetchAll();

// Include header
require __DIR__ . '/components/header.php';
?>

    <!-- Main Content -->
    <main>
        <!-- Page Header -->
        <section class="page-header">
            <div class="container">
                <div class="header-content">
                    <?php if ($viewUserNotes): ?>
                        <h1><i class="fas fa-file-alt"></i> My Notes</h1>
                        <p>Manage and view all your uploaded notes</p>
                    <?php elseif ($viewCategory): ?>
                        <h1><i class="fas fa-tag"></i> <?= htmlspecialchars(ucfirst($viewCategory)) ?> Notes</h1>
                        <p>Browse notes in the <?= htmlspecialchars($viewCategory) ?> category</p>
                    <?php elseif ($searchQuery): ?>
                        <h1><i class="fas fa-search"></i> Search Results</h1>
                        <p>Found <?= $total ?> results for "<?= htmlspecialchars($searchQuery) ?>"</p>
                    <?php else: ?>
                        <h1><i class="fas fa-book"></i> Browse Notes</h1>
                        <p>Discover notes from students around the world</p>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        
        <!-- Filters and Search -->
        <section class="filters">
            <div class="container">
                <div class="filters-container">
                    <!-- Category Filter -->
                    <div class="filter-group">
                        <label for="categoryFilter">
                            <i class="fas fa-filter"></i>
                            Filter by Category
                        </label>
                        <select id="categoryFilter" onchange="filterByCategory()">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['slug'] ?>" 
                                        <?= $viewCategory === $cat['slug'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Sort Options -->
                    <div class="filter-group">
                        <label for="sortFilter">
                            <i class="fas fa-sort"></i>
                            Sort By
                        </label>
                        <select id="sortFilter" onchange="sortNotes()">
                            <option value="newest">Newest First</option>
                            <option value="oldest">Oldest First</option>
                            <option value="popular">Most Popular</option>
                            <option value="downloads">Most Downloaded</option>
                            <option value="likes">Most Liked</option>
                        </select>
                    </div>
                    
                    <!-- View Toggle -->
                    <div class="view-toggle">
                        <button type="button" class="view-btn active" onclick="setView('grid')" title="Grid View">
                            <i class="fas fa-th-large"></i>
                        </button>
                        <button type="button" class="view-btn" onclick="setView('list')" title="List View">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Notes Grid -->
        <section class="notes-section">
            <div class="container">
                <?php if (empty($notes)): ?>
                    <div class="empty-state">
                        <i class="fas fa-file-alt" style="font-size: 4rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
                        
                        <?php if ($viewUserNotes): ?>
                            <h3>No notes uploaded yet</h3>
                            <p>You haven't uploaded any notes yet. Start sharing your knowledge!</p>
                            <a href="upload.php" class="btn btn-primary">
                                <i class="fas fa-upload"></i>
                                Upload Your First Note
                            </a>
                        <?php elseif ($searchQuery): ?>
                            <h3>No notes found</h3>
                            <p>No notes match your search for "<?= htmlspecialchars($searchQuery) ?>"</p>
                            <a href="notes.php" class="btn btn-primary">
                                <i class="fas fa-book"></i>
                                Browse All Notes
                            </a>
                        <?php else: ?>
                            <h3>No notes available</h3>
                            <p>There are no notes available at the moment. Check back later!</p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="notes-grid" id="notesGrid">
                        <?php foreach ($notes as $note): ?>
                            <div class="note-card" data-note-id="<?= $note['id'] ?>" onclick="window.location.href='note-detail.php?id=<?= $note['id'] ?>'" style="cursor: pointer;">
                                <div class="note-image">
                                    <i class="fas fa-<?= $note['icon'] ?? 'file-alt' ?>"></i>
                                </div>
                                <div class="note-content">
                                    <div class="note-category">
                                        <i class="fas fa-<?= $note['icon'] ?? 'file-alt' ?>"></i>
                                        <?= htmlspecialchars($note['cat_name']) ?>
                                    </div>
                                    <h3 class="note-title"><?= htmlspecialchars($note['title']) ?></h3>
                                    <p class="note-description"><?= htmlspecialchars($note['description'] ?? '') ?></p>
                                    
                                    <div class="note-meta">
                                        <a href="profile_users.php?user_id=<?= $note['user_id'] ?>" class="note-author" onclick="event.stopPropagation();">
                                            <img src="<?= htmlspecialchars($note['avatar_url'] ?? 'https://via.placeholder.com/24') ?>" 
                                                 alt="<?= htmlspecialchars($note['username']) ?>" 
                                                 style="width: 24px; height: 24px; border-radius: 50%; margin-right: 0.5rem;">
                                            <?= htmlspecialchars($note['username']) ?>
                                        </a>
                                        <div class="note-stats">
                                            <span title="Downloads">
                                                <i class="fas fa-download"></i>
                                                <?= $note['downloads'] ?? 0 ?>
                                            </span>
                                            <span title="Likes">
                                                <i class="fas fa-heart"></i>
                                                <?= $note['likes'] ?? 0 ?>
                                            </span>
                                            <span title="Views">
                                                <i class="fas fa-eye"></i>
                                                <?= $note['views'] ?? 0 ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="note-actions">
                                        <button type="button" class="btn btn-primary btn-sm" 
                                                onclick="viewNote(<?= $note['id'] ?>)" 
                                                title="View Note">
                                            <i class="fas fa-eye"></i>
                                            View
                                        </button>
                                        <button type="button" class="btn btn-secondary btn-sm" 
                                                onclick="downloadNote(<?= $note['id'] ?>)" 
                                                title="Download">
                                            <i class="fas fa-download"></i>
                                            Download
                                        </button>
                                        <?php if ($viewUserNotes): ?>
                                            <div class="note-user-actions">
                                                <a href="edit-note.php?id=<?= $note['id'] ?>" 
                                                   class="btn btn-outline btn-sm" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-danger btn-sm" 
                                                        onclick="deleteNote(<?= $note['id'] ?>)" 
                                                        title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="note-date">
                                        <i class="fas fa-clock"></i>
                                        <?= date('M j, Y', strtotime($note['created_at'])) ?>
                                        <?php if ($viewUserNotes && $note['status'] !== 'published'): ?>
                                            <span class="note-status status-<?= $note['status'] ?>">
                                                <?= ucfirst($note['status']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" 
                                   class="pagination-link">
                                    <i class="fas fa-chevron-left"></i>
                                    Previous
                                </a>
                            <?php endif; ?>
                            
                            <?php 
                            $startPage = max(1, $page - 2);
                            $endPage = min($pages, $page + 2);
                            
                            if ($startPage > 1): ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>" 
                                   class="pagination-link">1</a>
                                <?php if ($startPage > 2): ?>
                                    <span class="pagination-dots">...</span>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <?php if ($i === $page): ?>
                                    <span class="pagination-current"><?= $i ?></span>
                                <?php else: ?>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                                       class="pagination-link"><?= $i ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($endPage < $pages): ?>
                                <?php if ($endPage < $pages - 1): ?>
                                    <span class="pagination-dots">...</span>
                                <?php endif; ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pages])) ?>" 
                                   class="pagination-link"><?= $pages ?></a>
                            <?php endif; ?>
                            
                            <?php if ($page < $pages): ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" 
                                   class="pagination-link">
                                    Next
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

<?php
// Include footer
require __DIR__ . '/components/footer.php';
?>

    <!-- Additional Styles -->
    <style>
        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 3rem 0 2rem;
            text-align: center;
        }
        
        .header-content h1 {
            font-size: clamp(1.75rem, 4vw, 2.5rem);
            margin-bottom: 0.5rem;
            font-weight: 700;
        }
        
        .header-content p {
            font-size: 1.125rem;
            opacity: 0.9;
        }
        
        /* Filters Section */
        .filters {
            background: var(--bg-primary);
            padding: 1.5rem 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .filters-container {
            display: flex;
            align-items: center;
            gap: 2rem;
            flex-wrap: wrap;
        }
        
        .filter-group {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .filter-group label {
            font-weight: 500;
            color: var(--text-primary);
            white-space: nowrap;
        }
        
        .filter-group select {
            padding: 0.5rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            background: var(--bg-primary);
            color: var(--text-primary);
            font-size: 0.875rem;
            cursor: pointer;
            transition: all var(--transition-fast);
        }
        
        .filter-group select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .view-toggle {
            display: flex;
            gap: 0.5rem;
            margin-left: auto;
        }
        
        .view-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border: 1px solid var(--border-color);
            background: var(--bg-primary);
            color: var(--text-secondary);
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: all var(--transition-fast);
        }
        
        .view-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .view-btn.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        /* Notes Section */
        .notes-section {
            padding: 3rem 0;
        }
        
        .notes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .notes-grid.list-view {
            grid-template-columns: 1fr;
        }
        
        .notes-grid.list-view .note-card {
            display: flex;
            align-items: stretch;
            height: 200px;
        }
        
        .notes-grid.list-view .note-image {
            width: 200px;
            height: 100%;
            flex-shrink: 0;
        }
        
        .notes-grid.list-view .note-content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .note-card {
            background: var(--bg-primary);
            border-radius: var(--border-radius-lg);
            overflow: hidden;
            cursor: pointer;
            transition: all var(--transition-normal);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
        }
        
        .note-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
            border-color: var(--primary-color);
        }
        
        .note-image {
            height: 160px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
            position: relative;
        }
        
        .note-content {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .note-category {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary-color);
            padding: 0.25rem 0.75rem;
            border-radius: var(--border-radius);
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .note-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
            line-height: 1.3;
        }
        
        .note-description {
            color: var(--text-secondary);
            font-size: 0.875rem;
            line-height: 1.5;
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            flex: 1;
        }
        
        .note-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .note-author {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--text-secondary);
            text-decoration: none;
            transition: color 0.2s;
        }
        
        .note-author:hover {
            color: var(--primary-color);
        }
        
        .note-author img {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .note-stats {
            display: flex;
            gap: 1rem;
            font-size: 0.75rem;
            color: var(--text-muted);
        }
        
        .note-stats span {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .note-actions {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }
        
        .note-user-actions {
            display: flex;
            gap: 0.5rem;
            margin-left: auto;
        }
        
        .note-date {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.75rem;
            color: var(--text-muted);
        }
        
        .note-status {
            padding: 0.25rem 0.5rem;
            border-radius: var(--border-radius);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-draft {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
        }
        
        /* Removed private status */
            background: rgba(239, 68, 68, 0.1);
            color: var(--error-color);
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin: 3rem 0;
            flex-wrap: wrap;
        }
        
        .pagination-link,
        .pagination-current {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            font-weight: 500;
            transition: all var(--transition-fast);
            min-width: 40px;
            height: 40px;
        }
        
        .pagination-link {
            color: var(--text-secondary);
            background: var(--bg-primary);
        }
        
        .pagination-link:hover {
            background: var(--bg-light);
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .pagination-current {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .pagination-dots {
            padding: 0.5rem;
            color: var(--text-muted);
        }
        
        /* Empty State */
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
        
        .empty-state p {
            margin-bottom: 2rem;
            font-size: 1.125rem;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .filters-container {
                flex-direction: column;
                align-items: stretch;
                gap: 1rem;
            }
            
            .filter-group {
                flex-direction: column;
                align-items: stretch;
            }
            
            .view-toggle {
                margin-left: 0;
                justify-content: center;
            }
            
            .notes-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .notes-grid.list-view .note-card {
                flex-direction: column;
                height: auto;
            }
            
            .notes-grid.list-view .note-image {
                width: 100%;
                height: 200px;
            }
            
            .note-actions {
                flex-direction: column;
            }
            
            .note-user-actions {
                margin-left: 0;
                justify-content: center;
            }
            
            .pagination {
                gap: 0.25rem;
            }
            
            .pagination-link,
            .pagination-current {
                min-width: 35px;
                height: 35px;
                padding: 0.25rem 0.5rem;
            }
        }
        
        @media (max-width: 480px) {
            .page-header {
                padding: 2rem 0 1rem;
            }
            
            .header-content h1 {
                font-size: 1.5rem;
            }
            
            .filters {
                padding: 1rem 0;
            }
            
            .notes-section {
                padding: 2rem 0;
            }
        }
    </style>
    
    <script>
        // Filter by category
        function filterByCategory() {
            const category = document.getElementById('categoryFilter').value;
            const url = new URL(window.location);
            
            if (category) {
                url.searchParams.set('category', category);
                url.searchParams.delete('page'); // Reset to first page
            } else {
                url.searchParams.delete('category');
            }
            
            window.location.href = url.toString();
        }
        
        // Sort notes (client-side for now, could be server-side)
        function sortNotes() {
            const sortBy = document.getElementById('sortFilter').value;
            const notesGrid = document.getElementById('notesGrid');
            const notes = Array.from(notesGrid.children);
            
            notes.sort((a, b) => {
                const aId = parseInt(a.dataset.noteId);
                const bId = parseInt(b.dataset.noteId);
                
                switch (sortBy) {
                    case 'newest':
                        return bId - aId; // Assuming higher ID = newer
                    case 'oldest':
                        return aId - bId;
                    case 'popular': // Sort by downloads + likes + views
                        const aStats = getNoteStats(a);
                        const bStats = getNoteStats(b);
                        return (bStats.downloads + bStats.likes + bStats.views) - 
                               (aStats.downloads + aStats.likes + aStats.views);
                    case 'downloads':
                        return getNoteStats(b).downloads - getNoteStats(a).downloads;
                    case 'likes':
                        return getNoteStats(b).likes - getNoteStats(a).likes;
                    default:
                        return 0;
                }
            });
            
            // Re-append sorted notes
            notes.forEach(note => notesGrid.appendChild(note));
        }
        
        function getNoteStats(noteElement) {
            const stats = noteElement.querySelectorAll('.note-stats span i');
            return {
                downloads: parseInt(stats[0]?.nextSibling?.textContent?.trim() || 0),
                likes: parseInt(stats[1]?.nextSibling?.textContent?.trim() || 0),
                views: parseInt(stats[2]?.nextSibling?.textContent?.trim() || 0)
            };
        }
        
        // Set view mode
        function setView(mode) {
            const notesGrid = document.getElementById('notesGrid');
            const viewBtns = document.querySelectorAll('.view-btn');
            
            // Update button states
            viewBtns.forEach(btn => btn.classList.remove('active'));
            event.target.closest('.view-btn').classList.add('active');
            
            // Update grid layout
            if (mode === 'list') {
                notesGrid.classList.add('list-view');
            } else {
                notesGrid.classList.remove('list-view');
            }
            
            // Save preference
            localStorage.setItem('notesViewMode', mode);
        }
        
        // View note
        function viewNote(noteId) {
            window.location.href = 'note-detail.php?id=' + noteId;
        }
        
        // Download note
        function downloadNote(noteId) {
            // Track download
            fetch('api/download.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ note_id: noteId })
            }).then(() => {
                // Redirect to actual file
                window.open('download.php?id=' + noteId, '_blank');
            });
        }
        
        // Delete note (for user's own notes)
        function deleteNote(noteId) {
            if (confirm('Are you sure you want to delete this note? This action cannot be undone.')) {
                fetch('api/delete-note.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ note_id: noteId })
                }).then(response => response.json())
                  .then(data => {
                      if (data.success) {
                          showNotification('Note deleted successfully', 'success');
                          // Remove the note card
                          const noteCard = document.querySelector(`[data-note-id="${noteId}"]`);
                          if (noteCard) {
                              noteCard.remove();
                          }
                      } else {
                          showNotification('Failed to delete note: ' + data.message, 'error');
                      }
                  });
            }
        }
        
        // Load saved view preference
        document.addEventListener('DOMContentLoaded', function() {
            const savedView = localStorage.getItem('notesViewMode');
            if (savedView === 'list') {
                setView('list');
                document.querySelector('[onclick="setView(\'list\')"]').classList.add('active');
                document.querySelector('[onclick="setView(\'grid\')"]').classList.remove('active');
            }
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + F for search
            if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                e.preventDefault();
                document.getElementById('searchInput')?.focus();
            }
        });
        
        // Animation Classes
        const fadeInStyle = document.createElement('style');
        fadeInStyle.textContent = `
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
        `;
        document.head.appendChild(fadeInStyle);
        
        // Intersection Observer for animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in');
                }
            });
        }, observerOptions);
        
        // Observe sections and cards for animation
        document.addEventListener('DOMContentLoaded', function() {
            // Observe sections
            document.querySelectorAll('section').forEach(section => {
                observer.observe(section);
            });
            
            // Add stagger animation to note cards
            const noteCards = document.querySelectorAll('.note-card');
            noteCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
                card.classList.add('slide-up');
            });
        });
        
        // Auto-load more notes on scroll (infinite scroll)
        let isLoading = false;
        let hasMoreNotes = <?= $page < $pages ? 'true' : 'false' ?>;
        
        window.addEventListener('scroll', function() {
            if (isLoading || !hasMoreNotes) return;
            
            const scrollTop = window.scrollY;
            const windowHeight = window.innerHeight;
            const documentHeight = document.documentElement.scrollHeight;
            
            if (scrollTop + windowHeight >= documentHeight - 1000) {
                loadMoreNotes();
            }
        });
        
        function loadMoreNotes() {
            if (isLoading || !hasMoreNotes) return;
            
            isLoading = true;
            const currentPage = <?= $page ?>;
            const nextPage = currentPage + 1;
            
            // Show loading indicator
            const loadingIndicator = document.createElement('div');
            loadingIndicator.className = 'loading-indicator';
            loadingIndicator.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading more notes...';
            document.getElementById('notesGrid').parentNode.appendChild(loadingIndicator);
            
            // Build URL for next page
            const url = new URL(window.location);
            url.searchParams.set('page', nextPage);
            
            fetch(url.toString())
                .then(response => response.text())
                .then(html => {
                    // Parse the HTML and extract note cards
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newNotes = doc.querySelectorAll('.note-card');
                    
                    if (newNotes.length > 0) {
                        const notesGrid = document.getElementById('notesGrid');
                        const currentCardCount = notesGrid.children.length;
                        newNotes.forEach((note, index) => {
                            note.style.animationDelay = `${(currentCardCount + index) * 0.1}s`;
                            note.classList.add('slide-up');
                            notesGrid.appendChild(note);
                        });
                        
                        // Update URL
                        window.history.replaceState(null, '', url.toString());
                    } else {
                        hasMoreNotes = false;
                    }
                })
                .finally(() => {
                    loadingIndicator.remove();
                    isLoading = false;
                });
        }
    </script>
<?php
// Reset variables to avoid conflicts
unset($notes, $total, $pages, $page, $viewUserNotes, $viewCategory, $searchQuery);
?>