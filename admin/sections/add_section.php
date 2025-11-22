<?php
session_start();
require_once __DIR__ . '/../../paths.php';
require_once __DIR__ . '/../../config/DBconfig.php';
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$message = '';

// Get all departments for the dropdown
try {
    $stmt = $conn->query("SELECT id, name FROM departments ORDER BY name");
    $departments = $stmt->fetchAll();
} catch (PDOException $e) {
    $departments = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $department_id = intval($_POST['department_id']);
    $section_name = trim($_POST['section_name']);

    if ($department_id === 0 || $section_name === '') {
        $message = 'Please select a department and enter a section name.';
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO sections (department_id, name) VALUES (?, ?)");
            $stmt->execute([$department_id, $section_name]);
            $message = 'Section added successfully.';
        } catch (PDOException $e) {
            $message = 'Error adding section: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Section</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h1>Add Section</h1>
    <?php if ($message): ?>
        <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <form method="post">
        <div class="mb-3">
            <label for="department_id" class="form-label">Department</label>
            <select class="form-select" name="department_id" id="department_id" required>
                <option value="">Select Department</option>
                <?php foreach ($departments as $dept): ?>
                    <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="section_name" class="form-label">Section Name</label>
            <input type="text" class="form-control" name="section_name" id="section_name" required>
        </div>
        <button type="submit" class="btn btn-primary">Add Section</button>
        <a href="documents.php" class="btn btn-secondary">Back</a>
    </form>
</div>
</body>
</html>
