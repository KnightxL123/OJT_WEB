<?php
session_start();
require_once __DIR__ . '/../paths.php';
require_once __DIR__ . '/../config/DBconfig.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to('auth/password_reset_request.php');
}


$email = trim($_POST['email'] ?? '');

if ($email === '') {
    redirect_to('auth/password_reset_request.php?error=' . urlencode('Please enter your email.'));
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect_to('auth/password_reset_request.php?error=' . urlencode('Invalid email address.'));
}

// Check if email exists
try {
    $stmt = $conn->prepare('SELECT id, username FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    redirect_to('auth/password_reset_request.php?error=' . urlencode('Database error. Please try again later.'));
}

if (!$user) {
    // To avoid email enumeration, show generic message
    redirect_to('auth/password_reset_request.php?msg=' . urlencode('If the email exists, a reset link has been sent.'));
}

$user_id = $user['id'];
$username = $user['username'];

// Generate secure token
$token = bin2hex(random_bytes(32));
$expires_at = date('Y-m-d H:i:s', time() + 3600); // expire in 1 hour

// Insert or update token for user (PostgreSQL ON CONFLICT syntax assumes unique constraint on user_id)
try {
    $stmt = $conn->prepare('
        INSERT INTO password_resets (user_id, token, expires_at)
        VALUES (?, ?, ?)
        ON CONFLICT (user_id)
        DO UPDATE SET token = EXCLUDED.token, expires_at = EXCLUDED.expires_at
    ');
    $stmt->execute([$user_id, $token, $expires_at]);
} catch (PDOException $e) {
    redirect_to('auth/password_reset_request.php?error=' . urlencode('Failed to create reset token.'));
}

// Send reset email
$scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
$reset_path = url_for('auth/password_reset.php');
$reset_link = $scheme . '://' . $host . $reset_path . '?token=' . urlencode($token);

// Email headers and body
$to = $email;
$subject = "Password Reset Request";
$message = "Hello $username,\n\n";
$message .= "We received a request to reset your password. Please click the link below to reset it:\n\n";
$message .= "$reset_link\n\n";
$message .= "This link is valid for 1 hour.\n\n";
$message .= "If you did not request this, please ignore this email.\n\nRegards,\nYour Website Team";

$headers = 'From: no-reply@yourdomain.com' . "\r\n" .
    'Reply-To: no-reply@yourdomain.com' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();

$mail_sent = mail($to, $subject, $message, $headers);

// For demo or development, uncomment below to show reset link if email fails for testing:
// echo "Reset Link (for testing): $reset_link";

if ($mail_sent) {
    redirect_to('auth/password_reset_request.php?msg=' . urlencode('If the email exists, a reset link has been sent.'));
} else {
    redirect_to('auth/password_reset_request.php?error=' . urlencode('Failed to send reset email.'));
}
?>
