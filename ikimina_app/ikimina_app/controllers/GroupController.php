<?php
require_once __DIR__ . '/../models/Group.php';
require_once __DIR__ . '/../models/User.php';

class GroupController {
    private $pdo;
    private $groupModel;
    private $userModel;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->groupModel = new Group($pdo);
        $this->userModel = new User($pdo);
    }
    
    public function createGroup($data) {
        $required = ['name', 'created_by'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }
        
        $user = $this->userModel->getUserProfile($data['created_by']);
        if (!$user) {
            throw new Exception("User not found");
        }
        
        return $this->groupModel->createGroup($data);
    }
    
    public function joinGroup($groupId, $userId, $role = 'member') {
        $stmt = $this->pdo->prepare("SELECT id, status FROM groups WHERE id = ?");
        $stmt->execute([$groupId]);
        $group = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$group) {
            throw new Exception("Group not found");
        }
        
        if ($group['status'] !== 'active') {
            throw new Exception("This group is not currently accepting members");
        }
        
        return $this->groupModel->joinGroup($groupId, $userId, $role);
    }
    
    public function joinGroupByCode($groupCode, $userId, $role = 'member') {
        $group = $this->groupModel->getGroupByCode($groupCode);
        
        if (!$group) {
            throw new Exception("Invalid group code");
        }
        
        return $this->joinGroup($group['id'], $userId, $role);
    }
    
    public function approveMember($groupId, $userId, $approvedBy) {
        if (!$this->groupModel->canApproveMembers($groupId, $approvedBy)) {
            throw new Exception("You don't have permission to approve members");
        }
        
        return $this->groupModel->approveMember($groupId, $userId, $approvedBy);
    }
    
    public function getGroupDetails($groupId, $userId) {
        $stmt = $this->pdo->prepare("
            SELECT gm.role_in_group, gm.status 
            FROM group_members gm 
            WHERE gm.group_id = ? AND gm.user_id = ?
        ");
        $stmt->execute([$groupId, $userId]);
        $membership = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$membership) {
            throw new Exception("You are not a member of this group");
        }
        
        $stmt = $this->pdo->prepare("
            SELECT g.*, 
                (SELECT COUNT(*) FROM group_members WHERE group_id = g.id AND status = 'active') as active_members,
                (SELECT COUNT(*) FROM group_members WHERE group_id = g.id AND status = 'pending') as pending_members,
                u.first_name as admin_first_name, u.last_name as admin_last_name
            FROM groups g
            LEFT JOIN users u ON g.created_by = u.id
            WHERE g.id = ?
        ");
        $stmt->execute([$groupId]);
        $group = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$group) {
            throw new Exception("Group not found");
        }
        
        $group['user_role'] = $membership['role_in_group'];
        $group['membership_status'] = $membership['status'];
        
        return $group;
    }
    
    public function getGroupMembers($groupId, $userId) {
        $stmt = $this->pdo->prepare("
            SELECT 1 FROM group_members 
            WHERE group_id = ? AND user_id = ? AND status = 'active'
        ");
        $stmt->execute([$groupId, $userId]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception("You are not a member of this group");
        }
        
        return $this->groupModel->getGroupMembers($groupId);
    }
    
    public function updateMemberRole($groupId, $targetUserId, $newRole, $changedBy) {
        if (!$this->groupModel->canApproveMembers($groupId, $changedBy)) {
            throw new Exception("You don't have permission to change member roles");
        }
        
        $stmt = $this->pdo->prepare("SELECT role_in_group FROM group_members WHERE group_id = ? AND user_id = ?");
        $stmt->execute([$groupId, $targetUserId]);
        $currentRole = $stmt->fetch(PDO::FETCH_ASSOC)['role_in_group'];
        
        if ($currentRole === 'president' && $newRole !== 'vice_president') {
            throw new Exception("Cannot remove president role. First assign vice president.");
        }
        
        $sql = "UPDATE group_members SET role_in_group = ? WHERE group_id = ? AND user_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$newRole, $groupId, $targetUserId]);
    }
    
    public function searchGroups($searchTerm) {
        $sql = "
            SELECT g.*, 
                (SELECT COUNT(*) FROM group_members WHERE group_id = g.id AND status = 'active') as member_count,
                u.first_name as admin_first_name, u.last_name as admin_last_name
            FROM groups g
            LEFT JOIN users u ON g.created_by = u.id
            WHERE g.status = 'active' 
            AND (g.name LIKE ? OR g.group_code LIKE ? OR g.description LIKE ?)
            ORDER BY g.name
            LIMIT 20
        ";
        
        $searchParam = "%$searchTerm%";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$searchParam, $searchParam, $searchParam]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ✅ NEW METHOD ADDED HERE
    public function getUserGroups($userId) {
        return $this->groupModel->getGroupsByUserId($userId);
    }

// Add to your existing GroupController class

public function getPendingMembers($groupId) {
    $stmt = $this->pdo->prepare("
        SELECT u.id, u.first_name, u.last_name, u.email, u.profile_pic, gm.joined_at
        FROM group_members gm
        JOIN users u ON gm.user_id = u.id
        WHERE gm.group_id = ? AND gm.status = 'pending'
    ");
    $stmt->execute([$groupId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


public function rejectMember($groupId, $userId, $rejectedBy) {
    if (!$this->groupModel->canApproveMembers($groupId, $rejectedBy)) {
        throw new Exception("You don't have permission to reject members");
    }
    
    $sql = "DELETE FROM group_members WHERE group_id = ? AND user_id = ? AND status = 'pending'";
    $stmt = $this->pdo->prepare($sql);
    return $stmt->execute([$groupId, $userId]);
}

}

?>
