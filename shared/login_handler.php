<?php
session_start();
require_once __DIR__ . '/../paths.php';
require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        redirect_to('auth/login.php?error=' . urlencode('Please enter username and password.'));
        exit;
    }

    try {
        $stmt = $pdo->prepare('SELECT id, username, password_hash, role, department_id FROM users WHERE username = :username LIMIT 1');
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if ($user) {
            if (password_verify($password, $user['password_hash'])) {
                // Password matches, set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['department_id'] = $user['department_id'];

                // Redirect based on role
                switch ($user['role']) {
                    case 'admin':
                        redirect_to('admin/admin_panel.php');
                        break;
                    case 'coordinator':
                        redirect_to('coordinator/coordinator_panel.php');
                        break;
                    default:
                        redirect_to('student/user_panel.php');
                }
                exit;
            }
        }
        
        // Invalid credentials
        redirect_to('auth/login.php?error=' . urlencode('Invalid username or password.'));
        exit;
    } catch (PDOException $e) {
        redirect_to('auth/login.php?error=' . urlencode('Database error. Please try again later.'));
        exit;
    }
} else {
    // Only POST requests allowed
    redirect_to('auth/login.php');
    exit;
}
?>