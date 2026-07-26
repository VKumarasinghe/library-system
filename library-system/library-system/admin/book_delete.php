<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify()) {
    header('Location: books_list.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
$stmt->execute([$id]);
$book = $stmt->fetch();

if (!$book) {
    set_flash('error', 'Book not found.');
    header('Location: books_list.php');
    exit;
}

$check = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE book_id = ? AND status IN ('pending','approved')");
$check->execute([$id]);
if ((int)$check->fetchColumn() > 0) {
    set_flash('error', 'Cannot delete "' . $book['title'] . '" — it has active reservations.');
    header('Location: books_list.php');
    exit;
}

$del = $pdo->prepare("DELETE FROM books WHERE id = ?");
$del->execute([$id]);
delete_cover_file($book['cover_image']);

set_flash('success', 'Book "' . $book['title'] . '" was deleted.');
header('Location: books_list.php');
exit;
