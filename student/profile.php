<?php
session_start();
require_once __DIR__ . '/../paths.php';
require_once __DIR__ . '/../config/DBconfig.php';
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

// Fetch user info from DB
try {
    $stmt = $conn->prepare('SELECT username, email, role FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$_SESSION['username']]);
    $row = $stmt->fetch();
    if(!$row) {
        // User not found, logout for safety
        session_unset();
        session_destroy();
        header('Location: login.php');
        exit;
    }
    $username = $row['username'];
    $email = $row['email'];
    $role = $row['role'];
} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>User Profile</title>
<style>
    body {
        font-family: Arial, Helvetica, sans-serif;
        background: linear-gradient(to bottom, #006400, #008000);
        margin: 0;
        padding: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        color: white;
    }
    .profile-box {
        background: rgb(173, 173, 173);
        padding: 20px 30px;
        border-radius: 10px;
        width: 360px;
        box-shadow: 0 0 10px rgb(82, 82, 82);
        text-align: center;
    }
    h2 {
        margin-bottom: 20px;
    }
    p {
        font-size: 1.1rem;
        margin: 10px 0;
    }
    a.logout-btn {
        display: inline-block;
        margin-top: 20px;
        padding: 10px 30px;
        background: green;
        color: white;
        border-radius: 5px;
        text-decoration: none;
        font-weight: bold;
        transition: background 0.3s ease;
    }
    a.logout-btn:hover {
        background: darkgreen;
    }
</style>
</head>
<body>
    <div class="profile-box">
        <h2>Your Profile</h2>
        <p><strong>Username:</strong> <?php echo htmlspecialchars($username); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
        <p><strong>Role:</strong> <?php echo htmlspecialchars($role); ?></p>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</body>
</html>
