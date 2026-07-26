<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

if (is_admin()) {
    set_flash('error', 'Admin accounts cannot reserve books.');
    header('Location: ../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify()) {
    header('Location: ../books.php');
    exit;
}

$bookId = (int)($_POST['book_id'] ?? 0);
$uid = current_user_id();

$stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
$stmt->execute([$bookId]);
$book = $stmt->fetch();

if (!$book) {
    set_flash('error', 'That book could not be found.');
    header('Location: ../books.php');
    exit;
}

if ($book['available_copies'] < 1) {
    set_flash('error', 'Sorry, that book is currently out of stock.');
    header('Location: ../book_view.php?id=' . $bookId);
    exit;
}

$check = $pdo->prepare("SELECT id FROM reservations WHERE user_id = ? AND book_id = ? AND status IN ('pending','approved')");
$check->execute([$uid, $bookId]);
if ($check->fetch()) {
    set_flash('error', 'You already have an active reservation for this book.');
    header('Location: ../book_view.php?id=' . $bookId);
    exit;
}

$pdo->beginTransaction();
try {
    $ins = $pdo->prepare("INSERT INTO reservations (user_id, book_id, status) VALUES (?, ?, 'pending')");
    $ins->execute([$uid, $bookId]);
    $pdo->commit();
    set_flash('success', 'Your reservation request for "' . $book['title'] . '" has been submitted and is awaiting approval.');
} catch (Exception $e) {
    $pdo->rollBack();
    set_flash('error', 'Something went wrong while submitting your reservation. Please try again.');
}

header('Location: my_reservations.php');
exit;
