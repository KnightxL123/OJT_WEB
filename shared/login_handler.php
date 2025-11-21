<?php
session_start();
require_once __DIR__ . '/../paths.php';

$host = 'localhost';
$dbname = 'OJT';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die('Connection failed: '.$conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        redirect_to('auth/login.php?error=' . urlencode('Please enter username and password.'));
        exit;
    }

    // Prepare statement with department_id included
    $stmt = $conn->prepare('SELECT id, username, password_hash, role, department_id FROM users WHERE username = ? LIMIT 1');
    if (!$stmt) {
        redirect_to('auth/login.php?error=' . urlencode('Database error. Please try again later.'));
        exit;
    }
    
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id, $uname, $password_hash, $role, $department_id);
        $stmt->fetch();

        if (password_verify($password, $password_hash)) {
            // Password matches, set session variables
            $_SESSION['user_id'] = $id;
            $_SESSION['username'] = $uname;
            $_SESSION['role'] = $role;
            $_SESSION['department_id'] = $department_id; // Store department_id for coordinators

            // Redirect based on role
            switch ($role) {
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
        } else {
            // Password doesn't match
            redirect_to('auth/login.php?error=' . urlencode('Invalid username or password.'));
            exit;
        }
    } else {
        // No matching user
        redirect_to('auth/login.php?error=' . urlencode('Invalid username or password.'));
        exit;
    }
} else {
    // Only POST requests allowed
    redirect_to('auth/login.php');
    exit;
}
?>