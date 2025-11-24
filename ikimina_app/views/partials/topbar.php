<?php
// topbar.php - Ensure variables are available
if (!isset($userData)) {
    // Try to get user data from session or set defaults
    $userData = [
        'first_name' => $_SESSION['first_name'] ?? 'User',
        'last_name' => $_SESSION['last_name'] ?? '',
        'full_name' => $_SESSION['full_name'] ?? 'User'
    ];
}

$profilePicture = $profilePicture ?? '../../assets/images/default-avatar.jpg';
$unreadCount = $unreadCount ?? 0;
$currentLanguage = $currentLanguage ?? 'English';
$translations = $translations ?? [];
?>

<header class="topbar">
    <div class="topbar-left">
        <button class="menu-btn" id="menu-btn">
            <span class="material-icons-sharp">menu</span>
        </button>
        <div class="search-bar">
            <span class="material-icons-sharp">search</span>
            <input type="text" placeholder="<?= $translations['search_placeholder'] ?? 'Search...' ?>" id="globalSearch">
        </div>
    </div>
    
    <div class="topbar-right">
        <button class="theme-toggle" id="theme-toggle">
            <span class="material-icons-sharp">light_mode</span>
        </button>
        
        <!-- Language Switcher -->
        <div class="language-section" id="languageSection">
            <button class="language-btn">
                <span class="material-icons-sharp">language</span>
                <?= $currentLanguage ?>
                <span class="material-icons-sharp lang-arrow">expand_more</span>
            </button>
            <div class="language-dropdown">
                <div class="language-option <?= ($_SESSION['lang'] ?? 'en') === 'en' ? 'active' : '' ?>" data-lang="en">
                    <img src="../../assets/images/flags/uk.png" alt="English" class="language-flag">
                    English
                </div>
                <div class="language-option <?= ($_SESSION['lang'] ?? 'en') === 'rw' ? 'active' : '' ?>" data-lang="rw">
                    <img src="../../assets/images/flags/rwanda.png" alt="Kinyarwanda" class="language-flag">
                    Kinyarwanda
                </div>
                <div class="language-option <?= ($_SESSION['lang'] ?? 'en') === 'fr' ? 'active' : '' ?>" data-lang="fr">
                    <img src="../../assets/images/flags/france.png" alt="Français" class="language-flag">
                    Français
                </div>
            </div>
        </div>

        <!-- Notification System -->
        <div class="notification-section" id="notificationSection">
            <button class="notification-btn">
                <span class="material-icons-sharp notification-icon">notifications</span>
                <?php if ($unreadCount > 0): ?>
                    <span class="notification-badge"><?= $unreadCount ?></span>
                <?php endif; ?>
            </button>
            <div class="notification-dropdown">
                <div class="notification-header">
                    <h4><?= $translations['notifications'] ?? 'Notifications' ?></h4>
                    <?php if ($unreadCount > 0): ?>
                        <a href="?mark_all_read=1" class="mark-all-read"><?= $translations['mark_all_read'] ?? 'Mark all as read' ?></a>
                    <?php endif; ?>
                </div>
                <div class="no-notifications">
                    <span class="material-icons-sharp">notifications_none</span>
                    <p><?= $translations['no_notifications'] ?? 'No new notifications' ?></p>
                </div>
                <div class="notification-actions">
                    <a href="../notifications/notifications.php" class="btn btn-outline"><?= $translations['view_all'] ?? 'View All' ?></a>
                </div>
            </div>
        </div>
        
        <!-- User Profile -->
        <div class="user-profile-top">
            <img src="<?= $profilePicture ?>" alt="User" class="profile-picture">
            <div class="user-info-top">
                <div class="user-name"><?= htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']) ?></div>
                <div class="user-role"><?= $userData['role_global'] ?? 'Member' ?></div>
            </div>
        </div>
    </div>
</header>