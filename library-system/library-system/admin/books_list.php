<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$pageTitle = 'Manage Books';
$inAdminOrStudent = true;

$search = clean($_GET['q'] ?? '');
$params = [];
$where = '';
if ($search !== '') {
    $where = "WHERE title LIKE ? OR author LIKE ? OR isbn LIKE ?";
    $like = "%$search%";
    $params = [$like, $like, $like];
}

$stmt = $pdo->prepare("SELECT * FROM books $where ORDER BY created_at DESC");
$stmt->execute($params);
$books = $stmt->fetchAll();

require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
  <h1 class="h3 mb-0">Manage Books</h1>
  <a href="book_add.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add New Book</a>
</div>

<form method="get" class="row g-2 mb-4">
  <div class="col-md-6">
    <input type="text" name="q" class="form-control" placeholder="Search by title, author, or ISBN" value="<?= h($search) ?>">
  </div>
  <div class="col-md-2">
    <button class="btn btn-outline-primary w-100" type="submit">Search</button>
  </div>
</form>

<div class="table-responsive">
  <table class="table table-hover bg-white align-middle">
    <thead><tr><th>Cover</th><th>Title</th><th>Author</th><th>Category</th><th>Copies</th><th>Actions</th></tr></thead>
    <tbody>
      <?php foreach ($books as $b): ?>
        <tr>
          <td><img src="../<?= h(cover_url($b['cover_image'])) ?>" alt="" style="width:40px;height:56px;object-fit:cover;border-radius:.3rem;"></td>
          <td><?= h($b['title']) ?></td>
          <td><?= h($b['author']) ?></td>
          <td><?= h($b['category']) ?></td>
          <td><?= (int)$b['available_copies'] ?> / <?= (int)$b['total_copies'] ?></td>
          <td class="d-flex gap-1">
            <a href="book_edit.php?id=<?= (int)$b['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
            <form method="post" action="book_delete.php" data-confirm="Delete '<?= h(addslashes($b['title'])) ?>'? This cannot be undone.">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
              <button class="btn btn-sm btn-outline-danger">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$books): ?>
        <tr><td colspan="6" class="text-center text-muted py-4">No books found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
