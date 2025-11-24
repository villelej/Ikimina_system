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

// Use PresidentController
$presidentController = new PresidentController($pdo, $groupId, $userId);

try {
    $dashboardData = $presidentController->getDashboardData();
    $groupInfo = $dashboardData['group_info'];
    $financialSummary = $dashboardData['financial_summary'];
    $pendingApprovals = $dashboardData['pending_approvals'];
    $recentActivities = $dashboardData['recent_activities'];
    $upcomingMeetings = $dashboardData['upcoming_meetings'];
    
    // Get member statistics
    $memberStats = $presidentController->getMemberStatistics();
    
} catch (Exception $e) {
    header('Location: main_dashboard.php?error=' . urlencode($e->getMessage()));
    exit;
}

// Handle success/error messages
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>President Dashboard - <?= htmlspecialchars($groupInfo['name']) ?> - Ibimina</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom President CSS -->
    <link rel="stylesheet" href="../assets/css/president.css">
    
    <style>
    /* Inline CSS for immediate styling */
    :root {
        --primary: #2c5aa0;
        --primary-dark: #1e3d6f;
        --primary-light: #4a7bc8;
        --secondary: #6c757d;
        --success: #28a745;
        --danger: #dc3545;
        --warning: #ffc107;
        --info: #17a2b8;
        --light: #f8f9fa;
        --dark: #343a40;
        --white: #ffffff;
        --gray-100: #f8f9fa;
        --gray-200: #e9ecef;
        --gray-300: #dee2e6;
        --gray-400: #ced4da;
        --gray-500: #adb5bd;
        --gray-600: #6c757d;
        --gray-700: #495057;
        --gray-800: #343a40;
        --gray-900: #212529;
        
        --border-radius: 0.75rem;
        --border-radius-sm: 0.5rem;
        --border-radius-lg: 1rem;
        
        --shadow-sm: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        --shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        --shadow-lg: 0 1rem 3rem rgba(0, 0, 0, 0.175);
        
        --transition: all 0.3s ease;
    }

    .president-dashboard {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
    }

    .dashboard-header {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        color: var(--white);
        padding: 1.5rem 0;
        box-shadow: var(--shadow);
        position: relative;
        overflow: hidden;
    }

    .dashboard-header::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 300px;
        height: 300px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        transform: translate(30%, -30%);
    }

    .header-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: relative;
        z-index: 2;
    }

    .header-left {
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }

    .btn-back {
        background: rgba(255, 255, 255, 0.2);
        color: var(--white);
        border: 1px solid rgba(255, 255, 255, 0.3);
        padding: 0.5rem 1rem;
        border-radius: var(--border-radius-sm);
        text-decoration: none;
        transition: var(--transition);
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 500;
    }

    .btn-back:hover {
        background: rgba(255, 255, 255, 0.3);
        color: var(--white);
        transform: translateX(-2px);
    }

    .page-title {
        font-size: 1.75rem;
        font-weight: 700;
        margin: 0;
        color: var(--white);
    }

    .group-meta {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin: 0.5rem 0 0 0;
    }

    .badge-group {
        background: rgba(255, 255, 255, 0.2);
        color: var(--white);
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
    }

    .members-count {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.9rem;
        opacity: 0.9;
    }

    .header-right {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .badge-president {
        background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
        color: var(--gray-800);
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        box-shadow: var(--shadow-sm);
    }

    .btn-announcement {
        background: var(--success);
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: var(--border-radius-sm);
        color: var(--white);
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: var(--transition);
    }

    .btn-announcement:hover {
        background: #218838;
        transform: translateY(-2px);
        box-shadow: var(--shadow);
    }

    .dashboard-content {
        padding: 2rem 0;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: var(--white);
        border-radius: var(--border-radius);
        padding: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        box-shadow: var(--shadow);
        transition: var(--transition);
        border: 1px solid var(--gray-200);
        position: relative;
        overflow: hidden;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: var(--primary);
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-lg);
    }

    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: var(--white);
    }

    .stat-icon.members { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .stat-icon.pending { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
    .stat-icon.balance { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
    .stat-icon.loans { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
    .stat-icon.contributions { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
    .stat-icon.active-loans { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); color: var(--gray-700); }

    .stat-content {
        flex: 1;
    }

    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        margin: 0;
        color: var(--gray-800);
        line-height: 1;
    }

    .stat-label {
        font-size: 0.9rem;
        color: var(--gray-600);
        margin: 0.5rem 0 0 0;
        font-weight: 500;
    }

    .dashboard-layout {
        display: grid;
        grid-template-columns: 1fr 400px;
        gap: 2rem;
    }

    .left-column {
        display: flex;
        flex-direction: column;
        gap: 2rem;
    }

    .right-column {
        display: flex;
        flex-direction: column;
        gap: 2rem;
    }

    .dashboard-card {
        background: var(--white);
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        border: 1px solid var(--gray-200);
        overflow: hidden;
    }

    .card-header {
        padding: 1.5rem 1.5rem 0;
        border-bottom: 1px solid var(--gray-200);
    }

    .card-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--gray-800);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .card-icon {
        color: var(--primary);
        font-size: 1.1em;
    }

    .card-body {
        padding: 1.5rem;
    }

    .approval-section {
        margin-bottom: 2rem;
    }

    .approval-section:last-child {
        margin-bottom: 0;
    }

    .section-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--gray-700);
        margin: 0 0 1rem 0;
        padding-left: 1rem;
        border-left: 4px solid var(--primary);
    }

    .approval-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .approval-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        background: var(--gray-100);
        border-radius: var(--border-radius-sm);
        border-left: 4px solid var(--primary);
        transition: var(--transition);
    }

    .approval-item:hover {
        background: var(--gray-200);
        transform: translateX(5px);
    }

    .approval-avatar {
        flex-shrink: 0;
    }

    .avatar-img {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid var(--white);
        box-shadow: var(--shadow-sm);
    }

    .approval-info {
        flex: 1;
    }

    .approval-name {
        font-size: 1rem;
        font-weight: 600;
        color: var(--gray-800);
        margin: 0 0 0.5rem 0;
    }

    .approval-meta {
        display: flex;
        gap: 1rem;
        margin: 0 0 0.5rem 0;
    }

    .meta-item {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        font-size: 0.85rem;
        color: var(--gray-600);
    }

    .approval-date {
        font-size: 0.8rem;
        color: var(--gray-500);
    }

    .loan-details {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .detail-item {
        font-size: 0.9rem;
        color: var(--gray-700);
    }

    .approval-actions {
        display: flex;
        gap: 0.5rem;
        flex-shrink: 0;
    }

    .btn-sm {
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
        border-radius: var(--border-radius-sm);
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .meetings-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .meeting-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        background: var(--gray-100);
        border-radius: var(--border-radius-sm);
        transition: var(--transition);
    }

    .meeting-item:hover {
        background: var(--gray-200);
    }

    .meeting-date {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
        color: var(--white);
        padding: 0.75rem;
        border-radius: var(--border-radius-sm);
        text-align: center;
        min-width: 70px;
        flex-shrink: 0;
    }

    .date-day {
        display: block;
        font-size: 1.5rem;
        font-weight: 700;
        line-height: 1;
    }

    .date-month {
        display: block;
        font-size: 0.8rem;
        text-transform: uppercase;
        margin-top: 0.25rem;
    }

    .meeting-details {
        flex: 1;
    }

    .meeting-title {
        font-size: 1rem;
        font-weight: 600;
        color: var(--gray-800);
        margin: 0 0 0.25rem 0;
    }

    .meeting-time {
        font-size: 0.9rem;
        color: var(--gray-600);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-icon {
        background: transparent;
        border: 1px solid var(--gray-300);
        color: var(--gray-600);
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: var(--transition);
        flex-shrink: 0;
    }

    .btn-icon:hover {
        background: var(--primary);
        border-color: var(--primary);
        color: var(--white);
    }

    .tools-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
    }

    .tool-card {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        background: var(--gray-100);
        border-radius: var(--border-radius-sm);
        text-decoration: none;
        color: inherit;
        transition: var(--transition);
        border: 1px solid transparent;
    }

    .tool-card:hover {
        background: var(--white);
        border-color: var(--primary);
        transform: translateX(5px);
        color: inherit;
        text-decoration: none;
    }

    .tool-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        color: var(--white);
        flex-shrink: 0;
    }

    .tool-icon.members { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .tool-icon.loans { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
    .tool-icon.settings { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
    .tool-icon.reports { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
    .tool-icon.meetings { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
    .tool-icon.communication { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); color: var(--gray-700); }

    .tool-content {
        flex: 1;
    }

    .tool-content h4 {
        font-size: 1rem;
        font-weight: 600;
        color: var(--gray-800);
        margin: 0 0 0.25rem 0;
    }

    .tool-content p {
        font-size: 0.85rem;
        color: var(--gray-600);
        margin: 0;
    }

    .tool-arrow {
        color: var(--gray-400);
        transition: var(--transition);
    }

    .tool-card:hover .tool-arrow {
        color: var(--primary);
        transform: translateX(3px);
    }

    .activities-list {
        max-height: 400px;
        overflow-y: auto;
    }

    .activity-item {
        display: flex;
        align-items: start;
        gap: 1rem;
        padding: 1rem 0;
        border-bottom: 1px solid var(--gray-200);
    }

    .activity-item:last-child {
        border-bottom: none;
    }

    .activity-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        color: var(--white);
        flex-shrink: 0;
        margin-top: 0.25rem;
    }

    .activity-icon.contribution { background: var(--success); }
    .activity-icon.loan { background: var(--info); }
    .activity-icon.repayment { background: var(--warning); color: var(--gray-800); }

    .activity-details {
        flex: 1;
    }

    .activity-text {
        font-size: 0.9rem;
        color: var(--gray-700);
        margin: 0 0 0.5rem 0;
        line-height: 1.4;
    }

    .activity-text strong {
        color: var(--gray-800);
    }

    .activity-time {
        font-size: 0.8rem;
        color: var(--gray-500);
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .empty-state {
        text-align: center;
        padding: 3rem 2rem;
        color: var(--gray-500);
    }

    .empty-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    .empty-text {
        font-size: 1rem;
        margin: 0;
        font-style: italic;
    }

    @media (max-width: 1200px) {
        .dashboard-layout {
            grid-template-columns: 1fr;
        }
        
        .stats-grid {
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        }
    }

    @media (max-width: 768px) {
        .dashboard-header {
            padding: 1rem 0;
        }
        
        .header-content {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }
        
        .header-left {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }
        
        .header-right {
            width: 100%;
            justify-content: space-between;
        }
        
        .page-title {
            font-size: 1.5rem;
        }
        
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .stat-card {
            padding: 1rem;
        }
        
        .stat-value {
            font-size: 1.75rem;
        }
        
        .approval-item {
            flex-direction: column;
            text-align: center;
            gap: 1rem;
        }
        
        .approval-actions {
            justify-content: center;
            width: 100%;
        }
        
        .meeting-item {
            flex-direction: column;
            text-align: center;
            gap: 1rem;
        }
        
        .tool-card {
            padding: 0.75rem;
        }
        
        .activity-item {
            flex-direction: column;
            text-align: center;
            gap: 0.75rem;
        }
    }

    @media (max-width: 576px) {
        .dashboard-content {
            padding: 1rem 0;
        }
        
        .group-meta {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
        }
        
        .header-actions {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            width: 100%;
        }
        
        .btn-announcement {
            width: 100%;
            justify-content: center;
        }
    }
    </style>
</head>
<body class="president-dashboard">
<div class="container-fluid">
    <!-- President-specific header -->
    <header class="dashboard-header">
        <div class="header-content">
            <div class="header-left">
                <a href="main_dashboard.php" class="btn btn-back">
                    <i class="fas fa-arrow-left"></i>
                    Back to Main Dashboard
                </a>
                <div class="group-info">
                    <h1 class="page-title">President Dashboard - <?= htmlspecialchars($groupInfo['name']) ?></h1>
                    <p class="group-meta">
                        <span class="badge badge-group">Group Code: <?= htmlspecialchars($groupInfo['group_code']) ?></span>
                        <span class="members-count"><i class="fas fa-users"></i> <?= $memberStats['active_members'] ?? 0 ?> Active Members</span>
                    </p>
                </div>
            </div>
            <div class="header-right">
                <div class="header-actions">
                    <span class="badge badge-president">
                        <i class="fas fa-crown"></i>
                        President
                    </span>
                    <button class="btn btn-primary btn-announcement" onclick="openAnnouncementModal()">
                        <i class="fas fa-bullhorn"></i>
                        Send Announcement
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Alert Messages -->
    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <div class="alert-content">
            <i class="fas fa-check-circle alert-icon"></i>
            <div class="alert-message"><?= htmlspecialchars($success) ?></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <div class="alert-content">
            <i class="fas fa-exclamation-circle alert-icon"></i>
            <div class="alert-message"><?= htmlspecialchars($error) ?></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="dashboard-content">
        <!-- Quick Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon members">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-value"><?= $memberStats['active_members'] ?? 0 ?></h3>
                    <p class="stat-label">Active Members</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon pending">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-value"><?= count($pendingApprovals['members']) + count($pendingApprovals['loans']) ?></h3>
                    <p class="stat-label">Pending Approvals</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon balance">
                    <i class="fas fa-piggy-bank"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-value">RWF <?= number_format($financialSummary['group_balance'] ?? 0) ?></h3>
                    <p class="stat-label">Group Balance</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon loans">
                    <i class="fas fa-hand-holding-usd"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-value"><?= $financialSummary['pending_loans'] ?? 0 ?></h3>
                    <p class="stat-label">Pending Loans</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon contributions">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-value">RWF <?= number_format($financialSummary['total_contributions'] ?? 0) ?></h3>
                    <p class="stat-label">Total Contributions</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon active-loans">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-value">RWF <?= number_format($financialSummary['active_loans_balance'] ?? 0) ?></h3>
                    <p class="stat-label">Active Loans</p>
                </div>
            </div>
        </div>

        <!-- Two Column Layout -->
        <div class="dashboard-layout">
            <!-- Left Column -->
            <div class="left-column">
                <!-- Pending Approvals Section -->
                <?php if (!empty($pendingApprovals['members']) || !empty($pendingApprovals['loans'])): ?>
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-clock card-icon"></i>
                            Pending Approvals
                        </h3>
                    </div>
                    <div class="card-body">
                        <!-- Pending Members -->
                        <?php if (!empty($pendingApprovals['members'])): ?>
                        <div class="approval-section">
                            <h4 class="section-title">New Member Requests (<?= count($pendingApprovals['members']) ?>)</h4>
                            <div class="approval-list">
                                <?php foreach ($pendingApprovals['members'] as $member): ?>
                                <div class="approval-item">
                                    <div class="approval-avatar">
                                        <img src="<?= htmlspecialchars($member['profile_pic'] ?? '../assets/images/default-avatar.jpg') ?>" 
                                             alt="<?= htmlspecialchars($member['first_name']) ?>" class="avatar-img">
                                    </div>
                                    <div class="approval-info">
                                        <h5 class="approval-name"><?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?></h5>
                                        <p class="approval-meta">
                                            <span class="meta-item"><i class="fas fa-envelope"></i> <?= htmlspecialchars($member['email']) ?></span>
                                            <span class="meta-item"><i class="fas fa-phone"></i> <?= htmlspecialchars($member['phone']) ?></span>
                                        </p>
                                        <small class="approval-date">Requested: <?= date('M j, Y', strtotime($member['joined_at'])) ?></small>
                                    </div>
                                    <div class="approval-actions">
                                        <button class="btn btn-success btn-sm" onclick="approveMember(<?= $member['id'] ?>)">
                                            <i class="fas fa-check"></i>
                                            Approve
                                        </button>
                                        <button class="btn btn-danger btn-sm" onclick="rejectMember(<?= $member['id'] ?>)">
                                            <i class="fas fa-times"></i>
                                            Reject
                                        </button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Pending Loans -->
                        <?php if (!empty($pendingApprovals['loans'])): ?>
                        <div class="approval-section">
                            <h4 class="section-title">Loan Requests (<?= count($pendingApprovals['loans']) ?>)</h4>
                            <div class="approval-list">
                                <?php foreach ($pendingApprovals['loans'] as $loan): ?>
                                <div class="approval-item">
                                    <div class="approval-info">
                                        <h5 class="approval-name"><?= htmlspecialchars($loan['first_name'] . ' ' . $loan['last_name']) ?></h5>
                                        <div class="loan-details">
                                            <span class="detail-item"><strong>Amount:</strong> RWF <?= number_format($loan['amount']) ?></span>
                                            <span class="detail-item"><strong>Purpose:</strong> <?= htmlspecialchars($loan['purpose'] ?? 'Not specified') ?></span>
                                            <span class="detail-item"><strong>Due:</strong> <?= date('M j, Y', strtotime($loan['due_date'])) ?></span>
                                        </div>
                                    </div>
                                    <div class="approval-actions">
                                        <button class="btn btn-success btn-sm" onclick="approveLoan(<?= $loan['id'] ?>)">
                                            <i class="fas fa-check"></i>
                                            Approve
                                        </button>
                                        <button class="btn btn-danger btn-sm" onclick="rejectLoan(<?= $loan['id'] ?>)">
                                            <i class="fas fa-times"></i>
                                            Reject
                                        </button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Upcoming Meetings -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-calendar-alt card-icon"></i>
                            Upcoming Meetings
                        </h3>
                        <button class="btn btn-outline btn-sm" onclick="scheduleMeeting()">
                            <i class="fas fa-plus"></i>
                            Schedule
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="meetings-list">
                            <?php foreach ($upcomingMeetings as $meeting): ?>
                            <div class="meeting-item">
                                <div class="meeting-date">
                                    <span class="date-day"><?= date('j', strtotime($meeting['date'])) ?></span>
                                    <span class="date-month"><?= date('M', strtotime($meeting['date'])) ?></span>
                                </div>
                                <div class="meeting-details">
                                    <h5 class="meeting-title"><?= htmlspecialchars($meeting['title']) ?></h5>
                                    <p class="meeting-time">
                                        <i class="fas fa-clock"></i>
                                        <?= date('l, F j, Y', strtotime($meeting['date'])) ?> at <?= $meeting['time'] ?>
                                    </p>
                                </div>
                                <button class="btn btn-icon" title="Edit Meeting">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="right-column">
                <!-- President Controls -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-cogs card-icon"></i>
                            Management Tools
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="tools-grid">
                            <a href="../president/member_management.php?group_id=<?= $groupId ?>" class="tool-card">
                                <div class="tool-icon members">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="tool-content">
                                    <h4>Member Management</h4>
                                    <p>Manage members and roles</p>
                                </div>
                                <i class="fas fa-chevron-right tool-arrow"></i>
                            </a>
                            
                            <a href="../president/loan_management.php?group_id=<?= $groupId ?>" class="tool-card">
                                <div class="tool-icon loans">
                                    <i class="fas fa-hand-holding-usd"></i>
                                </div>
                                <div class="tool-content">
                                    <h4>Loan Management</h4>
                                    <p>Approve and manage loans</p>
                                </div>
                                <i class="fas fa-chevron-right tool-arrow"></i>
                            </a>
                            
                            <a href="../president/group_settings.php?group_id=<?= $groupId ?>" class="tool-card">
                                <div class="tool-icon settings">
                                    <i class="fas fa-cog"></i>
                                </div>
                                <div class="tool-content">
                                    <h4>Group Settings</h4>
                                    <p>Configure group rules</p>
                                </div>
                                <i class="fas fa-chevron-right tool-arrow"></i>
                            </a>
                            
                            <a href="../president/financial_reports.php?group_id=<?= $groupId ?>" class="tool-card">
                                <div class="tool-icon reports">
                                    <i class="fas fa-chart-bar"></i>
                                </div>
                                <div class="tool-content">
                                    <h4>Financial Reports</h4>
                                    <p>View financial overview</p>
                                </div>
                                <i class="fas fa-chevron-right tool-arrow"></i>
                            </a>

                            <a href="../president/meeting_management.php?group_id=<?= $groupId ?>" class="tool-card">
                                <div class="tool-icon meetings">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div class="tool-content">
                                    <h4>Meeting Manager</h4>
                                    <p>Schedule and manage meetings</p>
                                </div>
                                <i class="fas fa-chevron-right tool-arrow"></i>
                            </a>

                            <a href="../president/communication.php?group_id=<?= $groupId ?>" class="tool-card">
                                <div class="tool-icon communication">
                                    <i class="fas fa-bullhorn"></i>
                                </div>
                                <div class="tool-content">
                                    <h4>Communication</h4>
                                    <p>Send announcements</p>
                                </div>
                                <i class="fas fa-chevron-right tool-arrow"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-history card-icon"></i>
                            Recent Activities
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="activities-list">
                            <?php if (!empty($recentActivities)): ?>
                                <?php foreach ($recentActivities as $activity): ?>
                                <div class="activity-item">
                                    <div class="activity-icon <?= $activity['type'] ?>">
                                        <i class="fas <?= $activity['type'] === 'contribution' ? 'fa-money-bill-wave' : 
                                                       ($activity['type'] === 'loan' ? 'fa-hand-holding-usd' : 'fa-money-check') ?>"></i>
                                    </div>
                                    <div class="activity-details">
                                        <p class="activity-text">
                                            <strong><?= htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']) ?></strong>
                                            <?= $activity['type'] === 'contribution' ? 'made a contribution' : 
                                               ($activity['type'] === 'loan' ? 'received a loan' : 'made a repayment') ?>
                                            of <strong>RWF <?= number_format($activity['amount']) ?></strong>
                                        </p>
                                        <small class="activity-time">
                                            <i class="fas fa-clock"></i>
                                            <?= date('M j, Y g:i A', strtotime($activity['created_at'])) ?>
                                        </small>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-inbox empty-icon"></i>
                                    <p class="empty-text">No recent activities</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Announcement Modal -->
<div id="announcementModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-bullhorn me-2"></i>
                    Send Announcement
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="announcementForm" action="../../controllers/president/send_announcement.php" method="POST">
                    <input type="hidden" name="group_id" value="<?= $groupId ?>">
                    
                    <div class="mb-3">
                        <label for="announcementTitle" class="form-label">Title</label>
                        <input type="text" class="form-control" id="announcementTitle" name="title" required 
                               placeholder="Enter announcement title">
                    </div>
                    
                    <div class="mb-3">
                        <label for="announcementMessage" class="form-label">Message</label>
                        <textarea class="form-control" id="announcementMessage" name="message" required 
                                  placeholder="Enter your announcement message" rows="5"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="send_sms" value="1" id="sendSMS">
                            <label class="form-check-label" for="sendSMS">
                                Also send as SMS to all members
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="announcementForm" class="btn btn-primary">Send Announcement</button>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap & Custom JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function approveMember(memberId) {
    if (confirm('Are you sure you want to approve this member?')) {
        window.location.href = `../../controllers/president/approve_member.php?group_id=<?= $groupId ?>&member_id=${memberId}&action=approve`;
    }
}

function rejectMember(memberId) {
    if (confirm('Are you sure you want to reject this member?')) {
        window.location.href = `../../controllers/president/approve_member.php?group_id=<?= $groupId ?>&member_id=${memberId}&action=reject`;
    }
}

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

function openAnnouncementModal() {
    const modal = new bootstrap.Modal(document.getElementById('announcementModal'));
    modal.show();
}

function scheduleMeeting() {
    window.location.href = `meeting_management.php?group_id=<?= $groupId ?>`;
}

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

</body>
</html>