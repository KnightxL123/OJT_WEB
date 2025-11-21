<?php
session_start();
require_once __DIR__ . '/../paths.php';
require_once __DIR__ . '/../db.php';

// Redirect logged in users
if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['role']) {
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

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    try {
        // Fetch user by username
        $stmt = $pdo->prepare("SELECT id, username, password_hash, role, department_id FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();
        
        if ($user) {
            if (password_verify($password, $user['password_hash'])) {
                // For coordinators, check if their department is active
                if ($user['role'] === 'coordinator' && !empty($user['department_id'])) {
                    $stmt2 = $pdo->prepare("SELECT status FROM departments WHERE id = :id");
                    $stmt2->execute([':id' => $user['department_id']]);
                    $dept = $stmt2->fetch();
                    
                    if ($dept) {
                        if ($dept['status'] === 'inactive') {
                            $error = "Login failed: Your department has been deactivated. Please contact administrator.";
                        } else {
                            // Department is active, proceed with login
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['username'] = $user['username'];
                            $_SESSION['role'] = $user['role'];
                            $_SESSION['department_id'] = $user['department_id'];
                            
                            redirect_to('coordinator/coordinator_panel.php');
                        }
                    } else {
                        $error = "Login failed: Department not found.";
                    }
                } else {
                    // For admin and regular users, proceed normally
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    
                    if ($user['role'] === 'admin') {
                        redirect_to('admin/admin_panel.php');
                    } else {
                        redirect_to('student/user_panel.php');
                    }
                }
            } else {
                $error = "Invalid password.";
            }
        } else {
            $error = "User not found.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <link rel="stylesheet" href="<?php echo url_for('assets/css/auth/login.css'); ?>">
    <title>OJT Management System - Login</title>
    <style>
        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            text-align: center;
        }
        .error-message {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
        }
        .success-message {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-box">
            <img src="<?php echo url_for('assets/images/plsplogo.jpg'); ?>" alt="logo" class="logo">
            <h2>Login</h2>
            <?php
            // Display error from form processing
            if (!empty($error)) {
                echo '<div class="message error-message">' . htmlspecialchars($error) . '</div>';
            }
            
            // Display messages from URL parameters
            if (isset($_GET['error'])) {
                echo '<div class="message error-message">' . htmlspecialchars($_GET['error']) . '</div>';
            }
            if (isset($_GET['msg'])) {
                echo '<div class="message success-message">' . htmlspecialchars($_GET['msg']) . '</div>';
            }
            ?>
            <form method="POST" action="" autocomplete="off">
                <div class="form-group">
                    <input type="text" name="username" placeholder="Username" required autocomplete="username" />
                </div>
                <div class="form-group">
                    <input type="password" name="password" placeholder="Password" required autocomplete="current-password" />
                </div>
                <button type="submit" class="login-btn">Login</button>
            </form>
            <div class="links">
                <p><a href="password_reset_request.php">Forgot Password?</a></p>
                <p>Don't have an account? <a href="register.php">Register as Coordinator</a></p>
            </div>
        </div>
    </div>
</body>
</html>