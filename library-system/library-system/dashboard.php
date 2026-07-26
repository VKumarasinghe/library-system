<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_login();

$pageTitle = 'Dashboard';

if (is_admin()) {
    $totalBooks   = $pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();
    $totalCopies  = $pdo->query("SELECT COALESCE(SUM(total_copies),0) FROM books")->fetchColumn();
    $totalStudents= $pdo->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
    $pendingRes   = $pdo->query("SELECT COUNT(*) FROM reservations WHERE status='pending'")->fetchColumn();

    $recentRes = $pdo->query("
        SELECT r.*, u.full_name, b.title FROM reservations r
        JOIN users u ON u.id = r.user_id
        JOIN books b ON b.id = r.book_id
        ORDER BY r.reserved_at DESC LIMIT 8
    ")->fetchAll();
} else {
    $uid = current_user_id();
    $myReservations = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE user_id = ?");
    $myReservations->execute([$uid]);
    $totalMine = $myReservations->fetchColumn();

    $pendingMine = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE user_id = ? AND status='pending'");
    $pendingMine->execute([$uid]);
    $pendingCount = $pendingMine->fetchColumn();

    $approvedMine = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE user_id = ? AND status='approved'");
    $approvedMine->execute([$uid]);
    $approvedCount = $approvedMine->fetchColumn();

    $recent = $pdo->prepare("
        SELECT r.*, b.title, b.cover_image FROM reservations r
        JOIN books b ON b.id = r.book_id
        WHERE r.user_id = ? ORDER BY r.reserved_at DESC LIMIT 6
    ");
    $recent->execute([$uid]);
    $recentList = $recent->fetchAll();
}

require __DIR__ . '/includes/header.php';
?>

<h1 class="h3 mb-4">Welcome, <?= h($_SESSION['full_name']) ?></h1>

<?php if (is_admin()): ?>
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="stat-card bg-1"><div class="small">Total Books</div><div class="fs-2 fw-bold"><?= (int)$totalBooks ?></div></div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card bg-2"><div class="small">Total Copies</div><div class="fs-2 fw-bold"><?= (int)$totalCopies ?></div></div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card bg-3"><div class="small">Students</div><div class="fs-2 fw-bold"><?= (int)$totalStudents ?></div></div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card bg-4"><div class="small">Pending Requests</div><div class="fs-2 fw-bold"><?= (int)$pendingRes ?></div></div>
    </div>
  </div>

  <div class="d-flex gap-2 mb-4 flex-wrap">
    <a href="admin/book_add.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add New Book</a>
    <a href="admin/books_list.php" class="btn btn-outline-primary">Manage Books</a>
    <a href="admin/reservations.php" class="btn btn-outline-primary">Manage Reservations</a>
    <a href="admin/students.php" class="btn btn-outline-primary">Manage Students</a>
  </div>

  <h2 class="h5 mb-3">Recent Reservation Activity</h2>
  <div class="table-responsive">
    <table class="table table-hover align-middle bg-white">
      <thead><tr><th>Student</th><th>Book</th><th>Status</th><th>Requested</th></tr></thead>
      <tbody>
        <?php foreach ($recentRes as $r): ?>
          <tr>
            <td><?= h($r['full_name']) ?></td>
            <td><?= h($r['title']) ?></td>
            <td><span class="badge badge-status-<?= h($r['status']) ?>"><?= h(ucfirst($r['status'])) ?></span></td>
            <td><?= h(date('M j, Y g:i A', strtotime($r['reserved_at']))) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$recentRes): ?>
          <tr><td colspan="4" class="text-center text-muted py-4">No reservation activity yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

<?php else: ?>
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
      <div class="stat-card bg-1"><div class="small">My Reservations</div><div class="fs-2 fw-bold"><?= (int)$totalMine ?></div></div>
    </div>
    <div class="col-6 col-md-4">
      <div class="stat-card bg-3"><div class="small">Pending</div><div class="fs-2 fw-bold"><?= (int)$pendingCount ?></div></div>
    </div>
    <div class="col-6 col-md-4">
      <div class="stat-card bg-2"><div class="small">Approved</div><div class="fs-2 fw-bold"><?= (int)$approvedCount ?></div></div>
    </div>
  </div>

  <div class="d-flex gap-2 mb-4 flex-wrap">
    <a href="books.php" class="btn btn-primary"><i class="bi bi-search"></i> Browse Books</a>
    <a href="student/my_reservations.php" class="btn btn-outline-primary">My Reservations</a>
  </div>

  <h2 class="h5 mb-3">Recent Activity</h2>
  <div class="row g-3">
    <?php foreach ($recentList as $r): ?>
      <div class="col-md-6">
        <div class="card p-3 d-flex flex-row gap-3 align-items-center">
          <img src="<?= h(cover_url($r['cover_image'])) ?>" alt="" style="width:60px;height:84px;object-fit:cover;border-radius:.4rem;">
          <div>
            <div class="fw-semibold"><?= h($r['title']) ?></div>
            <span class="badge badge-status-<?= h($r['status']) ?>"><?= h(ucfirst($r['status'])) ?></span>
            <div class="small text-muted"><?= h(date('M j, Y', strtotime($r['reserved_at']))) ?></div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (!$recentList): ?>
      <p class="text-muted">You haven't reserved any books yet. <a href="books.php">Browse the catalog</a>.</p>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
