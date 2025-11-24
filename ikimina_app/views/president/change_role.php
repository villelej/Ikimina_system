<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../controllers/president/PresidentController.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['group_id']) || !isset($_POST['member_id']) || !isset($_POST['role'])) {
    header('Location: ../../views/auth/login.php');
    exit;
}

$groupId = $_POST['group_id'];
$userId = $_SESSION['user_id'];
$memberId = $_POST['member_id'];
$role = $_POST['role'];

try {
    $presidentController = new PresidentController($pdo, $groupId, $userId);
    $success = $presidentController->assignMemberRole($memberId, $role);
    
    if ($success) {
        header('Location: ../../views/president/member_management.php?group_id=' . $groupId . '&success=Member role updated successfully');
    } else {
        header('Location: ../../views/president/member_management.php?group_id=' . $groupId . '&error=Failed to update member role');
    }
} catch (Exception $e) {
    header('Location: ../../views/president/member_management.php?group_id=' . $groupId . '&error=' . urlencode($e->getMessage()));
}
?>