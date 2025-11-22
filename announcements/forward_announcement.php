<?php
session_start();
require_once __DIR__ . '/../paths.php';
require_once __DIR__ . '/../config/DBconfig.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $announcement_id = intval($_POST['announcement_id']);
    $forward_to_id = intval($_POST['forward_to_id']);

    try {
        $stmt = $conn->prepare("INSERT INTO announcement_recipients (announcement_id, recipient_id, forwarded_from_id) VALUES (?, ?, ?)");
        $stmt->execute([$announcement_id, $forward_to_id, $_SESSION['user_id']]);
    } catch (PDOException $e) {
        die('Database error: ' . $e->getMessage());
    }
    header('Location: user_inbox.php');
    exit;
}
?>
