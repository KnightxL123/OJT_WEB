<?php
session_start();
require_once __DIR__ . '/../paths.php';
require_once __DIR__ . '/../db.php';

function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'user';
    
    // Role-specific fields
    $department_id = null;
    $program_id = null;
    $section_id = null;
    
    if ($role === 'coordinator') {
        $department_id = $_POST['department_id'] ?? null;
    } else { // adviser
        $department_id = $_POST['adviser_department_id'] ?? null;
        $program_id = $_POST['program_id'] ?? null;
        $section_id = $_POST['section_id'] ?? null;
    }

    // Basic validation
    if ($username === '' || $email === '' || $password === '') {
        redirect_to('auth/register.php?error=' . urlencode('Please fill all required fields.'));
        exit;
    }

    if (!is_valid_email($email)) {
        redirect_to('auth/register.php?error=' . urlencode('Invalid email address.'));
        exit;
    }

    if ($password !== $confirm_password) {
        redirect_to('auth/register.php?error=' . urlencode('Passwords do not match.'));
        exit;
    }

    if (strlen($password) < 6) {
        redirect_to('auth/register.php?error=' . urlencode('Password must be at least 6 characters.'));
        exit;
    }

    // Additional validation for coordinators
    if ($role === 'coordinator' && empty($department_id)) {
        redirect_to('auth/register.php?error=' . urlencode('Please select a department for coordinator role.'));
        exit;
    }

    // Additional validation for advisers
    if ($role === 'user') {
        if (empty($department_id)) {
            redirect_to('auth/register.php?error=' . urlencode('Please select a department.'));
            exit;
        }
        if (empty($program_id)) {
            redirect_to('auth/register.php?error=' . urlencode('Please select a program.'));
            exit;
        }
        if (empty($section_id)) {
            redirect_to('auth/register.php?error=' . urlencode('Please select a section.'));
            exit;
        }
    }

    // Check if department exists
    try {
        $stmt = $pdo->prepare("SELECT id FROM departments WHERE id = :id AND status = 'active' AND deleted_at IS NULL");
        $stmt->execute([':id' => $department_id]);
        if (!$stmt->fetch()) {
            redirect_to('auth/register.php?error=' . urlencode('Invalid department selected.'));
            exit;
        }
    } catch (PDOException $e) {
        redirect_to('auth/register.php?error=' . urlencode('Database error.'));
        exit;
    }

    // For advisers, check program and section
    if ($role === 'user') {
        // Check program exists and belongs to department
        try {
            $stmt = $pdo->prepare("SELECT id FROM programs WHERE id = :prog_id AND department_id = :dept_id AND status = 'active'");
            $stmt->execute([':prog_id' => $program_id, ':dept_id' => $department_id]);
            if (!$stmt->fetch()) {
                redirect_to('auth/register.php?error=' . urlencode('Invalid program selected.'));
                exit;
            }
            
            // Check section exists and belongs to program
            $stmt = $pdo->prepare("SELECT id FROM sections WHERE id = :sect_id AND program_id = :prog_id");
            $stmt->execute([':sect_id' => $section_id, ':prog_id' => $program_id]);
            if (!$stmt->fetch()) {
                redirect_to('auth/register.php?error=' . urlencode('Invalid section selected.'));
                exit;
            }
        } catch (PDOException $e) {
            redirect_to('auth/register.php?error=' . urlencode('Database error.'));
            exit;
        }
    }

    // Check username or email exists
    try {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :username OR email = :email');
        $stmt->execute([':username' => $username, ':email' => $email]);
        if ($stmt->fetch()) {
            redirect_to('auth/register.php?error=' . urlencode('Username or email already taken.'));
            exit;
        }
    } catch (PDOException $e) {
        redirect_to('auth/register.php?error=' . urlencode('Database error.'));
        exit;
    }

    // Hash password and insert new user
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        if ($role === 'coordinator') {
            $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, role, department_id) VALUES (:username, :email, :password_hash, :role, :department_id)');
            $stmt->execute([
                ':username' => $username,
                ':email' => $email,
                ':password_hash' => $password_hash,
                ':role' => $role,
                ':department_id' => $department_id
            ]);
            
            redirect_to('auth/login.php?msg=' . urlencode('Registration successful. Please login.'));
            exit;
        } else {
            // For advisers, we'll store the section_id in a separate table
            $pdo->beginTransaction();
            
            try {
                // Insert user
                $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, role) VALUES (:username, :email, :password_hash, :role)');
                $stmt->execute([
                    ':username' => $username,
                    ':email' => $email,
                    ':password_hash' => $password_hash,
                    ':role' => $role
                ]);
                $user_id = $pdo->lastInsertId();
                
                // Insert adviser
                $stmt = $pdo->prepare('INSERT INTO advisers (name, email, department_id) VALUES (:name, :email, :department_id)');
                $stmt->execute([
                    ':name' => $username,
                    ':email' => $email,
                    ':department_id' => $department_id
                ]);
                $adviser_id = $pdo->lastInsertId();
                
                // Link adviser to section
                $stmt = $pdo->prepare('INSERT INTO section_adviser (section_id, adviser_id) VALUES (:section_id, :adviser_id)');
                $stmt->execute([
                    ':section_id' => $section_id,
                    ':adviser_id' => $adviser_id
                ]);
                
                $pdo->commit();
                
                redirect_to('auth/login.php?msg=' . urlencode('Registration successful. Please login.'));
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                redirect_to('auth/register.php?error=' . urlencode('Registration failed. Try again.'));
                exit;
            }
        }
    } catch (PDOException $e) {
        redirect_to('auth/register.php?error=' . urlencode('Registration failed. Try again.'));
        exit;
    }
} else {
    redirect_to('auth/register.php');
    exit;
}
?>