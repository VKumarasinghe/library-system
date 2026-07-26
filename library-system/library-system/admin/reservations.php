<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$pageTitle = 'Manage Reservations';
$inAdminOrStudent = true;

// Handle status-change actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        header('Location: reservations.php');
        exit;
    }

    $id     = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';

    $stmt = $pdo->prepare("SELECT r.*, b.title, b.available_copies FROM reservations r JOIN books b ON b.id=r.book_id WHERE r.id = ?");
    $stmt->execute([$id]);
    $res = $stmt->fetch();

    if (!$res) {
        set_flash('error', 'Reservation not found.');
    } else {
        $pdo->beginTransaction();
        try {
            if ($action === 'approve' && $res['status'] === 'pending') {
                if ($res['available_copies'] < 1) {
                    throw new Exception('No copies available to approve this reservation.');
                }
                $pdo->prepare("UPDATE reservations SET status='approved' WHERE id=?")->execute([$id]);
                $pdo->prepare("UPDATE books SET available_copies = available_copies - 1 WHERE id=?")->execute([$res['book_id']]);
                notify_user($pdo, $res['user_id'], 'Your reservation for "' . $res['title'] . '" was approved. Please collect it from the library.');
                set_flash('success', 'Reservation approved.');
            } elseif ($action === 'reject' && $res['status'] === 'pending') {
                $pdo->prepare("UPDATE reservations SET status='rejected' WHERE id=?")->execute([$id]);
                notify_user($pdo, $res['user_id'], 'Your reservation for "' . $res['title'] . '" was rejected.');
                set_flash('success', 'Reservation rejected.');
            } elseif ($action === 'return' && $res['status'] === 'approved') {
                $pdo->prepare("UPDATE reservations SET status='returned' WHERE id=?")->execute([$id]);
                $pdo->prepare("UPDATE books SET available_copies = available_copies + 1 WHERE id=?")->execute([$res['book_id']]);
                notify_user($pdo, $res['user_id'], 'Thanks for returning "' . $res['title'] . '".');
                set_flash('success', 'Book marked as returned.');
            } else {
                throw new Exception('That action is not valid for the reservation\'s current status.');
            }
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            set_flash('error', $e->getMessage());
        }
    }
    header('Location: reservations.php');
    exit;
}

$statusFilter = clean($_GET['status'] ?? '');
$where = '';
$params = [];
if ($statusFilter !== '') {
    $where = "WHERE r.status = ?";
    $params[] = $statusFilter;
}

$stmt = $pdo->prepare("
    SELECT r.*, u.full_name, u.email, b.title FROM reservations r
    JOIN users u ON u.id = r.user_id
    JOIN books b ON b.id = r.book_id
    $where
    ORDER BY r.reserved_at DESC
");
$stmt->execute($params);
$reservations = $stmt->fetchAll();

require __DIR__ . '/../includes/header.php';
?>

<h1 class="h3 mb-4">Manage Reservations</h1>

<div class="mb-3">
  <div class="btn-group" role="group">
    <a href="?status=" class="btn btn-sm btn-outline-primary <?= $statusFilter==='' ? 'active' : '' ?>">All</a>
    <a href="?status=pending" class="btn btn-sm btn-outline-primary <?= $statusFilter==='pending' ? 'active' : '' ?>">Pending</a>
    <a href="?status=approved" class="btn btn-sm btn-outline-primary <?= $statusFilter==='approved' ? 'active' : '' ?>">Approved</a>
    <a href="?status=returned" class="btn btn-sm btn-outline-primary <?= $statusFilter==='returned' ? 'active' : '' ?>">Returned</a>
    <a href="?status=rejected" class="btn btn-sm btn-outline-primary <?= $statusFilter==='rejected' ? 'active' : '' ?>">Rejected</a>
  </div>
</div>

<div class="table-responsive">
  <table class="table table-hover bg-white align-middle">
    <thead><tr><th>Student</th><th>Book</th><th>Status</th><th>Requested</th><th>Actions</th></tr></thead>
    <tbody>
      <?php foreach ($reservations as $r): ?>
        <tr>
          <td><?= h($r['full_name']) ?><div class="small text-muted"><?= h($r['email']) ?></div></td>
          <td><?= h($r['title']) ?></td>
          <td><span class="badge badge-status-<?= h($r['status']) ?>"><?= h(ucfirst($r['status'])) ?></span></td>
          <td><?= h(date('M j, Y g:i A', strtotime($r['reserved_at']))) ?></td>
          <td class="d-flex gap-1 flex-wrap">
            <?php if ($r['status'] === 'pending'): ?>
              <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="action" value="approve"><button class="btn btn-sm btn-success">Approve</button></form>
              <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="action" value="reject"><button class="btn btn-sm btn-outline-danger">Reject</button></form>
            <?php elseif ($r['status'] === 'approved'): ?>
              <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="action" value="return"><button class="btn btn-sm btn-outline-secondary">Mark Returned</button></form>
            <?php else: ?>
              <span class="text-muted small">—</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$reservations): ?>
        <tr><td colspan="5" class="text-center text-muted py-4">No reservations found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
