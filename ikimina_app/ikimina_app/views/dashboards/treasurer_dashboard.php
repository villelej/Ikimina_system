<?php
// views/dashboards/treasurer_dashboard.php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../models/Group.php';
require_once __DIR__ . '/../../controllers/GroupController.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['group_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$groupId = $_GET['group_id'];
$userId = $_SESSION['user_id'];

$groupController = new GroupController($pdo);

try {
    $groupDetails = $groupController->getGroupDetails($groupId, $userId);
    
    if ($groupDetails['user_role'] !== 'treasurer') {
        header('Location: main_dashboard.php?error=not_authorized');
        exit;
    }

    // Get financial data
    $financeStmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(savings_balance), 0) as total_savings,
            COALESCE(SUM(loan_balance), 0) as total_loans,
            COALESCE(SUM(penalty_balance), 0) as total_penalties,
            COUNT(CASE WHEN loan_balance > 0 THEN 1 END) as active_loans_count,
            COUNT(*) as total_members
        FROM group_members 
        WHERE group_id = ? AND status = 'active'
    ");
    $financeStmt->execute([$groupId]);
    $financeData = $financeStmt->fetch(PDO::FETCH_ASSOC);

    // Get recent transactions
    $transactionsStmt = $pdo->prepare("
        SELECT t.*, u.first_name, u.last_name 
        FROM transactions t
        JOIN users u ON t.user_id = u.id
        WHERE t.group_id = ?
        ORDER BY t.created_at DESC 
        LIMIT 10
    ");
    $transactionsStmt->execute([$groupId]);
    $recentTransactions = $transactionsStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    header('Location: main_dashboard.php?error=' . urlencode($e->getMessage()));
    exit;
}

include_once __DIR__ . '/../partials/header.php';
?>

<div class="container">
    <header class="topbar">
        <div class="topbar-left">
            <a href="main_dashboard.php" class="btn btn-outline">
                <span class="material-icons-sharp">arrow_back</span>
                Back to Main Dashboard
            </a>
            <h1>Treasurer - <?= htmlspecialchars($groupDetails['name']) ?></h1>
            <small>Group Code: <?= htmlspecialchars($groupDetails['group_code']) ?></small>
        </div>
        <div class="topbar-right">
            <span class="badge" style="background: #059669; color: white; padding: 0.5rem 1rem; border-radius: 20px;">
                <span class="material-icons-sharp">account_balance</span>
                Treasurer
            </span>
        </div>
    </header>

    <div class="dashboard-container">
        <!-- Financial Overview -->
        <div class="finance-overview">
            <h3>
                <span class="material-icons-sharp">analytics</span>
                Financial Overview
            </h3>
            <div class="finance-stats">
                <div class="finance-card primary">
                    <span class="material-icons-sharp">savings</span>
                    <div>
                        <h2><?= number_format($financeData['total_savings'] ?? 0, 0) ?> RWF</h2>
                        <p>Total Group Savings</p>
                    </div>
                </div>
                <div class="finance-card warning">
                    <span class="material-icons-sharp">request_quote</span>
                    <div>
                        <h2><?= number_format($financeData['total_loans'] ?? 0, 0) ?> RWF</h2>
                        <p>Total Active Loans</p>
                    </div>
                </div>
                <div class="finance-card danger">
                    <span class="material-icons-sharp">gavel</span>
                    <div>
                        <h2><?= number_format($financeData['total_penalties'] ?? 0, 0) ?> RWF</h2>
                        <p>Total Penalties</p>
                    </div>
                </div>
                <div class="finance-card info">
                    <span class="material-icons-sharp">groups</span>
                    <div>
                        <h2><?= $financeData['active_loans_count'] ?? 0 ?></h2>
                        <p>Active Loans</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Treasurer Controls -->
        <div class="admin-controls">
            <div class="admin-card" onclick="location.href='../groups/contribution_tracking.php?group_id=<?= $groupId ?>'">
                <span class="material-icons-sharp">payments</span>
                <h3>Contribution Tracking</h3>
                <p>Track member contributions</p>
            </div>
            
            <div class="admin-card" onclick="location.href='../groups/loan_disbursement.php?group_id=<?= $groupId ?>'">
                <span class="material-icons-sharp">request_quote</span>
                <h3>Loan Disbursement</h3>
                <p>Disburse approved loans</p>
            </div>
            
            <div class="admin-card" onclick="location.href='../groups/financial_reports.php?group_id=<?= $groupId ?>'">
                <span class="material-icons-sharp">assessment</span>
                <h3>Financial Reports</h3>
                <p>Generate reports</p>
            </div>
            
            <div class="admin-card" onclick="location.href='../groups/penalty_management.php?group_id=<?= $groupId ?>'">
                <span class="material-icons-sharp">gavel</span>
                <h3>Penalty Management</h3>
                <p>Manage penalties</p>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="recent-transactions">
            <h3>
                <span class="material-icons-sharp">history</span>
                Recent Transactions
            </h3>
            <div class="transactions-list">
                <?php if (!empty($recentTransactions)): ?>
                    <?php foreach ($recentTransactions as $transaction): ?>
                    <div class="transaction-item">
                        <div class="transaction-info">
                            <h4><?= htmlspecialchars($transaction['first_name'] . ' ' . $transaction['last_name']) ?></h4>
                            <p><?= htmlspecialchars($transaction['description']) ?></p>
                            <small><?= date('M j, Y g:i A', strtotime($transaction['created_at'])) ?></small>
                        </div>
                        <div class="transaction-amount <?= $transaction['type'] === 'contribution' ? 'positive' : 'negative' ?>">
                            <?= number_format($transaction['amount'], 0) ?> RWF
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-transactions">
                        <span class="material-icons-sharp">receipt</span>
                        <p>No recent transactions</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../partials/footer.php'; ?>