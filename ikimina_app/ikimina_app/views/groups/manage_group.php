<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../controllers/GroupController.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$groupController = new GroupController($pdo);
$groupId = $_GET['group_id'] ?? null;

if (!$groupId) {
    header('Location: my_groups.php');
    exit;
}

try {
    $group = $groupController->getGroupDetails($groupId, $_SESSION['user_id']);
    $members = $groupController->getGroupMembers($groupId, $_SESSION['user_id']);
    $pendingMembers = $groupController->getPendingMembers($groupId);
    
    $isAdmin = $groupController->isGroupAdmin($groupId, $_SESSION['user_id']);
    $canApprove = $groupController->canApproveMembers($groupId, $_SESSION['user_id']);
    
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Handle member approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_member'])) {
    try {
        $memberId = $_POST['member_id'];
        $groupController->approveMember($groupId, $memberId, $_SESSION['user_id']);
        $success = "Member approved successfully!";
        header("Location: manage_group.php?group_id=$groupId");
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle role change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_role'])) {
    try {
        $memberId = $_POST['member_id'];
        $newRole = $_POST['new_role'];
        $groupController->updateMemberRole($groupId, $memberId, $newRole, $_SESSION['user_id']);
        $success = "Member role updated successfully!";
        header("Location: manage_group.php?group_id=$groupId");
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Group - Ikimina</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Sharp" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/main_dashboard.css">
    <style>
        .management-tabs {
            display: flex;
            border-bottom: 1px solid var(--gray-300);
            margin-bottom: var(--spacing-lg);
        }
        
        .tab {
            padding: var(--spacing-md) var(--spacing-lg);
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: var(--transition);
        }
        
        .tab.active {
            border-bottom-color: var(--primary);
            color: var(--primary);
            font-weight: 500;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .member-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: var(--spacing-lg);
            margin-bottom: var(--spacing-md);
            box-shadow: var(--shadow-sm);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .member-info {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
        }
        
        .member-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--gray-200);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: var(--gray-600);
        }
        
        .member-details h4 {
            margin: 0 0 var(--spacing-xs) 0;
        }
        
        .member-meta {
            display: flex;
            gap: var(--spacing-md);
            font-size: var(--font-size-sm);
            color: var(--gray-600);
        }
        
        .role-badge {
            padding: var(--spacing-xs) var(--spacing-sm);
            border-radius: var(--border-radius-sm);
            font-size: var(--font-size-xs);
            font-weight: bold;
            text-transform: capitalize;
        }
        
        .badge-president { background: #FFD700; color: #000; }
        .badge-vice_president { background: #C0C0C0; color: #000; }
        .badge-treasurer { background: #CD7F32; color: #fff; }
        .badge-secretary { background: #4361ee; color: #fff; }
        .badge-loan_committee { background: #4cc9f0; color: #fff; }
        .badge-member { background: #6c757d; color: #fff; }
        
        .action-buttons {
            display: flex;
            gap: var(--spacing-sm);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-xl);
        }
        
        .stat-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: var(--spacing-lg);
            text-align: center;
            box-shadow: var(--shadow-sm);
        }
        
        .stat-number {
            font-size: var(--font-size-2xl);
            font-weight: bold;
            color: var(--primary);
            margin-bottom: var(--spacing-xs);
        }
        
        .stat-label {
            font-size: var(--font-size-sm);
            color: var(--gray-600);
        }
    </style>
</head>
<body class="<?= isset($_SESSION['theme']) && $_SESSION['theme'] === 'dark' ? 'dark-theme' : 'light-theme' ?>">
    <div class="container">
        <!-- Include sidebar -->
        <?php include '../partials/sidebar.php'; ?>
        
        <main class="main-content">
            <!-- Include topbar -->
            <?php include '../partials/topbar.php'; ?>
            
            <div class="dashboard-container">
                <div class="card" style="padding: var(--spacing-xl);">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-error">
                            <span class="material-icons-sharp">error</span>
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success">
                            <span class="material-icons-sharp">check_circle</span>
                            <?= htmlspecialchars($success) ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="section-header">
                        <div>
                            <h1><?= htmlspecialchars($group['name']) ?></h1>
                            <p class="text-muted">Group Code: <strong><?= htmlspecialchars($group['group_code']) ?></strong></p>
                        </div>
                        <div class="action-buttons">
                            <a href="group_dashboard.php?group_id=<?= $groupId ?>" class="btn btn-primary">
                                <span class="material-icons-sharp">dashboard</span>
                                Group Dashboard
                            </a>
                        </div>
                    </div>
                    
                    <!-- Group Stats -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-number"><?= $group['active_members'] ?></div>
                            <div class="stat-label">Active Members</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?= $group['pending_members'] ?></div>
                            <div class="stat-label">Pending Requests</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?= ucfirst($group['user_role']) ?></div>
                            <div class="stat-label">Your Role</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">
                                <?= $group['contribution_amount'] ? number_format($group['contribution_amount']) . ' RWF' : 'Not set' ?>
                            </div>
                            <div class="stat-label">Contribution Amount</div>
                        </div>
                    </div>
                    
                    <!-- Management Tabs -->
                    <div class="management-tabs">
                        <div class="tab active" data-tab="members">Active Members</div>
                        <?php if ($canApprove && count($pendingMembers) > 0): ?>
                            <div class="tab" data-tab="pending">
                                Pending Requests <span class="notification-badge"><?= count($pendingMembers) ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="tab" data-tab="info">Group Information</div>
                    </div>
                    
                    <!-- Members Tab -->
                    <div class="tab-content active" id="members-tab">
                        <h3>Active Members (<?= count($members) ?>)</h3>
                        
                        <?php foreach ($members as $member): ?>
                            <div class="member-card">
                                <div class="member-info">
                                    <div class="member-avatar">
                                        <?= strtoupper(substr($member['first_name'], 0, 1) . substr($member['last_name'], 0, 1)) ?>
                                    </div>
                                    <div class="member-details">
                                        <h4><?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?></h4>
                                        <div class="member-meta">
                                            <span><?= htmlspecialchars($member['email']) ?></span>
                                            <span><?= htmlspecialchars($member['phone']) ?></span>
                                            <span>Joined: <?= date('M d, Y', strtotime($member['joined_at'])) ?></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="member-actions">
                                    <span class="role-badge badge-<?= $member['role_in_group'] ?>">
                                        <?= ucfirst(str_replace('_', ' ', $member['role_in_group'])) ?>
                                    </span>
                                    
                                    <?php if ($isAdmin && $member['user_id'] != $_SESSION['user_id']): ?>
                                        <div class="action-buttons" style="margin-top: var(--spacing-sm);">
                                            <form method="POST" action="" style="display: inline;">
                                                <input type="hidden" name="member_id" value="<?= $member['user_id'] ?>">
                                                <select name="new_role" onchange="this.form.submit()" style="padding: var(--spacing-xs);">
                                                    <option value="member" <?= $member['role_in_group'] == 'member' ? 'selected' : '' ?>>Member</option>
                                                    <option value="vice_president" <?= $member['role_in_group'] == 'vice_president' ? 'selected' : '' ?>>Vice President</option>
                                                    <option value="treasurer" <?= $member['role_in_group'] == 'treasurer' ? 'selected' : '' ?>>Treasurer</option>
                                                    <option value="secretary" <?= $member['role_in_group'] == 'secretary' ? 'selected' : '' ?>>Secretary</option>
                                                    <option value="loan_committee" <?= $member['role_in_group'] == 'loan_committee' ? 'selected' : '' ?>>Loan Committee</option>
                                                </select>
                                                <input type="hidden" name="change_role" value="1">
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pending Requests Tab -->
                    <?php if ($canApprove): ?>
                        <div class="tab-content" id="pending-tab">
                            <h3>Pending Membership Requests (<?= count($pendingMembers) ?>)</h3>
                            
                            <?php if (empty($pendingMembers)): ?>
                                <p class="text-muted">No pending membership requests.</p>
                            <?php else: ?>
                                <?php foreach ($pendingMembers as $member): ?>
                                    <div class="member-card">
                                        <div class="member-info">
                                            <div class="member-avatar">
                                                <?= strtoupper(substr($member['first_name'], 0, 1) . substr($member['last_name'], 0, 1)) ?>
                                            </div>
                                            <div class="member-details">
                                                <h4><?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?></h4>
                                                <div class="member-meta">
                                                    <span><?= htmlspecialchars($member['email']) ?></span>
                                                    <span><?= htmlspecialchars($member['phone']) ?></span>
                                                    <span>Requested role: <strong><?= ucfirst(str_replace('_', ' ', $member['role_in_group'])) ?></strong></span>
                                                    <span>Requested: <?= date('M d, Y', strtotime($member['joined_at'])) ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="action-buttons">
                                            <form method="POST" action="">
                                                <input type="hidden" name="member_id" value="<?= $member['user_id'] ?>">
                                                <button type="submit" name="approve_member" value="1" class="btn btn-primary">
                                                    Approve
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Group Info Tab -->
                    <div class="tab-content" id="info-tab">
                        <h3>Group Information</h3>
                        
                        <div class="form-section">
                            <div class="form-group">
                                <label class="form-label">Group Name</label>
                                <div class="form-control" style="background: var(--gray-100);"><?= htmlspecialchars($group['name']) ?></div>
                            </div>
                            
                            <?php if ($group['description']): ?>
                                <div class="form-group">
                                    <label class="form-label">Description</label>
                                    <div class="form-control" style="background: var(--gray-100); height: auto;"><?= htmlspecialchars($group['description']) ?></div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="location-grid" style="grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));">
                                <?php if ($group['province']): ?>
                                    <div class="form-group">
                                        <label class="form-label">Province</label>
                                        <div class="form-control" style="background: var(--gray-100);"><?= htmlspecialchars($group['province']) ?></div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($group['district']): ?>
                                    <div class="form-group">
                                        <label class="form-label">District</label>
                                        <div class="form-control" style="background: var(--gray-100);"><?= htmlspecialchars($group['district']) ?></div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($group['sector']): ?>
                                    <div class="form-group">
                                        <label class="form-label">Sector</label>
                                        <div class="form-control" style="background: var(--gray-100);"><?= htmlspecialchars($group['sector']) ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($group['objective']): ?>
                                <div class="form-group">
                                    <label class="form-label">Objectives</label>
                                    <div class="form-control" style="background: var(--gray-100); height: auto;"><?= htmlspecialchars($group['objective']) ?></div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($group['loan_rules']): ?>
                                <div class="form-group">
                                    <label class="form-label">Loan Rules</label>
                                    <div class="form-control" style="background: var(--gray-100); height: auto;"><?= htmlspecialchars($group['loan_rules']) ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Tab functionality
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs and contents
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                // Add active class to clicked tab
                this.classList.add('active');
                
                // Show corresponding content
                const tabName = this.getAttribute('data-tab');
                document.getElementById(tabName + '-tab').classList.add('active');
            });
        });
    </script>
</body>
</html>