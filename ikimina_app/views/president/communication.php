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
    $groupInfo = $presidentController->getDashboardData()['group_info'];
    $activeMembers = $presidentController->getGroupMembers('active');
    
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
                <h1>Communication Center - <?= htmlspecialchars($groupInfo['name']) ?></h1>
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

    <div class="communication-container">
        <!-- Quick Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <span class="material-icons-sharp">groups</span>
                <h3><?= count($activeMembers) ?></h3>
                <p>Total Members</p>
            </div>
            <div class="stat-card">
                <span class="material-icons-sharp">phone_android</span>
                <h3><?= count($activeMembers) ?></h3>
                <p>Mobile Users</p>
            </div>
            <div class="stat-card">
                <span class="material-icons-sharp">email</span>
                <h3><?= count($activeMembers) ?></h3>
                <p>Email Users</p>
            </div>
        </div>

        <!-- Communication Tabs -->
        <div class="tabs">
            <button class="tab-button active" onclick="openTab('announcements')">
                <span class="material-icons-sharp">campaign</span>
                Announcements
            </button>
            <button class="tab-button" onclick="openTab('sms')">
                <span class="material-icons-sharp">sms</span>
                SMS Messages
            </button>
            <button class="tab-button" onclick="openTab('notifications')">
                <span class="material-icons-sharp">notifications</span>
                In-App Notifications
            </button>
        </div>

        <!-- Announcements Tab -->
        <div id="announcements" class="tab-content active">
            <div class="section-header">
                <h3>Send Announcement</h3>
            </div>
            
            <div class="announcement-form-container">
                <form id="announcementForm" action="../../controllers/president/send_announcement.php" method="POST">
                    <input type="hidden" name="group_id" value="<?= $groupId ?>">
                    
                    <div class="form-group">
                        <label for="announcementTitle">Title *</label>
                        <input type="text" id="announcementTitle" name="title" required 
                               placeholder="Enter announcement title">
                    </div>
                    
                    <div class="form-group">
                        <label for="announcementMessage">Message *</label>
                        <textarea id="announcementMessage" name="message" required 
                                  placeholder="Enter your announcement message..." rows="6"></textarea>
                        <div class="char-count">
                            <span id="charCount">0</span> characters
                        </div>
                    </div>
                    
                    <div class="delivery-options">
                        <h4>Delivery Options</h4>
                        <div class="options-grid">
                            <div class="option-card">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="send_in_app" value="1" checked>
                                    <span class="checkmark"></span>
                                    <div class="option-content">
                                        <span class="material-icons-sharp">notifications</span>
                                        <div>
                                            <strong>In-App Notification</strong>
                                            <small>Send to all members via app notification</small>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            
                            <div class="option-card">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="send_sms" value="1">
                                    <span class="checkmark"></span>
                                    <div class="option-content">
                                        <span class="material-icons-sharp">sms</span>
                                        <div>
                                            <strong>SMS Message</strong>
                                            <small>Send as SMS to all members (cost may apply)</small>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            
                            <div class="option-card">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="send_email" value="1">
                                    <span class="checkmark"></span>
                                    <div class="option-content">
                                        <span class="material-icons-sharp">email</span>
                                        <div>
                                            <strong>Email</strong>
                                            <small>Send as email to all members with email addresses</small>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="recipient-selection">
                        <h4>Recipients</h4>
                        <div class="recipient-options">
                            <label class="radio-label">
                                <input type="radio" name="recipients" value="all" checked>
                                <span class="radiomark"></span>
                                All Active Members (<?= count($activeMembers) ?>)
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="recipients" value="role">
                                <span class="radiomark"></span>
                                Specific Role
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="recipients" value="custom">
                                <span class="radiomark"></span>
                                Custom Selection
                            </label>
                        </div>
                        
                        <!-- Role Selection (hidden by default) -->
                        <div id="roleSelection" class="selection-options" style="display: none;">
                            <select name="selected_role" class="form-select">
                                <option value="member">Members</option>
                                <option value="treasurer">Treasurers</option>
                                <option value="secretary">Secretaries</option>
                                <option value="loan_committee">Loan Committee</option>
                            </select>
                        </div>
                        
                        <!-- Custom Selection (hidden by default) -->
                        <div id="customSelection" class="selection-options" style="display: none;">
                            <div class="members-list">
                                <?php foreach ($activeMembers as $member): ?>
                                <label class="checkbox-label member-checkbox">
                                    <input type="checkbox" name="selected_members[]" value="<?= $member['id'] ?>">
                                    <span class="checkmark"></span>
                                    <div class="member-info-small">
                                        <strong><?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?></strong>
                                        <small><?= htmlspecialchars($member['phone']) ?></small>
                                    </div>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-outline" onclick="previewAnnouncement()">
                            <span class="material-icons-sharp">preview</span>
                            Preview
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <span class="material-icons-sharp">send</span>
                            Send Announcement
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- SMS Tab -->
        <div id="sms" class="tab-content">
            <div class="section-header">
                <h3>SMS Messaging</h3>
                <div class="sms-info">
                    <span class="sms-balance">
                        <span class="material-icons-sharp">account_balance_wallet</span>
                        SMS Balance: <strong>1,250</strong> units
                    </span>
                </div>
            </div>
            
            <div class="sms-form-container">
                <form id="smsForm" action="../../controllers/president/send_sms.php" method="POST">
                    <input type="hidden" name="group_id" value="<?= $groupId ?>">
                    
                    <div class="form-group">
                        <label for="smsMessage">SMS Message *</label>
                        <textarea id="smsMessage" name="message" required 
                                  placeholder="Enter your SMS message (160 characters max)..." 
                                  maxlength="160" rows="4"></textarea>
                        <div class="char-count">
                            <span id="smsCharCount">0</span>/160 characters
                        </div>
                    </div>
                    
                    <div class="recipient-selection">
                        <h4>Recipients</h4>
                        <div class="recipient-options">
                            <label class="radio-label">
                                <input type="radio" name="sms_recipients" value="all" checked>
                                <span class="radiomark"></span>
                                All Active Members (<?= count($activeMembers) ?>)
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="sms_recipients" value="custom">
                                <span class="radiomark"></span>
                                Custom Selection
                            </label>
                        </div>
                        
                        <!-- Custom SMS Selection (hidden by default) -->
                        <div id="smsCustomSelection" class="selection-options" style="display: none;">
                            <div class="members-list compact">
                                <?php foreach ($activeMembers as $member): ?>
                                <label class="checkbox-label member-checkbox">
                                    <input type="checkbox" name="sms_selected_members[]" value="<?= $member['id'] ?>">
                                    <span class="checkmark"></span>
                                    <span class="member-name"><?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?></span>
                                    <span class="member-phone"><?= htmlspecialchars($member['phone']) ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="sms-cost-estimate">
                        <p>
                            <strong>Cost Estimate:</strong>
                            <span id="smsCost">0</span> SMS units 
                            (<span id="recipientCount">0</span> recipients × <span id="messageCount">0</span> messages)
                        </p>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <span class="material-icons-sharp">send</span>
                            Send SMS
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Notifications Tab -->
        <div id="notifications" class="tab-content">
            <div class="section-header">
                <h3>Notification History</h3>
            </div>
            
            <div class="notifications-list">
                <!-- This would be populated from database -->
                <div class="notification-item">
                    <div class="notification-icon">
                        <span class="material-icons-sharp">campaign</span>
                    </div>
                    <div class="notification-content">
                        <h5>Monthly Meeting Reminder</h5>
                        <p>Don't forget our monthly general meeting this Friday at 2:00 PM</p>
                        <div class="notification-meta">
                            <span class="material-icons-sharp">schedule</span>
                            Sent 2 days ago
                            <span class="material-icons-sharp">people</span>
                            To all members
                            <span class="material-icons-sharp">notifications</span>
                            45/50 read
                        </div>
                    </div>
                </div>
                
                <div class="notification-item">
                    <div class="notification-icon">
                        <span class="material-icons-sharp">request_quote</span>
                    </div>
                    <div class="notification-content">
                        <h5>Loan Application Update</h5>
                        <p>New loan applications are waiting for your approval</p>
                        <div class="notification-meta">
                            <span class="material-icons-sharp">schedule</span>
                            Sent 1 week ago
                            <span class="material-icons-sharp">people</span>
                            To loan committee
                            <span class="material-icons-sharp">notifications</span>
                            3/5 read
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div id="previewModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Announcement Preview</h3>
            <span class="close" onclick="closePreviewModal()">&times;</span>
        </div>
        <div class="modal-body">
            <div class="preview-container">
                <h4 id="previewTitle"></h4>
                <div class="preview-meta">
                    <span>From: <?= htmlspecialchars($groupInfo['name']) ?> President</span>
                    <span>Date: <?= date('M j, Y g:i A') ?></span>
                </div>
                <div class="preview-content" id="previewContent"></div>
                <div class="preview-recipients" id="previewRecipients"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closePreviewModal()">Close</button>
            <button type="button" class="btn btn-primary" onclick="sendAnnouncement()">Send Now</button>
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

