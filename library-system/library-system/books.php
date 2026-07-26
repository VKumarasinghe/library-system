<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Browse Books';

$search   = clean($_GET['q'] ?? '');
$category = clean($_GET['category'] ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 8;
$offset   = ($page - 1) * $perPage;

$where  = [];
$params = [];

if ($search !== '') {
    $where[] = "(title LIKE ? OR author LIKE ? OR isbn LIKE ?)";
    $like = "%$search%";
    array_push($params, $like, $like, $like);
}
if ($category !== '') {
    $where[] = "category = ?";
    $params[] = $category;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM books $whereSql");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

$stmt = $pdo->prepare("SELECT * FROM books $whereSql ORDER BY title ASC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$books = $stmt->fetchAll();

$categories = $pdo->query("SELECT DISTINCT category FROM books WHERE category IS NOT NULL AND category <> '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

require __DIR__ . '/includes/header.php';
?>

<h1 class="h3 mb-4">Browse Books</h1>

<form method="get" class="row g-2 mb-4">
  <div class="col-md-6">
    <input type="text" name="q" class="form-control" placeholder="Search by title, author, or ISBN" value="<?= h($search) ?>">
  </div>
  <div class="col-md-3">
    <select name="category" class="form-select">
      <option value="">All Categories</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= h($c) ?>" <?= $category === $c ? 'selected' : '' ?>><?= h($c) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-3 d-flex gap-2">
    <button class="btn btn-primary flex-grow-1" type="submit"><i class="bi bi-search"></i> Search</button>
    <?php if ($search || $category): ?><a href="books.php" class="btn btn-outline-secondary">Reset</a><?php endif; ?>
  </div>
</form>

<div class="row g-4">
  <?php foreach ($books as $book): ?>
    <div class="col-6 col-md-4 col-lg-3">
      <div class="card book-card h-100">
        <div class="cover-wrap">
          <img src="<?= h(cover_url($book['cover_image'])) ?>" alt="<?= h($book['title']) ?> cover">
        </div>
        <div class="card-body d-flex flex-column">
          <h3 class="h6 mb-1"><?= h($book['title']) ?></h3>
          <p class="text-muted small mb-1"><?= h($book['author']) ?></p>
          <p class="mb-2">
            <?php if ($book['available_copies'] > 0): ?>
              <span class="badge bg-success"><?= (int)$book['available_copies'] ?> available</span>
            <?php else: ?>
              <span class="badge bg-secondary">Out of stock</span>
            <?php endif; ?>
          </p>
          <a href="book_view.php?id=<?= (int)$book['id'] ?>" class="btn btn-sm btn-outline-primary mt-auto">View Details</a>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
  <?php if (!$books): ?>
    <p class="text-muted">No books matched your search.</p>
  <?php endif; ?>
</div>

<?php if ($totalPages > 1): ?>
<nav class="mt-4">
  <ul class="pagination justify-content-center">
    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
      <li class="page-item <?= $p === $page ? 'active' : '' ?>">
        <a class="page-link" href="?q=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>&page=<?= $p ?>"><?= $p ?></a>
      </li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
