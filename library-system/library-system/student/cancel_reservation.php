<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify()) {
    header('Location: my_reservations.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$uid = current_user_id();

$stmt = $pdo->prepare("SELECT * FROM reservations WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $uid]);
$res = $stmt->fetch();

if (!$res) {
    set_flash('error', 'Reservation not found.');
} elseif ($res['status'] !== 'pending') {
    set_flash('error', 'Only pending reservations can be cancelled.');
} else {
    $update = $pdo->prepare("UPDATE reservations SET status = 'cancelled' WHERE id = ?");
    $update->execute([$id]);
    set_flash('success', 'Reservation cancelled.');
}

header('Location: my_reservations.php');
exit;
