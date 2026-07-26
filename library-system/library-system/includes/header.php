<?php
require_once __DIR__ . '/auth.php';
$pageTitle = $pageTitle ?? 'Library System';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($pageTitle) ?> · Smart Library</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?= isset($inAdminOrStudent) ? '../assets/css/style.css' : 'assets/css/style.css' ?>" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark app-navbar sticky-top">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?= isset($inAdminOrStudent) ? '../index.php' : 'index.php' ?>">
      <i class="bi bi-book-half"></i> Smart Library
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
        <?php $base = isset($inAdminOrStudent) ? '../' : ''; ?>
        <li class="nav-item"><a class="nav-link" href="<?= $base ?>index.php">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= $base ?>books.php">Browse Books</a></li>
        <?php if (is_logged_in()): ?>
          <li class="nav-item"><a class="nav-link" href="<?= $base ?>dashboard.php">Dashboard</a></li>
          <?php if (!is_admin()): ?>
          <li class="nav-item position-relative">
            <a class="nav-link" href="<?= $base ?>student/notifications.php">
              <i class="bi bi-bell"></i>
              <?php
                require_once __DIR__ . '/../config/database.php';
                $ucount = unread_notification_count($pdo, current_user_id());
              ?>
              <?php if ($ucount > 0): ?>
                <span class="badge rounded-pill bg-danger notif-badge"><?= $ucount ?></span>
              <?php endif; ?>
            </a>
          </li>
          <?php endif; ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
              <i class="bi bi-person-circle"></i> <?= h($_SESSION['full_name'] ?? 'Account') ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="<?= $base ?>profile.php">Profile</a></li>
              <li><a class="dropdown-item" href="<?= $base ?>change_password.php">Change Password</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="<?= $base ?>logout.php">Logout</a></li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
          <li class="nav-item"><a class="btn btn-light btn-sm ms-lg-2" href="register.php">Register</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<main class="container py-4">
<?php foreach (get_flashes() as $flash): ?>
  <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : h($flash['type']) ?> alert-dismissible fade show" role="alert">
    <?= h($flash['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endforeach; ?>
