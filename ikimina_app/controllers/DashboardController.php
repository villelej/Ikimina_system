<?php
// controllers/DashboardController.php

// ADD THESE REQUIRES AT THE TOP (if they're missing)
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Group.php';
require_once __DIR__ . '/../models/Notification.php';

class DashboardController {
    private $pdo;
    private $userModel;
    private $groupModel;
    private $notificationModel;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->userModel = new User($pdo);
        $this->groupModel = new Group($pdo);
        $this->notificationModel = new Notification($pdo);
    }
    
    public function getUserGroups($userId) {
        try {
            // Call the GroupModel method
            $groups = $this->groupModel->getUserGroups($userId);
            
            // Debug logging
            error_log("DashboardController::getUserGroups - Found: " . count($groups) . " groups");
            
            return $groups;
            
        } catch (Exception $e) {
            error_log("ERROR in DashboardController::getUserGroups: " . $e->getMessage());
            return [];
        }
    }
    public function getSummaryStats($userId) {
        // Total groups user belongs to
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as total_groups 
            FROM group_members 
            WHERE user_id = ? AND status = 'active'
        ");
        $stmt->execute([$userId]);
        $totalGroups = $stmt->fetch(PDO::FETCH_ASSOC)['total_groups'] ?? 0;
        
        // Pending contributions
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as pending_contributions 
            FROM contributions 
            WHERE user_id = ? AND status = 'pending'
        ");
        $stmt->execute([$userId]);
        $pendingContributions = $stmt->fetch(PDO::FETCH_ASSOC)['pending_contributions'] ?? 0;
        
        // Total savings across all groups
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(balance), 0) as total_savings 
            FROM group_members 
            WHERE user_id = ? AND status = 'active'
        ");
        $stmt->execute([$userId]);
        $totalSavings = $stmt->fetch(PDO::FETCH_ASSOC)['total_savings'] ?? 0;
        
        // Active loans count
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as active_loans 
            FROM loans 
            WHERE user_id = ? AND status IN ('active', 'pending')
        ");
        $stmt->execute([$userId]);
        $activeLoans = $stmt->fetch(PDO::FETCH_ASSOC)['active_loans'] ?? 0;
        
        // Total contributions
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(amount), 0) as total_contributions 
            FROM contributions 
            WHERE user_id = ? AND status = 'completed'
        ");
        $stmt->execute([$userId]);
        $totalContributions = $stmt->fetch(PDO::FETCH_ASSOC)['total_contributions'] ?? 0;
        
        // Interest earned
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(interest_amount), 0) as interest_earned 
            FROM loan_repayments 
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        $interestEarned = $stmt->fetch(PDO::FETCH_ASSOC)['interest_earned'] ?? 0;
        
        // Penalties
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(penalty_amount), 0) as penalties 
            FROM penalties 
            WHERE user_id = ? AND status = 'paid'
        ");
        $stmt->execute([$userId]);
        $penalties = $stmt->fetch(PDO::FETCH_ASSOC)['penalties'] ?? 0;
        
        // Net profit (simplified calculation)
        $netProfit = $interestEarned - $penalties;
        
        return [
            'total_groups' => $totalGroups,
            'pending_contributions' => $pendingContributions,
            'total_savings' => $totalSavings,
            'total_contributions' => $totalContributions,
            'active_loans' => $activeLoans,
            'interest_earned' => $interestEarned,
            'penalties' => $penalties,
            'net_profit' => $netProfit
        ];
    }

    
    public function getRecentActivities($userId, $limit = 10) {
        $stmt = $this->pdo->prepare("
            SELECT 
                'payment' as type,
                CONCAT('Payment of ', amount, ' RWF to ', g.name) as message,
                c.created_at
            FROM contributions c
            JOIN groups g ON c.group_id = g.id
            WHERE c.user_id = ? AND c.status = 'completed'
            
            UNION ALL
            
            SELECT 
                'announcement' as type,
                a.title as message,
                a.created_at
            FROM announcements a
            JOIN group_members gm ON a.group_id = gm.group_id
            WHERE gm.user_id = ?
            
            UNION ALL
            
            SELECT 
                'member' as type,
                CONCAT('New member joined ', g.name) as message,
                gm.created_at
            FROM group_members gm
            JOIN groups g ON gm.group_id = g.id
            WHERE gm.group_id IN (SELECT group_id FROM group_members WHERE user_id = ?)
            AND gm.user_id != ?
            
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$userId, $userId, $userId, $userId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getUpcomingEvents($userId) {
        $stmt = $this->pdo->prepare("
            SELECT 
                'contribution' as type,
                'Contribution Due' as title,
                c.due_date as event_date,
                g.name as group_name
            FROM contributions c
            JOIN groups g ON c.group_id = g.id
            WHERE c.user_id = ? AND c.status = 'pending' AND c.due_date >= CURDATE()
            
            UNION ALL
            
            SELECT 
                'loan_payment' as type,
                'Loan Payment Due' as title,
                lr.due_date as event_date,
                g.name as group_name
            FROM loan_repayments lr
            JOIN loans l ON lr.loan_id = l.id
            JOIN groups g ON l.group_id = g.id
            WHERE l.user_id = ? AND lr.status = 'pending' AND lr.due_date >= CURDATE()
            
            UNION ALL
            
            SELECT 
                'meeting' as type,
                m.title,
                m.meeting_date as event_date,
                g.name as group_name
            FROM meetings m
            JOIN groups g ON m.group_id = g.id
            JOIN group_members gm ON g.id = gm.group_id
            WHERE gm.user_id = ? AND m.meeting_date >= CURDATE()
            
            ORDER BY event_date ASC
            LIMIT 10
        ");
        $stmt->execute([$userId, $userId, $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getQuickActions() {
        return [
            ['icon' => 'add', 'label' => 'Create Group', 'url' => 'create_group.php'],
            ['icon' => 'group_add', 'label' => 'Join Group', 'url' => 'join_group.php'],
            ['icon' => 'payments', 'label' => 'Make Payment', 'url' => 'contributions.php'],
            ['icon' => 'request_quote', 'label' => 'Apply Loan', 'url' => 'loans.php'],
            ['icon' => 'event', 'label' => 'Create Event', 'url' => 'events.php'],
            ['icon' => 'analytics', 'label' => 'View Reports', 'url' => 'reports.php']
        ];
    }
    
    public function getRoleBadgeClass($role) {
        switch ($role) {
            case 'admin':
                return 'badge-admin';
            case 'treasurer':
                return 'badge-treasurer';
            case 'secretary':
                return 'badge-secretary';
            default:
                return 'badge-member';
        }
    }
    
    public function getAccountSummary($userId) {
        $stmt = $this->pdo->prepare("
            SELECT 
                (SELECT COUNT(*) FROM groups WHERE created_by = ?) as groups_created,
                (SELECT COUNT(*) FROM group_members WHERE user_id = ? AND status = 'active') as groups_joined,
                (SELECT COALESCE(SUM(amount), 0) FROM contributions WHERE user_id = ? AND status = 'completed') as total_contributions,
                (SELECT COALESCE(SUM(amount), 0) FROM loans WHERE user_id = ? AND status = 'active') as active_loans_amount
        ");
        $stmt->execute([$userId, $userId, $userId, $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>