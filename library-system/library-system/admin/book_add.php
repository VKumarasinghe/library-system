<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$pageTitle = 'Add Book';
$inAdminOrStudent = true;
$errors = [];
$old = ['title' => '', 'author' => '', 'isbn' => '', 'category' => '', 'description' => '', 'total_copies' => 1];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        header('Location: book_add.php');
        exit;
    }

    $old['title']        = clean($_POST['title'] ?? '');
    $old['author']       = clean($_POST['author'] ?? '');
    $old['isbn']         = clean($_POST['isbn'] ?? '');
    $old['category']     = clean($_POST['category'] ?? '');
    $old['description']  = clean($_POST['description'] ?? '');
    $old['total_copies'] = (int)($_POST['total_copies'] ?? 0);

    if ($old['title'] === '') $errors[] = 'Title is required.';
    if ($old['author'] === '') $errors[] = 'Author is required.';
    if ($old['total_copies'] < 1) $errors[] = 'Total copies must be at least 1.';

    // Validate + upload the cover image (optional, but validated if provided)
    $coverFilename = null;
    if (!$errors) {
        $coverFilename = handle_cover_upload('cover_image');
        if ($coverFilename === false) {
            $errors[] = 'Please fix the cover image and try again.';
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare("
            INSERT INTO books (title, author, isbn, category, description, total_copies, available_copies, cover_image, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $old['title'], $old['author'], $old['isbn'], $old['category'], $old['description'],
            $old['total_copies'], $old['total_copies'], $coverFilename, current_user_id()
        ]);
        set_flash('success', 'Book "' . $old['title'] . '" was added successfully.');
        header('Location: books_list.php');
        exit;
    }
}

require __DIR__ . '/../includes/header.php';
?>

<h1 class="h3 mb-4">Add New Book</h1>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<div class="card p-4">
  <form method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
    <?= csrf_field() ?>
    <div class="row g-3">
      <div class="col-md-8">
        <div class="mb-3">
          <label class="form-label">Title *</label>
          <input type="text" name="title" class="form-control" value="<?= h($old['title']) ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Author *</label>
          <input type="text" name="author" class="form-control" value="<?= h($old['author']) ?>" required>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">ISBN</label>
            <input type="text" name="isbn" class="form-control" value="<?= h($old['isbn']) ?>">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Category</label>
            <input type="text" name="category" class="form-control" value="<?= h($old['category']) ?>">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">Total Copies *</label>
          <input type="number" name="total_copies" class="form-control" value="<?= h($old['total_copies']) ?>" min="1" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="4"><?= h($old['description']) ?></textarea>
        </div>
      </div>
      <div class="col-md-4 text-center">
        <label class="form-label d-block">Cover Image</label>
        <img src="../assets/img/no-cover.svg" id="coverPreview" class="cover-preview mb-2" alt="Cover preview">
        <input type="file" name="cover_image" id="cover_image" class="form-control" accept="image/jpeg,image/png,image/webp">
        <div class="form-text">JPG, PNG, or WEBP. Max 3MB.</div>
      </div>
    </div>
    <button type="submit" class="btn btn-primary mt-3">Save Book</button>
    <a href="books_list.php" class="btn btn-outline-secondary mt-3">Cancel</a>
  </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