// Character count for announcement
document.getElementById('announcementMessage').addEventListener('input', function() {
    document.getElementById('charCount').textContent = this.value.length;
});

// Character count for SMS
document.getElementById('smsMessage').addEventListener('input', function() {
    const count = this.value.length;
    document.getElementById('smsCharCount').textContent = count;
    
    // Calculate message count (160 chars per SMS)
    const messageCount = Math.ceil(count / 160);
    document.getElementById('messageCount').textContent = messageCount;
    calculateSMSCost();
});

// Recipient selection handling
document.querySelectorAll('input[name="recipients"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.getElementById('roleSelection').style.display = 'none';
        document.getElementById('customSelection').style.display = 'none';
        
        if (this.value === 'role') {
            document.getElementById('roleSelection').style.display = 'block';
        } else if (this.value === 'custom') {
            document.getElementById('customSelection').style.display = 'block';
        }
    });
});

// SMS recipient selection handling
document.querySelectorAll('input[name="sms_recipients"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.getElementById('smsCustomSelection').style.display = 
            this.value === 'custom' ? 'block' : 'none';
        calculateSMSCost();
    });
});

// Calculate SMS cost
function calculateSMSCost() {
    let recipientCount = <?= count($activeMembers) ?>;
    
    if (document.querySelector('input[name="sms_recipients"]:checked').value === 'custom') {
        recipientCount = document.querySelectorAll('input[name="sms_selected_members[]"]:checked').length;
    }
    
    const messageCount = parseInt(document.getElementById('messageCount').textContent) || 1;
    const totalCost = recipientCount * messageCount;
    
    document.getElementById('recipientCount').textContent = recipientCount;
    document.getElementById('smsCost').textContent = totalCost.toLocaleString();
}

