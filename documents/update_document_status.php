<?php
session_start();
require_once __DIR__ . '/../paths.php';
require_once __DIR__ . '/../config/DBconfig.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'coordinator') {
    header('Location: login.php');
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['doc_id'])) {
        $doc_id = intval($_POST['doc_id']);
        $action = $_POST['action']; // 'approve' or 'reject'
        $document_type = $_POST['document_type']; // 'all' or specific document type
        
        // Determine the status to set
        $status = ($action === 'approve') ? 'Approved' : 'Rejected';
        
        if ($document_type === 'all') {
            // Update all document statuses for this student
            $sql = "UPDATE documents SET 
                    certificate_of_completion = ?,
                    daily_time_record = ?,
                    performance_evaluation = ?,
                    narrative_report = ?,
                    printed_journal = ?,
                    company_profile = ?,
                    ojt_evaluation_form = ?
                    WHERE id = ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([$status, $status, $status, $status, $status, $status, $status, $doc_id]);
        } else {
            // Update specific document type
            $sql = "UPDATE documents SET $document_type = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$status, $doc_id]);
        }
        
        $_SESSION['success'] = "Documents updated successfully";
        
        // Redirect back to the previous page
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }
    
} catch (PDOException $e) {
} catch (Exception $e) {
    $_SESSION['error'] = "Error: " . $e->getMessage();
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

header('Location: coordinator_documents.php');
exit;
?>