<?php
// views/dashboards/group_dashboard.php - ROUTER/CONTROLLER
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['group_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$groupId = $_GET['group_id'];
$userId = $_SESSION['user_id'];

// Check user's role in this group
$stmt = $pdo->prepare("
    SELECT role_in_group, status 
    FROM group_members 
    WHERE group_id = ? AND user_id = ? AND status = 'active'
");
$stmt->execute([$groupId, $userId]);
$membership = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$membership) {
    header('Location: main_dashboard.php?error=not_member');
    exit;
}

// Route to appropriate dashboard based on role
$role = $membership['role_in_group'];
switch($role) {
    case 'president':
        header("Location: president_dashboard.php?group_id=$groupId");
        break;
    case 'vice_president':
        header("Location: vice_president_dashboard.php?group_id=$groupId");
        break;
    case 'treasurer':
        header("Location: treasurer_dashboard.php?group_id=$groupId");
        break;
    case 'secretary':
        header("Location: secretary_dashboard.php?group_id=$groupId");
        break;
    case 'loan_committee':
        header("Location: loan_committee_dashboard.php?group_id=$groupId");
        break;
    default:
        header("Location: member_dashboard.php?group_id=$groupId");
        break;
}
exit;
?>