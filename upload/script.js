// Student Notes Hub - Main JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Check authentication status
    checkAuthStatus();
    
    // Initialize components
    initNavigation();
    initSearch();
    initUserMenu();
    initNotesGrid();
    initStatsAnimation();
    initCategoryFilters();
    initFileUpload();
    
    // Load initial notes
    loadNotes();
});

// Authentication
function checkAuthStatus() {
    const user = JSON.parse(localStorage.getItem('user'));
    const loginBtn = document.getElementById('loginBtn');
    const userAvatar = document.getElementById('userAvatar');
    
    if (user) {
        if (loginBtn) loginBtn.style.display = 'none';
        if (userAvatar) userAvatar.style.display = 'flex';
        updateUserProfile(user);
    } else {
        if (loginBtn) loginBtn.style.display = 'block';
        if (userAvatar) userAvatar.style.display = 'none';
    }
}

function updateUserProfile(user) {
    const profileName = document.getElementById('profileName');
    const profileBio = document.getElementById('profileBio');
    const profileImage = document.getElementById('profileImage');
    
    if (profileName) profileName.textContent = user.username;
    if (profileBio) profileBio.textContent = user.bio || 'Student at Student Notes Hub';
    if (profileImage) profileImage.src = user.avatar || 'https://via.placeholder.com/150';
}

// Navigation
function initNavigation() {
    const mobileToggle = document.querySelector('.mobile-menu-toggle');
    const navMenu = document.querySelector('.nav-menu');
    
    if (mobileToggle) {
        mobileToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');
        });
    }
    
    // Smooth scrolling for navigation links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

// User Menu
function initUserMenu() {
    const userAvatar = document.getElementById('userAvatar');
    const userDropdown = document.getElementById('userDropdown');
    
    if (userAvatar && userDropdown) {
        userAvatar.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdown.classList.toggle('active');
        });
        
        document.addEventListener('click', function() {
            userDropdown.classList.remove('active');
        });
    }
    
    // Logout
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            localStorage.removeItem('user');
            window.location.href = 'index.html';
        });
    }
}

// Search functionality
function initSearch() {
    const searchInput = document.getElementById('searchInput');
    const searchBtn = document.getElementById('searchBtn');
    
    if (searchInput && searchBtn) {
        searchBtn.addEventListener('click', function() {
            performSearch(searchInput.value);
        });
        
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performSearch(searchInput.value);
            }
        });
    }
}

function performSearch(query) {
    if (query.trim()) {
        window.location.href = `search.php?q=${encodeURIComponent(query)}`;
    }
}

// Notes Grid
function initNotesGrid() {
    const notesGrid = document.getElementById('notesGrid');
    if (notesGrid) {
        loadNotes();
    }
}

function loadNotes() {
    const notesGrid = document.getElementById('notesGrid');
    if (!notesGrid) return;
    
    // Generate sample notes for demonstration
    const notes = generateNotes('all');
    displayNotes(notes);
}

function generateNotes(category) {
    const categories = ['Engineering', 'Software', 'Networking', 'Mathematics', 'Science', 'Business'];
    const notes = [];
    
    for (let i = 1; i <= 12; i++) {
        notes.push({
            id: i,
            title: `Sample Note ${i}`,
            description: 'This is a sample note description with detailed content about various topics.',
            category: categories[Math.floor(Math.random() * categories.length)],
            author: `User ${i}`,
            downloads: Math.floor(Math.random() * 1000),
            likes: Math.floor(Math.random() * 100),
            views: Math.floor(Math.random() * 5000)
        });
    }
    
    return notes;
}

function displayNotes(notes) {
    const notesGrid = document.getElementById('notesGrid');
    if (!notesGrid) return;
    
    const notesHTML = notes.map(note => `
        <div class="note-card" data-category="${note.category}">
            <div class="note-image">
                <i class="fas fa-file-pdf"></i>
            </div>
            <div class="note-content">
                <div class="note-category">${note.category}</div>
                <h3 class="note-title">${note.title}</h3>
                <p class="note-description">${note.description}</p>
                <div class="note-meta">
                    <div class="note-author">
                        <div class="note-author-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <span>${note.author}</span>
                    </div>
                    <div class="note-stats">
                        <span><i class="fas fa-download"></i> ${note.downloads}</span>
                        <span><i class="fas fa-heart"></i> ${note.likes}</span>
                    </div>
                </div>
            </div>
        </div>
    `).join('');
    
    notesGrid.innerHTML = notesHTML;
}

