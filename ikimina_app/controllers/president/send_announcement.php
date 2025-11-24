<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/PresidentController.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['group_id']) || !isset($_POST['title']) || !isset($_POST['message'])) {
    header('Location: ../../views/auth/login.php');
    exit;
}

$groupId = $_POST['group_id'];
$userId = $_SESSION['user_id'];
$title = $_POST['title'];
$message = $_POST['message'];
$sendSms = isset($_POST['send_sms']) ? true : false;

try {
    $presidentController = new PresidentController($pdo, $groupId, $userId);
    $success = $presidentController->sendAnnouncement($title, $message, $sendSms);
    
    if ($success) {
        header('Location: ../../views/dashboards/president_dashboard.php?group_id=' . $groupId . '&success=Announcement sent successfully');
    } else {
        header('Location: ../../views/dashboards/president_dashboard.php?group_id=' . $groupId . '&error=Failed to send announcement');
    }
} catch (Exception $e) {
    header('Location: ../../views/dashboards/president_dashboard.php?group_id=' . $groupId . '&error=' . urlencode($e->getMessage()));
}
?>