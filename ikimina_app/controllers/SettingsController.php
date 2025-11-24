<?php
// controllers/SettingsController.php
require_once __DIR__ . '/../models/User.php';

class SettingsController {
    private $pdo;
    private $userId;
    private $userModel;

    public function __construct($pdo, $userId) {
        $this->pdo = $pdo;
        $this->userId = $userId;
        $this->userModel = new User($pdo);
    }

    /**
     * Get user settings data for display
     */
    public function viewSettings($userId = null) {
        $userId = $userId ?? $this->userId;

        // get user basic info
        $user = $this->userModel->getUserById($userId);

        // get settings stored in user record or separate table
        $sql = "SELECT * FROM user_settings WHERE user_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        $settings = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return ['user' => $user, 'settings' => $settings];
    }

    /**
     * Save/update settings from POST data
     */
    public function saveSettings($userId, $data) {
        $userId = $userId ?? $this->userId;

        try {
            // Begin transaction
            $this->pdo->beginTransaction();

            // Update user basic info
            $updateUserFields = ['first_name','middle_name','last_name','full_name','bio'];
            $updates = [];
            $params = [];
            foreach ($updateUserFields as $field) {
                if (isset($data[$field])) {
                    $updates[] = "$field = ?";
                    $params[] = $data[$field];
                }
            }

            if (!empty($updates)) {
                $params[] = $userId;
                $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
            }

            // Update user_settings table
            $settingFields = [
                'theme','display_size','font_scale','high_contrast',
                'language','date_format','currency','number_format',
                'twofa_enabled','hide_phone','show_last_active','contribution_visibility','loan_visibility',
                'notification_contribution','notification_loan','notification_meeting',
                'auto_download_receipts','data_usage_limit_mb','backup_to_cloud','custom_tone','mute_groups'
            ];

            $settingsData = [];
            foreach ($settingFields as $field) {
                if (isset($data[$field])) {
                    $settingsData[$field] = is_bool($data[$field]) ? (int)$data[$field] : $data[$field];
                } elseif (in_array($field, ['twofa_enabled','hide_phone','show_last_active','notification_contribution','notification_loan','notification_meeting','auto_download_receipts','backup_to_cloud'])) {
                    $settingsData[$field] = !empty($data[$field]) ? 1 : 0;
                }
            }

            // Check if settings exist
            $sql = "SELECT COUNT(*) FROM user_settings WHERE user_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId]);
            $exists = $stmt->fetchColumn() > 0;

            if ($exists) {
                $updates = [];
                $params = [];
                foreach ($settingsData as $key => $val) {
                    $updates[] = "$key = ?";
                    $params[] = $val;
                }
                $params[] = $userId;
                $sql = "UPDATE user_settings SET " . implode(', ', $updates) . " WHERE user_id = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
            } else {
                $keys = array_keys($settingsData);
                $placeholders = implode(',', array_fill(0, count($keys), '?'));
                $sql = "INSERT INTO user_settings (user_id," . implode(',', $keys) . ") VALUES (?" . str_repeat(',?', count($keys)) . ")";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute(array_merge([$userId], array_values($settingsData)));
            }

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    /**
     * Helper to get user info for display
     */
    public function getUserInfo() {
        return $this->userModel->getUserById($this->userId);
    }
}
?>
