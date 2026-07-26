<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
$stmt->execute([$id]);
$book = $stmt->fetch();

if (!$book) {
    set_flash('error', 'That book could not be found.');
    header('Location: books.php');
    exit;
}

$pageTitle = $book['title'];

// Has the current student already got an active reservation on this book?
$alreadyReserved = false;
if (is_logged_in() && !is_admin()) {
    $check = $pdo->prepare("SELECT id FROM reservations WHERE user_id = ? AND book_id = ? AND status IN ('pending','approved')");
    $check->execute([current_user_id(), $id]);
    $alreadyReserved = (bool) $check->fetch();
}

require __DIR__ . '/includes/header.php';
?>

<div class="row g-4">
  <div class="col-md-4 text-center">
    <img src="<?= h(cover_url($book['cover_image'])) ?>" alt="<?= h($book['title']) ?> cover" class="book-detail-cover">
  </div>
  <div class="col-md-8">
    <h1 class="h3"><?= h($book['title']) ?></h1>
    <p class="text-muted mb-2">by <?= h($book['author']) ?></p>
    <?php if ($book['category']): ?><span class="badge bg-primary mb-3"><?= h($book['category']) ?></span><?php endif; ?>
    <?php if ($book['isbn']): ?><p class="small text-muted">ISBN: <?= h($book['isbn']) ?></p><?php endif; ?>
    <p><?= nl2br(h($book['description'])) ?></p>

    <p>
      <?php if ($book['available_copies'] > 0): ?>
        <span class="badge bg-success fs-6"><?= (int)$book['available_copies'] ?> of <?= (int)$book['total_copies'] ?> copies available</span>
      <?php else: ?>
        <span class="badge bg-secondary fs-6">Out of stock</span>
      <?php endif; ?>
    </p>

    <?php if (!is_logged_in()): ?>
      <a href="login.php" class="btn btn-primary">Log in to reserve</a>
    <?php elseif (is_admin()): ?>
      <a href="admin/book_edit.php?id=<?= (int)$book['id'] ?>" class="btn btn-outline-primary">Edit This Book</a>
    <?php elseif ($alreadyReserved): ?>
      <button class="btn btn-secondary" disabled>Already Reserved</button>
    <?php elseif ($book['available_copies'] < 1): ?>
      <button class="btn btn-secondary" disabled>Out of Stock</button>
    <?php else: ?>
      <form method="post" action="student/reserve.php">
        <?= csrf_field() ?>
        <input type="hidden" name="book_id" value="<?= (int)$book['id'] ?>">
        <button type="submit" class="btn btn-primary"><i class="bi bi-bookmark-plus"></i> Reserve This Book</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