function loadMoreNotes() {
    // Simulate loading more notes
    const loadMoreBtn = document.getElementById('loadMoreBtn');
    loadMoreBtn.textContent = 'Loading...';
    
    setTimeout(() => {
        const moreNotes = generateNotes('all');
        const notesGrid = document.getElementById('notesGrid');
        const currentNotes = notesGrid.innerHTML;
        const newNotesHTML = moreNotes.map(note => `
            <div class="note-card" data-category="${note.category}">
                <div class="note-image">
                    <i class="fas fa-file-pdf"></i>
                </div>
                <div class="note-content">
                    <div class="note-category">${note.category}</div>
                    <h3 class="note-title">${note.title}</h3>
                    <p class="note-description">${note.description}</p>
                    <div class="note-meta">
                        <div class="note-author">
                            <div class="note-author-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <span>${note.author}</span>
                        </div>
                        <div class="note-stats">
                            <span><i class="fas fa-download"></i> ${note.downloads}</span>
                            <span><i class="fas fa-heart"></i> ${note.likes}</span>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
        
        notesGrid.innerHTML = currentNotes + newNotesHTML;
        loadMoreBtn.textContent = 'Load More Notes';
    }, 1000);
}

// Stats Animation
function initStatsAnimation() {
    const stats = document.querySelectorAll('.stat-number');
    if (stats.length > 0) {
        animateStats(stats);
    }
}

function animateStats(stats) {
    stats.forEach(stat => {
        const target = parseInt(stat.textContent);
        let current = 0;
        const increment = target / 50;
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                stat.textContent = target;
                clearInterval(timer);
            } else {
                stat.textContent = Math.floor(current);
            }
        }, 50);
    });
}

// Category Filters
function initCategoryFilters() {
    const categoryBtns = document.querySelectorAll('.category-btn');
    
    categoryBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const category = this.dataset.category;
            
            // Update active state
            categoryBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            // Filter notes
            filterNotesByCategory(category);
        });
    });
}

function filterNotesByCategory(category) {
    const noteCards = document.querySelectorAll('.note-card');
    
    noteCards.forEach(card => {
        if (category === 'all' || card.dataset.category === category) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

// File Upload
function initFileUpload() {
    const fileInput = document.getElementById('file');
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            handleFileSelect(this);
        });
    }
}

function handleFileSelect(input) {
    const file = input.files[0];
    const display = input.parentElement.querySelector('.file-upload-display');
    
    if (file) {
        display.innerHTML = `
            <i class="fas fa-file"></i>
            <span class="upload-text">${file.name}</span>
            <small class="upload-hint">${formatFileSize(file.size)}</small>
        `;
        display.classList.add('file-selected');
    } else {
        display.innerHTML = `
            <i class="fas fa-cloud-upload-alt"></i>
            <span class="upload-text">Choose a file to upload</span>
            <small class="upload-hint">PDF, DOC, DOCX, or ZIP (Max 50MB)</small>
        `;
        display.classList.remove('file-selected');
    }
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Download Note Function
function downloadNote(noteId) {
    if (!noteId) {
        // Try to get note ID from URL
        const urlParams = new URLSearchParams(window.location.search);
        noteId = urlParams.get('id');
    }
    
    if (!noteId) {
        showNotification('Note ID not found', 'error');
        return;
    }
    
    // Create download link
    const downloadUrl = `download.php?id=${noteId}`;
    
    // Create temporary link and trigger download
    const link = document.createElement('a');
    link.href = downloadUrl;
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showNotification('Download started!', 'success');
}

// Like Note Function
function toggleLike() {
    const likeBtn = document.querySelector('.like-btn');
    const isLiked = likeBtn.dataset.liked === '1';
    
    // Toggle like state
    if (isLiked) {
        likeBtn.dataset.liked = '0';
        likeBtn.querySelector('span').textContent = 'Like';
        likeBtn.classList.remove('liked');
    } else {
        likeBtn.dataset.liked = '1';
        likeBtn.querySelector('span').textContent = 'Liked';
        likeBtn.classList.add('liked');
    }
    
    // Update like count
    const likesCount = document.getElementById('likesCount');
    if (likesCount) {
        const currentCount = parseInt(likesCount.textContent);
        likesCount.textContent = isLiked ? currentCount - 1 : currentCount + 1;
    }
}

// Share Note Function
function shareNote() {
    const url = window.location.href;
    
    if (navigator.share) {
        navigator.share({
            title: document.title,
            url: url
        }).catch(console.error);
    } else {
        // Fallback: copy to clipboard
        navigator.clipboard.writeText(url).then(() => {
            showNotification('Link copied to clipboard!', 'success');
        }).catch(() => {
            showNotification('Failed to copy link', 'error');
        });
    }
}

// Report Note Function
function reportNote() {
    showNotification('Report feature coming soon!', 'info');
}

// Utility Functions
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
        <div class="notification-content">
            <i class="fas ${getNotificationIcon(type)}"></i>
            <span>${message}</span>
        </div>
    `;
    
    // Add to container
    container.appendChild(notification);
    
    // Animate in
    setTimeout(() => notification.classList.add('show'), 100);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.classList.remove('show');
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
    }, 3000);
}

function getNotificationIcon(type) {
    const icons = {
        'success': 'fa-check-circle',
        'error': 'fa-exclamation-circle',
        'warning': 'fa-exclamation-triangle',
        'info': 'fa-info-circle'
    };
    return icons[type] || icons.info;
}