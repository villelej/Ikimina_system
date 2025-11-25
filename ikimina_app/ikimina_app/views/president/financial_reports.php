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

// Date range filter
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');

try {
    $financialReport = $presidentController->getFinancialReport($startDate, $endDate);
    $groupInfo = $presidentController->getDashboardData()['group_info'];
    $financialSummary = $presidentController->getFinancialSummary();
    
} catch (Exception $e) {
    header('Location: ../dashboards/president_dashboard.php?group_id=' . $groupId . '&error=' . urlencode($e->getMessage()));
    exit;
}

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
                <h1>Financial Reports - <?= htmlspecialchars($groupInfo['name']) ?></h1>
                <small>Group Code: <?= htmlspecialchars($groupInfo['group_code']) ?></small>
            </div>
        </div>
        <div class="topbar-right">
            <span class="badge president-badge">
                <span class="material-icons-sharp">admin_panel_settings</span>
                President
            </span>
            <button class="btn btn-primary" onclick="printReport()">
                <span class="material-icons-sharp">print</span>
                Print Report
            </button>
        </div>
    </header>

    <div class="reports-container">
        <!-- Date Filter -->
        <div class="filter-section">
            <form method="GET" class="date-filter-form">
                <input type="hidden" name="group_id" value="<?= $groupId ?>">
                
                <div class="filter-grid">
                    <div class="form-group">
                        <label for="startDate">From Date</label>
                        <input type="date" id="startDate" name="start_date" value="<?= $startDate ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="endDate">To Date</label>
                        <input type="date" id="endDate" name="end_date" value="<?= $endDate ?>" required>
                    </div>
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary">
                            <span class="material-icons-sharp">filter_alt</span>
                            Apply Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Financial Overview -->
        <div class="report-section">
            <h3>Financial Overview</h3>
            <div class="overview-cards">
                <div class="overview-card">
                    <div class="overview-icon">
                        <span class="material-icons-sharp">account_balance</span>
                    </div>
                    <div class="overview-content">
                        <h4>RWF <?= number_format($financialSummary['group_balance'] ?? 0) ?></h4>
                        <p>Total Group Balance</p>
                    </div>
                </div>
                <div class="overview-card">
                    <div class="overview-icon">
                        <span class="material-icons-sharp">savings</span>
                    </div>
                    <div class="overview-content">
                        <h4>RWF <?= number_format($financialReport['contributions']['total'] ?? 0) ?></h4>
                        <p>Contributions (Period)</p>
                        <small><?= $financialReport['contributions']['count'] ?? 0 ?> transactions</small>
                    </div>
                </div>
                <div class="overview-card">
                    <div class="overview-icon">
                        <span class="material-icons-sharp">request_quote</span>
                    </div>
                    <div class="overview-content">
                        <h4>RWF <?= number_format($financialReport['loans']['active_loans'] ?? 0) ?></h4>
                        <p>Active Loans</p>
                        <small><?= $financialReport['loans']['pending_loans'] ?? 0 ?> pending</small>
                    </div>
                </div>
                <div class="overview-card">
                    <div class="overview-icon">
                        <span class="material-icons-sharp">payments</span>
                    </div>
                    <div class="overview-content">
                        <h4>RWF <?= number_format($financialReport['repayments']['total'] ?? 0) ?></h4>
                        <p>Loan Repayments</p>
                        <small><?= $financialReport['repayments']['count'] ?? 0 ?> payments</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Reports -->
        <div class="report-section">
            <h3>Detailed Reports</h3>
            
            <!-- Contributions Report -->
            <div class="report-card">
                <div class="report-header">
                    <h4>
                        <span class="material-icons-sharp">savings</span>
                        Contributions Report
                    </h4>
                    <span class="report-period"><?= date('M j, Y', strtotime($startDate)) ?> - <?= date('M j, Y', strtotime($endDate)) ?></span>
                </div>
                <div class="report-stats">
                    <div class="stat-item">
                        <span class="stat-label">Total Contributions</span>
                        <span class="stat-value">RWF <?= number_format($financialReport['contributions']['total'] ?? 0) ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Number of Transactions</span>
                        <span class="stat-value"><?= $financialReport['contributions']['count'] ?? 0 ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Average per Member</span>
                        <span class="stat-value">RWF <?= number_format(($financialReport['contributions']['total'] ?? 0) / max(($financialReport['contributions']['count'] ?? 1), 1)) ?></span>
                    </div>
                </div>
            </div>

            <!-- Loans Report -->
            <div class="report-card">
                <div class="report-header">
                    <h4>
                        <span class="material-icons-sharp">request_quote</span>
                        Loans Report
                    </h4>
                    <span class="report-period"><?= date('M j, Y', strtotime($startDate)) ?> - <?= date('M j, Y', strtotime($endDate)) ?></span>
                </div>
                <div class="report-stats">
                    <div class="stat-item">
                        <span class="stat-label">Total Loans Issued</span>
                        <span class="stat-value">RWF <?= number_format($financialSummary['total_loans_issued'] ?? 0) ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Active Loan Balance</span>
                        <span class="stat-value">RWF <?= number_format($financialSummary['active_loans_balance'] ?? 0) ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Pending Loan Requests</span>
                        <span class="stat-value"><?= $financialReport['loans']['pending_loans'] ?? 0 ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Total Loans Processed</span>
                        <span class="stat-value"><?= $financialReport['loans']['total_loans'] ?? 0 ?></span>
                    </div>
                </div>
            </div>

            <!-- Repayments Report -->
            <div class="report-card">
                <div class="report-header">
                    <h4>
                        <span class="material-icons-sharp">payments</span>
                        Repayments Report
                    </h4>
                    <span class="report-period"><?= date('M j, Y', strtotime($startDate)) ?> - <?= date('M j, Y', strtotime($endDate)) ?></span>
                </div>
                <div class="report-stats">
                    <div class="stat-item">
                        <span class="stat-label">Total Repayments</span>
                        <span class="stat-value">RWF <?= number_format($financialReport['repayments']['total'] ?? 0) ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Number of Payments</span>
                        <span class="stat-value"><?= $financialReport['repayments']['count'] ?? 0 ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Average Payment</span>
                        <span class="stat-value">RWF <?= number_format(($financialReport['repayments']['total'] ?? 0) / max(($financialReport['repayments']['count'] ?? 1), 1)) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Charts Placeholder -->
        <div class="report-section">
            <h3>Financial Trends</h3>
            <div class="charts-grid">
                <div class="chart-card">
                    <h5>Contributions Trend</h5>
                    <div class="chart-placeholder">
                        <span class="material-icons-sharp">bar_chart</span>
                        <p>Contributions chart will be displayed here</p>
                    </div>
                </div>
                <div class="chart-card">
                    <h5>Loans Distribution</h5>
                    <div class="chart-placeholder">
                        <span class="material-icons-sharp">pie_chart</span>
                        <p>Loans distribution chart will be displayed here</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Export Options -->
        <div class="report-section">
            <h3>Export Reports</h3>
            <div class="export-options">
                <button class="btn btn-outline" onclick="exportPDF()">
                    <span class="material-icons-sharp">picture_as_pdf</span>
                    Export as PDF
                </button>
                <button class="btn btn-outline" onclick="exportExcel()">
                    <span class="material-icons-sharp">table_chart</span>
                    Export as Excel
                </button>
                <button class="btn btn-outline" onclick="exportCSV()">
                    <span class="material-icons-sharp">text_snippet</span>
                    Export as CSV
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function printReport() {
    window.print();
}

