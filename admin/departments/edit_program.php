<?php
session_start();
require_once __DIR__ . '/../../paths.php';
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    redirect_to('auth/login.php');
}

require_once __DIR__ . '/../../config/DBconfig.php';

$error = '';
$success = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_program'])) {
    $id = intval($_POST['id']);
    $name = trim($_POST['name']);
    $logo_url = trim($_POST['logo_url']) ?: null;
    $department_id = intval($_POST['department_id']);
    
    try {
        if (empty($name)) {
            throw new Exception("Program name cannot be empty");
        }
        
        $stmt = $conn->prepare("UPDATE programs SET name = ?, department_id = ?, logo_url = ? WHERE id = ?");
        $stmt->execute([$name, $department_id, $logo_url, $id]);
        $success = "Program updated successfully!";
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get current program data
$program = null;
$departments = [];
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    try {
        $stmt = $conn->prepare("SELECT * FROM programs WHERE id = ?");
        $stmt->execute([$id]);
        $program = $stmt->fetch();
        
        $stmt = $conn->query("SELECT * FROM departments ORDER BY name ASC");
        $departments = $stmt->fetchAll();
    } catch (Exception $e) {
        die("Database error: " . $e->getMessage());
    }
}

if (!$program) {
    redirect_to('admin/manage.php');
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Program</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Edit Program</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="id" value="<?= $program['id'] ?>">
            
            <div class="mb-3">
                <label for="name" class="form-label">Program Name</label>
                <input type="text" class="form-control" id="name" name="name" 
                       value="<?= htmlspecialchars($program['name']) ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="department_id" class="form-label">Department</label>
                <select class="form-select" id="department_id" name="department_id" required>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= $dept['id'] ?>" <?= $dept['id'] == $program['department_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dept['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="mb-3">
                <label for="logo_url" class="form-label">Logo URL (optional)</label>
                <input type="url" class="form-control" id="logo_url" name="logo_url" 
                       value="<?= htmlspecialchars($program['logo_url']) ?>">
            </div>
            
            <button type="submit" name="update_program" class="btn btn-primary">Update</button>
            <a href="<?php echo url_for('admin/manage.php'); ?>" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</body>
</html>