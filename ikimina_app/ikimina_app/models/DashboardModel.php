<?php
// models/DashboardModel.php
class DashboardModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getUserById($userId) {
        $stmt = $this->pdo->prepare("
            SELECT id, first_name, last_name, full_name, email, phone, profile_pic 
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            throw new Exception("User not found with ID: " . $userId);
        }
        
        return $user;
    }

    public function getUserStats($userId) {
        $stats = [];
        
        // Total groups count
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as total_groups 
            FROM group_members 
            WHERE user_id = ? AND status = 'active'
        ");
        $stmt->execute([$userId]);
        $stats['total_groups'] = (int)$stmt->fetch()['total_groups'];

        // Pending contributions count
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as pending_contributions 
            FROM contributions 
            WHERE member_id = ? AND status = 'pending'
        ");
        $stmt->execute([$userId]);
        $stats['pending_contributions'] = (int)$stmt->fetch()['pending_contributions'];

        // Total savings
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(savings_balance), 0) as total_savings 
            FROM group_members 
            WHERE user_id = ? AND status = 'active'
        ");
        $stmt->execute([$userId]);
        $stats['total_savings'] = (float)$stmt->fetch()['total_savings'];

        // Total contributions
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(amount), 0) as total_contributions 
            FROM contributions 
            WHERE member_id = ? AND status = 'verified'
        ");
        $stmt->execute([$userId]);
        $stats['total_contributions'] = (float)$stmt->fetch()['total_contributions'];

        // Active loans count
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as active_loans 
            FROM loans 
            WHERE member_id = ? AND status IN ('approved', 'disbursed')
        ");
        $stmt->execute([$userId]);
        $stats['active_loans'] = (int)$stmt->fetch()['active_loans'];

        // Interest earned (simplified calculation)
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(l.amount * l.interest_rate / 100), 0) as interest_earned 
            FROM loans l
            WHERE l.member_id = ? AND l.status IN ('disbursed', 'paid')
        ");
        $stmt->execute([$userId]);
        $stats['interest_earned'] = (float)$stmt->fetch()['interest_earned'];

        // Penalties
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(amount), 0) as penalties 
            FROM penalties 
            WHERE member_id = ? AND status = 'paid'
        ");
        $stmt->execute([$userId]);
        $stats['penalties'] = (float)$stmt->fetch()['penalties'];

        return $stats;
    }

    public function getUserGroups($userId) {
        $stmt = $this->pdo->prepare("
            SELECT 
                g.id as group_id,
                g.name as group_name,
                g.group_code,
                g.description,
                g.status as group_status,
                gm.role_in_group as role,
                gm.status as member_status,
                gm.savings_balance as balance,
                (SELECT COUNT(*) FROM group_members WHERE group_id = g.id AND status = 'active') as total_members,
                (SELECT COALESCE(SUM(savings_balance), 0) FROM group_members WHERE group_id = g.id AND status = 'active') as total_group_savings,
                (SELECT COALESCE(SUM(amount), 0) FROM contributions WHERE group_id = g.id AND status = 'verified') as total_contributions,
                (SELECT COUNT(*) FROM loans WHERE group_id = g.id AND status IN ('approved', 'disbursed')) as active_loans_count,
                (SELECT COALESCE(SUM(amount), 0) FROM contributions WHERE member_id = ? AND group_id = g.id AND status = 'verified') as my_contribution_amount
            FROM groups g
            JOIN group_members gm ON g.id = gm.group_id
            WHERE gm.user_id = ?
            ORDER BY 
                CASE gm.role_in_group 
                    WHEN 'president' THEN 1
                    WHEN 'vice_president' THEN 2
                    WHEN 'treasurer' THEN 3
                    WHEN 'secretary' THEN 4
                    WHEN 'loan_committee' THEN 5
                    ELSE 6
                END,
                gm.joined_at DESC
        ");
        $stmt->execute([$userId, $userId]);
        return $stmt->fetchAll();
    }

    public function getRecentActivities($userId) {
        $stmt = $this->pdo->prepare("
            SELECT 
                'contribution' as type,
                CONCAT('You made a contribution of ', FORMAT(c.amount, 0), ' RWF to ', g.name) as message,
                c.created_at,
                c.id as entity_id,
                'contribution' as entity_type,
                g.id as group_id
            FROM contributions c
            JOIN groups g ON c.group_id = g.id
            WHERE c.member_id = ? AND c.status = 'verified'
            
            UNION ALL
            
            SELECT 
                'loan' as type,
                CONCAT('Loan request for ', FORMAT(l.amount, 0), ' RWF was ', l.status) as message,
                l.created_at,
                l.id as entity_id,
                'loan' as entity_type,
                l.group_id as group_id
            FROM loans l
            WHERE l.member_id = ?
            
            UNION ALL
            
            SELECT 
                'group' as type,
                CONCAT('You joined ', g.name) as message,
                gm.joined_at as created_at,
                gm.group_id as entity_id,
                'group' as entity_type,
                gm.group_id as group_id
            FROM group_members gm
            JOIN groups g ON gm.group_id = g.id
            WHERE gm.user_id = ?
            
            ORDER BY created_at DESC
            LIMIT 5
        ");
        $stmt->execute([$userId, $userId, $userId]);
        return $stmt->fetchAll();
    }

    public function getUpcomingEvents($userId) {
        // Since there's no events table in your schema, return empty array
        // You can implement this later when you add an events table
        return [];
    }

    public function getGroupDetails($groupId) {
        $stmt = $this->pdo->prepare("
            SELECT 
                g.*,
                (SELECT COUNT(*) FROM group_members WHERE group_id = g.id AND status = 'active') as total_members,
                (SELECT COALESCE(SUM(savings_balance), 0) FROM group_members WHERE group_id = g.id AND status = 'active') as total_savings,
                (SELECT COALESCE(SUM(amount), 0) FROM contributions WHERE group_id = g.id AND status = 'verified') as total_contributions,
                (SELECT COUNT(*) FROM loans WHERE group_id = g.id AND status IN ('approved', 'disbursed')) as active_loans
            FROM groups g
            WHERE g.id = ?
        ");
        $stmt->execute([$groupId]);
        return $stmt->fetch();
    }
}
?>