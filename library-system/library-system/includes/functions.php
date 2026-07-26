<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Escape output for safe HTML display. */
function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/** Trim + strip tags from raw input. */
function clean($str) {
    return trim(strip_tags($str ?? ''));
}

/** Queue a one-time flash message. Types: success | error | info | warning. */
function set_flash($type, $message) {
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/** Pull and clear all queued flash messages. */
function get_flashes() {
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

/** Basic CSRF token helpers. */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}

function csrf_verify() {
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        set_flash('error', 'Your session expired. Please try again.');
        return false;
    }
    return true;
}

/**
 * Validate and move an uploaded book cover image.
 * Returns the saved filename on success, or false on failure (with a flash message set).
 * Accepts JPG, PNG, WEBP up to 3MB. Optional — pass no file and it returns null.
 */
function handle_cover_upload($fileField = 'cover_image') {
    if (!isset($_FILES[$fileField]) || $_FILES[$fileField]['error'] === UPLOAD_ERR_NO_FILE) {
        return null; // no file chosen — not an error
    }

    $file = $_FILES[$fileField];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        set_flash('error', 'There was a problem uploading the cover image.');
        return false;
    }

    $maxBytes = 3 * 1024 * 1024; // 3MB
    if ($file['size'] > $maxBytes) {
        set_flash('error', 'Cover image must be 3MB or smaller.');
        return false;
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!isset($allowed[$mime])) {
        set_flash('error', 'Cover image must be a JPG, PNG, or WEBP file.');
        return false;
    }

    // Confirm it's really a decodable image, not just a spoofed mime type.
    if (@getimagesize($file['tmp_name']) === false) {
        set_flash('error', 'The uploaded file is not a valid image.');
        return false;
    }

    $ext      = $allowed[$mime];
    $filename = 'cover_' . bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
    $destDir  = __DIR__ . '/../uploads/covers/';
    $destPath = $destDir . $filename;

    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        set_flash('error', 'Could not save the uploaded cover image.');
        return false;
    }

    return $filename;
}

/** Delete a previously uploaded cover file, if it exists. */
function delete_cover_file($filename) {
    if (!$filename) return;
    $path = __DIR__ . '/../uploads/covers/' . $filename;
    if (is_file($path)) {
        @unlink($path);
    }
}

/** Return the public URL/path for a book cover, or a placeholder if none set. */
function cover_url($filename) {
    if ($filename && is_file(__DIR__ . '/../uploads/covers/' . $filename)) {
        return 'uploads/covers/' . rawurlencode($filename);
    }
    return 'assets/img/no-cover.svg';
}

/** Push a notification for a specific user. */
function notify_user(PDO $pdo, $userId, $message) {
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    $stmt->execute([$userId, $message]);
}

function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/** Password policy: min 8 chars, at least one letter and one number. */
function validate_password_strength($password) {
    return strlen($password) >= 8
        && preg_match('/[A-Za-z]/', $password)
        && preg_match('/[0-9]/', $password);
}
