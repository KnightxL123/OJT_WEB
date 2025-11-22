<?php
session_start();
require_once __DIR__ . '/../paths.php';
require_once __DIR__ . '/../config/DBconfig.php';

$department_id = intval($_POST['department_id']);
$section_id = intval($_POST['section_id']);
$title = $_POST['title'];

$upload_dir = '../uploads';
$section_dir = $upload_dir . "/Section_$section_id";
if (!file_exists($section_dir)) {
    mkdir($section_dir, 0755, true);
}

$file = $_FILES['document'];
$filename = basename($file['name']);
$target_file = "$section_dir/$filename";

if (move_uploaded_file($file['tmp_name'], $target_file)) {
    try {
        $stmt = $conn->prepare("INSERT INTO documents (department_id, section_id, title, file_path) VALUES (?, ?, ?, ?)");
        $stmt->execute([$department_id, $section_id, $title, $target_file]);
        echo "<p>File uploaded and saved successfully. <a href='documents.php?department=$department_id&section=$section_id'>Back</a></p>";
    } catch (PDOException $e) {
        echo "<p>Database error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>Failed to upload file.</p>";
}
