<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_login();

$pageTitle = 'My Profile';
$uid = current_user_id();

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$uid]);
$user = $stmt->fetch();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        header('Location: profile.php');
        exit;
    }

    $fullName = clean($_POST['full_name'] ?? '');
    $email    = clean($_POST['email'] ?? '');
    $phone    = clean($_POST['phone'] ?? '');

    if ($fullName === '' || strlen($fullName) < 2) {
        $errors[] = 'Please enter your full name.';
    }
    if (!validate_email($email)) {
        $errors[] = 'Please enter a valid email address.';
    } else {
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id <> ?");
        $check->execute([$email, $uid]);
        if ($check->fetch()) {
            $errors[] = 'That email is already used by another account.';
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
        $stmt->execute([$fullName, $email, $phone, $uid]);
        $_SESSION['full_name'] = $fullName;
        set_flash('success', 'Profile updated successfully.');
        header('Location: profile.php');
        exit;
    } else {
        $user['full_name'] = $fullName;
        $user['email'] = $email;
        $user['phone'] = $phone;
    }
}

require __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card p-4">
      <h1 class="h4 mb-3">My Profile</h1>

      <?php if ($errors): ?>
        <div class="alert alert-danger">
          <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul>
        </div>
      <?php endif; ?>

      <form method="post" class="needs-validation" novalidate>
        <?= csrf_field() ?>
        <div class="mb-3">
          <label class="form-label">Full Name</label>
          <input type="text" name="full_name" class="form-control" value="<?= h($user['full_name']) ?>" required minlength="2">
        </div>
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" value="<?= h($user['email']) ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Phone</label>
          <input type="text" name="phone" class="form-control" value="<?= h($user['phone']) ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Role</label>
          <input type="text" class="form-control" value="<?= h(ucfirst($user['role'])) ?>" disabled>
        </div>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </form>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
