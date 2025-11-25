<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/PresidentController.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['group_id']) || !isset($_GET['member_id']) || !isset($_GET['action'])) {
    header('Location: ../../views/auth/login.php');
    exit;
}

$groupId = $_GET['group_id'];
$userId = $_SESSION['user_id'];
$memberId = $_GET['member_id'];
$action = $_GET['action'];

try {
    $presidentController = new PresidentController($pdo, $groupId, $userId);
    $success = $presidentController->handleMemberApproval($memberId, $action);
    
    if ($success) {
        header('Location: ../../views/dashboards/president_dashboard.php?group_id=' . $groupId . '&success=Member ' . $action . 'ed successfully');
    } else {
        header('Location: ../../views/dashboards/president_dashboard.php?group_id=' . $groupId . '&error=Operation failed');
    }
} catch (Exception $e) {
    header('Location: ../../views/dashboards/president_dashboard.php?group_id=' . $groupId . '&error=' . urlencode($e->getMessage()));
}
?>