<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../controllers/GroupController.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$groupController = new GroupController($pdo);
$userGroups = $groupController->getUserGroups($_SESSION['user_id']);

// Separate active and pending groups
$activeGroups = array_filter($userGroups, function($group) {
    return $group['membership_status'] === 'active';
});

$pendingGroups = array_filter($userGroups, function($group) {
    return $group['membership_status'] === 'pending';
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Groups - Ikimina</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Sharp" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/main_dashboard.css">
</head>
<body class="<?= isset($_SESSION['theme']) && $_SESSION['theme'] === 'dark' ? 'dark-theme' : 'light-theme' ?>">
    <div class="container">
        <!-- Include sidebar -->
        <?php include '../partials/sidebar.php'; ?>
        
        <main class="main-content">
            <!-- Include topbar -->
            <?php include '../partials/topbar.php'; ?>
            
            <div class="dashboard-container">
                <div class="section-header">
                    <h1>My Groups</h1>
                    <div class="header-buttons">
                        <a href="create_group.php" class="btn btn-primary">
                            <span class="material-icons-sharp">add</span>
                            Create New Group
                        </a>
                        <a href="join_group.php" class="btn btn-secondary">
                            <span class="material-icons-sharp">group_add</span>
                            Join Existing Group
                        </a>
                    </div>
                </div>
                
                <!-- Active Groups -->
                <section class="my-groups-section">
                    <div class="section-header">
                        <h3>Active Groups (<?= count($activeGroups) ?>)</h3>
                    </div>
                    
                    <?php if (!empty($activeGroups)): ?>
                        <div class="group-grid">
                            <?php foreach ($activeGroups as $group): ?>
                                <div class="group-card card">
                                    <div class="group-card-header">
                                        <div class="group-logo">
                                            <span class="material-icons-sharp">groups</span>
                                        </div>
                                        <div class="group-badges">
                                            <span class="role-badge badge-<?= $group['role_in_group'] ?>">
                                                <?= ucfirst(str_replace('_', ' ', $group['role_in_group'])) ?>
                                            </span>
                                            <span class="status-indicator status-active"></span>
                                        </div>
                                    </div>
                                    
                                    <div class="group-meta">
                                        <h5><?= htmlspecialchars($group['name']) ?></h5>
                                        <span class="text-muted"><?= htmlspecialchars($group['group_code']) ?></span>
                                        <?php if ($group['district']): ?>
                                            <span class="group-location">
                                                <span class="material-icons-sharp">location_on</span>
                                                <?= htmlspecialchars($group['district']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if (!empty($group['description'])): ?>
                                        <p class="group-description"><?= htmlspecialchars($group['description']) ?></p>
                                    <?php endif; ?>
                                    
                                    <div class="group-stats-grid">
                                        <div class="group-stat-item">
                                            <div class="group-stat-value"><?= $group['member_count'] ?></div>
                                            <div class="group-stat-label">Members</div>
                                        </div>
                                        <div class="group-stat-item">
                                            <div class="group-stat-value">
                                                <?= $group['contribution_amount'] ? number_format($group['contribution_amount']) . ' RWF' : 'Not set' ?>
                                            </div>
                                            <div class="group-stat-label">Contribution</div>
                                        </div>
                                    </div>
                                    
                                    <div class="group-actions">
                                        <button class="btn btn-primary" onclick="window.location.href='group_dashboard.php?group_id=<?= $group['id'] ?>'">
                                            Open Dashboard
                                        </button>
                                        <?php if (in_array($group['role_in_group'], ['president', 'vice_president', 'treasurer'])): ?>
                                            <button class="btn btn-outline" onclick="window.location.href='manage_group.php?group_id=<?= $group['id'] ?>'">
                                                Manage
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <span class="material-icons-sharp">groups</span>
                            <h4>No Active Groups</h4>
                            <p class="text-muted">You haven't joined any groups yet, or your join requests are pending approval.</p>
                            <div class="empty-state-actions">
                                <a href="join_group.php" class="btn btn-primary">Join a Group</a>
                                <a href="create_group.php" class="btn btn-secondary">Create Group</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </section>
                
                <!-- Pending Groups -->
                <?php if (!empty($pendingGroups)): ?>
                    <section class="my-groups-section">
                        <div class="section-header">
                            <h3>Pending Approval (<?= count($pendingGroups) ?>)</h3>
                        </div>
                        
                        <div class="group-grid">
                            <?php foreach ($pendingGroups as $group): ?>
                                <div class="group-card card">
                                    <div class="group-card-header">
                                        <div class="group-logo">
                                            <span class="material-icons-sharp">groups</span>
                                        </div>
                                        <div class="group-badges">
                                            <span class="role-badge badge-<?= $group['role_in_group'] ?>">
                                                <?= ucfirst(str_replace('_', ' ', $group['role_in_group'])) ?>
                                            </span>
                                            <span class="status-indicator status-pending"></span>
                                        </div>
                                    </div>
                                    
                                    <div class="group-meta">
                                        <h5><?= htmlspecialchars($group['name']) ?></h5>
                                        <span class="text-muted"><?= htmlspecialchars($group['group_code']) ?></span>
                                    </div>
                                    
                                    <div class="group-stats-grid">
                                        <div class="group-stat-item">
                                            <div class="group-stat-value"><?= $group['member_count'] ?></div>
                                            <div class="group-stat-label">Members</div>
                                        </div>
                                        <div class="group-stat-item">
                                            <div class="group-stat-value">Pending</div>
                                            <div class="group-stat-label">Status</div>
                                        </div>
                                    </div>
                                    
                                    <div class="group-actions">
                                        <button class="btn btn-outline" disabled>
                                            Waiting Approval
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>