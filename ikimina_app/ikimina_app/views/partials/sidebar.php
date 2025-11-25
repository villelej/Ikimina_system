<?php
// sidebar.php - Ensure variables are available
if (!isset($userData)) {
    // Try to get user data from session or set defaults
    $userData = [
        'first_name' => $_SESSION['first_name'] ?? 'User',
        'last_name' => $_SESSION['last_name'] ?? '',
        'full_name' => $_SESSION['full_name'] ?? 'User'
    ];
}

$profilePicture = $profilePicture ?? '../../assets/images/default-avatar.jpg';
$translations = $translations ?? [];
?>

<aside class="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <span class="material-icons-sharp logo-icon">account_balance</span>
            <span>Ikimina</span>
        </div>
        <button class="close-btn">
            <span class="material-icons-sharp">close</span>
        </button>
    </div>

    <nav class="sidebar-menu">
        <a href="../dashboard/main_dashboard.php" class="menu-item <?= basename($_SERVER['PHP_SELF']) == 'main_dashboard.php' ? 'active' : '' ?>">
            <span class="material-icons-sharp">dashboard</span>
            <span><?= $translations['dashboard'] ?? 'Dashboard' ?></span>
        </a>
        <a href="../groups/my_groups.php" class="menu-item <?= basename($_SERVER['PHP_SELF']) == 'my_groups.php' ? 'active' : '' ?>">
            <span class="material-icons-sharp">groups</span>
            <span><?= $translations['my_groups'] ?? 'Groups' ?></span>
        </a>
        <a href="../transactions/contributions.php" class="menu-item <?= basename($_SERVER['PHP_SELF']) == 'contributions.php' ? 'active' : '' ?>">
            <span class="material-icons-sharp">receipt_long</span>
            <span><?= $translations['transactions'] ?? 'Transactions' ?></span>
        </a>
        <a href="../loans/loans.php" class="menu-item <?= basename($_SERVER['PHP_SELF']) == 'loans.php' ? 'active' : '' ?>">
            <span class="material-icons-sharp">savings</span>
            <span><?= $translations['loans'] ?? 'Loans' ?></span>
        </a>
        <a href="../events/events.php" class="menu-item <?= basename($_SERVER['PHP_SELF']) == 'events.php' ? 'active' : '' ?>">
            <span class="material-icons-sharp">event</span>
            <span><?= $translations['events'] ?? 'Events' ?></span>
        </a>
        <a href="../settings/settings.php" class="menu-item <?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : '' ?>">
            <span class="material-icons-sharp">settings</span>
            <span><?= $translations['settings'] ?? 'Settings' ?></span>
        </a>
    </nav>
    
    <div class="sidebar-footer">
        <div class="user-profile">
            <img src="<?= $profilePicture ?>" alt="User" class="profile-picture" id="profileBtn">
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']) ?></div>
                <div class="user-role">Member</div>
            </div>
        </div>

        <!-- Hidden dropdown -->
        <div class="profile-dropdown" id="profileDropdown">
            <div class="profile-header">
                <img src="<?= $profilePicture ?>" alt="User" class="dropdown-pic">
                <div>
                    <strong><?= htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']) ?></strong><br>
                    <small>@<?= htmlspecialchars($userData['username'] ?? 'user') ?></small>
                </div>
            </div>
            <hr>
            <ul>
                <li><a href="../profile/profile.php"><span class="material-icons-sharp">person</span> Profile</a></li>
                <li><a href="../settings/security.php"><span class="material-icons-sharp">security</span> Security</a></li>
                <li><a href="../settings/payment_methods.php"><span class="material-icons-sharp">credit_card</span> Payment Methods</a></li>
                <li><a href="../auth/logout.php"><span class="material-icons-sharp">logout</span> Logout</a></li>
            </ul>
        </div>
    </div>
</aside>