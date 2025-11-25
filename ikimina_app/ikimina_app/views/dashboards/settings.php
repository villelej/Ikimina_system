<?php
// views/settings.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../views/auth/login.php');
    exit;
}

// Load database
require_once __DIR__ . '/../../config/db.php';

// Load models
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/GroupMember.php'; // if needed

// Load controller
require_once __DIR__ . '/../../controllers/SettingsController.php';

// Get current user ID
$userId = $_SESSION['user_id'];

// Instantiate controller with PDO and user ID
$settingsController = new SettingsController($pdo, $userId);

// handle save
$successMsg = $errorMsg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $ok = $settingsController->saveSettings($userId, $_POST);
    if ($ok) {
        $successMsg = "Settings saved successfully.";
    } else {
        $errorMsg = "Failed to save settings.";
    }
}

// load data
$data = $settingsController->viewSettings($userId);
$user = $data['user'];
$settings = $data['settings'] ?? [];

// display name and profile pic (use User helper)
$userModel = new User($pdo);
$displayName = $userModel->getDisplayName($user);
$profilePicture = !empty($user['profile_pic']) ? htmlspecialchars($user['profile_pic']) : '../../assets/images/default-avatar.jpg';

// theme class for body
$themeClass = ($settings['theme'] ?? 'auto') === 'dark' ? 'dark-theme' : (($settings['theme'] ?? 'auto') === 'light' ? 'light-theme' : '');
?>
<!doctype html>
<html lang="<?= htmlspecialchars($_SESSION['lang'] ?? 'en') ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Settings - Ikimina</title>
<link rel="stylesheet" href="../../assets/css/settings.css">
</head>
<body class="<?= $themeClass ?>">
<div class="settings-container">
    <aside class="settings-sidebar">
        <div class="profile">
            <img src="<?= $profilePicture ?>" alt="avatar" class="avatar">
            <div class="profile-info">
                <strong><?= htmlspecialchars($displayName) ?></strong>
                <small>@<?= htmlspecialchars($user['username'] ?? '') ?></small>
                <div class="role"><?= htmlspecialchars($user['role_global'] ?? 'member') ?></div>
            </div>
        </div>
        <nav class="settings-nav">
            <button data-tab="profile" class="tab-btn active">Profile</button>
            <button data-tab="account" class="tab-btn">Account</button>
            <button data-tab="privacy" class="tab-btn">Privacy</button>
            <button data-tab="notifications" class="tab-btn">Notifications</button>
            <button data-tab="display" class="tab-btn">Display & Accessibility</button>
            <button data-tab="language" class="tab-btn">Language & Region</button>
            <button data-tab="payments" class="tab-btn">Payments</button>
            <button data-tab="data" class="tab-btn">Data & Storage</button>
            <button data-tab="help" class="tab-btn">Help & Support</button>
        </nav>
        <div class="version">Ikimina v1.0.0</div>
    </aside>

    <main class="settings-main">
        <header class="settings-header">
            <h1>Settings</h1>
            <?php if ($successMsg): ?>
                <div class="alert success"><?= htmlspecialchars($successMsg) ?></div>
            <?php endif; ?>
            <?php if ($errorMsg): ?>
                <div class="alert error"><?= htmlspecialchars($errorMsg) ?></div>
            <?php endif; ?>
        </header>

        <form method="post" id="settingsForm" novalidate>
            <input type="hidden" name="save_settings" value="1">

            <!-- PROFILE TAB -->
            <section class="tab-panel active" id="tab-profile">
                <h2>Profile Settings</h2>
                <div class="row">
                    <div class="col">
                        <label>Profile Photo</label>
                        <div class="avatar-large">
                            <img src="<?= $profilePicture ?>" alt="avatar">
                        </div>
                        <small>Upload handled by profile endpoint (not in this form)</small>
                    </div>
                    <div class="col">
                        <label>Full name</label>
                        <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" placeholder="Full name">
                        <label>First name</label>
                        <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name'] ?? '') ?>">
                        <label>Middle name</label>
                        <input type="text" name="middle_name" value="<?= htmlspecialchars($user['middle_name'] ?? '') ?>">
                        <label>Last name</label>
                        <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name'] ?? '') ?>">
                        <label>Phone (read-only)</label>
                        <input type="text" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" readonly>
                        <label>Role</label>
                        <input type="text" value="<?= htmlspecialchars($user['role_global'] ?? '') ?>" readonly>
                        <label>About / Bio</label>
                        <textarea name="bio" placeholder="A short bio..."><?= htmlspecialchars($settings['bio'] ?? '') ?></textarea>
                        <label>Membership status</label>
                        <select name="membership_status" disabled>
                            <option><?= htmlspecialchars($user['status'] ?? 'pending') ?></option>
                        </select>
                    </div>
                </div>
            </section>

            <!-- ACCOUNT TAB -->
            <section class="tab-panel" id="tab-account">
                <h2>Account Settings</h2>
                <div class="card">
                    <h3>Change Password</h3>
                    <label>Old password</label>
                    <input type="password" name="old_password" id="old_password">
                    <label>New password</label>
                    <input type="password" name="new_password" id="new_password" minlength="8">
                    <label>Confirm new password</label>
                    <input type="password" name="confirm_password" id="confirm_password">
                    <button type="button" id="changePasswordBtn" class="btn">Change Password</button>
                </div>

                <div class="card">
                    <h3>Two-step verification</h3>
                    <label>
                        <input type="checkbox" name="twofa_enabled" <?= !empty($settings['twofa_enabled']) ? 'checked' : '' ?>>
                        Enable two-step verification
                    </label>
                    <p class="muted">We support App-based OTP (TOTP). Setup is available in your profile.</p>
                </div>

                <div class="card">
                    <h3>Linked Devices</h3>
                    <p>Manage web sessions and linked devices (listed below)</p>
                    <?php
                        $devices = json_decode($settings['linked_devices'] ?? '[]', true);
                        if (!empty($devices)):
                    ?>
                        <ul class="device-list">
                        <?php foreach ($devices as $dev): ?>
                            <li>
                                <strong><?= htmlspecialchars($dev['name'] ?? 'Unknown') ?></strong>
                                <small><?= htmlspecialchars($dev['last_seen'] ?? '') ?></small>
                                <button type="button" class="btn btn-sm btn-outline">Logout</button>
                            </li>
                        <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="muted">No linked devices.</p>
                    <?php endif; ?>
                </div>

                <div class="card danger">
                    <h3>Delete / Deactivate Account</h3>
                    <p class="muted">This will de-activate your account — admin approval required for permanent deletion.</p>
                    <label><input type="checkbox" id="confirmDelete"> I understand consequences</label>
                    <button type="button" id="deleteAccountBtn" class="btn btn-danger">Deactivate Account</button>
                </div>
            </section>

            <!-- PRIVACY TAB -->
            <section class="tab-panel" id="tab-privacy">
                <h2>Privacy Settings</h2>
                <div class="row">
                    <div class="col">
                        <label>Who can see my contributions</label>
                        <select name="contribution_visibility">
                            <option value="everyone" <?= ($settings['contribution_visibility'] ?? '') === 'everyone' ? 'selected' : '' ?>>Everyone</option>
                            <option value="groups_only" <?= ($settings['contribution_visibility'] ?? '') === 'groups_only' ? 'selected' : '' ?>>Groups only</option>
                            <option value="private" <?= ($settings['contribution_visibility'] ?? '') === 'private' ? 'selected' : '' ?>>Only me</option>
                        </select>

                        <label>Who can see my loans</label>
                        <select name="loan_visibility">
                            <option value="everyone" <?= ($settings['loan_visibility'] ?? '') === 'everyone' ? 'selected' : '' ?>>Everyone</option>
                            <option value="groups_only" <?= ($settings['loan_visibility'] ?? '') === 'groups_only' ? 'selected' : '' ?>>Groups only</option>
                            <option value="private" <?= ($settings['loan_visibility'] ?? '') === 'private' ? 'selected' : '' ?>>Only me</option>
                        </select>

                        <label><input type="checkbox" name="hide_phone" <?= !empty($settings['hide_phone']) ? 'checked' : '' ?>> Hide phone number</label>
                        <label><input type="checkbox" name="show_last_active" <?= !empty($settings['show_last_active']) ? 'checked' : '' ?>> Show Last Active</label>

                        <label>Block / Report</label>
                        <input type="text" name="block_user" placeholder="Enter username to block/report">
                    </div>
                </div>
            </section>

            <!-- NOTIFICATIONS TAB -->
            <section class="tab-panel" id="tab-notifications">
                <h2>Notifications</h2>
                <div class="grid-3">
                    <label><input type="checkbox" name="notification_contribution" <?= !empty($settings['notification_contribution']) ? 'checked' : '' ?>> Contribution reminders</label>
                    <label><input type="checkbox" name="notification_loan" <?= !empty($settings['notification_loan']) ? 'checked' : '' ?>> Loan repayment alerts</label>
                    <label><input type="checkbox" name="notification_meeting" <?= !empty($settings['notification_meeting']) ? 'checked' : '' ?>> Meeting notifications</label>
                    <label>Mute groups (enter comma separated IDs)</label>
                    <input type="text" name="mute_groups" value="<?= htmlspecialchars(is_string($settings['mute_groups']) ? $settings['mute_groups'] : '') ?>" placeholder="e.g., 12,45">
                    <label>Custom tone (URL or file name)</label>
                    <input type="text" name="custom_tone" value="<?= htmlspecialchars($settings['custom_tone'] ?? '') ?>">
                </div>
            </section>

            <!-- DISPLAY TAB -->
            <section class="tab-panel" id="tab-display">
                <h2>Display & Accessibility</h2>
                <div class="card">
                    <label>Theme</label>
                    <select name="theme">
                        <option value="auto" <?= ($settings['theme'] ?? '') === 'auto' ? 'selected' : '' ?>>Auto</option>
                        <option value="light" <?= ($settings['theme'] ?? '') === 'light' ? 'selected' : '' ?>>Light</option>
                        <option value="dark" <?= ($settings['theme'] ?? '') === 'dark' ? 'selected' : '' ?>>Dark</option>
                    </select>

                    <label>Display size</label>
                    <select name="display_size">
                        <option value="small" <?= ($settings['display_size'] ?? '') === 'small' ? 'selected' : '' ?>>Small</option>
                        <option value="medium" <?= ($settings['display_size'] ?? '') === 'medium' ? 'selected' : '' ?>>Medium</option>
                        <option value="large" <?= ($settings['display_size'] ?? '') === 'large' ? 'selected' : '' ?>>Large</option>
                    </select>

                    <label>Font scale (percent)</label>
                    <input type="number" name="font_scale" value="<?= htmlspecialchars($settings['font_scale'] ?? 100) ?>" min="80" max="150">

                    <label><input type="checkbox" name="high_contrast" <?= !empty($settings['high_contrast']) ? 'checked' : '' ?>> High contrast mode</label>
                </div>
            </section>

            <!-- LANGUAGE TAB -->
            <section class="tab-panel" id="tab-language">
                <h2>Language & Region</h2>
                <div class="row">
                    <label>App Language</label>
                    <select name="language">
                        <option value="en" <?= ($settings['language'] ?? '') === 'en' ? 'selected' : '' ?>>English</option>
                        <option value="rw" <?= ($settings['language'] ?? '') === 'rw' ? 'selected' : '' ?>>Kinyarwanda</option>
                        <option value="fr" <?= ($settings['language'] ?? '') === 'fr' ? 'selected' : '' ?>>French</option>
                    </select>

                    <label>Date format</label>
                    <select name="date_format">
                        <option value="DD/MM/YYYY" <?= ($settings['date_format'] ?? '') === 'DD/MM/YYYY' ? 'selected' : '' ?>>DD/MM/YYYY</option>
                        <option value="MM/DD/YYYY" <?= ($settings['date_format'] ?? '') === 'MM/DD/YYYY' ? 'selected' : '' ?>>MM/DD/YYYY</option>
                    </select>

                    <label>Currency</label>
                    <input type="text" name="currency" value="<?= htmlspecialchars($settings['currency'] ?? 'RWF') ?>">

                    <label>Number format</label>
                    <select name="number_format">
                        <option value="1,000.00" <?= ($settings['number_format'] ?? '') === '1,000.00' ? 'selected' : '' ?>>1,000.00</option>
                        <option value="1.000,00" <?= ($settings['number_format'] ?? '') === '1.000,00' ? 'selected' : '' ?>>1.000,00</option>
                    </select>
                </div>
            </section>

            <!-- PAYMENTS TAB -->
            <section class="tab-panel" id="tab-payments">
                <h2>Payment & Transactions</h2>
                <p>Add and manage payment methods in your profile (mobile money or bank). Backend endpoints required to link accounts.</p>
                <div class="card">
                    <label>Linked payment methods</label>
                    <ul>
                        <li>MTN Mobile Money (not linked)</li>
                        <li>Airtel Money (not linked)</li>
                        <li>Bank account (not linked)</li>
                    </ul>
                    <small>Integration: use a secure server-side flow to register tokens or verify accounts with OTP.</small>
                </div>
            </section>

            <!-- DATA TAB -->
            <section class="tab-panel" id="tab-data">
                <h2>Data & Storage</h2>
                <label><input type="checkbox" name="auto_download_receipts" <?= !empty($settings['auto_download_receipts']) ? 'checked' : '' ?>> Auto-download receipts</label>
                <label>Limit mobile data usage (MB)</label>
                <input type="number" name="data_usage_limit_mb" value="<?= htmlspecialchars($settings['data_usage_limit_mb'] ?? '') ?>">
                <label><input type="checkbox" name="backup_to_cloud" <?= !empty($settings['backup_to_cloud']) ? 'checked' : '' ?>> Backup to cloud</label>
                <div class="muted">Export: <a href="export.php?format=pdf">PDF</a> • <a href="export.php?format=csv">CSV</a></div>
            </section>

            <!-- HELP TAB -->
            <section class="tab-panel" id="tab-help">
                <h2>Help & Support</h2>
                <ul>
                    <li><a href="faq.php">FAQs</a></li>
                    <li><a href="report_problem.php">Report a problem</a></li>
                    <li><a href="contact_admin.php">Contact admin/support</a></li>
                    <li><a href="terms.php">Terms &amp; Privacy Policy</a></li>
                </ul>
            </section>

            <footer class="settings-footer">
                <button type="submit" class="btn btn-primary">Save changes</button>
                <a href="main_dashboard.php" class="btn btn-outline">Cancel</a>
            </footer>
        </form>
    </main>
