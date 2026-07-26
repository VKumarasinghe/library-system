<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
if (is_admin()) { header('Location: ../dashboard.php'); exit; }

$pageTitle = 'My Reservations';
$inAdminOrStudent = true;
$uid = current_user_id();

$stmt = $pdo->prepare("
    SELECT r.*, b.title, b.author, b.cover_image FROM reservations r
    JOIN books b ON b.id = r.book_id
    WHERE r.user_id = ?
    ORDER BY r.reserved_at DESC
");
$stmt->execute([$uid]);
$reservations = $stmt->fetchAll();

require __DIR__ . '/../includes/header.php';
?>

<h1 class="h3 mb-4">My Reservations</h1>

<div class="table-responsive">
  <table class="table table-hover bg-white align-middle">
    <thead>
      <tr><th>Book</th><th>Status</th><th>Requested</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($reservations as $r): ?>
        <tr>
          <td class="d-flex align-items-center gap-2">
            <img src="../<?= h(cover_url($r['cover_image'])) ?>" alt="" style="width:40px;height:56px;object-fit:cover;border-radius:.3rem;">
            <div>
              <div class="fw-semibold"><?= h($r['title']) ?></div>
              <div class="small text-muted"><?= h($r['author']) ?></div>
            </div>
          </td>
          <td><span class="badge badge-status-<?= h($r['status']) ?>"><?= h(ucfirst($r['status'])) ?></span></td>
          <td><?= h(date('M j, Y g:i A', strtotime($r['reserved_at']))) ?></td>
          <td>
            <?php if ($r['status'] === 'pending'): ?>
              <form method="post" action="cancel_reservation.php" data-confirm="Cancel this reservation request?">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button class="btn btn-sm btn-outline-danger">Cancel</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$reservations): ?>
        <tr><td colspan="4" class="text-center text-muted py-4">You haven't reserved any books yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
