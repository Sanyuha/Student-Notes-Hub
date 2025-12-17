<?php
// Search page for Student Notes Hub - TITLE-ONLY SEARCH
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

// Set page title
$pageTitle = 'Search Notes - Student Notes Hub';

/* ----------  Search Parameters  ---------- */
$searchQuery = trim($_GET['q'] ?? '');
$category = $_GET['category'] ?? '';
$author = $_GET['author'] ?? '';
$sort = $_GET['sort'] ?? 'relevance';
$fileType = $_GET['file_type'] ?? '';

/* ----------  Pagination Setup  ---------- */
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

/* ----------  Build Search Query  ---------- */
$whereConditions = ['n.status = "published"'];
$searchParams = [];

// Title-only search query (case-insensitive)
if ($searchQuery !== '') {
    $whereConditions[] = 'LOWER(n.title) LIKE LOWER(:search)';
    $searchParams[':search'] = '%' . $searchQuery . '%';
}

// Category filter
if ($category !== '') {
    $whereConditions[] = 'c.slug = :category';
    $searchParams[':category'] = $category;
}

// Author filter
if ($author !== '') {
    $whereConditions[] = 'u.username LIKE :author';
    $searchParams[':author'] = '%' . $author . '%';
}

// File type filter
if ($fileType !== '') {
    $whereConditions[] = 'n.file_type = :file_type';
    $searchParams[':file_type'] = $fileType;
}

$whereClause = implode(' AND ', $whereConditions);

/* ----------  Get Total Count  ---------- */
$countQuery = "SELECT COUNT(DISTINCT n.id) as total FROM notes n 
               JOIN categories c ON c.id = n.category_id 
               JOIN users u ON u.id = n.user_id 
               WHERE $whereClause";

try {
    $countStmt = $pdo->prepare($countQuery);
    
    // Bind search parameters to count query
    foreach ($searchParams as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    
    $countStmt->execute();
    $total = (int)$countStmt->fetchColumn();
    $pages = ceil($total / $limit);
    
} catch (PDOException $e) {
    $total = 0;
    $pages = 0;
}

/* ----------  Get Search Results  ---------- */
$orderBy = match ($sort) {
    'newest' => 'n.created_at DESC',
    'oldest' => 'n.created_at ASC',
    'popular' => '(n.downloads + n.likes * 2 + n.views * 0.5) DESC',
    'downloads' => 'n.downloads DESC',
    'likes' => 'n.likes DESC',
    'views' => 'n.views DESC',
    default => '(n.downloads + n.likes * 2 + n.views * 0.5) DESC, n.created_at DESC'
};

// Relevance ordering for title search (case-insensitive)
$relevanceOrder = '';
if ($searchQuery !== '') {
    $relevanceOrder = "(
        CASE 
            WHEN LOWER(n.title) = LOWER(:relevance_exact) THEN 100
            WHEN LOWER(n.title) LIKE LOWER(:relevance_start) THEN 50
            WHEN LOWER(n.title) LIKE LOWER(:relevance_contains) THEN 25
            ELSE 0
        END
    ) DESC, ";
    $searchParams[':relevance_exact'] = $searchQuery;
    $searchParams[':relevance_start'] = $searchQuery . '%';
    $searchParams[':relevance_contains'] = '%' . $searchQuery . '%';
}

$searchSQL = "SELECT DISTINCT n.id, n.title, n.description, n.file_url, n.file_type, n.file_size, 
                     n.downloads, n.likes, n.views, n.created_at,
                     c.slug AS category_slug, c.name AS cat_name, c.icon,
                     u.username, u.avatar_url
              FROM notes n
              JOIN categories c ON c.id = n.category_id 
              JOIN users u ON u.id = n.user_id
              WHERE $whereClause
              ORDER BY $relevanceOrder $orderBy
              LIMIT :limit OFFSET :offset";

