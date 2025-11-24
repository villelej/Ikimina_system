<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../PresidentController.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['group_id']) || !isset($_GET['loan_id'])) {
    header('Location: ../../views/auth/login.php');
    exit;
}

$groupId = $_GET['group_id'];
$userId = $_SESSION['user_id'];
$loanId = $_GET['loan_id'];

try {
    // This would update the loan status to 'disbursed' and record the disbursement
    $sql = "UPDATE loans SET status = 'disbursed', disbursed_by = ?, disbursed_at = NOW() 
            WHERE id = ? AND group_id = ? AND status = 'approved'";
    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute([$userId, $loanId, $groupId]);
    
    if ($success) {
        header('Location: ../../views/president/loan_management.php?group_id=' . $groupId . '&success=Loan disbursed successfully');
    } else {
        header('Location: ../../views/president/loan_management.php?group_id=' . $groupId . '&error=Failed to disburse loan');
    }
} catch (Exception $e) {
    header('Location: ../../views/president/loan_management.php?group_id=' . $groupId . '&error=' . urlencode($e->getMessage()));
}
?>