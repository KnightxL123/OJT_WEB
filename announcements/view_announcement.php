<?php
session_start();
require_once __DIR__ . '/../paths.php';
require_once __DIR__ . '/../config/DBconfig.php';
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

try {
    $stmt = $conn->prepare("SELECT * FROM announcements WHERE id=?");
    $stmt->execute([$id]);
    $announcement = $stmt->fetch();

    if ($announcement) {
        // Mark as read
        $stmt = $conn->prepare("UPDATE announcements SET is_read=1 WHERE id=?");
        $stmt->execute([$id]);
    } else {
        header('Location: Inbox.php');
        exit;
    }
} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}

function escape($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>View Announcement</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" />
    <style>
        .container { max-width: 800px; margin: 30px auto; }
    </style>
</head>
<body>
<div class="container">
    <a href="Inbox.php" class="btn btn-secondary mb-3"><i class="bi bi-arrow-left"></i> Back</a>
    <h2><?php echo escape($announcement['title']); ?></h2>
    <p class="text-muted"><?php echo date('F j, Y g:i A', strtotime($announcement['created_at'])); ?></p>
    <hr>
    <p><?php echo nl2br(escape($announcement['message'])); ?></p>
</div>
</body>
</html>
