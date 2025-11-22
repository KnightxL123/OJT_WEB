<?php
session_start();
require_once __DIR__ . '/../../paths.php';
require_once __DIR__ . '/../../config/DBconfig.php';

// Check if the user is logged in and has the correct role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'user') {
    header('Location: login.php');
    exit;
}

// Get the document ID from the URL
$doc_id = isset($_GET['doc_id']) ? intval($_GET['doc_id']) : 0;

if ($doc_id > 0) {
    // Fetch the document details from the database
    try {
        $stmt = $conn->prepare("SELECT * FROM documents WHERE id = ?");
        $stmt->execute([$doc_id]);
        $row = $stmt->fetch();
    } catch (PDOException $e) {
        die('Database error: ' . $e->getMessage());
    }

    if ($row) {
        $file_path = $row['file_path']; // Assuming you store the path to the file in the 'file_path' column

        // Check if the file exists
        if (file_exists($file_path)) {
            // Set headers for file download
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
            header('Content-Length: ' . filesize($file_path));

            // Output the file
            readfile($file_path);
            exit;
        } else {
            echo "File not found.";
        }
    } else {
        echo "Document not found.";
    }
} else {
    echo "Invalid document ID.";
}
?>
