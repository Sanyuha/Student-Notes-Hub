<?php
// About page for Student Notes Hub
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

$pageTitle = 'About Us - Student Notes Hub';
require __DIR__ . '/components/header.php';
?>

<main>
    <!-- Hero Section -->
    <section class="page-hero">
        <div class="container">
            <h1><i class="fas fa-info-circle"></i> About Student Notes Hub</h1>
            <p>Empowering students to share knowledge and excel together</p>
        </div>
    </section>

    <!-- Mission Section -->
    <section class="about-section">
        <div class="container">
            <div class="about-content">
                <div class="about-text">
                    <h2>Our Mission</h2>
                    <p>Student Notes Hub is a platform designed to help students share knowledge, collaborate on academic materials, and support each other's learning journey. We believe that education is a collaborative effort, and by sharing resources, we can all achieve greater success.</p>
                    
                    <h2>What We Offer</h2>
                    <ul class="feature-list">
                        <li><i class="fas fa-check-circle"></i> <strong>Note Sharing:</strong> Upload and share your study notes with the community</li>
                        <li><i class="fas fa-check-circle"></i> <strong>Category Organization:</strong> Browse notes by subject and academic discipline</li>
                        <li><i class="fas fa-check-circle"></i> <strong>Community Features:</strong> Follow other students, like notes, and engage with content</li>
                        <li><i class="fas fa-check-circle"></i> <strong>Messaging:</strong> Connect with other students through private and group chats</li>
                        <li><i class="fas fa-check-circle"></i> <strong>Search & Discovery:</strong> Find exactly what you need with our powerful search</li>
                    </ul>
                    
                    <h2>Our Values</h2>
                    <div class="values-grid">
                        <div class="value-card">
                            <i class="fas fa-users"></i>
                            <h3>Community</h3>
                            <p>Building a supportive learning community where everyone can contribute and benefit</p>
                        </div>
                        <div class="value-card">
                            <i class="fas fa-share-alt"></i>
                            <h3>Sharing</h3>
                            <p>Promoting the sharing of knowledge and resources for collective growth</p>
                        </div>
                        <div class="value-card">
                            <i class="fas fa-graduation-cap"></i>
                            <h3>Excellence</h3>
                            <p>Striving for academic excellence through collaboration and quality content</p>
                        </div>
                        <div class="value-card">
                            <i class="fas fa-heart"></i>
                            <h3>Support</h3>
                            <p>Providing a platform that supports students throughout their academic journey</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <h2 class="section-title">Our Impact</h2>
            <div class="stats-grid">
                <?php
                $totalNotes = $pdo->query('SELECT COUNT(*) FROM notes WHERE status = "published"')->fetchColumn();
                $totalUsers = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
                $totalCategories = $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
                $totalDownloads = $pdo->query('SELECT COUNT(*) FROM downloads')->fetchColumn();
                ?>
                <div class="stat-card">
                    <i class="fas fa-file-alt"></i>
                    <div class="stat-number"><?= number_format($totalNotes) ?></div>
                    <div class="stat-label">Notes Shared</div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-users"></i>
                    <div class="stat-number"><?= number_format($totalUsers) ?></div>
                    <div class="stat-label">Active Users</div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-folder"></i>
                    <div class="stat-number"><?= number_format($totalCategories) ?></div>
                    <div class="stat-label">Categories</div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-download"></i>
                    <div class="stat-number"><?= number_format($totalDownloads) ?></div>
                    <div class="stat-label">Downloads</div>
                </div>
            </div>
        </div>
    </section>
</main>

<style>
    .page-hero {
        background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        color: white;
        padding: 4rem 0;
        text-align: center;
    }
    
    .page-hero h1 {
        font-size: clamp(2rem, 5vw, 3rem);
        margin-bottom: 1rem;
        font-weight: 700;
    }
    
    .page-hero p {
        font-size: 1.25rem;
        opacity: 0.9;
    }
    
    .about-section {
        padding: 4rem 0;
        background: var(--bg-primary);
    }
    
    .about-content {
        max-width: 900px;
        margin: 0 auto;
    }
    
    .about-text h2 {
        font-size: 2rem;
        margin: 2rem 0 1rem;
        color: var(--text-primary);
        font-weight: 700;
    }
    
    .about-text p {
        font-size: 1.125rem;
        line-height: 1.8;
        color: var(--text-secondary);
        margin-bottom: 1.5rem;
    }
    
    .feature-list {
        list-style: none;
        margin: 2rem 0;
    }
    
    .feature-list li {
        padding: 1rem;
        margin-bottom: 0.75rem;
        background: var(--bg-secondary);
        border-radius: var(--border-radius);
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        transition: all var(--transition-normal);
    }
    
    .feature-list li:hover {
        background: var(--bg-light);
        transform: translateX(5px);
    }
    
    .feature-list i {
        color: var(--success-color);
        font-size: 1.25rem;
        margin-top: 0.25rem;
        flex-shrink: 0;
    }
    
    .values-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 2rem;
        margin: 2rem 0;
    }
    
    .value-card {
        text-align: center;
        padding: 2rem;
        background: var(--bg-secondary);
        border-radius: var(--border-radius-lg);
        transition: all var(--transition-normal);
    }
    
    .value-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-lg);
    }
    
    .value-card i {
        font-size: 3rem;
        color: var(--primary-color);
        margin-bottom: 1rem;
    }
    
    .value-card h3 {
        font-size: 1.5rem;
        margin-bottom: 0.75rem;
        color: var(--text-primary);
    }
    
    .value-card p {
        color: var(--text-secondary);
        line-height: 1.6;
    }
    
    .stats-section {
        padding: 4rem 0;
        background: var(--bg-secondary);
    }
    
    .section-title {
        text-align: center;
        font-size: 2.5rem;
        margin-bottom: 3rem;
        color: var(--text-primary);
        font-weight: 700;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 2rem;
    }
    
    .stat-card {
        text-align: center;
        padding: 2rem;
        background: var(--bg-primary);
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-sm);
        transition: all var(--transition-normal);
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-lg);
    }
    
    .stat-card i {
        font-size: 2.5rem;
        color: var(--primary-color);
        margin-bottom: 1rem;
    }
    
    .stat-number {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 0.5rem;
    }
    
    .stat-label {
        color: var(--text-secondary);
        font-weight: 500;
    }
    
    /* Dark Mode */
    [data-theme="dark"] .feature-list li {
        background: var(--bg-secondary);
    }
    
    [data-theme="dark"] .value-card {
        background: var(--bg-secondary);
    }
    
    [data-theme="dark"] .stat-card {
        background: var(--bg-secondary);
    }
    
    @media (max-width: 768px) {
        .values-grid {
            grid-template-columns: 1fr;
        }
        
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>

<?php require __DIR__ . '/components/footer.php'; ?>


