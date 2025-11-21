<?php
session_start();
require_once __DIR__ . '/../paths.php';
require_once __DIR__ . '/../config/DBconfig.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to('auth/password_reset_request.php');
}

$token = trim($_POST['token'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if ($token === '' || $password === '' || $confirm_password === '') {
    redirect_to('auth/password_reset.php?token=' . urlencode($token) . '&error=' . urlencode('All fields are required.'));
}
if ($password !== $confirm_password) {
    redirect_to('auth/password_reset.php?token=' . urlencode($token) . '&error=' . urlencode('Passwords do not match.'));
}
if (strlen($password) < 6) {
    redirect_to('auth/password_reset.php?token=' . urlencode($token) . '&error=' . urlencode('Password must be at least 6 characters.'));
}

// Validate token and get user
try {
    $stmt = $conn->prepare('SELECT user_id, expires_at FROM password_resets WHERE token = ? LIMIT 1');
    $stmt->execute([$token]);
    $row = $stmt->fetch();
} catch (PDOException $e) {
    redirect_to('auth/password_reset_request.php?error=' . urlencode('Database error. Please try again later.'));
}

if (!$row) {
    redirect_to('auth/password_reset_request.php?error=' . urlencode('Invalid or expired token.'));
}

$user_id = $row['user_id'];
$expires_at = $row['expires_at'];

if (strtotime($expires_at) < time()) {
    // Token expired, delete it
    $del_stmt = $conn->prepare('DELETE FROM password_resets WHERE token = ?');
    $del_stmt->execute([$token]);
    redirect_to('auth/password_reset_request.php?error=' . urlencode('Token expired. Please request a new reset.'));
}

// Update password hash
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
try {
    $stmt = $conn->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    $stmt->execute([$hashed_password, $user_id]);
} catch (PDOException $e) {
    redirect_to('auth/password_reset.php?token=' . urlencode($token) . '&error=' . urlencode('Failed to reset password.'));
}

// Delete token after use
$del_stmt = $conn->prepare('DELETE FROM password_resets WHERE token = ?');
$del_stmt->execute([$token]);

redirect_to('auth/login.php?msg=' . urlencode('Password reset successful. Please login.'));
?>
