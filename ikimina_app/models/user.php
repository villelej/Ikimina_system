<?php
class User {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /* ==================== USER PROFILE METHODS ==================== */

    /**
     * Get user profile by user ID - ALWAYS returns array for dashboard compatibility
     */
    public function getUserProfile($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    u.id, u.first_name, u.middle_name, u.last_name, u.username,
                    u.email, u.phone, u.profile_pic, u.role_global, u.status,
                    u.created_at,
                    CONCAT(u.first_name, ' ', COALESCE(u.middle_name, ''), ' ', u.last_name) AS full_name
                FROM users u 
                WHERE u.id = ?
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                // Return default structure instead of throwing exception for dashboard compatibility
                return [
                    'first_name' => 'User',
                    'last_name' => '',
                    'username' => 'user',
                    'email' => 'user@example.com',
                    'phone' => '',
                    'role_global' => 'member',
                    'profile_pic' => null,
                    'status' => 'active',
                    'full_name' => 'User'
                ];
            }

            return $user;
        } catch (Exception $e) {
            error_log("getUserProfile error for ID $userId: " . $e->getMessage());
            // Return fallback data for dashboard compatibility
            return [
                'first_name' => 'User',
                'last_name' => '',
                'username' => 'user',
                'email' => 'user@example.com',
                'phone' => '',
                'role_global' => 'member',
                'profile_pic' => null,
                'status' => 'active',
                'full_name' => 'User'
            ];
        }
    }

    /**
     * Get user verification status for dashboard
     */
    public function getVerificationStatus($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    id_verified as email_verified,
                    phone_verified
                FROM users 
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ?: ['email_verified' => false, 'phone_verified' => false];
            
        } catch (Exception $e) {
            error_log("Verification status error: " . $e->getMessage());
            return ['email_verified' => false, 'phone_verified' => false];
        }
    }

    /**
     * Get display name for dashboard - handles all edge cases
     */
    public function getDisplayName($userData) {
        // Handle case where $userData might be empty or invalid
        if (!$userData || !is_array($userData)) {
            return "User";
        }
        
        // Use first_name + last_name if available
        if (!empty($userData['first_name']) || !empty($userData['last_name'])) {
            $name = trim(($userData['first_name'] ?? '') . ' ' . ($userData['last_name'] ?? ''));
            if (!empty($name)) {
                return $name;
            }
        }
        
        // Use username as fallback
        if (!empty($userData['username'])) {
            return $userData['username'];
        }

        // Use full_name as last resort
        if (!empty($userData['full_name'])) {
            return $userData['full_name'];
        }

        return "User";
    }

    /**
     * Update user profile
     */
    public function updateProfile($userId, $data) {
        try {
            $allowed = ['first_name', 'middle_name', 'last_name', 'email', 'phone', 'profile_pic'];
            $updates = [];
            $params = [];

            foreach ($data as $field => $value) {
                if (in_array($field, $allowed)) {
                    $updates[] = "$field = ?";
                    $params[] = $value;
                }
            }

            if (empty($updates)) {
                return false;
            }

            $params[] = $userId;
            $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
            
        } catch (Exception $e) {
            error_log("Update profile error: " . $e->getMessage());
            return false;
        }
    }

    /* ==================== AUTHENTICATION & FIND METHODS ==================== */

    /**
     * Find user by username, email, or phone
     */
    public function findByUsernameEmailOrPhone($identifier) {
        try {
            $sql = "SELECT * FROM users 
                    WHERE username = :identifier 
                       OR email = :identifier 
                       OR phone = :identifier
                    LIMIT 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['identifier' => $identifier]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Find user error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create new user
     */
    public function createUser($userData) {
        try {
            $sql = "INSERT INTO users (first_name, last_name, username, email, phone, password_hash, role_global, date_of_birth, gender) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                $userData['first_name'],
                $userData['last_name'],
                $userData['username'],
                $userData['email'],
                $userData['phone'],
                $userData['password_hash'],
                $userData['role_global'] ?? 'member',
                $userData['date_of_birth'] ?? null,
                $userData['gender'] ?? null
            ]);
            
            return $result ? $this->pdo->lastInsertId() : false;
            
        } catch (Exception $e) {
            error_log("Create user error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update user password
     */
    public function updatePassword($userId, $passwordHash) {
        try {
            $sql = "UPDATE users SET password_hash = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$passwordHash, $userId]);
            
        } catch (Exception $e) {
            error_log("Update password error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify user email
     */
    public function verifyEmail($userId) {
        try {
            $sql = "UPDATE users SET id_verified = 1 WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$userId]);
            
        } catch (Exception $e) {
            error_log("Verify email error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify user phone
     */
    public function verifyPhone($userId) {
        try {
            $sql = "UPDATE users SET phone_verified = 1 WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$userId]);
            
        } catch (Exception $e) {
            error_log("Verify phone error: " . $e->getMessage());
            return false;
        }
    }

    /* ==================== VALIDATION METHODS ==================== */

    /**
     * Check if username exists
     */
    public function usernameExists($username, $excludeUserId = null) {
        try {
            $sql = "SELECT id FROM users WHERE username = ?";
            $params = [$username];
            
            if ($excludeUserId) {
                $sql .= " AND id != ?";
                $params[] = $excludeUserId;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount() > 0;
            
        } catch (Exception $e) {
            error_log("Username exists check error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if email exists
     */
    public function emailExists($email, $excludeUserId = null) {
        try {
            $sql = "SELECT id FROM users WHERE email = ?";
            $params = [$email];
            
            if ($excludeUserId) {
                $sql .= " AND id != ?";
                $params[] = $excludeUserId;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount() > 0;
            
        } catch (Exception $e) {
            error_log("Email exists check error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if phone exists
     */
    public function phoneExists($phone, $excludeUserId = null) {
        try {
            $sql = "SELECT id FROM users WHERE phone = ?";
            $params = [$phone];
            
            if ($excludeUserId) {
                $sql .= " AND id != ?";
                $params[] = $excludeUserId;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount() > 0;
            
        } catch (Exception $e) {
            error_log("Phone exists check error: " . $e->getMessage());
            return false;
        }
    }

    /* ==================== USER MANAGEMENT METHODS ==================== */

    /**
     * Get user by ID
     */
    public function getUserById($userId) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Get user by ID error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update last login timestamp
     */
    public function updateLastLogin($userId) {
        try {
            $sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$userId]);
            
        } catch (Exception $e) {
            error_log("Update last login error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update user status
     */
    public function updateStatus($userId, $status) {
        try {
            $allowedStatuses = ['active', 'pending', 'suspended'];
            if (!in_array($status, $allowedStatuses)) {
                return false;
            }
            
            $sql = "UPDATE users SET status = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$status, $userId]);
            
        } catch (Exception $e) {
            error_log("Update status error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all users (for admin purposes)
     */
    public function getAllUsers($limit = 50, $offset = 0) {
        try {
            $sql = "SELECT id, first_name, last_name, username, email, phone, role_global, status, created_at 
                    FROM users 
                    ORDER BY created_at DESC 
                    LIMIT ? OFFSET ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$limit, $offset]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Get all users error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Search users by name, email, or phone
     */
    public function searchUsers($searchTerm, $limit = 20) {
        try {
            $sql = "SELECT id, first_name, last_name, username, email, phone, role_global, status, created_at 
                    FROM users 
                    WHERE first_name LIKE ? OR last_name LIKE ? OR username LIKE ? OR email LIKE ? OR phone LIKE ?
                    ORDER BY 
                        (first_name LIKE ? OR last_name LIKE ?) DESC,
                        created_at DESC
                    LIMIT ?";
            
            $searchParam = "%$searchTerm%";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $searchParam, $searchParam, $searchParam, $searchParam, $searchParam,
                $searchParam, $searchParam,
                $limit
            ]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Search users error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Count total users
     */
    public function countUsers($status = null) {
        try {
            $sql = "SELECT COUNT(*) as total FROM users";
            $params = [];
            
            if ($status) {
                $sql .= " WHERE status = ?";
                $params[] = $status;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? $result['total'] : 0;
            
        } catch (Exception $e) {
            error_log("Count users error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Delete user (soft delete by updating status)
     */
    public function deleteUser($userId) {
        try {
            return $this->updateStatus($userId, 'suspended');
            
        } catch (Exception $e) {
            error_log("Delete user error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user roles and permissions
     */
    public function getUserRoles($userId) {
        try {
            $sql = "SELECT r.role_name, r.description 
                    FROM user_roles ur 
                    JOIN roles r ON ur.role_id = r.id 
                    WHERE ur.user_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Get user roles error: " . $e->getMessage());
            return [];
        }
    }

    /* ==================== USER SETTINGS METHODS ==================== */

    /**
     * Get user settings with automatic creation if not exists
     */
    public function getUserSettings($userId) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM user_settings WHERE user_id = ?");
            $stmt->execute([$userId]);
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$settings) {
                // Insert default settings automatically
                $this->pdo->prepare("INSERT INTO user_settings (user_id) VALUES (?)")->execute([$userId]);
                return $this->getUserSettings($userId);
            }

            // Decode JSON fields
            $settings['mute_groups'] = $settings['mute_groups'] ? json_decode($settings['mute_groups'], true) : [];
            $settings['linked_devices'] = $settings['linked_devices'] ? json_decode($settings['linked_devices'], true) : [];

            return $settings;

        } catch (Exception $e) {
            error_log("getUserSettings: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update user settings
     */
    public function updateUserSettings($userId, $data) {
        try {
            $allowed = [
                'theme', 'display_size', 'font_scale', 'high_contrast', 'language', 'date_format',
                'currency', 'number_format', 'show_last_active', 'hide_phone',
                'contribution_visibility', 'loan_visibility',
                'notification_contribution', 'notification_loan', 'notification_meeting',
                'backup_to_cloud', 'auto_download_receipts', 'data_usage_limit_mb',
                'mute_groups', 'custom_tone', 'linked_devices'
            ];

            $updates = [];
            $params = [];

            foreach ($data as $field => $value) {
                if (!in_array($field, $allowed)) continue;

                if (in_array($field, ['mute_groups', 'linked_devices'])) {
                    $value = json_encode($value);
                }

                $updates[] = "$field = ?";
                $params[] = $value;
            }

            if (empty($updates)) return false;

            $params[] = $userId;
            $stmt = $this->pdo->prepare("UPDATE user_settings SET ".implode(',', $updates)." WHERE user_id = ?");
            return $stmt->execute($params);

        } catch (Exception $e) {
            error_log("updateUserSettings: " . $e->getMessage());
            return false;
        }
    }

    /* ==================== SECURITY METHODS ==================== */

    /**
     * Get security data with automatic creation if not exists
     */
    public function getSecurityData($userId) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM user_security WHERE user_id = ?");
            $stmt->execute([$userId]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$data) {
                $this->pdo->prepare("INSERT INTO user_security (user_id) VALUES (?)")->execute([$userId]);
                return $this->getSecurityData($userId);
            }

            $data['device_last_seen'] = $data['device_last_seen'] ? json_decode($data['device_last_seen'], true) : [];

            return $data;

        } catch (Exception $e) {
            error_log("getSecurityData: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Set login PIN
     */
    public function setLoginPin($userId, $plainPin) {
        $hashed = password_hash($plainPin, PASSWORD_BCRYPT);
        $stmt = $this->pdo->prepare("UPDATE user_security SET login_pin_hash = ? WHERE user_id = ?");
        return $stmt->execute([$hashed, $userId]);
    }

    /**
     * Verify login PIN
     */
    public function verifyLoginPin($userId, $plainPin) {
        $stmt = $this->pdo->prepare("SELECT login_pin_hash FROM user_security WHERE user_id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row && password_verify($plainPin, $row['login_pin_hash']);
    }

    /**
     * Enable two-factor authentication
     */
    public function enableTwoFactor($userId, $secret) {
        $stmt = $this->pdo->prepare("UPDATE user_security SET twofa_enabled = 1, twofa_secret = ? WHERE user_id = ?");
        return $stmt->execute([$secret, $userId]);
    }

    /**
     * Disable two-factor authentication
     */
    public function disableTwoFactor($userId) {
        $stmt = $this->pdo->prepare("UPDATE user_security SET twofa_enabled = 0, twofa_secret = NULL WHERE user_id = ?");
        return $stmt->execute([$userId]);
    }

    /**
     * Update last seen device
     */
    public function updateLastSeenDevice($userId, $deviceInfo) {
        $security = $this->getSecurityData($userId);
        $devices = $security['device_last_seen'] ?: [];

        $deviceInfo['last_seen'] = date("Y-m-d H:i:s");
        $devices[] = $deviceInfo;

        $stmt = $this->pdo->prepare("UPDATE user_security SET device_last_seen = ? WHERE user_id = ?");
        return $stmt->execute([json_encode($devices), $userId]);
    }
}
?>