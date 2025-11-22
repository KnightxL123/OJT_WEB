<?php
session_start();
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['admin', 'user'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../paths.php';
require_once __DIR__ . '/../config/DBconfig.php'; 

// Get distinct partnership years from partners table 
try {
    $stmt = $conn->query("SELECT DISTINCT year FROM partners ORDER BY year"); 
    $years = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $years = [];
} 

function escape($str) { 
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8'); 
}
?> 

<!DOCTYPE html> 
<html lang="en"> 
<head>
    <meta charset="UTF-8"> 
    <title>Partnership</title> 
    <link rel="stylesheet" href="<?php echo url_for('assets/css/partnership.css'); ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"> 
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css"> 
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> 

    <style> 
        .handshake-grid { display: flex; flex-wrap: wrap; gap: 1rem; justify-content: center; } 
        .handshake-container { display: flex; flex-direction: column; align-items: center; width: 120px; background: #eee; padding: 10px; border-radius: 8px; text-decoration: none; color: #333; cursor: pointer; } 
        .handshake-container img { max-width: 100px; margin-bottom: 0.5rem; } 
    </style>
</head>
<body> 
<header class="header"> 
    <img src="<?php echo url_for('assets/images/PLSP.png'); ?>" alt="Logo" class="header-logo">
    <div class="search-bar"> <input type="text" placeholder="Search..."></div> 
    <div class="header-icons">
        <a href="#"><i class="bi bi-bell"></i></a> 
        <a href="<?php echo url_for('student/profile.php'); ?>"><i class="bi bi-person-circle"></i></a> 
    </div> 
</header> 

<div class="main-container"> 
    <nav class="sidebar"> 
        <ul> 
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <li><a href="<?php echo url_for('admin/admin_panel.php'); ?>"><i class="bi bi-speedometer2"></i> Dashboard</a></li> 
                <li><a href="<?php echo url_for('documents/documents.php'); ?>"><i class="bi bi-folder"></i> Documents</a></li> 
                <li><a href="<?php echo url_for('admin/monitoring.php'); ?>"><i class="bi bi-clipboard-data"></i> Monitoring</a></li> 
                <li><a href="<?php echo url_for('partnership/partnership.php'); ?>" class="active"><i class="bi bi-handshake"></i> Partnership</a></li>
                <li><a href="<?php echo url_for('admin/manage.php'); ?>"><i class="bi bi-diagram-3"></i> Departments</a></li>
                <li><a href="<?php echo url_for('announcements/admin_inbox.php'); ?>"><i class="bi bi-envelope-paper"></i> Sent Announcements</a></li>
                <li><a href="<?php echo url_for('auth/logout.php'); ?>"><i class="bi bi-box-arrow-right"></i> Log Out</a></li>
            <?php elseif ($_SESSION['role'] === 'user'): ?>
                <li><a href="<?php echo url_for('student/user_panel.php'); ?>"><i class="bi bi-person"></i> Dashboard</a></li>
                <li><a href="<?php echo url_for('documents/documents.php'); ?>"><i class="bi bi-folder"></i> Documents</a></li> 
                <li><a href="<?php echo url_for('student/monitoring.php'); ?>"><i class="bi bi-clipboard-data"></i> Monitoring</a></li> 
                <li><a href="<?php echo url_for('partnership/partnership.php'); ?>" class="active"><i class="bi bi-handshake"></i> Partnership</a></li>
                <li><a href="<?php echo url_for('student/manage.php'); ?>"><i class="bi bi-diagram-3"></i> Departments</a></li>
                <li><a href="<?php echo url_for('announcements/user_inbox.php'); ?>"><i class="bi bi-inbox"></i> Inbox</a></li>
                <li><a href="<?php echo url_for('auth/logout.php'); ?>"><i class="bi bi-box-arrow-right"></i> Log Out</a></li>
            <?php endif; ?>
        </ul> 
    </nav>

    <div class="content px-4 py-5 w-100">
        <div class="d-flex justify-content-end mb-4">
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="manage_partners.php" class="btn btn-primary">Manage Partners</a>
            <?php endif; ?>
        </div>

        <h2 class="text-center mb-4">Partnership Years</h2>

        <div class="handshake-grid">
            <?php if (empty($years)): ?>
                <p>No partnership years found.</p>
            <?php else: ?>
                <?php foreach ($years as $year): ?>
                    <a class="handshake-container" href="partners_year.php?year=<?php echo escape($year); ?>">
                        <img src="<?php echo url_for('assets/images/plsplogo.jpg'); ?>" alt="<?php echo escape($year); ?>">
                        <p><?php echo escape($year); ?></p>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div> 
</div>
</body>
</html>
