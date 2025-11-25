<?php
// controllers/groups/approve_member.php - CONTROLLER
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Group.php';
require_once __DIR__ . '/../controllers/GroupController.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../views/auth/login.php');
    exit;
}

$groupId = $_GET['group_id'] ?? null;
$targetUserId = $_GET['user_id'] ?? null;
$action = $_GET['action'] ?? ''; // 'approve' or 'reject'
$approverId = $_SESSION['user_id'];

if (!$groupId || !$targetUserId || !$action) {
    header('Location: ../../views/dashboards/main_dashboard.php?error=invalid_parameters');
    exit;
}

try {
    $groupController = new GroupController($pdo);
    
    if ($action === 'approve') {
        $result = $groupController->approveMember($groupId, $targetUserId, $approverId);
        $message = "Member approved successfully";
    } elseif ($action === 'reject') {
        // You need to add rejectMember method to your GroupController
        $result = $groupController->rejectMember($groupId, $targetUserId, $approverId);
        $message = "Member rejected successfully";
    } else {
        throw new Exception("Invalid action");
    }
    
    header("Location: ../../views/dashboards/president_dashboard.php?group_id=$groupId&success=" . urlencode($message));
    
} catch (Exception $e) {
    header("Location: ../../views/dashboards/president_dashboard.php?group_id=$groupId&error=" . urlencode($e->getMessage()));
}
exit;
?>