function exportPDF() {
    alert('PDF export functionality will be implemented here');
    // Implement PDF export
}

function exportExcel() {
    alert('Excel export functionality will be implemented here');
    // Implement Excel export
}

function exportCSV() {
    alert('CSV export functionality will be implemented here');
    // Implement CSV export
}

// Date validation
document.querySelector('.date-filter-form').addEventListener('submit', function(e) {
    const startDate = new Date(document.getElementById('startDate').value);
    const endDate = new Date(document.getElementById('endDate').value);
    
    if (startDate > endDate) {
        e.preventDefault();
        alert('Start date cannot be after end date');
        return false;
    }
    
    const diffTime = Math.abs(endDate - startDate);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffDays > 365) {
        if (!confirm('You are viewing a report for more than 1 year. This might take longer to load. Continue?')) {
            e.preventDefault();
            return false;
        }
    }
    
    return true;
});
</script>

<style>
.reports-container {
    margin-top: 2rem;
}

.filter-section {
    background: white;
    padding: 1.5rem;
    border-radius: 10px;
    margin-bottom: 2rem;
    border: 1px solid #e9ecef;
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    align-items: end;
}

.report-section {
    margin-bottom: 2rem;
}

.report-section h3 {
    color: #2c3e50;
    margin-bottom: 1.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #f1f3f4;
}

.overview-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.overview-card {
    background: white;
    padding: 1.5rem;
    border-radius: 10px;
    border: 1px solid #e9ecef;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: transform 0.2s;
}

.overview-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.overview-icon {
    background: #007bff;
    color: white;
    padding: 1rem;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.overview-content h4 {
    margin: 0 0 0.5rem 0;
    color: #2c3e50;
    font-size: 1.5rem;
}

.overview-content p {
    margin: 0 0 0.25rem 0;
    color: #6c757d;
    font-weight: 500;
}

.overview-content small {
    color: #6c757d;
    font-size: 0.8rem;
}

.report-card {
    background: white;
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    border: 1px solid #e9ecef;
}

.report-header {
    display: flex;
    justify-content: between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #f1f3f4;
}

.report-header h4 {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #2c3e50;
}

.report-period {
    color: #6c757d;
    font-size: 0.9rem;
}

.report-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
}

.stat-item {
    display: flex;
    flex-direction: column;
}

.stat-label {
    font-size: 0.9rem;
    color: #6c757d;
    margin-bottom: 0.5rem;
}

.stat-value {
    font-size: 1.25rem;
    font-weight: 600;
    color: #2c3e50;
}

.charts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 1.5rem;
}

.chart-card {
    background: white;
    padding: 1.5rem;
    border-radius: 10px;
    border: 1px solid #e9ecef;
}

.chart-card h5 {
    margin: 0 0 1rem 0;
    color: #2c3e50;
}

.chart-placeholder {
    height: 200px;
    background: #f8f9fa;
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #6c757d;
}

.chart-placeholder .material-icons-sharp {
    font-size: 3rem;
    margin-bottom: 1rem;
    color: #adb5bd;
}

.export-options {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .overview-cards {
        grid-template-columns: 1fr;
    }
    
    .report-stats {
        grid-template-columns: 1fr;
    }
    
    .charts-grid {
        grid-template-columns: 1fr;
    }
    
    .export-options {
        flex-direction: column;
    }
    
    .report-header {
        flex-direction: column;
        align-items: start;
        gap: 1rem;
    }
}

@media print {
    .topbar, .filter-section, .export-options {
        display: none;
    }
    
    .reports-container {
        margin: 0;
    }
    
    .report-card {
        break-inside: avoid;
    }
}
</style>

<?php include_once __DIR__ . '/../partials/footer.php'; ?>