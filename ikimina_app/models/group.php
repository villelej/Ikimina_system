<?php
class Group {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    
public function getUserGroups($userId) {
    $sql = "SELECT 
                g.id as group_id,
                g.name as group_name,
                g.group_code,
                g.description,
                g.province as location,
                g.founding_document as logo,
                g.created_at,
                gm.role_in_group as role,
                gm.status,
                gm.joined_at,
                gm.savings_balance as balance,
                (SELECT COUNT(*) FROM group_members WHERE group_id = g.id AND status = 'active') as total_members,
                (SELECT COALESCE(SUM(savings_balance), 0) FROM group_members WHERE group_id = g.id AND status = 'active') as total_group_savings,
                (SELECT COUNT(*) FROM loans WHERE group_id = g.id AND status IN ('approved', 'disbursed')) as active_loans_count
            FROM groups g 
            INNER JOIN group_members gm ON g.id = gm.group_id 
            WHERE gm.user_id = ? 
            AND gm.status IN ('active', 'pending')
            ORDER BY gm.joined_at DESC";
            
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([$userId]);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // DEBUG: Log what we found
    error_log("GroupModel::getUserGroups - User: $userId, Found: " . count($result) . " groups");
    if (!empty($result)) {
        error_log("First group: " . json_encode($result[0]));
    }
    
    return $result;
}
    
    public function createGroup($data) {
    try {
        $this->pdo->beginTransaction();
        
        // Generate unique group code
        $groupCode = $this->generateGroupCode();
        
        // Insert group with ACTIVE status (not pending)
        $sql = "INSERT INTO groups (
            group_code, name, description, province, district, sector, cell, village,
            term_duration, objective, contribution_amount, contribution_method,
            loan_rules, investment_rules, founding_document, rca_number, created_by, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')"; // ✅ CHANGED 'pending' to 'active'
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $groupCode,
            $data['name'],
            $data['description'] ?? null,
            $data['province'] ?? null,
            $data['district'] ?? null,
            $data['sector'] ?? null,
            $data['cell'] ?? null,
            $data['village'] ?? null,
            $data['term_duration'] ?? null,
            $data['objective'] ?? null,
            $data['contribution_amount'] ?? null,
            $data['contribution_method'] ?? null,
            $data['loan_rules'] ?? null,
            $data['investment_rules'] ?? null,
            $data['founding_document'] ?? null,
            $data['rca_number'] ?? null,
            $data['created_by']
        ]);
        
        $groupId = $this->pdo->lastInsertId();
        
        // Add creator as president with ACTIVE status
       $this->addGroupMember($groupId, $data['created_by'], 'president', 'pending');

        
        // Create default group rules
        $this->createDefaultRules($groupId, $data['created_by']);
        
        $this->pdo->commit();
        
        // Send group code email
        $this->sendGroupCodeEmail($data['created_by'], $groupCode, $data['name']);
        
        return $groupId;
        
    } catch (Exception $e) {
        $this->pdo->rollBack();
        throw $e;
    }
}
    
    private function generateGroupCode() {
        $prefix = 'IKI';
        $unique = false;
        $code = '';
        
        while (!$unique) {
            $random = mt_rand(100, 999);
            $code = $prefix . $random;
            
            $stmt = $this->pdo->prepare("SELECT id FROM groups WHERE group_code = ?");
            $stmt->execute([$code]);
            $unique = $stmt->rowCount() === 0;
        }
        
        return $code;
    }
    
/**
 * Add a user to a group
 * @param int $groupId
 * @param int $userId
 * @param string $role ('member' or 'admin')
 * @param string|null $status Optional, default 'pending'. Admin is active in membership only
 * @return bool
 */
public function addGroupMember($groupId, $userId, $role = 'member', $status = null) {
    // Creator/admin membership is active, regular members are pending
    if ($role === 'admin' && $status === null) {
        $status = 'active'; // membership only
    } elseif ($status === null) {
        $status = 'pending';
    }

    $sql = "INSERT INTO group_members (user_id, group_id, role_in_group, status, joined_at) 
            VALUES (?, ?, ?, ?, NOW()) 
            ON DUPLICATE KEY UPDATE role_in_group = VALUES(role_in_group), status = VALUES(status)";
    
    $stmt = $this->pdo->prepare($sql);
    return $stmt->execute([$userId, $groupId, $role, $status]);
}

/**
 * Join a group as a regular member
 * @param int $groupId
 * @param int $userId
 * @param string $role Default 'member'
 * @return bool
 * @throws Exception if already joined and active
 */
