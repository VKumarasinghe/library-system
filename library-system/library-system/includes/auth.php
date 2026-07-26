<?php
require_once __DIR__ . '/functions.php';

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return is_logged_in() && ($_SESSION['role'] ?? '') === 'admin';
}

function current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function require_login() {
    if (!is_logged_in()) {
        set_flash('error', 'Please log in to continue.');
        header('Location: login.php');
        exit;
    }
}

function require_admin() {
    require_login();
    if (!is_admin()) {
        set_flash('error', 'You do not have permission to view that page.');
        header('Location: dashboard.php');
        exit;
    }
}

function unread_notification_count(PDO $pdo, $userId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}
