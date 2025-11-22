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
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_department'])) {
    $id = intval($_POST['id']);
    $name = trim($_POST['name']);
    $status = trim($_POST['status']);
    
    try {
        if (empty($name)) {
            throw new Exception("Department name cannot be empty");
        }
        
        $stmt = $conn->prepare("UPDATE departments SET name = ?, status = ? WHERE id = ?");
        $stmt->execute([$name, $status, $id]);
        $success = "Department updated successfully!";
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get current department data
$department = null;
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    try {
        $stmt = $conn->prepare("SELECT * FROM departments WHERE id = ?");
        $stmt->execute([$id]);
        $department = $stmt->fetch();
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}

if (!$department) {
    redirect_to('admin/manage.php');
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Department</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .status-badge {
            font-size: 0.8rem;
            padding: 3px 8px;
            border-radius: 10px;
        }
        .status-active {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        .status-inactive {
            background-color: #f8d7da;
            color: #842029;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2>Edit Department</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="id" value="<?= $department['id'] ?>">
            
            <div class="mb-3">
                <label for="name" class="form-label">Department Name</label>
                <input type="text" class="form-control" id="name" name="name" 
                       value="<?= htmlspecialchars($department['name']) ?>" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Status</label>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="status" id="statusActive" 
                           value="active" <?= $department['status'] == 'active' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="statusActive">
                        Active <span class="status-badge status-active">Active</span>
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="status" id="statusInactive" 
                           value="inactive" <?= $department['status'] == 'inactive' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="statusInactive">
                        Inactive <span class="status-badge status-inactive">Inactive</span>
                    </label>
                </div>
            </div>
            
            <button type="submit" name="update_department" class="btn btn-primary">Update</button>
            <a href="<?php echo url_for('admin/manage.php'); ?>" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</body>
</html>