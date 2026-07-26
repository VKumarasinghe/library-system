<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_login();

$pageTitle = 'Change Password';
$uid = current_user_id();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        header('Location: change_password.php');
        exit;
    }

    $current = $_POST['current_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$uid]);
    $hash = $stmt->fetchColumn();

    if (!password_verify($current, $hash)) {
        $errors[] = 'Your current password is incorrect.';
    }
    if (!validate_password_strength($newPass)) {
        $errors[] = 'New password must be at least 8 characters and include a letter and a number.';
    }
    if ($newPass !== $confirm) {
        $errors[] = 'New passwords do not match.';
    }

    if (!$errors) {
        $newHash = password_hash($newPass, PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update->execute([$newHash, $uid]);
        set_flash('success', 'Password changed successfully.');
        header('Location: profile.php');
        exit;
    }
}

require __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
  <div class="col-md-5">
    <div class="card p-4">
      <h1 class="h4 mb-3">Change Password</h1>

      <?php if ($errors): ?>
        <div class="alert alert-danger">
          <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul>
        </div>
      <?php endif; ?>

      <form method="post" class="needs-validation" novalidate>
        <?= csrf_field() ?>
        <div class="mb-3">
          <label class="form-label">Current Password</label>
          <input type="password" name="current_password" class="form-control" required>
        </div>
        <div class="mb-1">
          <label class="form-label">New Password</label>
          <input type="password" name="new_password" id="password" class="form-control" required minlength="8">
          <div class="pw-strength"><div class="pw-strength-bar" id="pwStrengthBar"></div></div>
        </div>
        <div class="mb-3 mt-2">
          <label class="form-label">Confirm New Password</label>
          <input type="password" name="confirm_password" class="form-control" required minlength="8">
        </div>
        <button type="submit" class="btn btn-primary w-100">Update Password</button>
      </form>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