public function joinGroup($groupId, $userId, $role = 'member') {
    // Check if the user is already in the group
    $stmt = $this->pdo->prepare("SELECT status FROM group_members WHERE user_id = ? AND group_id = ?");
    $stmt->execute([$userId, $groupId]);
    
    if ($stmt->rowCount() > 0) {
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($existing['status'] !== 'pending') {
            throw new Exception("You have already joined this group");
        }
        // If existing status is pending, do nothing
        return false;
    }

    // Add user as pending member
    return $this->addGroupMember($groupId, $userId, $role, 'pending');
}

    public function approveMember($groupId, $userId, $approvedBy) {
        $sql = "UPDATE group_members SET status = 'active', approved_at = NOW() 
                WHERE user_id = ? AND group_id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $success = $stmt->execute([$userId, $groupId]);
        
        if ($success) {
            $this->logMemberApproval($groupId, $userId, $approvedBy);
        }
        
        return $success;
    }
    
    public function getGroupByCode($groupCode) {
        $stmt = $this->pdo->prepare("
            SELECT g.*, u.first_name, u.last_name, u.email as admin_email
            FROM groups g 
            LEFT JOIN users u ON g.created_by = u.id 
            WHERE g.group_code = ? AND g.status = 'active'
        ");
        $stmt->execute([$groupCode]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /* ✅ ALIAS (Fixes Controller & View Compatibility) */
    public function getGroupsByUserId($userId) {
        return $this->getUserGroups($userId);
    }
    
    public function getGroupMembers($groupId, $status = 'active') {
        $sql = "
            SELECT u.id, u.first_name, u.last_name, u.email, u.phone, u.profile_pic,
                   gm.role_in_group, gm.status, gm.joined_at, gm.approved_at,
                   gm.savings_balance, gm.loan_balance, gm.penalty_balance
            FROM group_members gm
            INNER JOIN users u ON gm.user_id = u.id
            WHERE gm.group_id = ? AND gm.status = ?
            ORDER BY 
                CASE gm.role_in_group 
                    WHEN 'president' THEN 1
                    WHEN 'vice_president' THEN 2
                    WHEN 'treasurer' THEN 3
                    WHEN 'secretary' THEN 4
                    WHEN 'loan_committee' THEN 5
                    ELSE 6
                END,
                u.first_name
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$groupId, $status]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getPendingMembers($groupId) {
        return $this->getGroupMembers($groupId, 'pending');
    }
    
    public function isGroupAdmin($groupId, $userId) {
        $stmt = $this->pdo->prepare("
            SELECT role_in_group 
            FROM group_members 
            WHERE group_id = ? AND user_id = ? AND status = 'active'
        ");
        $stmt->execute([$groupId, $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result && in_array($result['role_in_group'], ['president', 'vice_president', 'treasurer']);
    }
    
    public function canApproveMembers($groupId, $userId) {
        $stmt = $this->pdo->prepare("
            SELECT role_in_group 
            FROM group_members 
            WHERE group_id = ? AND user_id = ? AND status = 'active'
        ");
        $stmt->execute([$groupId, $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result && in_array($result['role_in_group'], ['president', 'vice_president']);
    }
    
    private function createDefaultRules($groupId, $createdBy) {
        $sql = "INSERT INTO group_rules (group_id, min_contribution, contribution_deadline, late_fee, max_loan_multiplier, updated_by) 
                VALUES (?, 5000.00, 5, 1000.00, 3, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$groupId, $createdBy]);
    }
    
    private function sendGroupCodeEmail($userId, $groupCode, $groupName) {
        $stmt = $this->pdo->prepare("SELECT email, first_name FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $to = $user['email'];
            $subject = "Your Ikimina Group Code - " . $groupName;
            $message = "
Hello {$user['first_name']},

Your Ikimina group '{$groupName}' has been created successfully!

Group Code: {$groupCode}

Share this code with members you want to join your group.

Thank you!
            ";
            
            $headers = "From: no-reply@ikimina.com\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            @mail($to, $subject, $message, $headers);
        }
    }
    
    private function logMemberApproval($groupId, $userId, $approvedBy) {
        $sql = "INSERT INTO audit_logs (user_id, action, entity_type, entity_id, created_at) 
                VALUES (?, 'approve_member', 'group_members', ?, NOW())";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$approvedBy, $userId]);
    }
    
    public function updateGroup($groupId, $data) {
        $allowedFields = [
            'name', 'description', 'province', 'district', 'sector', 'cell', 'village',
            'term_duration', 'objective', 'contribution_amount', 'contribution_method',
            'loan_rules', 'investment_rules', 'founding_document', 'rca_number'
        ];
        
        $updates = [];
        $params = [];
        
        foreach ($data as $field => $value) {
            if (in_array($field, $allowedFields)) {
                $updates[] = "$field = ?";
                $params[] = $value;
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $params[] = $groupId;
        $sql = "UPDATE groups SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }
}
?>




