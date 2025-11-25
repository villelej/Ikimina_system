<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../PresidentController.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['group_id'])) {
    header('Location: ../../views/auth/login.php');
    exit;
}

$groupId = $_POST['group_id'];
$userId = $_SESSION['user_id'];

try {
    $presidentController = new PresidentController($pdo, $groupId, $userId);
    
    // Remove group_id from POST data before passing to update method
    $settings = $_POST;
    unset($settings['group_id']);
    
    $success = $presidentController->updateGroupSettings($settings);
    
    if ($success) {
        header('Location: ../../views/president/group_settings.php?group_id=' . $groupId . '&success=Group settings updated successfully');
    } else {
        header('Location: ../../views/president/group_settings.php?group_id=' . $groupId . '&error=Failed to update group settings');
    }
} catch (Exception $e) {
    header('Location: ../../views/president/group_settings.php?group_id=' . $groupId . '&error=' . urlencode($e->getMessage()));
}
?>