// Update SMS cost when custom recipients change
document.addEventListener('change', function(e) {
    if (e.target.name === 'sms_selected_members[]') {
        calculateSMSCost();
    }
});

// Preview announcement
function previewAnnouncement() {
    const title = document.getElementById('announcementTitle').value;
    const content = document.getElementById('announcementMessage').value;
    
    if (!title || !content) {
        alert('Please enter both title and message to preview.');
        return;
    }
    
    document.getElementById('previewTitle').textContent = title;
    document.getElementById('previewContent').textContent = content;
    
    // Show recipients
    const recipients = document.querySelector('input[name="recipients"]:checked').value;
    let recipientsText = 'All active members';
    if (recipients === 'role') {
        const role = document.querySelector('select[name="selected_role"]').value;
        recipientsText = `All ${role}s`;
    } else if (recipients === 'custom') {
        const selectedCount = document.querySelectorAll('input[name="selected_members[]"]:checked').length;
        recipientsText = `${selectedCount} selected members`;
    }
    document.getElementById('previewRecipients').textContent = `To: ${recipientsText}`;
    
    document.getElementById('previewModal').style.display = 'block';
}

function closePreviewModal() {
    document.getElementById('previewModal').style.display = 'none';
}

function sendAnnouncement() {
    document.getElementById('announcementForm').submit();
}

// Initialize calculations
calculateSMSCost();

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
.communication-container {
    margin-top: 2rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.announcement-form-container,
.sms-form-container {
    background: white;
    border-radius: 10px;
    padding: 2rem;
    border: 1px solid #e9ecef;
}

.char-count {
    text-align: right;
    color: #6c757d;
    font-size: 0.8rem;
    margin-top: 0.5rem;
}

.delivery-options {
    margin: 2rem 0;
}

.delivery-options h4 {
    margin-bottom: 1rem;
    color: #2c3e50;
}

.options-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1rem;
}