</div>

<script>
/* client side interactions */
document.addEventListener('DOMContentLoaded', function(){
    // simple tab switcher
    document.querySelectorAll('.settings-nav .tab-btn').forEach(btn=>{
        btn.addEventListener('click', e=>{
            document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
            btn.classList.add('active');
            const tab = btn.dataset.tab;
            document.querySelectorAll('.tab-panel').forEach(p=>{
                p.classList.toggle('active', p.id === 'tab-' + tab);
            });
        });
    });

    // change password action (AJAX)
    document.getElementById('changePasswordBtn').addEventListener('click', function(){
        const oldP = document.getElementById('old_password').value;
        const newP = document.getElementById('new_password').value;
        const confirmP = document.getElementById('confirm_password').value;
        if (newP.length < 8) { alert('New password must be at least 8 characters'); return; }
        if (newP !== confirmP) { alert('Passwords do not match'); return; }

        fetch('change_password.php', {
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body: JSON.stringify({old_password:oldP, new_password:newP})
        }).then(r=>r.json()).then(res=>{
            if (res.success) alert('Password changed');
            else alert(res.message || 'Failed to change password');
        }).catch(()=>alert('Server error'));
    });

    // delete account
    document.getElementById('deleteAccountBtn').addEventListener('click', function(){
        if (!document.getElementById('confirmDelete').checked) { alert('Please confirm'); return; }
        if (!confirm('Are you sure you want to deactivate your account?')) return;
        fetch('deactivate_account.php', {method:'POST'}).then(r=>r.json()).then(res=>{
            if (res.success) {
                alert('Account deactivated. Logging out...');
                window.location.href = 'logout.php';
            } else alert('Failed: ' + (res.message || 'server error'));
        });
    });

    // theme auto: respect system preference if 'auto'
    const themeSelect = document.querySelector('select[name="theme"]');
    if (themeSelect && themeSelect.value === 'auto') {
        const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        document.body.classList.toggle('dark-theme', prefersDark);
    }
});
</script>
</body>
</html>
