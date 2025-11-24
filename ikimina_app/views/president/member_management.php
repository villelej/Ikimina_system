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
    $activeMembers = $presidentController->getGroupMembers('active');
    $pendingMembers = $presidentController->getGroupMembers('pending');
    $suspendedMembers = $presidentController->getGroupMembers('suspended');
    $groupInfo = $presidentController->getDashboardData()['group_info'];
    
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
                <h1>Member Management - <?= htmlspecialchars($groupInfo['name']) ?></h1>
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

    <div class="management-container">
        <!-- Tabs -->
        <div class="tabs">
            <button class="tab-button active" onclick="openTab('active-members')">
                <span class="material-icons-sharp">group</span>
                Active Members (<?= count($activeMembers) ?>)
            </button>
            <button class="tab-button" onclick="openTab('pending-members')">
                <span class="material-icons-sharp">pending</span>
                Pending Approval (<?= count($pendingMembers) ?>)
            </button>
            <button class="tab-button" onclick="openTab('suspended-members')">
                <span class="material-icons-sharp">block</span>
                Suspended (<?= count($suspendedMembers) ?>)
            </button>
        </div>

        <!-- Active Members Tab -->
        <div id="active-members" class="tab-content active">
            <div class="section-header">
                <h3>Active Members</h3>
                <div class="search-box">
                    <input type="text" id="searchActive" placeholder="Search members..." onkeyup="searchMembers('active')">
                    <span class="material-icons-sharp">search</span>
                </div>
            </div>
            
            <div class="members-grid">
                <?php foreach ($activeMembers as $member): ?>
                <div class="member-card">
                    <div class="member-header">
                        <img src="<?= htmlspecialchars($member['profile_pic'] ?? '../../assets/images/default-avatar.jpg') ?>" 
                             alt="<?= htmlspecialchars($member['first_name']) ?>" class="member-avatar">
                        <div class="member-basic-info">
                            <h4><?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?></h4>
                            <span class="role-badge <?= $member['role_in_group'] ?>">
                                <?= ucfirst(str_replace('_', ' ', $member['role_in_group'])) ?>
                            </span>
                        </div>
                        <div class="member-actions">
                            <button class="btn-icon" onclick="openRoleModal(<?= $member['id'] ?>, '<?= $member['role_in_group'] ?>')" title="Change Role">
                                <span class="material-icons-sharp">admin_panel_settings</span>
                            </button>
                            <button class="btn-icon" onclick="openSuspendModal(<?= $member['id'] ?>, '<?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?>')" title="Suspend Member">
                                <span class="material-icons-sharp">block</span>
                            </button>
                            <button class="btn-icon" onclick="viewMemberDetails(<?= $member['id'] ?>)" title="View Details">
                                <span class="material-icons-sharp">visibility</span>
                            </button>
                        </div>
                    </div>
                    
                    <div class="member-details">
                        <div class="detail-item">
                            <span class="material-icons-sharp">phone</span>
                            <span><?= htmlspecialchars($member['phone']) ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="material-icons-sharp">email</span>
                            <span><?= htmlspecialchars($member['email']) ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="material-icons-sharp">calendar_today</span>
                            <span>Joined: <?= date('M j, Y', strtotime($member['joined_at'])) ?></span>
                        </div>
                    </div>
                    
                    <div class="member-financials">
                        <div class="financial-item">
                            <small>Savings</small>
                            <strong>RWF <?= number_format($member['savings_balance']) ?></strong>
                        </div>
                        <div class="financial-item">
                            <small>Loans</small>
                            <strong>RWF <?= number_format($member['loan_balance']) ?></strong>
                        </div>
                        <div class="financial-item">
                            <small>Total Loans</small>
                            <strong><?= $member['total_loans'] ?></strong>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Pending Members Tab -->
        <div id="pending-members" class="tab-content">
            <div class="section-header">
                <h3>Pending Member Approvals</h3>
            </div>
            
            <?php if (!empty($pendingMembers)): ?>
            <div class="pending-list">
                <?php foreach ($pendingMembers as $member): ?>
                <div class="pending-card">
                    <div class="pending-info">
                        <img src="<?= htmlspecialchars($member['profile_pic'] ?? '../../assets/images/default-avatar.jpg') ?>" 
                             alt="<?= htmlspecialchars($member['first_name']) ?>" class="member-avatar">
                        <div>
                            <h4><?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?></h4>
                            <p><?= htmlspecialchars($member['email']) ?> | <?= htmlspecialchars($member['phone']) ?></p>
                            <small>Requested: <?= date('M j, Y', strtotime($member['joined_at'])) ?></small>
                        </div>
                    </div>
                    <div class="pending-actions">
                        <button class="btn btn-success" onclick="approveMember(<?= $member['id'] ?>)">
                            <span class="material-icons-sharp">check</span>
                            Approve
                        </button>
                        <button class="btn btn-danger" onclick="rejectMember(<?= $member['id'] ?>)">
                            <span class="material-icons-sharp">close</span>
                            Reject
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <span class="material-icons-sharp">check_circle</span>
                <h3>No Pending Members</h3>
                <p>All member requests have been processed.</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Suspended Members Tab -->
        <div id="suspended-members" class="tab-content">
            <div class="section-header">
                <h3>Suspended Members</h3>
            </div>
            
            <?php if (!empty($suspendedMembers)): ?>
            <div class="suspended-list">
                <?php foreach ($suspendedMembers as $member): ?>
                <div class="suspended-card">
                    <div class="suspended-info">
                        <img src="<?= htmlspecialchars($member['profile_pic'] ?? '../../assets/images/default-avatar.jpg') ?>" 
                             alt="<?= htmlspecialchars($member['first_name']) ?>" class="member-avatar">
                        <div>
                            <h4><?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?></h4>
                            <p><?= htmlspecialchars($member['email']) ?> | <?= htmlspecialchars($member['phone']) ?></p>
                            <small>Suspended since: <?= date('M j, Y', strtotime($member['joined_at'])) ?></small>
                        </div>
                    </div>
                    <div class="suspended-actions">
                        <button class="btn btn-success" onclick="activateMember(<?= $member['id'] ?>)">
                            <span class="material-icons-sharp">check_circle</span>
                            Activate
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <span class="material-icons-sharp">sentiment_satisfied</span>
                <h3>No Suspended Members</h3>
                <p>All members are currently active.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Role Change Modal -->
