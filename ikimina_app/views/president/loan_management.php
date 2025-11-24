<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../controllers/president/PresidentController.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['group_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$groupId = $_GET['group_id'];
$userId = $_SESSION['user_id'];

$presidentController = new PresidentController($pdo, $groupId, $userId);

try {
    $pendingLoans = $presidentController->getLoanRequests('pending');
    $approvedLoans = $presidentController->getLoanRequests('approved');
    $activeLoans = $presidentController->getLoanRequests('disbursed');
    $groupInfo = $presidentController->getDashboardData()['group_info'];
    $financialSummary = $presidentController->getFinancialSummary();
    
} catch (Exception $e) {
    header('Location: ../dashboards/president_dashboard.php?group_id=' . $groupId . '&error=' . urlencode($e->getMessage()));
    exit;
}

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

include_once __DIR__ . '/../partials/header.php';
?>

<div class="container">
    <header class="topbar">
        <div class="topbar-left">
            <a href="../dashboards/president_dashboard.php?group_id=<?= $groupId ?>" class="btn btn-outline">
                <span class="material-icons-sharp">arrow_back</span>
                Back to Dashboard
            </a>
            <div class="group-info">
                <h1>Loan Management - <?= htmlspecialchars($groupInfo['name']) ?></h1>
                <small>Group Code: <?= htmlspecialchars($groupInfo['group_code']) ?></small>
            </div>
        </div>
        <div class="topbar-right">
            <span class="badge president-badge">
                <span class="material-icons-sharp">admin_panel_settings</span>
                President
            </span>
        </div>
    </header>

    <?php if ($success): ?>
    <div class="alert alert-success">
        <span class="material-icons-sharp">check_circle</span>
        <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-danger">
        <span class="material-icons-sharp">error</span>
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <!-- Loan Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <span class="material-icons-sharp">pending_actions</span>
            <h3><?= count($pendingLoans) ?></h3>
            <p>Pending Loans</p>
        </div>
        <div class="stat-card">
            <span class="material-icons-sharp">check_circle</span>
            <h3><?= count($approvedLoans) ?></h3>
            <p>Approved Loans</p>
        </div>
        <div class="stat-card">
            <span class="material-icons-sharp">trending_up</span>
            <h3>RWF <?= number_format($financialSummary['active_loans_balance'] ?? 0) ?></h3>
            <p>Active Loan Balance</p>
        </div>
        <div class="stat-card">
            <span class="material-icons-sharp">account_balance</span>
            <h3>RWF <?= number_format($financialSummary['total_loans_issued'] ?? 0) ?></h3>
            <p>Total Loans Issued</p>
        </div>
    </div>

    <div class="management-container">
        <!-- Tabs -->
        <div class="tabs">
            <button class="tab-button active" onclick="openTab('pending-loans')">
                <span class="material-icons-sharp">pending</span>
                Pending Loans (<?= count($pendingLoans) ?>)
            </button>
            <button class="tab-button" onclick="openTab('approved-loans')">
                <span class="material-icons-sharp">check_circle</span>
                Approved (<?= count($approvedLoans) ?>)
            </button>
            <button class="tab-button" onclick="openTab('active-loans')">
                <span class="material-icons-sharp">trending_up</span>
                Active Loans (<?= count($activeLoans) ?>)
            </button>
        </div>

        <!-- Pending Loans Tab -->
        <div id="pending-loans" class="tab-content active">
            <?php if (!empty($pendingLoans)): ?>
            <div class="loans-list">
                <?php foreach ($pendingLoans as $loan): ?>
                <div class="loan-card pending">
                    <div class="loan-header">
                        <div class="loan-applicant">
                            <img src="<?= htmlspecialchars($loan['profile_pic'] ?? '../../assets/images/default-avatar.jpg') ?>" 
                                 alt="<?= htmlspecialchars($loan['first_name']) ?>" class="applicant-avatar">
                            <div>
                                <h4><?= htmlspecialchars($loan['first_name'] . ' ' . $loan['last_name']) ?></h4>
                                <p><?= htmlspecialchars($loan['phone']) ?></p>
                            </div>
                        </div>
                        <div class="loan-amount">
                            <h3>RWF <?= number_format($loan['amount']) ?></h3>
                            <small>Requested <?= date('M j, Y', strtotime($loan['created_at'])) ?></small>
                        </div>
                    </div>
                    
                    <div class="loan-details">
                        <div class="detail-row">
                            <div class="detail-item">
                                <span class="label">Purpose</span>
                                <span class="value"><?= htmlspecialchars($loan['purpose'] ?? 'Not specified') ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Due Date</span>
                                <span class="value"><?= date('M j, Y', strtotime($loan['due_date'])) ?></span>
                            </div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-item">
                                <span class="label">Interest Rate</span>
                                <span class="value"><?= $loan['interest_rate'] ?>%</span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Member Savings</span>
                                <span class="value">RWF <?= number_format($loan['savings_balance']) ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="loan-actions">
                        <button class="btn btn-success" onclick="approveLoan(<?= $loan['id'] ?>)">
                            <span class="material-icons-sharp">check</span>
                            Approve Loan
                        </button>
                        <button class="btn btn-danger" onclick="rejectLoan(<?= $loan['id'] ?>)">
                            <span class="material-icons-sharp">close</span>
                            Reject Loan
                        </button>
                        <button class="btn btn-outline" onclick="viewLoanDetails(<?= $loan['id'] ?>)">
                            <span class="material-icons-sharp">visibility</span>
                            View Details
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <span class="material-icons-sharp">check_circle</span>
                <h3>No Pending Loans</h3>
                <p>All loan requests have been processed.</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Approved Loans Tab -->
        <div id="approved-loans" class="tab-content">
            <?php if (!empty($approvedLoans)): ?>
            <div class="loans-list">
                <?php foreach ($approvedLoans as $loan): ?>
                <div class="loan-card approved">
                    <div class="loan-header">
                        <div class="loan-applicant">
                            <img src="<?= htmlspecialchars($loan['profile_pic'] ?? '../../assets/images/default-avatar.jpg') ?>" 
                                 alt="<?= htmlspecialchars($loan['first_name']) ?>" class="applicant-avatar">
                            <div>
                                <h4><?= htmlspecialchars($loan['first_name'] . ' ' . $loan['last_name']) ?></h4>
                                <p><?= htmlspecialchars($loan['phone']) ?></p>
                            </div>
                        </div>
                        <div class="loan-amount">
                            <h3>RWF <?= number_format($loan['amount']) ?></h3>
                            <small>Approved <?= date('M j, Y', strtotime($loan['created_at'])) ?></small>
                        </div>
                    </div>
                    
                    <div class="loan-actions">
                        <button class="btn btn-primary" onclick="disburseLoan(<?= $loan['id'] ?>)">
                            <span class="material-icons-sharp">payments</span>
                            Disburse Funds
                        </button>
                        <button class="btn btn-outline" onclick="viewLoanDetails(<?= $loan['id'] ?>)">
                            <span class="material-icons-sharp">visibility</span>
                            View Details
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <span class="material-icons-sharp">inventory_2</span>
                <h3>No Approved Loans</h3>
                <p>There are no approved loans waiting for disbursement.</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Active Loans Tab -->
        <div id="active-loans" class="tab-content">
            <?php if (!empty($activeLoans)): ?>
            <div class="loans-table-container">
                <table class="loans-table">
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Loan Amount</th>
                            <th>Amount Repaid</th>
                            <th>Balance</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activeLoans as $loan): 
                            $balance = $loan['amount'] - $loan['amount_repaid'];
                            $dueDate = new DateTime($loan['due_date']);
                            $today = new DateTime();
                            $isOverdue = $dueDate < $today;
                        ?>
                        <tr>
                            <td>
                                <div class="member-cell">
                                    <img src="<?= htmlspecialchars($loan['profile_pic'] ?? '../../assets/images/default-avatar.jpg') ?>" 
                                         alt="<?= htmlspecialchars($loan['first_name']) ?>" class="applicant-avatar">
                                    <div>
                                        <strong><?= htmlspecialchars($loan['first_name'] . ' ' . $loan['last_name']) ?></strong>
                                        <small><?= htmlspecialchars($loan['phone']) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>RWF <?= number_format($loan['amount']) ?></td>
                            <td>RWF <?= number_format($loan['amount_repaid']) ?></td>
                            <td>
                                <strong>RWF <?= number_format($balance) ?></strong>
                            </td>
                            <td>
                                <span class="<?= $isOverdue ? 'text-danger' : '' ?>">
                                    <?= date('M j, Y', strtotime($loan['due_date'])) ?>
                                    <?php if ($isOverdue): ?>
                                    <br><small class="text-danger">Overdue</small>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge active">Active</span>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <button class="btn-icon" onclick="viewLoanDetails(<?= $loan['id'] ?>)" title="View Details">
                                        <span class="material-icons-sharp">visibility</span>
                                    </button>
                                    <button class="btn-icon" onclick="recordRepayment(<?= $loan['id'] ?>)" title="Record Repayment">
                                        <span class="material-icons-sharp">payments</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <span class="material-icons-sharp">trending_up</span>
                <h3>No Active Loans</h3>
                <p>There are no currently active loans.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Tab functionality
