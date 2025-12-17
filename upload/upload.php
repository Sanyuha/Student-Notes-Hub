<?php
// Upload page for Student Notes Hub
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

// Ensure user is logged in
ensureLoggedIn();

// Set page title
$pageTitle = 'Upload Notes - Student Notes Hub';

/* ----------  handle upload  ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_note'])) {

    if (!csrf_ok($_POST['csrf'] ?? '')) {
        http_response_code(403);
        exit('Bad CSRF token');
    }

    $title       = trim($_POST['title']       ?? '');
    $description = trim($_POST['description'] ?? '');
    $categoryId  = (int)($_POST['category']  ?? 0);
    $status      = in_array($_POST['status'], ['published','draft'], true)
                   ? $_POST['status'] : 'draft';

    if ($title === '' || $categoryId === 0 || !isset($_FILES['file'])) {
        $error = 'Please fill in all required fields and select a file';
    } else {
        $file     = $_FILES['file'];
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed  = ['pdf','doc','docx','zip'];
        
        if (!in_array($ext, $allowed, true)) {
            $error = 'Only PDF, DOC, DOCX, and ZIP files are allowed';
        } elseif ($file['error'] !== 0) {
            $error = 'File upload failed. Please try again.';
        } else {
            // Create upload directory if it doesn't exist (go up one level from upload/ to root)
            $uploadDir = dirname(__DIR__) . '/uploads';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            // Generate unique filename
            $filename = bin2hex(random_bytes(8)) . '.' . $ext;
            $filepath = $uploadDir . '/' . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                $uid = (int)$_SESSION['auth_user_id'];
                
                $stmt = $pdo->prepare(
                    'INSERT INTO notes (user_id,category_id,title,description,file_url,file_size,file_type,status)
                     VALUES (?,?,?,?,?,?,?,?)'
                );
                $stmt->execute([
                    $uid, $categoryId, $title, $description,
                    'uploads/'.$filename, filesize($filepath), $ext, $status
                ]);
                $newNoteId = (int)$pdo->lastInsertId();
                
                redirect('my-notes.php?upload=success');
            } else {
                $error = 'Failed to save uploaded file';
            }
        }
    }
}

/* ----------  categories for form  ---------- */
$cats = $pdo->query('SELECT id, name, icon FROM categories ORDER BY name')->fetchAll();

$_SESSION['csrf'] = csrf();

