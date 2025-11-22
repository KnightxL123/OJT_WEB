<?php
session_start();
require_once __DIR__ . '/../paths.php';
require_once __DIR__ . '/../config/DBconfig.php';

if (!isset($_SESSION['username'])) {
    header('Location: ../../auth/login.php?error=' . urlencode("Please log in to access the section editor."));
    exit;
}

if ($_SESSION['role'] !== 'coordinator') {
    header('Location: ../../auth/login.php?error=' . urlencode("Unauthorized access. Coordinator role required."));
    exit;
}

$coordinator_id = $_SESSION['user_id'] ?? null;
$department_id = null;
$department_name = '';

if ($coordinator_id) {
    $stmt = $conn->prepare("SELECT department_id FROM users WHERE id = ?");
    $stmt->execute([$coordinator_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $department_id = $row['department_id'];
    }

    if ($department_id) {
        $stmt = $conn->prepare("SELECT name FROM departments WHERE id = ?");
        $stmt->execute([$department_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $department_name = $row['name'];
        }
    }
}

if (!$department_id) {
    die("Error: No department assigned to this coordinator.");
}

function sanitize($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

$section_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($section_id <= 0) {
    die("Invalid section ID.");
}

$stmt = $conn->prepare("SELECT s.id, s.name, s.program_id, p.name AS program_name
                        FROM sections s
                        LEFT JOIN programs p ON s.program_id = p.id
                        WHERE s.id = ? AND s.department_id = ?");
$stmt->execute([$section_id, $department_id]);
$section = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$section) {
    die("Section not found or you do not have permission to edit it.");
}

// Load programs in this department for dropdown
$stmt = $conn->prepare("SELECT id, name FROM programs WHERE department_id = ? ORDER BY name");
$stmt->execute([$department_id]);
$programs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_section'])) {
        // Delete section and its students
        try {
            $conn->beginTransaction();

            $delStudents = $conn->prepare("DELETE FROM students WHERE section_id = ?");
            $delStudents->execute([$section_id]);

            $delSection = $conn->prepare("DELETE FROM sections WHERE id = ? AND department_id = ?");
            $delSection->execute([$section_id, $department_id]);

            $conn->commit();

            header('Location: coordinator_managesection.php?msg=' . urlencode('Section deleted successfully.'));
            exit;
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $error = 'Failed to delete section: ' . $e->getMessage();
        }
    } else {
        $name = trim($_POST['section_name'] ?? '');
        $program_id = (int)($_POST['program_id'] ?? 0);

        if ($name === '' || $program_id <= 0) {
            $error = 'Section name and program are required.';
        } else {
            $stmt = $conn->prepare("UPDATE sections SET name = ?, program_id = ? WHERE id = ? AND department_id = ?");
            if ($stmt->execute([$name, $program_id, $section_id, $department_id])) {
                $success = 'Section updated successfully.';

                // Refresh section data
                $stmt = $conn->prepare("SELECT s.id, s.name, s.program_id, p.name AS program_name
                                        FROM sections s
                                        LEFT JOIN programs p ON s.program_id = p.id
                                        WHERE s.id = ? AND s.department_id = ?");
                $stmt->execute([$section_id, $department_id]);
                $section = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $error = 'Failed to update section.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Section - <?php echo sanitize($department_name); ?></title>
    <link rel="stylesheet" href="<?php echo url_for('assets/css/dash.css'); ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
</head>
<body>
<header class="header">
    <img src="<?php echo url_for('assets/images/PLSP.png'); ?>" alt="Logo" class="header-logo">
    <div class="header-title">
        <h2><?php echo sanitize($department_name); ?> Department</h2>
        <p>Edit Section</p>
    </div>
    <div class="header-icons">
        <a href="#"><i class="bi bi-bell"></i></a>
        <a href="profile.php"><i class="bi bi-person-circle"></i></a>
    </div>
</header>
<div class="main-container">
    <nav class="sidebar">
        <ul>
            <li><a href="coordinator_panel.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
            <li><a href="coordinator_announcement.php"><i class="bi bi-megaphone"></i> Announcement</a></li>
            <li><a href="coordinator_monitor.php"><i class="bi bi-clipboard-data"></i> Monitor</a></li>
            <li><a href="coordinator_documents.php"><i class="bi bi-folder"></i> Documents</a></li>
            <li><a href="coordinator_managesection.php" class="active"><i class="bi bi-people"></i> Manage Section</a></li>
            <li><a href="coordinator_partnership.php"><i class="bi bi-handshake"></i> Partnership</a></li>
            <li><a href="../auth/logout.php"><i class="bi bi-box-arrow-right"></i> Log Out</a></li>
        </ul>
    </nav>

    <main class="dashboard-content">
        <h1>Edit Section</h1>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo sanitize($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo sanitize($success); ?></div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h3>Section Details</h3>
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label for="section_name" class="form-label">Section Name</label>
                        <input type="text" class="form-control" id="section_name" name="section_name" required
                               value="<?php echo sanitize($section['name'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="program_id" class="form-label">Program</label>
                        <select class="form-select" id="program_id" name="program_id" required>
                            <option value="">Select Program</option>
                            <?php foreach ($programs as $program): ?>
                                <option value="<?php echo $program['id']; ?>" <?php echo ($program['id'] == ($section['program_id'] ?? 0)) ? 'selected' : ''; ?>>
                                    <?php echo sanitize($program['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="d-flex justify-content-between mt-4">
                        <div>
                            <a href="coordinator_managesection.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Back
                            </a>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" name="delete_section" value="1" class="btn btn-danger"
                                    onclick="return confirm('Are you sure you want to delete this section and all its students?');">
                                <i class="bi bi-trash"></i> Delete Section
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Save Changes
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
