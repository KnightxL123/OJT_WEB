<?php
session_start();
require_once __DIR__ . '/../paths.php';
require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to('auth/password_reset_request.php');
    exit;
}

$token = trim($_POST['token'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if ($token === '' || $password === '' || $confirm_password === '') {
    redirect_to('auth/password_reset.php?token=' . urlencode($token) . '&error=' . urlencode('All fields are required.'));
    exit;
}
if ($password !== $confirm_password) {
    redirect_to('auth/password_reset.php?token=' . urlencode($token) . '&error=' . urlencode('Passwords do not match.'));
    exit;
}
if (strlen($password) < 6) {
    redirect_to('auth/password_reset.php?token=' . urlencode($token) . '&error=' . urlencode('Password must be at least 6 characters.'));
    exit;
}

try {
    // Validate token and get user
    $stmt = $pdo->prepare('
        SELECT user_id, expires_at FROM password_resets WHERE token = :token LIMIT 1');
    $stmt->execute([':token' => $token]);
    $row = $stmt->fetch();

    if (!$row) {
        redirect_to('auth/password_reset_request.php?error=' . urlencode('Invalid or expired token.'));
        exit;
    }

    $user_id = $row['user_id'];
    $expires_at = $row['expires_at'];

    if (strtotime($expires_at) < time()) {
        // Token expired, delete it
        $del_stmt = $pdo->prepare('DELETE FROM password_resets WHERE token = :token');
        $del_stmt->execute([':token' => $token]);
        redirect_to('auth/password_reset_request.php?error=' . urlencode('Token expired. Please request a new reset.'));
        exit;
    }

    // Update password hash
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
    $stmt->execute([
        ':password_hash' => $hashed_password,
        ':id' => $user_id,
    ]);

    // Delete token after use
    $del_stmt = $pdo->prepare('DELETE FROM password_resets WHERE token = :token');
    $del_stmt->execute([':token' => $token]);

    redirect_to('auth/login.php?msg=' . urlencode('Password reset successful. Please login.'));
    exit;
} catch (PDOException $e) {
    redirect_to('auth/password_reset.php?token=' . urlencode($token) . '&error=' . urlencode('Failed to reset password.'));
    exit;
}
?>