// Include header
require __DIR__ . '/components/header.php';
?>

    <!-- Main Content -->
    <main>
        <!-- Page Header -->
        <section class="page-header">
            <div class="container">
                <div class="header-content">
                    <h1><i class="fas fa-upload"></i> Upload Notes</h1>
                    <p>Share your knowledge with the student community</p>
                </div>
            </div>
        </section>
        
        <!-- Upload Form -->
        <section class="upload-section">
            <div class="container">
                <div class="upload-container">
                    <div class="upload-card">
                        <div class="upload-header">
                            <h2>Note Details</h2>
                            <p>Fill in the information about your notes</p>
                        </div>
                        
                        <?php if (isset($error)): ?>
                            <div class="alert alert-error">
                                <i class="fas fa-exclamation-circle"></i>
                                <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="post" enctype="multipart/form-data" class="upload-form">
                            <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
                            <input type="hidden" name="upload_note" value="1">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="title">
                                        <i class="fas fa-heading"></i>
                                        Note Title *
                                    </label>
                                    <input type="text" id="title" name="title" required 
                                           placeholder="Give your notes a descriptive title"
                                           value="<?= isset($title) ? htmlspecialchars($title) : '' ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="category">
                                        <i class="fas fa-tags"></i>
                                        Category *
                                    </label>
                                    <select id="category" name="category" required>
                                        <option value="">Select a category</option>
                                        <?php foreach ($cats as $cat): ?>
                                            <option value="<?= $cat['id'] ?>" 
                                                    <?= isset($categoryId) && $categoryId == $cat['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cat['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="description">
                                    <i class="fas fa-align-left"></i>
                                    Description
                                </label>
                                <textarea id="description" name="description" rows="4" 
                                          placeholder="Describe what your notes cover, topics included, etc."><?= isset($description) ? htmlspecialchars($description) : '' ?></textarea>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="file">
                                        <i class="fas fa-file-upload"></i>
                                        Upload File *
                                    </label>
                                    <div class="file-upload">
                                        <input type="file" id="file" name="file" required 
                                               accept=".pdf,.doc,.docx,.zip"
                                               onchange="handleFileSelect(this)">
                                        <div class="file-upload-display">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                            <span class="upload-text">Choose a file to upload</span>
                                            <small class="upload-hint">PDF, DOC, DOCX, or ZIP (Max 50MB)</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="status">
                                        <i class="fas fa-eye"></i>
                                        Visibility
                                    </label>
                                    <select id="status" name="status">
                                        <option value="draft">Draft (Only you can see)</option>
                                        <option value="published" selected>Published (Everyone can see)</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="button" class="btn btn-secondary" onclick="window.history.back()">
                                    <i class="fas fa-arrow-left"></i>
                                    Cancel
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-upload"></i>
                                    Upload Notes
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <div class="upload-guidelines">
                        <h3>Upload Guidelines</h3>
                        <div class="guidelines-content">
                            <div class="guideline-section">
                                <h4><i class="fas fa-file-alt"></i> File Requirements</h4>
                                <ul>
                                    <li>Supported formats: PDF, DOC, DOCX, ZIP</li>
                                    <li>Maximum file size: 50MB</li>
                                    <li>Ensure your notes are clear and readable</li>
                                    <li>Scanned documents should be high quality</li>
                                </ul>
                            </div>
                            
                            <div class="guideline-section">
                                <h4><i class="fas fa-tag"></i> Title & Description</h4>
                                <ul>
                                    <li>Use descriptive titles that clearly state the subject</li>
                                    <li>Include topics covered in the description</li>
                                    <li>Mention the academic level (e.g., "Undergraduate", "Graduate")</li>
                                    <li>Add any prerequisites or required background</li>
                                </ul>
                            </div>
                            
                            <div class="guideline-section">
                                <h4><i class="fas fa-shield-alt"></i> Content Policy</h4>
                                <ul>
                                    <li>Only upload notes you own or have permission to share</li>
                                    <li>Do not upload copyrighted material without permission</li>
                                    <li>Ensure content is appropriate for all ages</li>
                                    <li>No spam or promotional content</li>
                                </ul>
                            </div>
                            
                            <div class="guideline-section">
                                <h4><i class="fas fa-star"></i> Quality Tips</h4>
                                <ul>
                                    <li>Organize content with clear headings</li>
                                    <li>Include examples and practice problems</li>
                                    <li>Add visual aids like diagrams when helpful</li>
                                    <li>Proofread for spelling and grammar errors</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="upload-stats">
                            <h4>Community Stats</h4>
                            <div class="stats-grid">
                                <div class="stat">
                                    <span class="stat-number">1000+</span>
                                    <span class="stat-label">Notes Shared</span>
                                </div>
                                <div class="stat">
                                    <span class="stat-number">500+</span>
                                    <span class="stat-label">Active Contributors</span>
                                </div>
                                <div class="stat">
                                    <span class="stat-number">50+</span>
                                    <span class="stat-label">Categories</span>
                                </div>
                            </div>
                        </div>
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
        }
        
        /* Upload Section */
        .upload-section {
            padding: 3rem 0;
            background: var(--bg-secondary);
        }
        
        .upload-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 3rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .upload-card {
            background: var(--bg-primary);
            border-radius: var(--border-radius-lg);
            padding: 2.5rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
        }
        
        .upload-header {
            margin-bottom: 2rem;
        }
        
        .upload-header h2 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }
        
        .upload-header p {
            color: var(--text-secondary);
        }
        
        /* Alert Styles */
        .alert {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            border: 1px solid;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: var(--error-color);
            border-color: rgba(239, 68, 68, 0.2);
        }
        
        /* Form Styles */
        .upload-form {
            margin-bottom: 2rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            font-size: 1rem;
            background: var(--bg-primary);
            transition: all var(--transition-fast);
            font-family: inherit;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        /* File Upload */
        .file-upload {
            position: relative;
        }
        
        .file-upload input[type="file"] {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        .file-upload-display {
            border: 2px dashed var(--border-color);
            border-radius: var(--border-radius-lg);
            padding: 2rem;
            text-align: center;
            transition: all var(--transition-normal);
            background: var(--bg-secondary);
        }
        
        .file-upload:hover .file-upload-display {
            border-color: var(--primary-color);
            background: rgba(99, 102, 241, 0.05);
        }
        
        .file-upload-display.dragover {
            border-color: var(--primary-color);
            background: rgba(99, 102, 241, 0.1);
        }
        
        .file-upload-display i {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .upload-text {
            display: block;
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        
        .upload-hint {
            color: var(--text-muted);
            font-size: 0.875rem;
        }
        
        .file-info {
            display: none;
            margin-top: 1rem;
            padding: 1rem;
            background: var(--bg-light);
            border-radius: var(--border-radius);
            border: 1px solid var(--border-color);
        }
        
        .file-info.show {
            display: block;
        }
        
        .file-details {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 0.5rem;
        }
        
        .file-icon {
            font-size: 1.5rem;
            color: var(--primary-color);
        }
        
        .file-name {
            font-weight: 500;
            color: var(--text-primary);
        }
        
        .file-size {
            color: var(--text-muted);
            font-size: 0.875rem;
        }
        
        .remove-file {
            background: none;
            border: none;
            color: var(--error-color);
            cursor: pointer;
            padding: 0.25rem;
            margin-left: auto;
        }
        
        .remove-file:hover {
            background: rgba(239, 68, 68, 0.1);
            border-radius: var(--border-radius);
        }
        
        .progress-bar {
            width: 100%;
            height: 6px;
            background: var(--border-color);
            border-radius: 3px;
            overflow: hidden;
            margin-top: 0.5rem;
            display: none;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-light));
            width: 0%;
            transition: width 0.3s ease;
        }
        
        /* Form Actions */
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border-color);
        }
        
        /* Upload Guidelines */
        .upload-guidelines {
            background: var(--bg-primary);
            border-radius: var(--border-radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            height: fit-content;
        }
        
        .upload-guidelines h3 {
            font-size: 1.25rem;
            margin-bottom: 1.5rem;
            color: var(--text-primary);
        }
        
        .guidelines-content {
            margin-bottom: 2rem;
        }
        
        .guideline-section {
            margin-bottom: 1.5rem;
        }
        
        .guideline-section h4 {
            font-size: 1rem;
            margin-bottom: 0.75rem;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .guideline-section h4 i {
            color: var(--primary-color);
        }
        
        .guideline-section ul {
            list-style: none;
            padding-left: 1.5rem;
        }
        
        .guideline-section li {
            margin-bottom: 0.5rem;
            color: var(--text-secondary);
            font-size: 0.875rem;
            position: relative;
        }
        
        .guideline-section li::before {
            content: '•';
            color: var(--primary-color);
            position: absolute;
            left: -1rem;
        }
        
        .upload-stats {
            background: var(--bg-secondary);
            border-radius: var(--border-radius);
            padding: 1.5rem;
        }
        
        .upload-stats h4 {
            font-size: 1rem;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.75rem;
        }
        
        .stat {
            text-align: center;
            padding: 1rem;
            background: var(--bg-primary);
            border-radius: var(--border-radius);
        }
        
        .stat-number {
            display: block;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            font-size: 0.75rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .upload-container {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            
            .upload-guidelines {
                order: -1;
            }
            
            .upload-card {
                padding: 2rem;
            }
            
            .form-row {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .form-actions {
                flex-direction: column;
            }
        }
        
        @media (max-width: 480px) {
            .upload-card {
                padding: 1.5rem;
            }
            
            .page-header {
                padding: 2rem 0 1rem;
            }
            
            .header-content h1 {
                font-size: 1.5rem;
            }
        }
    </style>
    
    <script>
        // Handle file selection
        function handleFileSelect(input) {
            const file = input.files[0];
            if (!file) return;
            
            const display = input.parentElement;
            const fileInfo = display.querySelector('.file-info');
            const fileName = fileInfo.querySelector('.file-name');
            const fileSize = fileInfo.querySelector('.file-size');
            const fileIcon = fileInfo.querySelector('.file-icon');
            
            // Show file info
            display.querySelector('.upload-text').textContent = file.name;
            display.querySelector('.upload-hint').textContent = 'Click to change file';
            
            fileName.textContent = file.name;
            fileSize.textContent = formatFileSize(file.size);
            
            // Set file icon based on type
            const ext = file.name.split('.').pop().toLowerCase();
            const iconMap = {
                'pdf': 'fa-file-pdf',
                'doc': 'fa-file-word',
                'docx': 'fa-file-word',
                'zip': 'fa-file-archive'
            };
            fileIcon.className = 'file-icon fas ' + (iconMap[ext] || 'fa-file');
            
            fileInfo.classList.add('show');
            
            // Show progress bar
            const progressBar = display.querySelector('.progress-bar');
            const progressFill = display.querySelector('.progress-fill');
            
            progressBar.style.display = 'block';
            
            // Simulate upload progress
            let progress = 0;
            const interval = setInterval(() => {
                progress += Math.random() * 15;
                if (progress >= 100) {
                    progress = 100;
                    clearInterval(interval);
                    setTimeout(() => {
                        progressBar.style.display = 'none';
                    }, 500);
                }
                progressFill.style.width = progress + '%';
            }, 100);
        }
        
        // Remove selected file
        function removeFile(button) {
            const fileInfo = button.closest('.file-info');
            const display = button.closest('.file-upload');
            const input = display.querySelector('input[type="file"]');
            
            fileInfo.classList.remove('show');
            display.querySelector('.upload-text').textContent = 'Choose a file to upload';
            display.querySelector('.upload-hint').textContent = 'PDF, DOC, DOCX, or ZIP (Max 50MB)';
            
            input.value = '';
        }
        
        // Format file size
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        // Drag and drop functionality
        const fileUpload = document.querySelector('.file-upload');
        const fileInput = document.querySelector('input[type="file"]');
        
        fileUpload.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.querySelector('.file-upload-display').classList.add('dragover');
        });
        
        fileUpload.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.querySelector('.file-upload-display').classList.remove('dragover');
        });
        
        fileUpload.addEventListener('drop', function(e) {
            e.preventDefault();
            this.querySelector('.file-upload-display').classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                handleFileSelect(fileInput);
            }
        });
        
        // Form validation
        document.querySelector('.upload-form').addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const category = document.getElementById('category').value;
            const file = document.getElementById('file').files[0];
            
            if (!title || !category || !file) {
                e.preventDefault();
                showNotification('Please fill in all required fields and select a file', 'error');
                return false;
            }
            
            if (file.size > 50 * 1024 * 1024) { // 50MB limit
                e.preventDefault();
                showNotification('File size must be less than 50MB', 'error');
                return false;
            }
        });
        
        // Auto-resize textarea
        document.getElementById('description').addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + U to focus file upload
            if ((e.ctrlKey || e.metaKey) && e.key === 'u') {
                e.preventDefault();
                document.getElementById('file').click();
            }
            
            // Ctrl/Cmd + Enter to submit
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                document.querySelector('.upload-form').submit();
            }
        });
    </script>
<?php
// Reset variables to avoid conflicts
unset($error, $title, $description, $categoryId, $status);
?>