function openTab(tabName) {
    const tabContents = document.getElementsByClassName('tab-content');
    const tabButtons = document.getElementsByClassName('tab-button');
    
    for (let i = 0; i < tabContents.length; i++) {
        tabContents[i].classList.remove('active');
    }
    
    for (let i = 0; i < tabButtons.length; i++) {
        tabButtons[i].classList.remove('active');
    }
    
    document.getElementById(tabName).classList.add('active');
    event.currentTarget.classList.add('active');
}

// Action functions
function approveLoan(loanId) {
    if (confirm('Are you sure you want to approve this loan?')) {
        window.location.href = `../../controllers/president/approve_loan.php?group_id=<?= $groupId ?>&loan_id=${loanId}&action=approve`;
    }
}

function rejectLoan(loanId) {
    if (confirm('Are you sure you want to reject this loan?')) {
        window.location.href = `../../controllers/president/approve_loan.php?group_id=<?= $groupId ?>&loan_id=${loanId}&action=reject`;
    }
}

function disburseLoan(loanId) {
    if (confirm('Are you sure you want to disburse funds for this loan?')) {
        window.location.href = `../../controllers/president/disburse_loan.php?group_id=<?= $groupId ?>&loan_id=${loanId}`;
    }
}

function viewLoanDetails(loanId) {
    // Implement loan details view
    alert('Loan details view will be implemented here');
}