<div id="roleModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Change Member Role</h3>
            <span class="close" onclick="closeRoleModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form id="roleForm" action="../../controllers/president/change_role.php" method="POST">
                <input type="hidden" name="group_id" value="<?= $groupId ?>">
                <input type="hidden" id="roleMemberId" name="member_id">
                
                <div class="form-group">
                    <label for="newRole">Select New Role</label>
                    <select id="newRole" name="role" class="form-select" required>
                        <option value="member">Member</option>
                        <option value="treasurer">Treasurer</option>
                        <option value="secretary">Secretary</option>
                        <option value="vice_president">Vice President</option>
                        <option value="loan_committee">Loan Committee</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeRoleModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Role</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Suspend Member Modal -->
<div id="suspendModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Suspend Member</h3>
            <span class="close" onclick="closeSuspendModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form id="suspendForm" action="../../controllers/president/suspend_member.php" method="POST">
                <input type="hidden" name="group_id" value="<?= $groupId ?>">
                <input type="hidden" id="suspendMemberId" name="member_id">
                
                <div class="form-group">
                    <p>You are about to suspend <strong id="suspendMemberName"></strong>.</p>
                    <label for="suspendReason">Reason for Suspension</label>
                    <textarea id="suspendReason" name="reason" required 
                              placeholder="Please provide a reason for suspending this member..." rows="4"></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeSuspendModal()">Cancel</button>
                    <button type="submit" class="btn btn-danger">Suspend Member</button>
                </div>
            </form>
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

// Search functionality
function searchMembers(tab) {
    const input = document.getElementById('search' + tab.charAt(0).toUpperCase() + tab.slice(1));
    const filter = input.value.toLowerCase();
    const members = document.querySelectorAll(`#${tab.replace('_', '-')} .member-card`);
    
    members.forEach(member => {
        const text = member.textContent.toLowerCase();
        member.style.display = text.includes(filter) ? 'block' : 'none';
    });
}

// Modal functions
function openRoleModal(memberId, currentRole) {
    document.getElementById('roleMemberId').value = memberId;
    document.getElementById('newRole').value = currentRole;
    document.getElementById('roleModal').style.display = 'block';
}

function closeRoleModal() {
    document.getElementById('roleModal').style.display = 'none';
}

