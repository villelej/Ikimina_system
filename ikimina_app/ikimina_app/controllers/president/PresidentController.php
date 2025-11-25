<?php
class PresidentController {
    private $pdo;
    private $groupId;
    private $userId;

    public function __construct($pdo, $groupId, $userId) {
        $this->pdo = $pdo;
        $this->groupId = $groupId;
        $this->userId = $userId;
    }

    // Verify user is president of the group
    private function verifyPresidentRole() {
        $sql = "SELECT role_in_group FROM group_members 
                WHERE user_id = ? AND group_id = ? AND status = 'active'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$this->userId, $this->groupId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result || $result['role_in_group'] !== 'president') {
            throw new Exception("Unauthorized: User is not president of this group");
        }
        return true;
    }

    // Get president dashboard data
    public function getDashboardData() {
        $this->verifyPresidentRole();
        
        $dashboardData = [];
        
        // Group basic info
        $sql = "SELECT g.*, 
                (SELECT COUNT(*) FROM group_members WHERE group_id = g.id AND status = 'active') as active_members,
                (SELECT COUNT(*) FROM group_members WHERE group_id = g.id AND status = 'pending') as pending_members
                FROM groups g WHERE g.id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$this->groupId]);
        $dashboardData['group_info'] = $stmt->fetch(PDO::FETCH_ASSOC);

        // Financial summary
        $dashboardData['financial_summary'] = $this->getFinancialSummary();
        
        // Pending approvals
        $dashboardData['pending_approvals'] = $this->getPendingApprovals();
        
        // Recent activities
        $dashboardData['recent_activities'] = $this->getRecentActivities();
        
        // Upcoming meetings
        $dashboardData['upcoming_meetings'] = $this->getUpcomingMeetings();
        
        return $dashboardData;
    }

    // Get financial summary
    private function getFinancialSummary() {
        $sql = "SELECT 
                COALESCE(SUM(amount), 0) as total_contributions,
                (SELECT COALESCE(SUM(amount), 0) FROM loans WHERE group_id = ? AND status IN ('approved', 'disbursed', 'paid')) as total_loans_issued,
                (SELECT COALESCE(SUM(amount), 0) FROM loans WHERE group_id = ? AND status IN ('approved', 'disbursed')) as active_loans_balance,
                (SELECT COUNT(*) FROM loans WHERE group_id = ? AND status = 'pending') as pending_loans,
                (SELECT COALESCE(SUM(amount), 0) FROM contributions WHERE group_id = ? AND status = 'verified') as group_balance
                FROM contributions 
                WHERE group_id = ? AND status = 'verified'";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$this->groupId, $this->groupId, $this->groupId, $this->groupId, $this->groupId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get pending approvals
    private function getPendingApprovals() {
        $approvals = [];
        
        // Pending members
        $sql = "SELECT u.id, u.first_name, u.last_name, u.email, u.phone, u.profile_pic, gm.joined_at 
                FROM group_members gm 
                JOIN users u ON gm.user_id = u.id 
                WHERE gm.group_id = ? AND gm.status = 'pending'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$this->groupId]);
        $approvals['members'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Pending loans
        $sql = "SELECT l.*, u.first_name, u.last_name, u.phone,
                gm.savings_balance, gm.loan_balance
                FROM loans l 
                JOIN users u ON l.member_id = u.id 
                JOIN group_members gm ON l.member_id = gm.user_id AND l.group_id = gm.group_id
                WHERE l.group_id = ? AND l.status = 'pending' 
                ORDER BY l.created_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$this->groupId]);
        $approvals['loans'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $approvals;
    }

    // Get recent activities
    private function getRecentActivities() {
        $sql = "SELECT 'contribution' as type, c.amount, u.first_name, u.last_name, c.created_at 
                FROM contributions c 
                JOIN users u ON c.member_id = u.id 
                WHERE c.group_id = ? AND c.status = 'verified'
                UNION ALL
                SELECT 'loan' as type, l.amount, u.first_name, u.last_name, l.created_at 
                FROM loans l 
                JOIN users u ON l.member_id = u.id 
                WHERE l.group_id = ? AND l.status IN ('approved', 'disbursed')
                UNION ALL
                SELECT 'repayment' as type, lr.amount, u.first_name, u.last_name, lr.created_at 
                FROM loan_repayments lr 
                JOIN loans l ON lr.loan_id = l.id 
                JOIN users u ON l.member_id = u.id 
                WHERE l.group_id = ?
                ORDER BY created_at DESC 
                LIMIT 10";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$this->groupId, $this->groupId, $this->groupId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get upcoming meetings
    private function getUpcomingMeetings() {
        // This would come from a meetings table - creating placeholder
        return [
            ['title' => 'Monthly General Meeting', 'date' => date('Y-m-d', strtotime('+7 days')), 'time' => '14:00'],
            ['title' => 'Loan Committee Meeting', 'date' => date('Y-m-d', strtotime('+3 days')), 'time' => '10:00']
        ];
    }

    // Approve/reject member
    public function handleMemberApproval($memberId, $action) {
        $this->verifyPresidentRole();
        
        if (!in_array($action, ['approve', 'reject'])) {
            throw new Exception("Invalid action");
        }

        $status = $action === 'approve' ? 'active' : 'rejected';
        
        $sql = "UPDATE group_members SET status = ?, approved_at = NOW() 
                WHERE user_id = ? AND group_id = ? AND status = 'pending'";
        $stmt = $this->pdo->prepare($sql);
        $success = $stmt->execute([$status, $memberId, $this->groupId]);

        if ($success && $action === 'approve') {
            $this->logActivity("Member approved", "User ID: $memberId");
            
            // Send notification to member
            $this->sendMemberNotification($memberId, 
                "Membership Approved", 
                "Your membership request has been approved. Welcome to the group!"
            );
        }

        return $success;
    }

    // Approve/reject loan
    public function handleLoanApproval($loanId, $action) {
        $this->verifyPresidentRole();
        
        if (!in_array($action, ['approve', 'reject'])) {
            throw new Exception("Invalid action");
        }

        $status = $action === 'approve' ? 'approved' : 'rejected';
        $approvedBy = $action === 'approve' ? $this->userId : null;
        
        $sql = "UPDATE loans SET status = ?, approved_by = ? 
                WHERE id = ? AND group_id = ? AND status = 'pending'";
        $stmt = $this->pdo->prepare($sql);
        $success = $stmt->execute([$status, $approvedBy, $loanId, $this->groupId]);

        if ($success) {
            $actionText = $action === 'approve' ? "approved" : "rejected";
            $this->logActivity("Loan $actionText", "Loan ID: $loanId");
            
            // Get loan details for notification
            $loan = $this->getLoanDetails($loanId);
            if ($loan) {
                $message = $action === 'approve' 
                    ? "Your loan request of RWF " . number_format($loan['amount']) . " has been approved!"
                    : "Your loan request of RWF " . number_format($loan['amount']) . " has been rejected.";
                
                $this->sendMemberNotification($loan['member_id'], "Loan $actionText", $message);
            }
        }

        return $success;
    }

    // Get loan details
    private function getLoanDetails($loanId) {
        $sql = "SELECT * FROM loans WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$loanId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Update group settings
    public function updateGroupSettings($settings) {
        $this->verifyPresidentRole();
        
        $allowedFields = [
            'name', 'description', 'contribution_amount', 'contribution_frequency',
            'contribution_method', 'province', 'district', 'sector', 'cell', 'village',
            'email', 'term_duration', 'objective', 'loan_rules', 'investment_rules'
        ];
        
        $updates = [];
        $params = [];
        
        foreach ($settings as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $updates[] = "$key = ?";
                $params[] = $value;
            }
        }
        
        if (empty($updates)) {
            throw new Exception("No valid fields to update");
        }
        
        $params[] = $this->groupId;
        $sql = "UPDATE groups SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $success = $stmt->execute($params);

        if ($success) {
            $this->logActivity("Group settings updated", json_encode($settings));
        }

        return $success;
    }

    // Assign role to member
    public function assignMemberRole($memberId, $role) {
        $this->verifyPresidentRole();
        
        $allowedRoles = ['member', 'treasurer', 'secretary', 'vice_president', 'loan_committee'];
        
        if (!in_array($role, $allowedRoles)) {
            throw new Exception("Invalid role");
        }

        // Check if member belongs to group
        $sql = "SELECT user_id FROM group_members 
                WHERE user_id = ? AND group_id = ? AND status = 'active'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$memberId, $this->groupId]);
        
        if (!$stmt->fetch()) {
            throw new Exception("Member not found in group");
        }

        $sql = "UPDATE group_members SET role_in_group = ? 
                WHERE user_id = ? AND group_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $success = $stmt->execute([$role, $memberId, $this->groupId]);

        if ($success) {
            $this->logActivity("Member role assigned", "User ID: $memberId, Role: $role");
            
            // Notify member of role change
            $this->sendMemberNotification($memberId, 
                "Role Updated", 
                "Your role has been updated to: " . ucfirst(str_replace('_', ' ', $role))
            );
        }

        return $success;
    }

    // Suspend member
    public function suspendMember($memberId, $reason) {
        $this->verifyPresidentRole();

        $sql = "UPDATE group_members SET status = 'suspended' 
                WHERE user_id = ? AND group_id = ? AND status = 'active'";
        $stmt = $this->pdo->prepare($sql);
        $success = $stmt->execute([$memberId, $this->groupId]);

        if ($success) {
            $this->logActivity("Member suspended", "User ID: $memberId, Reason: $reason");
            
            // Notify member
            $this->sendMemberNotification($memberId, 
                "Membership Suspended", 
                "Your membership has been suspended. Reason: $reason"
            );
        }

        return $success;
    }

    // Activate suspended member
    public function activateMember($memberId) {
        $this->verifyPresidentRole();

        $sql = "UPDATE group_members SET status = 'active' 
                WHERE user_id = ? AND group_id = ? AND status = 'suspended'";
        $stmt = $this->pdo->prepare($sql);
        $success = $stmt->execute([$memberId, $this->groupId]);

        if ($success) {
            $this->logActivity("Member activated", "User ID: $memberId");
            
            $this->sendMemberNotification($memberId, 
                "Membership Reactivated", 
                "Your membership has been reactivated. Welcome back!"
            );
        }

        return $success;
    }

    // Get group members with details
    public function getGroupMembers($status = 'active') {
        $this->verifyPresidentRole();
        
        $sql = "SELECT u.id, u.first_name, u.last_name, u.email, u.phone, u.profile_pic,
                gm.role_in_group, gm.status, gm.joined_at, gm.savings_balance, gm.loan_balance,
                (SELECT COUNT(*) FROM loans WHERE member_id = u.id AND group_id = ? AND status IN ('approved', 'disbursed')) as total_loans,
                (SELECT COALESCE(SUM(amount), 0) FROM contributions WHERE member_id = u.id AND group_id = ? AND status = 'verified') as total_contributions
                FROM group_members gm 
                JOIN users u ON gm.user_id = u.id 
                WHERE gm.group_id = ? AND gm.status = ?
                ORDER BY gm.role_in_group, u.first_name";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$this->groupId, $this->groupId, $this->groupId, $status]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get loan requests
    public function getLoanRequests($status = 'pending') {
        $this->verifyPresidentRole();
        
        $sql = "SELECT l.*, u.first_name, u.last_name, u.phone, u.profile_pic,
                gm.savings_balance, gm.loan_balance,
                (SELECT COALESCE(SUM(amount), 0) FROM loan_repayments WHERE loan_id = l.id) as amount_repaid
                FROM loans l 
                JOIN users u ON l.member_id = u.id 
                JOIN group_members gm ON l.member_id = gm.user_id AND l.group_id = gm.group_id
                WHERE l.group_id = ? AND l.status = ? 
                ORDER BY l.created_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$this->groupId, $status]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get group financial report
    public function getFinancialReport($startDate = null, $endDate = null) {
        $this->verifyPresidentRole();
        
        $whereClause = "WHERE group_id = ?";
        $params = [$this->groupId];
        
        if ($startDate && $endDate) {
            $whereClause .= " AND created_at BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }
        
        $report = [];
        
        // Contributions
        $sql = "SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total 
                FROM contributions 
                $whereClause AND status = 'verified'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $report['contributions'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Loans
        $sql = "SELECT 
                COUNT(*) as total_loans,
                COALESCE(SUM(CASE WHEN status IN ('approved', 'disbursed') THEN amount ELSE 0 END), 0) as active_loans,
                COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) as pending_loans
                FROM loans 
                $whereClause";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $report['loans'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Repayments
        $sql = "SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total 
                FROM loan_repayments lr 
                JOIN loans l ON lr.loan_id = l.id 
                WHERE l.group_id = ?";
        $repaymentParams = [$this->groupId];
        if ($startDate && $endDate) {
            $sql .= " AND lr.created_at BETWEEN ? AND ?";
            $repaymentParams[] = $startDate;
            $repaymentParams[] = $endDate;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($repaymentParams);
        $report['repayments'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $report;
    }

    // Send announcement to group
    public function sendAnnouncement($title, $message, $sendSms = false) {
        $this->verifyPresidentRole();
        
        $sql = "INSERT INTO broadcast_notifications (group_id, title, message, send_sms, created_by, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $this->pdo->prepare($sql);
        $success = $stmt->execute([$this->groupId, $title, $message, $sendSms ? 1 : 0, $this->userId]);
        
        if ($success) {
            $this->logActivity("Announcement sent", "Title: $title");
            
            // If SMS is enabled, you would integrate with SMS gateway here
            if ($sendSms) {
                $this->sendBulkSMS($message);
            }
        }
        
        return $success;
    }

    // Get member statistics
    public function getMemberStatistics() {
        $this->verifyPresidentRole();
        
        $sql = "SELECT 
                COUNT(*) as total_members,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_members,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_members,
                SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) as suspended_members,
                AVG(savings_balance) as avg_savings,
                SUM(loan_balance) as total_loan_balance
                FROM group_members 
                WHERE group_id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$this->groupId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Send notification to member
    private function sendMemberNotification($memberId, $title, $message) {
        $sql = "INSERT INTO notifications (user_id, title, message, type, created_at) 
                VALUES (?, ?, ?, 'system', NOW())";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$memberId, $title, $message]);
    }

    // Send bulk SMS (placeholder for SMS integration)
    private function sendBulkSMS($message) {
        // Integrate with SMS gateway like MTN, Airtel here
        // This is a placeholder implementation
        error_log("SMS would be sent to all group members: $message");
        return true;
    }

    // Log activity
    private function logActivity($action, $details = '') {
        $sql = "INSERT INTO audit_logs (user_id, action, entity_type, entity_id, new_values, created_at) 
                VALUES (?, ?, 'president_action', ?, ?, NOW())";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $this->userId, 
            $action, 
            $this->groupId,
            json_encode(['details' => $details, 'group_id' => $this->groupId])
        ]);
    }
}
?>