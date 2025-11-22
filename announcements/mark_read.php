<?php
session_start();
require_once __DIR__ . '/../paths.php';
require_once __DIR__ . '/../config/DBconfig.php';
$id = intval($_GET['id']);
try {
    $stmt = $conn->prepare("UPDATE announcement_recipients SET is_read = 1 WHERE announcement_id = ? AND recipient_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}
header('Location: user_inbox.php');
exit;
?>