function openSuspendModal(memberId, memberName) {
    document.getElementById('suspendMemberId').value = memberId;
    document.getElementById('suspendMemberName').textContent = memberName;
    document.getElementById('suspendModal').style.display = 'block';
}

function closeSuspendModal() {
    document.getElementById('suspendModal').style.display = 'none';
}

// Action functions
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

function activateMember(memberId) {
    if (confirm('Are you sure you want to activate this member?')) {
        window.location.href = `../../controllers/president/activate_member.php?group_id=<?= $groupId ?>&member_id=${memberId}`;
    }
}

function viewMemberDetails(memberId) {
    // Implement member detail view
    alert('Member details view will be implemented here');
}

// Close modals when clicking outside
window.onclick = function(event) {
    const modals = document.getElementsByClassName('modal');
    for (let modal of modals) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
}
</script>

<style>
.management-container {
    margin-top: 2rem;
}

.tabs {
    display: flex;
    border-bottom: 1px solid #e9ecef;
    margin-bottom: 2rem;
}

.tab-button {
    padding: 1rem 2rem;
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;
    color: #6c757d;
    transition: all 0.3s;
}

.tab-button:hover {
    color: #007bff;
    background: #f8f9fa;
}

.tab-button.active {
    color: #007bff;
    border-bottom-color: #007bff;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.section-header {
    display: flex;
    justify-content: between;
    align-items: center;
    margin-bottom: 2rem;
}

.search-box {
    position: relative;
    width: 300px;
}

.search-box input {
    width: 100%;
    padding: 0.75rem 2.5rem 0.75rem 1rem;
    border: 1px solid #ddd;
    border-radius: 6px;
}

.search-box .material-icons-sharp {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
}

.members-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
}

.member-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 1.5rem;
    transition: transform 0.2s, box-shadow 0.2s;
}

.member-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.member-header {
    display: flex;
    align-items: start;
    margin-bottom: 1rem;
    gap: 1rem;
}

.member-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    object-fit: cover;
}

.member-basic-info {
    flex: 1;
}

.member-basic-info h4 {
    margin: 0 0 0.5rem 0;
    color: #2c3e50;
}

.role-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.role-badge.president { background: #dc2626; color: white; }
.role-badge.treasurer { background: #059669; color: white; }
.role-badge.secretary { background: #7c3aed; color: white; }
.role-badge.vice_president { background: #dc5e00; color: white; }
.role-badge.loan_committee { background: #0d7ea3; color: white; }
.role-badge.member { background: #6b7280; color: white; }

.member-actions {
    display: flex;
    gap: 0.5rem;
}

.btn-icon {
    background: none;
    border: none;
    padding: 0.5rem;
    border-radius: 6px;
    cursor: pointer;
    color: #6c757d;
    transition: all 0.2s;
}

.btn-icon:hover {
    background: #f8f9fa;
    color: #007bff;
}

.member-details {
    margin-bottom: 1rem;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    color: #6c757d;
    font-size: 0.9rem;
}

.member-financials {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #f1f3f4;
}

.financial-item {
    text-align: center;
}

.financial-item small {
    display: block;
    color: #6c757d;
    font-size: 0.8rem;
    margin-bottom: 0.25rem;
}

.financial-item strong {
    color: #2c3e50;
    font-size: 0.9rem;
}

.pending-list, .suspended-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.pending-card, .suspended-card {
    display: flex;
    justify-content: between;
    align-items: center;
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.pending-info, .suspended-info {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex: 1;
}

.pending-actions, .suspended-actions {
    display: flex;
    gap: 1rem;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: #6c757d;
}

.empty-state .material-icons-sharp {
    font-size: 4rem;
    color: #6c757d;
    margin-bottom: 1rem;
}

.empty-state h3 {
    margin: 0 0 0.5rem 0;
    color: #495057;
}

.form-select {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 1rem;
}

@media (max-width: 768px) {
    .members-grid {
        grid-template-columns: 1fr;
    }
    
    .member-header {
        flex-direction: column;
        text-align: center;
    }
    
    .member-actions {
        justify-content: center;
    }
    
    .pending-card, .suspended-card {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }
    
    .pending-info, .suspended-info {
        flex-direction: column;
    }
}
</style>

<?php include_once __DIR__ . '/../partials/footer.php'; ?>