<?php
class PresidentModel {
    private $conn;
    private $table_name = "users";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Get president's group ID
    public function getPresidentGroup($president_id) {
        $query = "SELECT group_id FROM group_members 
                  WHERE user_id = :president_id 
                  AND role_in_group = 'president' 
                  AND status = 'active'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':president_id', $president_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get dashboard statistics
    public function getDashboardStats($group_id) {
        $stats = [];

        // Total members
        $query = "SELECT COUNT(*) as total_members 
                  FROM group_members 
                  WHERE group_id = :group_id AND status = 'active'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':group_id', $group_id);
        $stmt->execute();
        $stats['total_members'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_members'];

        // Total savings
        $query = "SELECT COALESCE(SUM(savings_balance), 0) as total_savings 
                  FROM group_members 
                  WHERE group_id = :group_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':group_id', $group_id);
        $stmt->execute();
        $stats['total_savings'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_savings'];

        // Active loans
        $query = "SELECT COUNT(*) as active_loans 
                  FROM loans 
                  WHERE group_id = :group_id 
                  AND status IN ('approved', 'disbursed')";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':group_id', $group_id);
        $stmt->execute();
        $stats['active_loans'] = $stmt->fetch(PDO::FETCH_ASSOC)['active_loans'];

        // Pending approvals
        $query = "SELECT 
                  (SELECT COUNT(*) FROM loans WHERE group_id = :group_id AND status = 'pending') +
                  (SELECT COUNT(*) FROM contributions WHERE group_id = :group_id AND status = 'pending') +
                  (SELECT COUNT(*) FROM group_members WHERE group_id = :group_id AND status = 'pending') as pending_approvals";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':group_id', $group_id);
        $stmt->execute();
        $stats['pending_approvals'] = $stmt->fetch(PDO::FETCH_ASSOC)['pending_approvals'];

        return $stats;
    }

    // Get recent activities
    public function getRecentActivities($group_id) {
        $query = "SELECT 
                  'contribution' as type, 
                  CONCAT('Contribution: RWF ', amount) as description,
                  created_at as date
                  FROM contributions 
                  WHERE group_id = :group_id 
                  UNION ALL
                  SELECT 
                  'loan' as type,
                  CONCAT('Loan Request: RWF ', amount) as description,
                  created_at as date
                  FROM loans 
                  WHERE group_id = :group_id 
                  ORDER BY date DESC 
                  LIMIT 10";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':group_id', $group_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get members list
    public function getMembers($group_id) {
        $query = "SELECT u.id, u.full_name, u.phone, u.email, 
                         gm.role_in_group, gm.savings_balance, gm.loan_balance,
                         gm.status, gm.joined_at
                  FROM users u
                  INNER JOIN group_members gm ON u.id = gm.user_id
                  WHERE gm.group_id = :group_id
                  ORDER BY gm.role_in_group, u.full_name";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':group_id', $group_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get pending loans
    public function getPendingLoans($group_id) {
        $query = "SELECT l.*, u.full_name, u.phone
                  FROM loans l
                  INNER JOIN users u ON l.member_id = u.id
                  WHERE l.group_id = :group_id AND l.status = 'pending'
                  ORDER BY l.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':group_id', $group_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Approve/reject loan
    public function updateLoanStatus($loan_id, $status, $president_id) {
        $query = "UPDATE loans 
                  SET status = :status, 
                      approved_by = :approved_by,
                      updated_at = NOW()
                  WHERE id = :loan_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':approved_by', $president_id);
        $stmt->bindParam(':loan_id', $loan_id);
        
        return $stmt->execute();
    }

    // Get financial overview
    public function getFinancialOverview($group_id) {
        $query = "SELECT 
                  COALESCE(SUM(gm.savings_balance), 0) as total_savings,
                  COALESCE(SUM(gm.loan_balance), 0) as total_loans,
                  COALESCE(SUM(gm.penalty_balance), 0) as total_penalties,
                  (SELECT COALESCE(SUM(amount), 0) FROM contributions 
                   WHERE group_id = :group_id AND status = 'verified' 
                   AND MONTH(created_at) = MONTH(CURRENT_DATE())) as monthly_contributions
                  FROM group_members gm
                  WHERE gm.group_id = :group_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':group_id', $group_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>