function recordRepayment(loanId) {
    // Implement record repayment
    alert('Record repayment feature will be implemented here');
}
</script>

<style>
.loans-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.loan-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 1.5rem;
    border-left: 4px solid #007bff;
}

.loan-card.pending {
    border-left-color: #f59e0b;
}

.loan-card.approved {
    border-left-color: #10b981;
}

.loan-header {
    display: flex;
    justify-content: between;
    align-items: start;
    margin-bottom: 1rem;
}

.loan-applicant {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex: 1;
}

.applicant-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
}

.loan-applicant h4 {
    margin: 0 0 0.25rem 0;
    color: #2c3e50;
}

.loan-applicant p {
    margin: 0;
    color: #6c757d;
    font-size: 0.9rem;
}

.loan-amount {
    text-align: right;
}

.loan-amount h3 {
    margin: 0 0 0.25rem 0;
    color: #059669;
}

.loan-amount small {
    color: #6c757d;
}

.loan-details {
    margin-bottom: 1.5rem;
}

.detail-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}

.detail-item {
    display: flex;
    flex-direction: column;
}

.detail-item .label {
    font-size: 0.8rem;
    color: #6c757d;
    margin-bottom: 0.25rem;
}

.detail-item .value {
    font-weight: 500;
    color: #2c3e50;
}

.loan-actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.loans-table-container {
    background: white;
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid #e9ecef;
}

.loans-table {
    width: 100%;
    border-collapse: collapse;
}

.loans-table th {
    background: #f8f9fa;
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: #2c3e50;
    border-bottom: 1px solid #e9ecef;
}

.loans-table td {
    padding: 1rem;
    border-bottom: 1px solid #f1f3f4;
}

.loans-table tr:last-child td {
    border-bottom: none;
}

.member-cell {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.member-cell strong {
    display: block;
    margin-bottom: 0.25rem;
}

.member-cell small {
    color: #6c757d;
    font-size: 0.8rem;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.status-badge.active {
    background: #d1fae5;
    color: #065f46;
}

.status-badge.pending {
    background: #fef3c7;
    color: #92400e;
}

.table-actions {
    display: flex;
    gap: 0.5rem;
}

.text-danger {
    color: #dc2626 !important;
}

@media (max-width: 768px) {
    .loan-header {
        flex-direction: column;
        gap: 1rem;
    }
    
    .loan-amount {
        text-align: left;
    }
    
    .detail-row {
        grid-template-columns: 1fr;
    }
    
    .loan-actions {
        flex-direction: column;
    }
    
    .loans-table-container {
        overflow-x: auto;
    }
}
</style>

<?php include_once __DIR__ . '/../partials/footer.php'; ?>