<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

/* ---------- 1.  AUTH / FETCH NOTE  ---------- */
ensureLoggedIn();
$uid = (int)$_SESSION['auth_user_id'];

$noteId = (int)($_GET['id'] ?? 0);
if (!$noteId) {
    http_response_code(404);
    exit('Note not found');
}

$note = $pdo->prepare('SELECT * FROM notes WHERE id = ? AND user_id = ?');
$note->execute([$noteId, $uid]);
$note = $note->fetch();
if (!$note) {
    http_response_code(403);
    exit('You can only edit your own notes');
}

/* ---------- 2.  CATEGORIES  ---------- */
$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();

/* ---------- 3.  HANDLE FORM SUBMISSION  ---------- */
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* 3-a. CSRF  */
    if (!csrf_ok($_POST['csrf'] ?? '')) {
        http_response_code(403);
        exit('Bad CSRF token');
    }

    /* 3-b. INPUT  */
    $title       = trim($_POST['title']       ?? '');
    $description = trim($_POST['description'] ?? '');
    $categoryId  = (int)($_POST['category']  ?? 0);
    $status      = in_array($_POST['status']  ?? '', ['published','draft'], true)
                   ? $_POST['status'] : 'draft';

    /* 3-c. VALIDATION  */
    if ($title === '')      $errors[] = 'Title is required';
    if ($categoryId === 0)  $errors[] = 'Category is required';

    /* 3-d. UPDATE NOTE  */
    if (!$errors) {
        $pdo->prepare(
           'UPDATE notes
            SET title = ?, description = ?, category_id = ?, status = ?, updated_at = NOW()
            WHERE id = ? AND user_id = ?'
        )->execute([$title, $description, $categoryId, $status, $noteId, $uid]);

        /* 3-e. OPTIONAL FILE REPLACE  */
        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['file'];
            $allowedTypes = [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/zip'
            ];

            if (in_array($file['type'], $allowedTypes, true)) {
                $dir = 'uploads/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);

                $filename = uniqid() . '_' . basename($file['name']);
                $filepath = $dir . $filename;

                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    // remove old file if it exists
                    if ($note['file_url'] && file_exists(__DIR__ . '/' . $note['file_url'])) {
                        @unlink(__DIR__ . '/' . $note['file_url']);
                    }
                    // Get new file size
                    $newFileSize = file_exists($filepath) ? filesize($filepath) : 0;
                    $pdo->prepare('UPDATE notes SET file_url = ?, file_size = ? WHERE id = ?')
                        ->execute([$filepath, $newFileSize, $noteId]);
                }
            }
        }

        /* 3-f. REDIRECT TO PROFILE  */
        redirect('profile.php?updated=1');
    }
}

/* ---------- 4.  OUTPUT  ---------- */
$_SESSION['csrf'] = csrf();
$pageTitle = 'Edit Note - ' . htmlspecialchars($note['title']) . ' - Student Notes Hub';
require __DIR__ . '/components/header.php';
?>

<main>
<section class="page-header">
  <div class="container">
    <div class="header-content">
      <h1><i class="fas fa-edit"></i> Edit Note</h1>
      <p>Update your note details and content</p>
    </div>
  </div>
</section>

<section class="edit-note-section">
  <div class="container">
    <div class="edit-note-container">
      <div class="edit-note-card">
        <div class="edit-note-header">
          <h2>Edit Note Details</h2>
          <p>Make changes to your note information</p>
        </div>

        <?php if (isset($_GET['updated'])): ?>
          <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> Note updated successfully!
          </div>
        <?php endif; ?>

        <?php if ($errors): ?>
          <div class="alert alert-error">
            <?php foreach ($errors as $e): ?>
              <p><?= e($e) ?></p>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="edit-note-form">
          <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">

          <div class="form-group">
            <label for="title">Note Title</label>
            <input type="text" id="title" name="title" value="<?= e($note['title']) ?>" class="form-control" required>
          </div>

          <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" class="form-control" rows="4"><?= e($note['description'] ?? '') ?></textarea>
          </div>

          <div class="form-group">
            <label for="category">Category</label>
            <select id="category" name="category" class="form-control" required>
              <option value="">Select a category</option>
              <?php foreach ($categories as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $c['id'] == $note['category_id'] ? 'selected' : '' ?>>
                  <?= e($c['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status" class="form-control">
              <option value="draft"     <?= $note['status'] === 'draft'     ? 'selected' : '' ?>>Draft</option>
              <option value="published" <?= $note['status'] === 'published' ? 'selected' : '' ?>>Published</option>
            </select>
          </div>

          <div class="form-group">
            <label for="file">Replace File (Optional)</label>
            <input type="file" id="file" name="file" class="form-control"
                   accept=".pdf,.doc,.docx,.zip">
            <small class="form-text text-muted">
              Current file: <?= $note['file_url'] ? basename($note['file_url']) : 'none' ?><br>
              Allowed formats: PDF, DOC, DOCX, ZIP
            </small>
          </div>

          <div class="form-actions">
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-save"></i> Update Note
            </button>
            <a href="profile.php" class="btn btn-secondary">
              <i class="fas fa-times"></i> Cancel
            </a>
            <a href="note-detail.php?id=<?= $note['id'] ?>" class="btn btn-info" target="_blank">
              <i class="fas fa-eye"></i> View Note
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>
</main>

<!-- reused CSS from your original file -->
<style>
.edit-note-section{padding:2rem 0;min-height:calc(100vh - 200px);}
.edit-note-container{max-width:800px;margin:0 auto;padding:0 1rem;}
.edit-note-card{background:#fff;border-radius:12px;box-shadow:0 4px 6px -1px rgba(0,0,0,.1);padding:2rem;}
.edit-note-header{margin-bottom:2rem;text-align:center;}
.edit-note-header h2{color:var(--primary-color);margin-bottom:.5rem;}
.edit-note-header p{color:var(--text-muted);}
.alert-success{background:rgba(16,185,129,.1);color:var(--success-color);border:1px solid rgba(16,185,129,.2);padding:1rem;border-radius:8px;margin-bottom:1.5rem;}
.alert-error{background:rgba(239,68,68,.1);color:var(--error-color);border:1px solid rgba(239,68,68,.2);padding:1rem;border-radius:8px;margin-bottom:1.5rem;}
.edit-note-form .form-group{margin-bottom:1.5rem;}
.edit-note-form label{display:block;margin-bottom:.5rem;font-weight:500;color:var(--text-color);}
.edit-note-form .form-control{width:100%;padding:.75rem;border:1px solid var(--border-color);border-radius:8px;font-size:1rem;transition:border-color .2s;}
.edit-note-form .form-control:focus{outline:none;border-color:var(--primary-color);box-shadow:0 0 0 3px rgba(99,102,241,.1);}
.edit-note-form textarea.form-control{resize:vertical;min-height:100px;}
.form-actions{display:flex;gap:1rem;margin-top:2rem;flex-wrap:wrap;}
.form-actions .btn{flex:1;min-width:120px;}
@media(max-width:768px){.form-actions{flex-direction:column;}.form-actions .btn{flex:none;}}
</style>

<?php require __DIR__ . '/components/footer.php'; ?>