.option-card {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 1rem;
    transition: border-color 0.3s;
}

.option-card:hover {
    border-color: #007bff;
}

.option-content {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.option-content .material-icons-sharp {
    font-size: 2rem;
    color: #007bff;
}

.option-content strong {
    display: block;
    margin-bottom: 0.25rem;
    color: #2c3e50;
}

.option-content small {
    color: #6c757d;
    font-size: 0.8rem;
}

.recipient-selection {
    margin: 2rem 0;
}

.recipient-selection h4 {
    margin-bottom: 1rem;
    color: #2c3e50;
}

.recipient-options {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.radio-label {
    display: flex;
    align-items: center;
    cursor: pointer;
    padding: 0.75rem;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    transition: all 0.3s;
}

.radio-label:hover {
    border-color: #007bff;
    background: #f8f9fa;
}

.radiomark {
    display: inline-block;
    width: 18px;
    height: 18px;
    border: 2px solid #ddd;
    border-radius: 50%;
    margin-right: 1rem;
    position: relative;
}

.radio-label input:checked + .radiomark {
    border-color: #007bff;
}

.radio-label input:checked + .radiomark::after {
    content: '';
    position: absolute;
    top: 3px;
    left: 3px;
    width: 8px;
    height: 8px;
    background: #007bff;
    border-radius: 50%;
}

.selection-options {
    margin-top: 1rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 6px;
}

.members-list {
    max-height: 200px;
    overflow-y: auto;
}

.members-list.compact {
    max-height: 150px;
}

.member-checkbox {
    display: flex;
    align-items: center;
    padding: 0.5rem;
    margin-bottom: 0.5rem;
    background: white;
    border-radius: 4px;
}

.member-info-small {
    flex: 1;
}

.member-info-small strong {
    display: block;
    font-size: 0.9rem;
}

.member-info-small small {
    color: #6c757d;
    font-size: 0.8rem;
}

.member-name {
    flex: 1;
    font-size: 0.9rem;
}

.member-phone {
    color: #6c757d;
    font-size: 0.8rem;
}

.sms-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.sms-balance {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: #e7f3ff;
    border-radius: 20px;
    color: #007bff;
    font-size: 0.9rem;
}

.sms-cost-estimate {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 6px;
    padding: 1rem;
    margin: 1.5rem 0;
    color: #856404;
}

.notifications-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.notification-item {
    display: flex;
    gap: 1rem;
    padding: 1.5rem;
    background: white;
    border-radius: 10px;
    border: 1px solid #e9ecef;
}

.notification-icon {
    background: #007bff;
    color: white;
    padding: 1rem;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.notification-content {
    flex: 1;
}

.notification-content h5 {
    margin: 0 0 0.5rem 0;
    color: #2c3e50;
}

.notification-content p {
    margin: 0 0 1rem 0;
    color: #6c757d;
}

.notification-meta {
    display: flex;
    align-items: center;
    gap: 1rem;
    color: #6c757d;
    font-size: 0.8rem;
}

.notification-meta .material-icons-sharp {
    font-size: 1rem;
}

.preview-container {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.preview-container h4 {
    margin: 0 0 1rem 0;
    color: #2c3e50;
    border-bottom: 2px solid #007bff;
    padding-bottom: 0.5rem;
}

.preview-meta {
    display: flex;
    justify-content: between;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #f1f3f4;
    color: #6c757d;
    font-size: 0.9rem;
}

.preview-content {
    line-height: 1.6;
    margin-bottom: 1.5rem;
    white-space: pre-wrap;
}

.preview-recipients {
    padding-top: 1rem;
    border-top: 1px solid #f1f3f4;
    color: #6c757d;
    font-style: italic;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    padding: 1.5rem;
    border-top: 1px solid #e9ecef;
}

@media (max-width: 768px) {
    .options-grid {
        grid-template-columns: 1fr;
    }
    
    .recipient-options {
        flex-direction: column;
    }
    
    .notification-item {
        flex-direction: column;
        text-align: center;
    }
    
    .notification-meta {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .preview-meta {
        flex-direction: column;
        gap: 0.5rem;
    }
}
</style>

<?php include_once __DIR__ . '/../partials/footer.php'; ?>