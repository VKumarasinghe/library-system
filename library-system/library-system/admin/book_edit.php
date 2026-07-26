<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$pageTitle = 'Edit Book';
$inAdminOrStudent = true;

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
$stmt->execute([$id]);
$book = $stmt->fetch();

if (!$book) {
    set_flash('error', 'Book not found.');
    header('Location: books_list.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        header('Location: book_edit.php?id=' . $id);
        exit;
    }

    $title       = clean($_POST['title'] ?? '');
    $author      = clean($_POST['author'] ?? '');
    $isbn        = clean($_POST['isbn'] ?? '');
    $category    = clean($_POST['category'] ?? '');
    $description = clean($_POST['description'] ?? '');
    $totalCopies = (int)($_POST['total_copies'] ?? 0);
    $removeCover = isset($_POST['remove_cover']);

    // Reserved (pending/approved) copies must still fit if total is reduced
    $reservedStmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE book_id = ? AND status IN ('pending','approved')");
    $reservedStmt->execute([$id]);
    $reservedCount = (int)$reservedStmt->fetchColumn();

    if ($title === '') $errors[] = 'Title is required.';
    if ($author === '') $errors[] = 'Author is required.';
    if ($totalCopies < 1) $errors[] = 'Total copies must be at least 1.';
    if ($totalCopies < $reservedCount) $errors[] = "Total copies can't be less than the $reservedCount copies currently reserved.";

    $coverFilename = $book['cover_image'];
    if (!$errors) {
        $uploaded = handle_cover_upload('cover_image');
        if ($uploaded === false) {
            $errors[] = 'Please fix the cover image and try again.';
        } elseif ($uploaded !== null) {
            delete_cover_file($book['cover_image']);
            $coverFilename = $uploaded;
        } elseif ($removeCover) {
            delete_cover_file($book['cover_image']);
            $coverFilename = null;
        }
    }

    if (!$errors) {
        // Keep available_copies consistent with the change in total_copies
        $diff = $totalCopies - $book['total_copies'];
        $newAvailable = max(0, $book['available_copies'] + $diff);

        $update = $pdo->prepare("
            UPDATE books SET title=?, author=?, isbn=?, category=?, description=?,
                total_copies=?, available_copies=?, cover_image=? WHERE id=?
        ");
        $update->execute([$title, $author, $isbn, $category, $description, $totalCopies, $newAvailable, $coverFilename, $id]);
        set_flash('success', 'Book updated successfully.');
        header('Location: books_list.php');
        exit;
    } else {
        $book = array_merge($book, compact('title', 'author', 'isbn', 'category', 'description', 'totalCopies'));
        $book['total_copies'] = $totalCopies;
    }
}

require __DIR__ . '/../includes/header.php';
?>

<h1 class="h3 mb-4">Edit Book</h1>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<div class="card p-4">
  <form method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int)$book['id'] ?>">
    <div class="row g-3">
      <div class="col-md-8">
        <div class="mb-3">
          <label class="form-label">Title *</label>
          <input type="text" name="title" class="form-control" value="<?= h($book['title']) ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Author *</label>
          <input type="text" name="author" class="form-control" value="<?= h($book['author']) ?>" required>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">ISBN</label>
            <input type="text" name="isbn" class="form-control" value="<?= h($book['isbn']) ?>">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Category</label>
            <input type="text" name="category" class="form-control" value="<?= h($book['category']) ?>">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">Total Copies *</label>
          <input type="number" name="total_copies" class="form-control" value="<?= h($book['total_copies']) ?>" min="1" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="4"><?= h($book['description']) ?></textarea>
        </div>
      </div>
      <div class="col-md-4 text-center">
        <label class="form-label d-block">Cover Image</label>
        <img src="../<?= h(cover_url($book['cover_image'])) ?>" id="coverPreview" class="cover-preview mb-2" alt="Cover preview">
        <input type="file" name="cover_image" id="cover_image" class="form-control mb-2" accept="image/jpeg,image/png,image/webp">
        <?php if ($book['cover_image']): ?>
          <div class="form-check text-start">
            <input class="form-check-input" type="checkbox" name="remove_cover" id="remove_cover">
            <label class="form-check-label small" for="remove_cover">Remove current cover</label>
          </div>
        <?php endif; ?>
        <div class="form-text">JPG, PNG, or WEBP. Max 3MB.</div>
      </div>
    </div>
    <button type="submit" class="btn btn-primary mt-3">Save Changes</button>
    <a href="books_list.php" class="btn btn-outline-secondary mt-3">Cancel</a>
  </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
