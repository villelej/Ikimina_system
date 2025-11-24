<?php
// views/dashboards/loan_committee_dashboard.php
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
    
    if ($groupDetails['user_role'] !== 'loan_committee') {
        header('Location: main_dashboard.php?error=not_authorized');
        exit;
    }

    // Get pending loan applications
    $loansStmt = $pdo->prepare("
        SELECT l.*, u.first_name, u.last_name, u.profile_pic,
               gm.savings_balance, gm.loan_balance
        FROM loans l
        JOIN users u ON l.user_id = u.id
        JOIN group_members gm ON l.user_id = gm.user_id AND l.group_id = gm.group_id
        WHERE l.group_id = ? AND l.status = 'pending'
        ORDER BY l.created_at DESC
    ");
    $loansStmt->execute([$groupId]);
    $pendingLoans = $loansStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get active loans
    $activeLoansStmt = $pdo->prepare("
        SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total_amount
        FROM loans 
        WHERE group_id = ? AND status IN ('approved', 'disbursed')
    ");
    $activeLoansStmt->execute([$groupId]);
    $activeLoansData = $activeLoansStmt->fetch(PDO::FETCH_ASSOC);

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
            <h1>Loan Committee - <?= htmlspecialchars($groupDetails['name']) ?></h1>
            <small>Group Code: <?= htmlspecialchars($groupDetails['group_code']) ?></small>
        </div>
        <div class="topbar-right">
            <span class="badge" style="background: #7c3aed; color: white; padding: 0.5rem 1rem; border-radius: 20px;">
                <span class="material-icons-sharp">balance</span>
                Loan Committee
            </span>
        </div>
    </header>

    <div class="dashboard-container">
        <!-- Loan Statistics -->
        <div class="loan-stats">
            <h3>
                <span class="material-icons-sharp">analytics</span>
                Loan Portfolio
            </h3>
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="material-icons-sharp">pending_actions</span>
                    <h3><?= count($pendingLoans) ?></h3>
                    <p>Pending Applications</p>
                </div>
                <div class="stat-card">
                    <span class="material-icons-sharp">check_circle</span>
                    <h3><?= $activeLoansData['count'] ?? 0 ?></h3>
                    <p>Active Loans</p>
                </div>
                <div class="stat-card">
                    <span class="material-icons-sharp">savings</span>
                    <h3><?= number_format($activeLoansData['total_amount'] ?? 0, 0) ?> RWF</h3>
                    <p>Total Loan Portfolio</p>
                </div>
                <div class="stat-card">
                    <span class="material-icons-sharp">schedule</span>
                    <h3>0</h3>
                    <p>Overdue Loans</p>
                </div>
            </div>
        </div>

        <!-- Pending Loan Applications -->
        <?php if (!empty($pendingLoans)): ?>
        <div class="pending-loans-section">
            <h3>
                <span class="material-icons-sharp">pending_actions</span>
                Pending Loan Applications (<?= count($pendingLoans) ?>)
            </h3>
            <div class="loans-list">
                <?php foreach ($pendingLoans as $loan): ?>
                <div class="loan-card">
                    <div class="loan-applicant">
                        <img src="<?= htmlspecialchars($loan['profile_pic'] ?? '../../assets/images/default-avatar.jpg') ?>" 
                             class="profile-picture">
                        <div class="applicant-info">
                            <h4><?= htmlspecialchars($loan['first_name'] . ' ' . $loan['last_name']) ?></h4>
                            <div class="applicant-financials">
                                <small>Savings: <?= number_format($loan['savings_balance'], 0) ?> RWF</small>
                                <small>Existing Loans: <?= number_format($loan['loan_balance'], 0) ?> RWF</small>
                            </div>
                        </div>
                    </div>
                    <div class="loan-details">
                        <div class="loan-amount">
                            <strong><?= number_format($loan['amount'], 0) ?> RWF</strong>
                            <small>for <?= $loan['duration_months'] ?> months</small>
                        </div>
                        <div class="loan-purpose">
                            <p><?= htmlspecialchars($loan['purpose']) ?></p>
                            <small>Applied: <?= date('M j, Y', strtotime($loan['created_at'])) ?></small>
                        </div>
                    </div>
                    <div class="loan-actions">
                        <button class="btn btn-primary" onclick="reviewLoan(<?= $loan['id'] ?>)">
                            <span class="material-icons-sharp">visibility</span>
                            Review
                        </button>
                        <button class="btn btn-success" onclick="approveLoan(<?= $loan['id'] ?>)">
                            <span class="material-icons-sharp">check</span>
                            Approve
                        </button>
                        <button class="btn btn-outline" onclick="rejectLoan(<?= $loan['id'] ?>)">
                            <span class="material-icons-sharp">close</span>
                            Reject
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="no-pending-loans">
            <span class="material-icons-sharp">check_circle</span>
            <h3>No Pending Loan Applications</h3>
            <p>All loan applications have been processed</p>
        </div>
        <?php endif; ?>

        <!-- Loan Committee Controls -->
        <div class="admin-controls">
            <div class="admin-card" onclick="location.href='../groups/loan_applications.php?group_id=<?= $groupId ?>'">
                <span class="material-icons-sharp">list_alt</span>
                <h3>All Applications</h3>
                <p>View all loan applications</p>
            </div>
            
            <div class="admin-card" onclick="location.href='../groups/active_loans.php?group_id=<?= $groupId ?>'">
                <span class="material-icons-sharp">assignment</span>
                <h3>Active Loans</h3>
                <p>Monitor current loans</p>
            </div>
            
            <div class="admin-card" onclick="location.href='../groups/loan_policies.php?group_id=<?= $groupId ?>'">
                <span class="material-icons-sharp">policy</span>
                <h3>Loan Policies</h3>
                <p>Review lending rules</p>
            </div>
            
            <div class="admin-card" onclick="location.href='../groups/repayment_tracking.php?group_id=<?= $groupId ?>'">
                <span class="material-icons-sharp">payment</span>
                <h3>Repayment Tracking</h3>
                <p>Track loan repayments</p>
            </div>
        </div>
    </div>
</div>

<script>
function reviewLoan(loanId) {
    window.location.href = `../groups/review_loan.php?group_id=<?= $groupId ?>&loan_id=${loanId}`;
}

function approveLoan(loanId) {
    if (confirm('Are you sure you want to approve this loan?')) {
        window.location.href = `../../controllers/loans/approve_loan.php?group_id=<?= $groupId ?>&loan_id=${loanId}&action=approve`;
    }
}

function rejectLoan(loanId) {
    if (confirm('Are you sure you want to reject this loan?')) {
        window.location.href = `../../controllers/loans/approve_loan.php?group_id=<?= $groupId ?>&loan_id=${loanId}&action=reject`;
    }
}
</script>

<?php include_once __DIR__ . '/../partials/footer.php'; ?>