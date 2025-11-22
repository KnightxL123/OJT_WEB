<?php
session_start();
require_once __DIR__ . '/../paths.php';
require_once __DIR__ . '/../config/DBconfig.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'coordinator') {
    header('Location: login.php');
    exit;
}

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_id'])) {
        $student_id = intval($_POST['student_id']);
        $action = $_POST['action']; // 'approve' or 'reject'
        
        // Determine the status to set
        $status = ($action === 'approve') ? 'Approved' : 'Rejected';
        
        // Check if student exists
        $stmt = $conn->prepare("SELECT id FROM students WHERE id = ?");
        $stmt->execute([$student_id]);
        $result = $stmt->fetch();
        
        if (!$result) {
            throw new Exception("Student not found");
        }
        
        // Update or insert document status
        $sql = "INSERT INTO student_documents (
                    student_id, 
                    registration_status, 
                    monitoring_status, 
                    recommendation_status, 
                    acceptance_status, 
                    training_plan_status, 
                    waiver_status, 
                    moa_status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    registration_status = VALUES(registration_status),
                    monitoring_status = VALUES(monitoring_status),
                    recommendation_status = VALUES(recommendation_status),
                    acceptance_status = VALUES(acceptance_status),
                    training_plan_status = VALUES(training_plan_status),
                    waiver_status = VALUES(waiver_status),
                    moa_status = VALUES(moa_status)";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$student_id, $status, $status, $status, $status, $status, $status, $status]);
        
        echo json_encode(['success' => true, 'message' => 'Requirements updated successfully']);
        
        exit;
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
exit;
?>