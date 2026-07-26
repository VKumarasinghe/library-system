<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$pageTitle = 'Notifications';
$inAdminOrStudent = true;
$uid = current_user_id();

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->execute([$uid]);
$notifications = $stmt->fetchAll();

// Mark all as read
$pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$uid]);

require __DIR__ . '/../includes/header.php';
?>

<h1 class="h3 mb-4">Notifications</h1>

<div class="list-group">
  <?php foreach ($notifications as $n): ?>
    <div class="list-group-item <?= $n['is_read'] ? '' : 'fw-semibold' ?>">
      <?= h($n['message']) ?>
      <div class="small text-muted"><?= h(date('M j, Y g:i A', strtotime($n['created_at']))) ?></div>
    </div>
  <?php endforeach; ?>
  <?php if (!$notifications): ?>
    <p class="text-muted">You have no notifications yet.</p>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
