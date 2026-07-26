<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$pageTitle = 'Manage Students';
$inAdminOrStudent = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        header('Location: students.php');
        exit;
    }
    $id = (int)($_POST['id'] ?? 0);

    if ($id === (int)current_user_id()) {
        set_flash('error', "You can't change your own status.");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'student'");
        $stmt->execute([$id]);
        $student = $stmt->fetch();
        if ($student) {
            $newStatus = $student['status'] === 'active' ? 'inactive' : 'active';
            $pdo->prepare("UPDATE users SET status = ? WHERE id = ?")->execute([$newStatus, $id]);
            set_flash('success', $student['full_name'] . ' is now ' . $newStatus . '.');
        } else {
            set_flash('error', 'Student not found.');
        }
    }
    header('Location: students.php');
    exit;
}

$search = clean($_GET['q'] ?? '');
$params = ['student'];
$where = "role = ?";
if ($search !== '') {
    $where .= " AND (full_name LIKE ? OR email LIKE ?)";
    $like = "%$search%";
    array_push($params, $like, $like);
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE $where ORDER BY created_at DESC");
$stmt->execute($params);
$students = $stmt->fetchAll();

require __DIR__ . '/../includes/header.php';
?>

<h1 class="h3 mb-4">Manage Students</h1>

<form method="get" class="row g-2 mb-4">
  <div class="col-md-6">
    <input type="text" name="q" class="form-control" placeholder="Search by name or email" value="<?= h($search) ?>">
  </div>
  <div class="col-md-2">
    <button class="btn btn-outline-primary w-100" type="submit">Search</button>
  </div>
</form>

<div class="table-responsive">
  <table class="table table-hover bg-white align-middle">
    <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
    <tbody>
      <?php foreach ($students as $s): ?>
        <tr>
          <td><?= h($s['full_name']) ?></td>
          <td><?= h($s['email']) ?></td>
          <td><?= h($s['phone']) ?></td>
          <td><span class="badge <?= $s['status']==='active' ? 'bg-success' : 'bg-secondary' ?>"><?= h(ucfirst($s['status'])) ?></span></td>
          <td><?= h(date('M j, Y', strtotime($s['created_at']))) ?></td>
          <td>
            <form method="post" data-confirm="<?= $s['status']==='active' ? 'Deactivate' : 'Activate' ?> <?= h(addslashes($s['full_name'])) ?>?">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
              <button class="btn btn-sm <?= $s['status']==='active' ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                <?= $s['status']==='active' ? 'Deactivate' : 'Activate' ?>
              </button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$students): ?>
        <tr><td colspan="6" class="text-center text-muted py-4">No students found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