try {
    $stmt = $pdo->prepare($searchSQL);
    
    // Bind search parameters
    foreach ($searchParams as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    // Bind pagination parameters with explicit types
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $notes = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $notes = [];
}

/* ----------  Get Categories for Filter  ---------- */
$categories = $pdo->query('SELECT slug, name, icon FROM categories ORDER BY name')->fetchAll();

/* ----------  Get Recent Searches (from session)  ---------- */
$recentSearches = $_SESSION['recent_searches'] ?? [];

// Add current search to recent searches if it's not empty
if ($searchQuery !== '' && !in_array($searchQuery, $recentSearches)) {
    array_unshift($recentSearches, $searchQuery);
    $recentSearches = array_slice($recentSearches, 0, 5);
    $_SESSION['recent_searches'] = $recentSearches;
}

// Include header
require __DIR__ . '/components/header.php';
?>

<main>
    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <div class="header-content">
                <h1><i class="fas fa-search"></i> Search Notes</h1>
                <p>Find the perfect study materials from our community</p>
                
                <!-- Quick Search Bar -->
                <div class="quick-search">
                    <form method="GET" action="search.php" class="search-form">
                        <div class="search-container">
                            <i class="fas fa-search"></i>
                            <input type="text" 
                                   name="q" 
                                   placeholder="Search notes by title..." 
                                   value="<?= htmlspecialchars($searchQuery) ?>"
                                   class="search-input"
                                   autofocus>
                        </div>
                    </form>
                </div>
                
                <!-- Search Stats -->
                <?php if ($searchQuery !== ''): ?>
                    <div class="search-stats">
                        <span class="stat-item">
                            <i class="fas fa-file-alt"></i>
                            <?= $total ?> results found
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    
    <!-- Search Interface -->
    <section class="search-interface">
        <div class="container">
            <div class="search-layout">
                <!-- Filters Sidebar -->
                <aside class="filters-sidebar">
                    <div class="filters-card">
                        <h3 class="filters-title">
                            <i class="fas fa-filter"></i>
                            Filters
                        </h3>
                        
                        <form method="GET" action="search.php" class="filters-form">
                            <!-- Hidden search query -->
                            <input type="hidden" name="q" value="<?= htmlspecialchars($searchQuery) ?>">
                            
                            <!-- Category Filter -->
                            <div class="filter-group">
                                <label for="category" class="filter-label">Category</label>
                                <select name="category" id="category" class="filter-select">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['slug'] ?>" 
                                                <?= $category === $cat['slug'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Author Filter -->
                            <div class="filter-group">
                                <label for="author" class="filter-label">Author</label>
                                <input type="text" 
                                       name="author" 
                                       id="author" 
                                       placeholder="Search by author..." 
                                       value="<?= htmlspecialchars($author) ?>"
                                       class="filter-input">
                            </div>
                            
                            <!-- File Type Filter -->
                            <div class="filter-group">
                                <label for="file_type" class="filter-label">File Type</label>
                                <select name="file_type" id="file_type" class="filter-select">
                                    <option value="">All Types</option>
                                    <option value="pdf" <?= $fileType === 'pdf' ? 'selected' : '' ?>>PDF</option>
                                    <option value="doc" <?= $fileType === 'doc' ? 'selected' : '' ?>>DOC</option>
                                    <option value="docx" <?= $fileType === 'docx' ? 'selected' : '' ?>>DOCX</option>
                                    <option value="zip" <?= $fileType === 'zip' ? 'selected' : '' ?>>ZIP</option>
                                </select>
                            </div>
                            
                            <!-- Sort By -->
                            <div class="filter-group">
                                <label for="sort" class="filter-label">Sort By</label>
                                <select name="sort" id="sort" class="filter-select">
                                    <option value="relevance" <?= $sort === 'relevance' ? 'selected' : '' ?>>Relevance</option>
                                    <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest First</option>
                                    <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                                    <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>Most Popular</option>
                                    <option value="downloads" <?= $sort === 'downloads' ? 'selected' : '' ?>>Most Downloaded</option>
                                    <option value="likes" <?= $sort === 'likes' ? 'selected' : '' ?>>Most Liked</option>
                                    <option value="views" <?= $sort === 'views' ? 'selected' : '' ?>>Most Viewed</option>
                                </select>
                            </div>
                            
                            <!-- Filter Actions -->
                            <div class="filter-actions">
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-filter"></i>
                                    Apply Filters
                                </button>
                                <a href="search.php?q=<?= htmlspecialchars($searchQuery) ?>" 
                                   class="btn btn-outline btn-block">
                                    <i class="fas fa-times"></i>
                                    Clear Filters
                                </a>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Recent Searches -->
                    <?php if (!empty($recentSearches)): ?>
                        <div class="recent-searches-card">
                            <h4 class="recent-searches-title">
                                <i class="fas fa-history"></i>
                                Recent Searches
                            </h4>
                            <div class="recent-searches-list">
                                <?php foreach ($recentSearches as $search): ?>
                                    <a href="search.php?q=<?= urlencode($search) ?>" 
                                       class="recent-search-item">
                                        <i class="fas fa-search"></i>
                                        <?= htmlspecialchars($search) ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </aside>
                
                <!-- Search Results -->
                <div class="search-results">
                    <?php if ($searchQuery === '' && empty($category) && empty($author)): ?>
                        <!-- Welcome State -->
                        <div class="welcome-state">
                            <i class="fas fa-search" style="font-size: 4rem; margin-bottom: 1rem; color: var(--text-muted);"></i>
                            <h3>Start Your Search</h3>
                            <p>Enter a search term above or use the filters to find the perfect study materials.</p>
                            
                            <?php if (!empty($recentSearches)): ?>
                                <div class="recent-searches-inline">
                                    <h4>Or continue with:</h4>
                                    <div class="recent-searches-chips">
                                        <?php foreach ($recentSearches as $search): ?>
                                            <a href="search.php?q=<?= urlencode($search) ?>" 
                                               class="search-chip">
                                                <?= htmlspecialchars($search) ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php elseif (empty($notes)): ?>
                        <!-- No Results -->
                        <div class="no-results">
                            <i class="fas fa-search" style="font-size: 4rem; margin-bottom: 1rem; color: var(--text-muted);"></i>
                            <h3>No notes found</h3>
                            <p>Try adjusting your search terms or filters to find what you're looking for.</p>
                            
                            <div class="no-results-actions">
                                <a href="search.php" class="btn btn-primary">
                                    <i class="fas fa-times"></i>
                                    Clear All Filters
                                </a>
                                <a href="notes.php" class="btn btn-outline">
                                    <i class="fas fa-book"></i>
                                    Browse All Notes
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Results Header -->
                        <div class="results-header">
                            <h2 class="results-title">
                                <?= $total ?> Results
                                <?php if ($searchQuery !== ''): ?>
                                    for "<?= htmlspecialchars($searchQuery) ?>"
                                <?php endif; ?>
                            </h2>
                        </div>
                        
                        <!-- Results Grid -->
                        <div class="notes-grid" id="searchResults">
                            <?php foreach ($notes as $note): ?>
                                <div class="note-card" data-note-id="<?= $note['id'] ?>" onclick="window.location.href='note-detail.php?id=<?= $note['id'] ?>'">
                                    <div class="note-image">
                                        <i class="fas fa-<?= $note['icon'] ?? 'file-alt' ?>"></i>
                                    </div>
                                    <div class="note-content">
                                        <div class="note-category">
                                            <i class="fas fa-<?= $note['icon'] ?? 'file-alt' ?>"></i>
                                            <?= htmlspecialchars($note['cat_name']) ?>
                                        </div>
                                        <h3 class="note-title">
                                            <?= htmlspecialchars($note['title']) ?>
                                        </h3>
                                        <p class="note-description">
                                            <?= htmlspecialchars($note['description'] ?? '') ?>
                                        </p>
                                        
                                        <div class="note-meta">
                                            <span class="note-author">
                                                <img src="<?= htmlspecialchars($note['avatar_url'] ?? 'https://via.placeholder.com/24') ?>" 
                                                     alt="<?= htmlspecialchars($note['username']) ?>" 
                                                     style="width: 24px; height: 24px; border-radius: 50%; margin-right: 0.5rem;">
                                                <?= htmlspecialchars($note['username']) ?>
                                            </span>
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
                                        
                                        <div class="note-date">
                                            <i class="fas fa-clock"></i>
                                            <?= date('M j, Y', strtotime($note['created_at'])) ?>
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
        margin-bottom: 2rem;
    }
    
    /* Quick Search */
    .quick-search {
        max-width: 600px;
        margin: 0 auto;
    }
    
    .search-container {
        position: relative;
        display: flex;
        align-items: center;
        background: white;
        border-radius: 50px;
        box-shadow: var(--shadow-lg);
        overflow: hidden;
    }
    
    .search-container:focus-within {
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }
    
    .search-container i {
        position: absolute;
        left: 1.5rem;
        color: var(--text-muted);
        z-index: 1;
    }
    
    .search-input {
        flex: 1;
        padding: 1rem 1.5rem 1rem 3rem;
        border: none;
        font-size: 1rem;
        background: transparent;
    }
    
    .search-input:focus {
        outline: none;
    }
    
    .search-stats {
        display: flex;
        justify-content: center;
        gap: 2rem;
        flex-wrap: wrap;
        margin-top: 1rem;
    }
    
    .stat-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.875rem;
        opacity: 0.9;
    }
    
    /* Search Interface */
    .search-interface {
        background: var(--bg-secondary);
        padding: 3rem 0;
        min-height: 600px;
    }
    
    .search-layout {
        display: grid;
        grid-template-columns: 300px 1fr;
        gap: 2rem;
        align-items: start;
    }
    
    /* Filters Sidebar */
    .filters-sidebar {
        position: sticky;
        top: 2rem;
    }
    
    .filters-card {
        background: var(--bg-primary);
        border-radius: var(--border-radius-lg);
        padding: 1.5rem;
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--border-color);
        margin-bottom: 1.5rem;
    }
    
    .filters-title {
        font-size: 1.125rem;
        margin-bottom: 1.5rem;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .filter-group {
        margin-bottom: 1.5rem;
    }
    
    .filter-label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: var(--text-primary);
        font-size: 0.875rem;
    }
    
    .filter-input,
    .filter-select {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid var(--border-color);
        border-radius: var(--border-radius);
        font-size: 0.875rem;
        background: var(--bg-primary);
        transition: all var(--transition-fast);
    }
    
    .filter-input:focus,
    .filter-select:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }
    
    .filter-actions {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .btn-block {
        width: 100%;
        justify-content: center;
    }
    
    /* Recent Searches */
    .recent-searches-card {
        background: var(--bg-primary);
        border-radius: var(--border-radius-lg);
        padding: 1.5rem;
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--border-color);
        margin-bottom: 1.5rem;
    }
    
    .recent-searches-title {
        font-size: 1rem;
        margin-bottom: 1rem;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .recent-searches-list {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .recent-search-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem;
        color: var(--text-secondary);
        text-decoration: none;
        border-radius: var(--border-radius);
        transition: all var(--transition-fast);
        font-size: 0.875rem;
    }
    
    .recent-search-item:hover {
        background: var(--bg-secondary);
        color: var(--primary-color);
    }
    
    /* Search Results */
    .search-results {
        background: var(--bg-primary);
        border-radius: var(--border-radius-lg);
        padding: 2rem;
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--border-color);
    }
    
    .results-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .results-title {
        font-size: 1.5rem;
        color: var(--text-primary);
        margin: 0;
    }
    
    /* Welcome State */
    .welcome-state {
        text-align: center;
        padding: 4rem 2rem;
        color: var(--text-secondary);
    }
    
    .welcome-state h3 {
        font-size: 1.5rem;
        margin-bottom: 1rem;
        color: var(--text-primary);
    }
    
    .welcome-state p {
        margin-bottom: 2rem;
        font-size: 1.125rem;
    }
    
    .recent-searches-inline {
        margin-top: 2rem;
    }
    
    .recent-searches-inline h4 {
        font-size: 1rem;
        margin-bottom: 1rem;
        color: var(--text-primary);
    }
    
    .recent-searches-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        justify-content: center;
    }
    
    .search-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: var(--bg-secondary);
        color: var(--text-secondary);
        text-decoration: none;
        border-radius: 25px;
        font-size: 0.875rem;
        transition: all var(--transition-fast);
    }
    
    .search-chip:hover {
        background: var(--primary-color);
        color: white;
    }
    
    /* No Results */
    .no-results {
        text-align: center;
        padding: 4rem 2rem;
        color: var(--text-secondary);
    }
    
    .no-results h3 {
        font-size: 1.5rem;
        margin-bottom: 1rem;
        color: var(--text-primary);
    }
    
    .no-results p {
        margin-bottom: 2rem;
        font-size: 1.125rem;
    }
    
    .no-results-actions {
        display: flex;
        gap: 1rem;
        justify-content: center;
        flex-wrap: wrap;
    }
    
    /* Notes Grid */
    .notes-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 2rem;
        margin-bottom: 3rem;
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
        border-radius: 20px;
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
    
    .note-date {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.75rem;
        color: var(--text-muted);
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
        border-radius: 8px;
        font-weight: 500;
        transition: all var(--transition-fast);
        min-width: 40px;
        height: 40px;
    }
    
    .pagination-link {
        color: var(--text-secondary);
        background: var(--bg-primary);
        text-decoration: none;
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
    
    /* Responsive Design */
    @media (max-width: 768px) {
        .search-layout {
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }
        
        .filters-sidebar {
            position: static;
        }
        
        .results-header {
            flex-direction: column;
            align-items: stretch;
        }
        
        .search-container {
            flex-direction: column;
            border-radius: var(--border-radius-lg);
        }
        
        .search-btn {
            width: 100%;
            border-radius: 0 0 var(--border-radius-lg) var(--border-radius-lg);
        }
        
        .notes-grid {
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }
    }
    
    @media (max-width: 480px) {
        .page-header {
            padding: 2rem 0 1rem;
        }
        
        .header-content h1 {
            font-size: 1.5rem;
        }
        
        .search-interface {
            padding: 2rem 0;
        }
        
        .search-results {
            padding: 1.5rem;
        }
        
        .results-title {
            font-size: 1.25rem;
        }
        
        .filters-card {
            padding: 1.5rem;
        }
    }
</style>

<script>
    // Auto-submit filters when changed
    document.addEventListener('DOMContentLoaded', function() {
        const filterElements = document.querySelectorAll('.filter-select, .filter-input');
        filterElements.forEach(element => {
            element.addEventListener('change', function() {
                if (this.value !== '' || this.tagName === 'SELECT') {
                    this.closest('form').submit();
                }
            });
        });
        
        // Auto-focus search input
        const searchInput = document.querySelector('.search-input');
        if (searchInput && searchInput.value === '') {
            searchInput.focus();
        }
    });
</script>

<?php
// Reset variables to avoid conflicts
unset($notes, $total, $pages, $page, $searchQuery, $category, $author, $sort, $fileType);
?>