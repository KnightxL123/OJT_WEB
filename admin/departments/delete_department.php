<?php
session_start();
require_once __DIR__ . '/../../paths.php';
require_once __DIR__ . '/../../config/DBconfig.php';
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    try {
        $stmt = $conn->prepare("DELETE FROM departments WHERE id = ?");
        $stmt->execute([$id]);
    } catch (PDOException $e) {
        die('Database error: ' . $e->getMessage());
    }
}
header("Location: ../manage.php");
exit;
