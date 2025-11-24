<?php
// views/dashboards/vice_president_dashboard.php
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
    $pendingMembers = $groupController->getPendingMembers($groupId);
    $groupMembers = $groupController->getGroupMembers($groupId, $userId);
    
    if (!in_array($groupDetails['user_role'], ['vice_president', 'president'])) {
        header('Location: main_dashboard.php?error=not_authorized');
        exit;
    }
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
            <h1>Vice President - <?= htmlspecialchars($groupDetails['name']) ?></h1>
            <small>Group Code: <?= htmlspecialchars($groupDetails['group_code']) ?></small>
        </div>
        <div class="topbar-right">
            <span class="badge" style="background: #7c3aed; color: white; padding: 0.5rem 1rem; border-radius: 20px;">
                <span class="material-icons-sharp">admin_panel_settings</span>
                Vice President
            </span>
        </div>
    </header>

    <div class="dashboard-container">
        <!-- Pending Approvals (Vice President can also approve) -->
        <?php if (!empty($pendingMembers)): ?>
        <div class="approval-section">
            <h3>
                <span class="material-icons-sharp">pending_actions</span>
                Pending Member Approvals (<?= count($pendingMembers) ?>)
            </h3>
            <div class="pending-members-list">
                <?php foreach ($pendingMembers as $member): ?>
                <div class="member-card">
                    <img src="<?= htmlspecialchars($member['profile_pic'] ?? '../../assets/images/default-avatar.jpg') ?>" 
                         class="profile-picture">
                    <div class="member-info">
                        <h4><?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?></h4>
                        <p><?= htmlspecialchars($member['email']) ?></p>
                        <small>Joined: <?= date('M j, Y', strtotime($member['joined_at'])) ?></small>
                    </div>
                    <div class="member-actions">
                        <button class="btn btn-primary" onclick="approveMember(<?= $member['id'] ?>)">
                            <span class="material-icons-sharp">check</span>
                            Approve
                        </button>
                        <button class="btn btn-outline" onclick="rejectMember(<?= $member['id'] ?>)">
                            <span class="material-icons-sharp">close</span>
                            Reject
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Vice President Controls -->
        <div class="admin-controls">
            <div class="admin-card" onclick="location.href='../groups/member_management.php?group_id=<?= $groupId ?>'">
                <span class="material-icons-sharp">groups</span>
                <h3>Member Management</h3>
                <p>Manage members and roles</p>
            </div>
            
            <div class="admin-card" onclick="location.href='../groups/meeting_management.php?group_id=<?= $groupId ?>'">
                <span class="material-icons-sharp">event</span>
                <h3>Meeting Management</h3>
                <p>Schedule and manage meetings</p>
            </div>
            
            <div class="admin-card" onclick="location.href='../groups/communication.php?group_id=<?= $groupId ?>'">
                <span class="material-icons-sharp">chat</span>
                <h3>Group Communication</h3>
                <p>Send announcements</p>
            </div>
            
            <div class="admin-card" onclick="location.href='../groups/loan_review.php?group_id=<?= $groupId ?>'">
                <span class="material-icons-sharp">request_quote</span>
                <h3>Loan Review</h3>
                <p>Review loan applications</p>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <span class="material-icons-sharp">groups</span>
                <h3><?= $groupDetails['active_members'] ?? 0 ?></h3>
                <p>Active Members</p>
            </div>
            <div class="stat-card">
                <span class="material-icons-sharp">pending</span>
                <h3><?= count($pendingMembers) ?></h3>
                <p>Pending Approvals</p>
            </div>
            <div class="stat-card">
                <span class="material-icons-sharp">event</span>
                <h3>0</h3>
                <p>Upcoming Meetings</p>
            </div>
            <div class="stat-card">
                <span class="material-icons-sharp">chat</span>
                <h3>0</h3>
                <p>Unread Messages</p>
            </div>
        </div>
    </div>
</div>

<script>
function approveMember(userId) {
    if (confirm('Are you sure you want to approve this member?')) {
        window.location.href = `../../controllers/groups/approve_member.php?group_id=<?= $groupId ?>&user_id=${userId}&action=approve`;
    }
}

function rejectMember(userId) {
    if (confirm('Are you sure you want to reject this member?')) {
        window.location.href = `../../controllers/groups/approve_member.php?group_id=<?= $groupId ?>&user_id=${userId}&action=reject`;
    }
}
</script>

<?php include_once __DIR__ . '/../partials/footer.php'; ?>