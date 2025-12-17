<?php
// Categories page for browsing notes by category
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

// Set page title
$pageTitle = 'Browse Categories - Student Notes Hub';

/* ----------  Get Categories with Note Counts  ---------- */
$categories = $pdo->query(
    'SELECT c.slug, c.name, c.icon, c.description,
            (SELECT COUNT(*) FROM notes n 
             WHERE n.category_id = c.id AND n.status = "published") as note_count
     FROM categories c
     ORDER BY c.name'
)->fetchAll();

/* ----------  Get Popular Categories (most notes)  ---------- */
$popularCategories = $pdo->query(
    'SELECT c.slug, c.name, c.icon, c.description,
            COUNT(n.id) as note_count
     FROM categories c
     LEFT JOIN notes n ON n.category_id = c.id AND n.status = "published"
     GROUP BY c.id
     HAVING note_count > 0
     ORDER BY note_count DESC
     LIMIT 6'
)->fetchAll();

/* ----------  Get Recent Notes from Each Category  ---------- */
$recentNotesByCategory = [];
foreach ($categories as $category) {
    $recentNotes = $pdo->prepare(
        'SELECT n.id, n.title, n.created_at,
                u.username, u.avatar_url
         FROM notes n
         JOIN users u ON u.id = n.user_id
         WHERE n.category_id = (SELECT id FROM categories WHERE slug = ?) 
               AND n.status = "published"
         ORDER BY n.created_at DESC
         LIMIT 3'
    );
    $recentNotes->execute([$category['slug']]);
    $recentNotesByCategory[$category['slug']] = $recentNotes->fetchAll();
}

