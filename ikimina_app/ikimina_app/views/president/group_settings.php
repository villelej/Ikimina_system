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
                <h1>Group Settings - <?= htmlspecialchars($groupInfo['name']) ?></h1>
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

    <div class="settings-container">
        <form id="groupSettingsForm" action="../../controllers/president/update_group_settings.php" method="POST">
            <input type="hidden" name="group_id" value="<?= $groupId ?>">
            
            <div class="settings-sections">
                <!-- Basic Information -->
                <div class="settings-section">
                    <h3>
                        <span class="material-icons-sharp">info</span>
                        Basic Information
                    </h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="groupName">Group Name *</label>
                            <input type="text" id="groupName" name="name" value="<?= htmlspecialchars($groupInfo['name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="groupDescription">Description</label>
                            <textarea id="groupDescription" name="description" rows="3"><?= htmlspecialchars($groupInfo['description'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="settings-section">
                    <h3>
                        <span class="material-icons-sharp">contact_mail</span>
                        Contact Information
                    </h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="groupEmail">Email Address</label>
                            <input type="email" id="groupEmail" name="email" value="<?= htmlspecialchars($groupInfo['email'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="supportContact">Support Contact</label>
                            <input type="text" id="supportContact" name="support_contact" value="<?= htmlspecialchars($groupInfo['support_contact'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <!-- Location Information -->
                <div class="settings-section">
                    <h3>
                        <span class="material-icons-sharp">location_on</span>
                        Location
                    </h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="province">Province</label>
                            <input type="text" id="province" name="province" value="<?= htmlspecialchars($groupInfo['province'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="district">District</label>
                            <input type="text" id="district" name="district" value="<?= htmlspecialchars($groupInfo['district'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="sector">Sector</label>
                            <input type="text" id="sector" name="sector" value="<?= htmlspecialchars($groupInfo['sector'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="cell">Cell</label>
                            <input type="text" id="cell" name="cell" value="<?= htmlspecialchars($groupInfo['cell'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="village">Village</label>
                            <input type="text" id="village" name="village" value="<?= htmlspecialchars($groupInfo['village'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <!-- Contribution Settings -->
                <div class="settings-section">
                    <h3>
                        <span class="material-icons-sharp">savings</span>
                        Contribution Settings
                    </h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="contributionAmount">Contribution Amount (RWF) *</label>
                            <input type="number" id="contributionAmount" name="contribution_amount" 
                                   value="<?= $groupInfo['contribution_amount'] ?? '5000' ?>" step="100" required>
                        </div>
                        <div class="form-group">
                            <label for="contributionFrequency">Contribution Frequency *</label>
                            <select id="contributionFrequency" name="contribution_frequency" required>
                                <option value="weekly" <?= ($groupInfo['contribution_frequency'] ?? '') == 'weekly' ? 'selected' : '' ?>>Weekly</option>
                                <option value="monthly" <?= ($groupInfo['contribution_frequency'] ?? '') == 'monthly' ? 'selected' : '' ?>>Monthly</option>
                                <option value="quarterly" <?= ($groupInfo['contribution_frequency'] ?? '') == 'quarterly' ? 'selected' : '' ?>>Quarterly</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="contributionMethod">Contribution Method</label>
                            <select id="contributionMethod" name="contribution_method">
                                <option value="bank" <?= ($groupInfo['contribution_method'] ?? '') == 'bank' ? 'selected' : '' ?>>Bank Transfer</option>
                                <option value="mobile_money" <?= ($groupInfo['contribution_method'] ?? '') == 'mobile_money' ? 'selected' : '' ?>>Mobile Money</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Loan Settings -->
                <div class="settings-section">
                    <h3>
                        <span class="material-icons-sharp">request_quote</span>
                        Loan Settings
                    </h3>
                    <div class="form-group">
                        <label for="loanRules">Loan Rules & Policies</label>
                        <textarea id="loanRules" name="loan_rules" rows="4" placeholder="Enter loan rules and policies..."><?= htmlspecialchars($groupInfo['loan_rules'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- Investment Settings -->
                <div class="settings-section">
                    <h3>
                        <span class="material-icons-sharp">trending_up</span>
                        Investment Settings
                    </h3>
                    <div class="form-group">
                        <label for="investmentRules">Investment Rules</label>
                        <textarea id="investmentRules" name="investment_rules" rows="4" placeholder="Enter investment rules and policies..."><?= htmlspecialchars($groupInfo['investment_rules'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- Group Objectives -->
                <div class="settings-section">
                    <h3>
                        <span class="material-icons-sharp">flag</span>
                        Group Objectives
                    </h3>
                    <div class="form-group">
                        <label for="objective">Group Objectives</label>
                        <textarea id="objective" name="objective" rows="4" placeholder="Enter group objectives and goals..."><?= htmlspecialchars($groupInfo['objective'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-outline" onclick="resetForm()">Reset Changes</button>
                <button type="submit" class="btn btn-primary">
                    <span class="material-icons-sharp">save</span>
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function resetForm() {
    if (confirm('Are you sure you want to reset all changes?')) {
        document.getElementById('groupSettingsForm').reset();
    }
}

// Form validation
document.getElementById('groupSettingsForm').addEventListener('submit', function(e) {
    const contributionAmount = document.getElementById('contributionAmount').value;
    if (contributionAmount < 1000) {
        e.preventDefault();
        alert('Contribution amount must be at least RWF 1,000');
        return false;
    }
    return true;
});
</script>

<style>
.settings-container {
    margin-top: 2rem;
}

.settings-sections {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.settings-section {
    background: white;
    border-radius: 10px;
    padding: 2rem;
    border: 1px solid #e9ecef;
}

.settings-section h3 {
    margin: 0 0 1.5rem 0;
    color: #2c3e50;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #f1f3f4;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #2c3e50;
}

.form-group input,
.form-group select,
.form-group textarea {
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 1rem;
    transition: border-color 0.3s;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #007bff;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid #e9ecef;
}

@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
}
</style>

<?php include_once __DIR__ . '/../partials/footer.php'; ?>