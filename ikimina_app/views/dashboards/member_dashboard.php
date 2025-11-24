<?php
// views/dashboards/member_dashboard.php - VIEW
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
    $userRole = $groupDetails['user_role'];
    
    // Get member-specific data
    $memberStmt = $pdo->prepare("
        SELECT savings_balance, loan_balance, penalty_balance 
        FROM group_members 
        WHERE group_id = ? AND user_id = ?
    ");
    $memberStmt->execute([$groupId, $userId]);
    $memberData = $memberStmt->fetch(PDO::FETCH_ASSOC);
    
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
            <h1><?= htmlspecialchars($groupDetails['name']) ?> - Member Dashboard</h1>
        </div>
        <div class="topbar-right">
            <span class="badge" style="background: #2563eb; color: white; padding: 0.5rem 1rem; border-radius: 20px;">
                <span class="material-icons-sharp">person</span>
                <?= ucfirst(str_replace('_', ' ', $userRole)) ?>
            </span>
        </div>
    </header>

    <div class="dashboard-container">
        <!-- Member-specific content -->
        <div class="member-stats">
            <div class="stat-card">
                <span class="material-icons-sharp">savings</span>
                <h3><?= number_format($memberData['savings_balance'] ?? 0, 0) ?> RWF</h3>
                <p>My Savings</p>
            </div>
            <div class="stat-card">
                <span class="material-icons-sharp">request_quote</span>
                <h3><?= number_format($memberData['loan_balance'] ?? 0, 0) ?> RWF</h3>
                <p>My Loans</p>
            </div>
            <div class="stat-card">
                <span class="material-icons-sharp">gavel</span>
                <h3><?= number_format($memberData['penalty_balance'] ?? 0, 0) ?> RWF</h3>
                <p>My Penalties</p>
            </div>
        </div>

        <!-- Member Actions -->
        <div class="member-actions">
            <a href="../groups/make_contribution.php?group_id=<?= $groupId ?>" class="btn btn-primary">
                <span class="material-icons-sharp">payments</span>
                Make Contribution
            </a>
            <a href="../groups/apply_loan.php?group_id=<?= $groupId ?>" class="btn btn-outline">
                <span class="material-icons-sharp">request_quote</span>
                Apply for Loan
            </a>
            <a href="../groups/my_contributions.php?group_id=<?= $groupId ?>" class="btn btn-outline">
                <span class="material-icons-sharp">history</span>
                My Contributions
            </a>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../partials/footer.php'; ?>