// Include header
require __DIR__ . '/components/header.php';
?>

    <!-- Main Content -->
    <main>
        <!-- Page Header -->
        <section class="page-header">
            <div class="container">
                <div class="header-content">
                    <h1><i class="fas fa-tags"></i> Browse Categories</h1>
                    <p>Explore notes organized by subject and academic discipline</p>
                    <div class="header-stats">
                        <span class="stat-item">
                            <i class="fas fa-folder"></i>
                            <?= count($categories) ?> Categories
                        </span>
                        <span class="stat-item">
                            <i class="fas fa-file-alt"></i>
                            <?= array_sum(array_column($categories, 'note_count')) ?> Total Notes
                        </span>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Popular Categories -->
        <?php if (!empty($popularCategories)): ?>
        <section class="popular-categories">
            <div class="container">
                <h2 class="section-title">Popular Categories</h2>
                <p class="section-subtitle">Most active categories with the most notes</p>
                
                <div class="popular-grid">
                    <?php foreach ($popularCategories as $index => $category): ?>
                        <div class="popular-card" style="animation-delay: <?= $index * 0.1 ?>s">
                            <div class="popular-header">
                                <div class="category-icon">
                                    <i class="fas fa-<?= $category['icon'] ?? 'folder' ?>"></i>
                                </div>
                                <div class="popular-info">
                                    <h3><?= htmlspecialchars($category['name']) ?></h3>
                                    <div class="popular-stats">
                                        <span class="note-count"><?= $category['note_count'] ?> notes</span>
                                        <span class="rank">#<?= $index + 1 ?></span>
                                    </div>
                                </div>
                            </div>
                            <p class="popular-description"><?= htmlspecialchars($category['description'] ?? 'Explore notes in this category') ?></p>
                            <a href="notes.php?category=<?= $category['slug'] ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-arrow-right"></i>
                                Browse Notes
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- All Categories -->
        <section class="all-categories">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">All Categories</h2>
                    <p class="section-subtitle">Browse through all available subject categories</p>
                </div>
                
                <!-- Search Filter -->
                <div class="category-search">
                    <div class="search-container">
                        <i class="fas fa-search"></i>
                        <input type="text" id="categorySearch" placeholder="Search categories..." onkeyup="filterCategories()">
                    </div>
                </div>
                
                <div class="categories-grid" id="categoriesGrid">
                    <?php foreach ($categories as $category): ?>
                        <div class="category-card" data-category="<?= htmlspecialchars($category['name']) ?>">
                            <div class="category-header">
                                <div class="category-icon-large">
                                    <i class="fas fa-<?= $category['icon'] ?? 'folder' ?>"></i>
                                </div>
                                <div class="category-info">
                                    <h3><?= htmlspecialchars($category['name']) ?></h3>
                                    <div class="category-stats">
                                        <span class="note-count">
                                            <i class="fas fa-file-alt"></i>
                                            <?= $category['note_count'] ?> notes
                                        </span>
                                        <?php if ($category['note_count'] > 0): ?>
                                            <span class="activity-indicator active" title="Active category"></span>
                                        <?php else: ?>
                                            <span class="activity-indicator inactive" title="No notes yet"></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <p class="category-description"><?= htmlspecialchars($category['description'] ?? 'Explore notes and resources in ' . $category['name']) ?></p>
                            
                            <!-- Recent Notes Preview -->
                            <?php if (!empty($recentNotesByCategory[$category['slug']])): ?>
                                <div class="recent-notes-preview">
                                    <h4>Recent Notes</h4>
                                    <div class="recent-notes-list">
                                        <?php foreach ($recentNotesByCategory[$category['slug']] as $note): ?>
                                            <div class="recent-note-item">
                                                <a href="note-detail.php?id=<?= $note['id'] ?>" class="recent-note-title">
                                                    <?= htmlspecialchars($note['title']) ?>
                                                </a>
                                                <div class="recent-note-meta">
                                                    <span class="recent-note-author">
                                                        <?php if (!empty($note['avatar_url']) && $note['avatar_url'] !== 'https://via.placeholder.com/20' && $note['avatar_url'] !== 'https://via.placeholder.com/150'): ?>
                                                            <img src="<?= htmlspecialchars($note['avatar_url']) ?>" 
                                                                 alt="<?= htmlspecialchars($note['username']) ?>">
                                                        <?php else: ?>
                                                            <div style="width: 16px; height: 16px; border-radius: 50%; background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); display: inline-flex; align-items: center; justify-content: center; color: white; font-size: 0.6rem;">
                                                                <i class="fas fa-user"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?= htmlspecialchars($note['username']) ?>
                                                    </span>
                                                    <span class="recent-note-date">
                                                        <?= date('M j', strtotime($note['created_at'])) ?>
                                                    </span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="category-actions">
                                <a href="notes.php?category=<?= $category['slug'] ?>" 
                                   class="btn btn-primary btn-sm">
                                    <i class="fas fa-book-open"></i>
                                    Browse Notes
                                </a>
                                <?php if ($category['note_count'] > 0): ?>
                                    <a href="notes.php?category=<?= $category['slug'] ?>&sort=popular" 
                                       class="btn btn-outline btn-sm">
                                        <i class="fas fa-fire"></i>
                                        Popular
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- No Results -->
                <div id="noResults" class="no-results hidden">
                    <i class="fas fa-search" style="font-size: 3rem; margin-bottom: 1rem; color: var(--text-muted);"></i>
                    <h3>No categories found</h3>
                    <p>Try adjusting your search terms or browse all categories.</p>
                    <button type="button" class="btn btn-primary" onclick="clearSearch()">
                        <i class="fas fa-times"></i>
                        Clear Search
                    </button>
                </div>
            </div>
        </section>
        
        <!-- Category Stats -->
        <section class="category-stats">
            <div class="container">
                <h2 class="section-title">Category Statistics</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-folder"></i>
                        </div>
                        <div class="stat-number"><?= count($categories) ?></div>
                        <div class="stat-label">Total Categories</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="stat-number"><?= array_sum(array_column($categories, 'note_count')) ?></div>
                        <div class="stat-label">Total Notes</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-fire"></i>
                        </div>
                        <div class="stat-number"><?= count(array_filter($categories, fn($c) => $c['note_count'] > 10)) ?></div>
                        <div class="stat-label">Active Categories</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-crown"></i>
                        </div>
                        <div class="stat-number">
                            <?php 
                            $topCategory = max(array_column($categories, 'note_count'));
                            echo $topCategory;
                            ?>
                        </div>
                        <div class="stat-label">Most in Category</div>
                    </div>
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
            margin-bottom: 1rem;
        }
        
        .header-stats {
            display: flex;
            justify-content: center;
            gap: 2rem;
            flex-wrap: wrap;
        }
        
        .stat-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1rem;
            opacity: 0.9;
        }
        
        /* Popular Categories */
        .popular-categories {
            background: var(--bg-secondary);
            padding: 4rem 0;
        }
        
        .section-title {
            font-size: clamp(1.75rem, 4vw, 2.5rem);
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
        
        .popular-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .popular-card {
            background: var(--bg-primary);
            border-radius: var(--border-radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            transition: all var(--transition-normal);
            animation: slideUp 0.6s ease-out;
        }
        
        .popular-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
            border-color: var(--primary-color);
        }
        
        .popular-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .popular-info h3 {
            font-size: 1.25rem;
            margin-bottom: 0.25rem;
            color: var(--text-primary);
        }
        
        .popular-stats {
            display: flex;
            gap: 1rem;
            font-size: 0.875rem;
        }
        
        .note-count {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .rank {
            background: var(--primary-color);
            color: white;
            padding: 0.125rem 0.5rem;
            border-radius: var(--border-radius);
            font-weight: 600;
            font-size: 0.75rem;
        }
        
        .popular-description {
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }
        
        /* All Categories */
        .all-categories {
            background: var(--bg-primary);
            padding: 4rem 0;
        }
        
        .section-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .category-search {
            margin-bottom: 3rem;
            display: flex;
            justify-content: center;
        }
        
        .search-container {
            position: relative;
            width: 100%;
            max-width: 500px;
            display: flex;
            align-items: center;
        }
        
        .search-container i {
            position: absolute;
            left: 1rem;
            color: var(--text-muted);
            z-index: 1;
        }
        
        .search-container input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius-lg);
            font-size: 1rem;
            background: var(--bg-secondary);
            transition: all var(--transition-normal);
        }
        
        .search-container input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            background: var(--bg-primary);
        }
        
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
        }
        
        .category-card {
            background: var(--bg-primary);
            border-radius: var(--border-radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            transition: all var(--transition-normal);
            display: flex;
            flex-direction: column;
        }
        
        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
            border-color: var(--primary-color);
        }
        
        .category-header {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .category-icon-large {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            border-radius: var(--border-radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            flex-shrink: 0;
        }
        
        .category-info {
            flex: 1;
        }
        
        .category-info h3 {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }
        
        .category-stats {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 0.875rem;
        }
        
        .note-count {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .activity-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }
        
        .activity-indicator.active {
            background: var(--success-color);
        }
        
        .activity-indicator.inactive {
            background: var(--text-muted);
        }
        
        .category-description {
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: 1.5rem;
            flex: 1;
        }
        
        .recent-notes-preview {
            background: var(--bg-secondary);
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .recent-notes-preview h4 {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .recent-notes-preview h4::before {
            content: '📄';
        }
        
        .recent-notes-list {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .recent-note-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem;
            border-radius: var(--border-radius);
            transition: background-color var(--transition-fast);
        }
        
        .recent-note-item:hover {
            background: var(--bg-light);
        }
        
        .recent-note-title {
            font-size: 0.875rem;
            color: var(--text-primary);
            font-weight: 500;
            flex: 1;
            margin-right: 0.5rem;
        }
        
        .recent-note-meta {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.75rem;
            color: var(--text-muted);
            flex-shrink: 0;
        }
        
        .recent-note-author {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .recent-note-author img {
            width: 16px;
            height: 16px;
            border-radius: 50%;
        }
        
        .category-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        /* Category Stats */
        .category-stats {
            background: var(--bg-secondary);
            padding: 4rem 0;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
        }
        
        .stat-card {
            background: var(--bg-primary);
            border-radius: var(--border-radius-lg);
            padding: 2rem;
            text-align: center;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            transition: all var(--transition-normal);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        
        .stat-icon {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: var(--text-secondary);
            font-weight: 500;
        }
        
        /* No Results */
        .no-results {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-secondary);
        }
        
        .no-results.hidden {
            display: none;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .categories-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .popular-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
            
            .category-header {
                flex-direction: column;
                text-align: center;
                align-items: center;
            }
            
            .category-actions {
                justify-content: center;
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .header-stats {
                flex-direction: column;
                gap: 1rem;
            }
            
            .page-header {
                padding: 2rem 0 1rem;
            }
            
            .header-content h1 {
                font-size: 1.5rem;
            }
            
            .section-title {
                font-size: 1.5rem;
            }
        }
        
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
        
        .slideUp {
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
        
        /* Dark Mode Support */
        [data-theme="dark"] .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        }
        
        [data-theme="dark"] .popular-categories {
            background: var(--bg-secondary);
        }
        
        [data-theme="dark"] .all-categories {
            background: var(--bg-primary);
        }
        
        [data-theme="dark"] .category-stats {
            background: var(--bg-secondary);
        }
        
        [data-theme="dark"] .popular-card,
        [data-theme="dark"] .category-card,
        [data-theme="dark"] .stat-card {
            background: var(--bg-secondary);
            border-color: var(--border-color);
        }
        
        [data-theme="dark"] .search-container input {
            background: var(--bg-secondary);
            border-color: var(--border-color);
            color: var(--text-primary);
        }
        
        [data-theme="dark"] .search-container input:focus {
            background: var(--bg-primary);
        }
        
        [data-theme="dark"] .recent-notes-preview {
            background: var(--bg-primary);
        }
    </style>
    
    <script>
        // Filter categories by search
        function filterCategories() {
            const searchTerm = document.getElementById('categorySearch').value.toLowerCase();
            const categoryCards = document.querySelectorAll('.category-card');
            const noResults = document.getElementById('noResults');
            let visibleCount = 0;
            
            categoryCards.forEach(card => {
                const categoryName = card.dataset.category.toLowerCase();
                const categoryDescription = card.querySelector('.category-description').textContent.toLowerCase();
                
                if (categoryName.includes(searchTerm) || categoryDescription.includes(searchTerm)) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Show/hide no results message
            if (visibleCount === 0 && searchTerm.length > 0) {
                noResults.classList.remove('hidden');
            } else {
                noResults.classList.add('hidden');
            }
        }
        
        // Clear search
        function clearSearch() {
            document.getElementById('categorySearch').value = '';
            filterCategories();
        }
        
        // Animate stats counters
        function animateCounters() {
            const counters = document.querySelectorAll('.stat-number');
            
            counters.forEach(counter => {
                const target = parseInt(counter.textContent);
                const increment = target / 100;
                let current = 0;
                
                const updateCounter = () => {
                    if (current < target) {
                        current += increment;
                        counter.textContent = Math.ceil(current);
                        requestAnimationFrame(updateCounter);
                    } else {
                        counter.textContent = target;
                    }
                };
                
                updateCounter();
            });
        }
        
        // Intersection Observer for animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    if (entry.target.classList.contains('category-stats')) {
                        animateCounters();
                    }
                    entry.target.classList.add('fade-in');
                }
            });
        }, observerOptions);
        
        // Observe elements for animation
        document.addEventListener('DOMContentLoaded', function() {
            // Observe sections for fade-in animation
            document.querySelectorAll('section').forEach(section => {
                observer.observe(section);
            });
            
            // Add stagger animation to category cards
            const categoryCards = document.querySelectorAll('.category-card');
            categoryCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
                card.classList.add('slideUp');
            });
            
            // Add stagger animation to popular cards
            const popularCards = document.querySelectorAll('.popular-card');
            popularCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
            });
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + F for search
            if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                e.preventDefault();
                document.getElementById('categorySearch').focus();
            }
            
            // Escape to clear search
            if (e.key === 'Escape') {
                clearSearch();
            }
        });
        
        // Auto-focus search on page load if there's a search parameter
        window.addEventListener('load', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('search')) {
                document.getElementById('categorySearch').value = urlParams.get('search');
                filterCategories();
            }
        });
    </script>
<?php
// Reset variables to avoid conflicts
unset($categories, $popularCategories, $recentNotesByCategory);
?>