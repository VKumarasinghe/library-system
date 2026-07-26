<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'Register';
$errors = [];
$old = ['full_name' => '', 'email' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        header('Location: register.php');
        exit;
    }

    $old['full_name'] = clean($_POST['full_name'] ?? '');
    $old['email']     = clean($_POST['email'] ?? '');
    $old['phone']     = clean($_POST['phone'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    if ($old['full_name'] === '' || strlen($old['full_name']) < 2) {
        $errors[] = 'Please enter your full name.';
    }
    if (!validate_email($old['email'])) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (!validate_password_strength($password)) {
        $errors[] = 'Password must be at least 8 characters and include a letter and a number.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors) {
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$old['email']]);
        if ($check->fetch()) {
            $errors[] = 'An account with that email already exists.';
        }
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role, phone, status) VALUES (?, ?, ?, 'student', ?, 'active')");
        $stmt->execute([$old['full_name'], $old['email'], $hash, $old['phone']]);
        set_flash('success', 'Account created successfully! Please log in.');
        header('Location: login.php');
        exit;
    }
}

require __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
  <div class="col-md-6 col-lg-5">
    <div class="card p-4">
      <h1 class="h4 mb-3 text-center">Create Your Account</h1>

      <?php if ($errors): ?>
        <div class="alert alert-danger">
          <ul class="mb-0">
            <?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="post" class="needs-validation" novalidate>
        <?= csrf_field() ?>
        <div class="mb-3">
          <label class="form-label">Full Name</label>
          <input type="text" name="full_name" class="form-control" value="<?= h($old['full_name']) ?>" required minlength="2">
        </div>
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" value="<?= h($old['email']) ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Phone (optional)</label>
          <input type="text" name="phone" class="form-control" value="<?= h($old['phone']) ?>">
        </div>
        <div class="mb-1">
          <label class="form-label">Password</label>
          <input type="password" name="password" id="password" class="form-control" required minlength="8">
          <div class="pw-strength"><div class="pw-strength-bar" id="pwStrengthBar"></div></div>
          <div class="form-text">At least 8 characters, with a letter and a number.</div>
        </div>
        <div class="mb-3 mt-2">
          <label class="form-label">Confirm Password</label>
          <input type="password" name="confirm_password" class="form-control" required minlength="8">
        </div>
        <button type="submit" class="btn btn-primary w-100">Register</button>
      </form>
      <p class="text-center mt-3 mb-0">Already have an account? <a href="login.php">Log in</a></p>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
