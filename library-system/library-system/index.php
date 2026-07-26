<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Home';
$stmt = $pdo->query("SELECT * FROM books ORDER BY created_at DESC LIMIT 6");
$featured = $stmt->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<div class="p-5 mb-4 rounded-4" style="background: linear-gradient(135deg,#2c4770,#4a6fa5); color:#fff;">
  <div class="row align-items-center">
    <div class="col-md-8">
      <h1 class="fw-bold">Welcome to Smart Library</h1>
      <p class="lead mb-4">Browse our catalog, reserve books online, and manage your reading — all in one place.</p>
      <a href="books.php" class="btn btn-light btn-lg me-2"><i class="bi bi-search"></i> Browse Books</a>
      <?php if (!is_logged_in()): ?>
        <a href="register.php" class="btn btn-outline-light btn-lg">Get Started</a>
      <?php endif; ?>
    </div>
    <div class="col-md-4 text-center d-none d-md-block">
      <i class="bi bi-book" style="font-size: 8rem; opacity:.85;"></i>
    </div>
  </div>
</div>

<h2 class="h4 mb-3">Recently Added</h2>
<div class="row g-4">
  <?php foreach ($featured as $book): ?>
    <div class="col-6 col-md-4 col-lg-2">
      <div class="card book-card h-100">
        <div class="cover-wrap">
          <img src="<?= h(cover_url($book['cover_image'])) ?>" alt="<?= h($book['title']) ?> cover">
        </div>
        <div class="card-body">
          <h3 class="h6 mb-1"><?= h($book['title']) ?></h3>
          <p class="text-muted small mb-2"><?= h($book['author']) ?></p>
          <a href="book_view.php?id=<?= (int)$book['id'] ?>" class="btn btn-sm btn-outline-primary w-100">View</a>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
  <?php if (!$featured): ?>
    <p class="text-muted">No books have been added yet.</p>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
