<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'Login';
$errors = [];
$oldEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        header('Location: login.php');
        exit;
    }

    $oldEmail = clean($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!validate_email($oldEmail) || $password === '') {
        $errors[] = 'Please enter a valid email and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$oldEmail]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $errors[] = 'Incorrect email or password.';
        } elseif ($user['status'] !== 'active') {
            $errors[] = 'Your account has been deactivated. Please contact the library admin.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];
            set_flash('success', 'Welcome back, ' . $user['full_name'] . '!');
            header('Location: dashboard.php');
            exit;
        }
    }
}

require __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
  <div class="col-md-5">
    <div class="card p-4">
      <h1 class="h4 mb-3 text-center">Login</h1>

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
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" value="<?= h($oldEmail) ?>" required autofocus>
        </div>
        <div class="mb-3">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Login</button>
      </form>
      <p class="text-center mt-3 mb-0">No account yet? <a href="register.php">Register here</a></p>
      <hr>
      <p class="text-muted small mb-0 text-center">
        Demo admin: admin@library.com / Admin@123<br>
        Demo student: student@library.com / Student@123
      </p>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
