<?php
// main_dashboard.php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../views/auth/login.php');
    exit;
} 

// ===== THEME HANDLING =====
if (isset($_GET['theme'])) {
    $_SESSION['theme'] = ($_GET['theme'] === 'dark') ? 'dark' : 'light';
}

// ===== LANGUAGE HANDLING =====
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en'; // Default: English
}

if (isset($_GET['lang'])) {
    $allowedLangs = ['en', 'rw', 'fr'];
    $selectedLang = $_GET['lang'];
    if (in_array($selectedLang, $allowedLangs)) {
        $_SESSION['lang'] = $selectedLang;
    }
}

// ===== NOTIFICATION HANDLING =====
if (isset($_GET['mark_notification_read']) && is_numeric($_GET['mark_notification_read'])) {
    $notificationId = $_GET['mark_notification_read'];
    require_once __DIR__ . '/../../config/db.php';
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$notificationId, $_SESSION['user_id']]);
    header('Location: main_dashboard.php');
    exit;
}

if (isset($_GET['mark_all_read'])) {
    require_once __DIR__ . '/../../config/db.php';
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    header('Location: main_dashboard.php');
    exit;
}

// ===== LOAD LANGUAGE FILE =====
$langFile = __DIR__ . '/../../lang/' . $_SESSION['lang'] . '.php';
if (file_exists($langFile)) {
    $translations = include $langFile;
} else {
    $translations = include __DIR__ . '/../../lang/en.php';
}

// ===== SET THEME CLASS =====
$themeClass = (isset($_SESSION['theme']) && $_SESSION['theme'] === 'dark')
    ? 'dark-theme'
    : 'light-theme';

// ===== INCLUDE DATABASE =====
require_once __DIR__ . '/../../config/db.php';

// ===== GET USER DATA =====
$userId = $_SESSION['user_id'];
$userData = [];
$groups = [];
$stats = [];
$activities = [];
$events = [];
$notifications = [];
$notificationCategories = [];

try {
    // Get user basic info
    $userStmt = $pdo->prepare("
        SELECT id, username, email, first_name, last_name, profile_pic, role_global 
        FROM users 
        WHERE id = ?
    ");
    $userStmt->execute([$userId]);
    $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$userData) {
        throw new Exception("User not found");
    }

    // Create display name
    $displayName = '';
    if (!empty($userData['first_name']) && !empty($userData['last_name'])) {
        $displayName = htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']);
    } elseif (!empty($userData['first_name'])) {
        $displayName = htmlspecialchars($userData['first_name']);
    } else {
        $displayName = htmlspecialchars($userData['username'] ?? 'User');
    }

    // Get user groups
    $groupStmt = $pdo->prepare("
        SELECT 
    g.id AS group_id,
    g.name AS group_name,
    g.status AS group_status,
    gm.role_in_group AS role,
    gm.status AS member_status
FROM groups g
INNER JOIN group_members gm 
    ON g.id = gm.group_id
WHERE gm.user_id = ?
    ");
    $groupStmt->execute([$userId]);
    $groups = $groupStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get groups stats
    $statsStmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT CASE WHEN g.status = 'active' THEN g.id END) AS total_groups,
            COUNT(DISTINCT CASE WHEN g.status = 'pending' THEN g.id END) AS pending_groups,
            COALESCE(SUM(CASE WHEN gm.status = 'active' AND g.status = 'active' THEN gm.savings_balance ELSE 0 END), 0) AS total_savings
        FROM groups g
        INNER JOIN group_members gm ON g.id = gm.group_id
        WHERE gm.user_id = ?
    ");
    $statsStmt->execute([$userId]);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

    if (!$stats) {
        $stats = [
            'total_groups' => 0,
            'pending_groups' => 0,
            'total_savings' => 0
        ];
    }

    // Add other default stats
    $stats['pending_contributions'] = 0;
    $stats['total_contributions'] = 0;
    $stats['active_loans'] = 0;
    $stats['interest_earned'] = 0;
    $stats['penalties'] = 0;
    $stats['net_profit'] = 0;

    // Get notifications with count
    $notifStmt = $pdo->prepare("
        SELECT id, title, message, type, is_read, created_at 
        FROM notifications 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 20
    ");
    $notifStmt->execute([$userId]);
    $notifications = $notifStmt->fetchAll(PDO::FETCH_ASSOC);

    // Count unread notifications
    $unreadCount = 0;
    foreach ($notifications as $notification) {
        if (!$notification['is_read']) {
            $unreadCount++;
        }
    }

    $profilePicture = !empty($userData['profile_pic'])
        ? htmlspecialchars($userData['profile_pic'])
        : '../../assets/images/default-avatar.jpg';

} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    
    // Fallback data
    $userData = [
        'first_name' => $_SESSION['first_name'] ?? 'User',
        'last_name' => $_SESSION['last_name'] ?? '',
        'username' => $_SESSION['username'] ?? 'user',
        'email' => $_SESSION['email'] ?? 'user@example.com',
        'role_global' => $_SESSION['role_global'] ?? 'member'
    ];
    
    $displayName = htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']);
    $stats = [
        'total_groups' => 0,
        'pending_groups' => 0,
        'pending_contributions' => 0,
        'total_savings' => 0,
        'total_contributions' => 0,
        'active_loans' => 0,
        'interest_earned' => 0,
        'penalties' => 0,
        'net_profit' => 0
    ];
    $groups = [];
    $notifications = [];
    $unreadCount = 0;
    $profilePicture = '../../assets/images/default-avatar.jpg';
}

// Set default profile picture if not set
if (!isset($profilePicture) || empty($profilePicture)) {
    $profilePicture = '../../assets/images/default-avatar.jpg';
}

// Set default translations if not set
if (!isset($translations)) {
    $translations = include __DIR__ . '/../../lang/en.php';
}

// Store user data in session for fallback
if (!isset($_SESSION['first_name']) && isset($userData['first_name'])) {
    $_SESSION['first_name'] = $userData['first_name'];
    $_SESSION['last_name'] = $userData['last_name'] ?? '';
    $_SESSION['username'] = $userData['username'] ?? 'user';
    $_SESSION['email'] = $userData['email'] ?? 'user@example.com';
    $_SESSION['role_global'] = $userData['role_global'] ?? 'member';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Main Dashboard - Ikimina</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Sharp" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/main_dashboard.css">
</head>

<body class="<?= $themeClass ?>">
    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <span class="material-icons-sharp logo-icon">savings</span>
                    <span>Ikimina</span>
                </div>
            </div>
        
            <nav class="sidebar-menu">
                <a href="main_dashboard.php" class="menu-item active">
                    <span class="material-icons-sharp">dashboard</span>
                    <span><?= $translations['dashboard'] ?? 'Dashboard' ?></span>
                </a>
                <a href="my_groups.php" class="menu-item">
                    <span class="material-icons-sharp">groups</span>
                    <span><?= $translations['my_groups'] ?? 'My Groups' ?></span>
                </a>
                <a href="contributions.php" class="menu-item">
                    <span class="material-icons-sharp">payments</span>
                    <span><?= $translations['contributions'] ?? 'Contributions' ?></span>
                </a>
                <a href="loans.php" class="menu-item">
                    <span class="material-icons-sharp">request_quote</span>
                    <span><?= $translations['loans'] ?? 'Loans' ?></span>
                </a>
                <a href="events.php" class="menu-item">
                    <span class="material-icons-sharp">event</span>
                    <span><?= $translations['events'] ?? 'Events' ?></span>
                </a>
                <a href="reports.php" class="menu-item">
                    <span class="material-icons-sharp">analytics</span>
                    <span><?= $translations['reports'] ?? 'Reports' ?></span>
                </a>
                <a href="settings.php" class="menu-item">
                    <span class="material-icons-sharp">settings</span>
                    <span><?= $translations['settings'] ?? 'Settings' ?></span>
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <div class="user-profile" id="userProfileBtn">
                    <img src="<?= $profilePicture ?>" alt="User" class="profile-picture">
                    <div class="user-info">
                        <div class="user-name"><?= $displayName ?></div>
                        <div class="user-email">@<?= htmlspecialchars($userData['username']) ?></div>
                    </div>
                </div>
                
                <!-- User Dropdown Menu -->
                <div class="user-dropdown" id="userDropdown">
                    <a href="profile.php" class="dropdown-item">
                        <span class="material-icons-sharp">person</span>
                        My Profile
                    </a>
                    <a href="settings.php" class="dropdown-item">
                        <span class="material-icons-sharp">settings</span>
                        Settings
                    </a>
                    <a href="help.php" class="dropdown-item">
                        <span class="material-icons-sharp">help</span>
                        Help & Support
                    </a>
                    <div class="dropdown-item logout" id="logoutBtn">
                        <span class="material-icons-sharp">logout</span>
                        Logout
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Topbar -->
            <header class="topbar">
                <div class="topbar-left">
                    <div class="search-bar">
                        <span class="material-icons-sharp">search</span>
                        <input type="text" placeholder="<?= $translations['search_placeholder'] ?? 'Search groups, members...' ?>" id="globalSearch">
                    </div>
                </div>
                
                <div class="topbar-right">
                    <!-- Notifications -->
                    <div class="notification-bell" id="notificationBell">
                        <span class="material-icons-sharp">notifications</span>
                        <?php if ($unreadCount > 0): ?>
                            <span class="notification-count"><?= $unreadCount ?></span>
                        <?php endif; ?>
                        
                        <!-- Notifications Dropdown -->
                        <div class="notification-dropdown" id="notificationDropdown">
                            <div class="notification-header">
                                <h4>Notifications</h4>
                                <?php if ($unreadCount > 0): ?>
                                    <a href="main_dashboard.php?mark_all_read=1" class="mark-all-read">Mark all as read</a>
                                <?php endif; ?>
                            </div>
                            <div class="notification-list">
                                <?php if (!empty($notifications)): ?>
                                    <?php foreach ($notifications as $notification): ?>
                                        <div class="notification-item <?= !$notification['is_read'] ? 'unread' : '' ?>" 
                                             data-notification-id="<?= $notification['id'] ?>">
                                            <div class="notification-title"><?= htmlspecialchars($notification['title']) ?></div>
                                            <div class="notification-message"><?= htmlspecialchars($notification['message']) ?></div>
                                            <div class="notification-time">
                                                <?= date('M j, Y g:i A', strtotime($notification['created_at'])) ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="no-notifications">
                                        <span class="material-icons-sharp">notifications_none</span>
                                        <p>No notifications</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- User Profile -->
                    <div class="user-profile-top" id="userProfileTop">
                        <img src="<?= $profilePicture ?>" alt="User" class="profile-picture">
                        <div class="user-info-top">
                            <div class="user-name"><?= $displayName ?></div>
                            <div class="user-email">@<?= htmlspecialchars($userData['username']) ?></div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Dashboard Content -->
            <section class="dashboard-container">
                <!-- Welcome Banner -->
                <div class="welcome-banner">
                    <div class="welcome-content">
                        <h1><?= $translations['welcome'] ?? 'Welcome' ?>, <?= $displayName ?>! 👋</h1>
                        <p><?= $translations['dashboard_overview'] ?? 'Here\'s your overview across all groups.' ?></p>
                        
                        <!-- Quick Stats Row -->
                        <div class="quick-stats-row">
                            <div class="quick-stat">
                                <span class="material-icons-sharp">groups</span>
                                <div>
                                    <strong><?= htmlspecialchars($stats['total_groups'] ?? 0) ?></strong>
                                    <span><?= $translations['total_groups'] ?? 'Total Groups' ?></span>
                                </div>
                            </div>
                            <div class="quick-stat">
                                <span class="material-icons-sharp">pending_actions</span>
                                <div>
                                    <strong><?= htmlspecialchars($stats['pending_groups'] ?? 0) ?></strong>
                                    <span><?= $translations['pending_groups'] ?? 'Pending Groups' ?></span>
                                </div>
                            </div>
                            <div class="quick-stat">
                                <span class="material-icons-sharp">savings</span>
                                <div>
                                    <strong><?= number_format($stats['total_savings'] ?? 0, 0) ?> RWF</strong>
                                    <span><?= $translations['total_savings'] ?? 'Total Savings' ?></span>
                                </div>
                            </div>
                            <div class="quick-stat">
                                <span class="material-icons-sharp">request_quote</span>
                                <div>
                                    <strong><?= htmlspecialchars($stats['active_loans'] ?? 0) ?></strong>
                                    <span><?= $translations['active_loans'] ?? 'Active Loans' ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="welcome-actions">
                        <a href="../groups/create_group.php" class="btn btn-primary btn-large">
                            <span class="material-icons-sharp">add</span>
                            Create New Group
                        </a>
                        <a href="../groups/join_group.php" class="btn btn-secondary btn-large">
                            <span class="material-icons-sharp">group_add</span>
                            Join Existing Group
                        </a>
                    </div>
                </div>

                <!-- My Groups Section with Horizontal Scrolling -->
                <section class="groups-section">
                    <div class="section-header">
                        <h3><?= $translations['my_groups'] ?? 'My Groups' ?></h3>
                        <div class="section-controls">
                            <!-- Search Bar -->
                            <div class="group-search">
                                <span class="material-icons-sharp">search</span>
                                <input type="text" placeholder="<?= $translations['search_groups'] ?? 'Search groups...' ?>" id="groupSearch">
                            </div>
                            
                            <a href="my_groups.php" class="btn btn-outline"><?= $translations['view_all'] ?? 'View All' ?></a>
                        </div>
                    </div>

                    <!-- Horizontal Scrolling Group Cards -->
                    <div class="groups-scroll-container">
                        <button class="scroll-btn scroll-btn-left hidden" id="scrollLeft">
                            <span class="material-icons-sharp">chevron_left</span>
                        </button>
                        
                      <div class="groups-scroll-wrapper" id="groupsScroll">
    <?php if (!empty($groups)): ?>
        <?php foreach ($groups as $group): ?>
            <?php 
            // Determine display status
            $role = $group['role'] ?? 'member';
            $groupStatus = $group['group_status'] ?? 'pending';
            $memberStatus = $group['member_status'] ?? 'pending';

            $displayStatus = ($role === 'admin' || $role === 'creator') 
                ? $groupStatus 
                : $memberStatus;

            $statusClass = ($displayStatus === 'active') ? 'status-active' : 'status-pending';
            $groupDashboardUrl = "group_dashboard.php?group_id=" . ($group['group_id'] ?? 0);
            $isPending = $displayStatus === 'pending';
            ?>
            
            <div class="group-card <?= $isPending ? 'pending' : '' ?>" 
                 data-group-name="<?= strtolower(htmlspecialchars($group['group_name'] ?? 'Unnamed Group')) ?>" 
                 data-group-code="<?= strtolower(htmlspecialchars($group['group_code'] ?? '')) ?>"
                 data-group-status="<?= $displayStatus ?>">
                
                <?php if (!$isPending): ?>
                    <a href="<?= $groupDashboardUrl ?>" class="group-card-link"></a>
                <?php endif; ?>
                
                <div class="group-header">
                    <div>
                        <div class="group-name"><?= htmlspecialchars($group['group_name'] ?? 'Unnamed Group') ?></div>
                        <div class="group-code"><?= htmlspecialchars($group['group_code'] ?? '') ?></div>
                    </div>
                    <span class="group-status <?= $statusClass ?>">
                        <?= ucfirst($displayStatus) ?>
                    </span>
                </div>
                
                <div class="group-role">
                    Role: <?= ucfirst(str_replace('_', ' ', $role)) ?>
                </div>
                
                <div class="group-actions">
                    <?php if ($isPending): ?>
                        <button class="btn btn-primary" onclick="showPendingModal()">
                            Open Dashboard
                        </button>
                    <?php else: ?>
                        <a href="<?= $groupDashboardUrl ?>" class="btn btn-primary">Open Dashboard</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-groups">
            <span class="material-icons-sharp">groups</span>
            <h3><?= $translations['no_groups_title'] ?? 'No Groups Yet' ?></h3>
            <p><?= $translations['no_groups_message'] ?? 'You haven\'t joined or created any groups yet.' ?></p>
            <div class="btn-group">
                <a href="../groups/join_group.php" class="btn btn-primary">Join a Group</a>
                <a href="../groups/create_group.php" class="btn btn-secondary">Create Group</a>
            </div>
        </div>
    <?php endif; ?>
</div>


                        
                        <button class="scroll-btn scroll-btn-right" id="scrollRight">
                            <span class="material-icons-sharp">chevron_right</span>
                        </button>
                    </div>
                </section>

                <!-- Bottom Dashboard Grid -->
                <div class="bottom-dashboard-grid">
                    <!-- Financial Summary -->
                    <div class="finance-summary">
                        <div class="section-header">
                            <h3><?= $translations['financial_overview'] ?? 'Financial Overview' ?></h3>
                            <a href="financial_reports.php" class="btn btn-outline"><?= $translations['view_full_report'] ?? 'View Full Report' ?></a>
                        </div>
                        <div class="financial-cards-grid">
                            <div class="financial-card">
                                <span class="material-icons-sharp icon">payments</span>
                                <h5><?= $translations['total_contributions'] ?? 'Total Contributions' ?></h5>
                                <p><strong><?= number_format($stats['total_contributions'] ?? 0, 0) ?> RWF</strong></p>
                                <small>Across all groups</small>
                            </div>
                            <div class="financial-card">
                                <span class="material-icons-sharp icon">request_quote</span>
                                <h5><?= $translations['active_loans'] ?? 'Active Loans' ?></h5>
                                <p><strong><?= htmlspecialchars($stats['active_loans'] ?? 0) ?></strong></p>
                                <small>Current active loans</small>
                            </div>
                            <div class="financial-card">
                                <span class="material-icons-sharp icon">trending_up</span>
                                <h5><?= $translations['interest_earned'] ?? 'Interest Earned' ?></h5>
                                <p><strong><?= number_format($stats['interest_earned'] ?? 0, 0) ?> RWF</strong></p>
                                <small>Total interest earned</small>
                            </div>
                            <div class="financial-card">
                                <span class="material-icons-sharp icon">gavel</span>
                                <h5><?= $translations['penalties'] ?? 'Penalties' ?></h5>
                                <p><strong><?= number_format($stats['penalties'] ?? 0, 0) ?> RWF</strong></p>
                                <small>Total penalties paid</small>
                            </div>
                        </div>
                    </div>

                    <!-- Upcoming Events & Reminders -->
                    <div class="upcoming-events">
                        <div class="section-header">
                            <h3><?= $translations['upcoming_events'] ?? 'Upcoming Events & Reminders' ?></h3>
                            <a href="events.php" class="btn btn-outline"><?= $translations['view_all'] ?? 'View All' ?></a>
                        </div>
                        <div class="no-events">
                            <span class="material-icons-sharp">event</span>
                            <p><?= $translations['no_events'] ?? 'No upcoming events' ?></p>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <footer class="dashboard-footer">
                    <div class="footer-content">
                        <div class="system-info">
                            <span>Ikimina v1.0.0</span>
                            <span>&copy; <?= date('Y') ?> Ikimina. All rights reserved.</span>
                        </div>
                        <div class="footer-links">
                            <a href="terms.php">Terms of Service</a>
                            <a href="privacy.php">Privacy Policy</a>
                        </div>
                    </div>
                </footer>
            </section>
        </main>
    </div>

    <!-- Pending Group Modal -->
    <div class="modal" id="pendingModal">
        <div class="modal-content">
            <div class="modal-icon">
                <span class="material-icons-sharp">pending_actions</span>
            </div>
            <h3>Group Pending Approval</h3>
            <p>This group is still pending approval from the group administrators. Please wait for your membership to be approved before accessing the group dashboard.</p>
            <div class="modal-actions">
                <button class="btn btn-close" onclick="closePendingModal()">Close</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Horizontal Scrolling Functionality
            const groupsScroll = document.getElementById('groupsScroll');
            const scrollLeft = document.getElementById('scrollLeft');
            const scrollRight = document.getElementById('scrollRight');

            function updateScrollButtons() {
                if (groupsScroll.scrollLeft <= 10) {
                    scrollLeft.classList.add('hidden');
                } else {
                    scrollLeft.classList.remove('hidden');
                }

                if (groupsScroll.scrollLeft + groupsScroll.clientWidth >= groupsScroll.scrollWidth - 10) {
                    scrollRight.classList.add('hidden');
                } else {
                    scrollRight.classList.remove('hidden');
                }
            }

            scrollLeft.addEventListener('click', function() {
                groupsScroll.scrollBy({ left: -300, behavior: 'smooth' });
            });

            scrollRight.addEventListener('click', function() {
                groupsScroll.scrollBy({ left: 300, behavior: 'smooth' });
            });

            groupsScroll.addEventListener('scroll', updateScrollButtons);
            updateScrollButtons(); // Initial check

            // Group Search Functionality
            const groupSearch = document.getElementById('groupSearch');
            if (groupSearch) {
                groupSearch.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase().trim();
                    const groupCards = document.querySelectorAll('.group-card');
                    let hasVisibleCards = false;

                    groupCards.forEach(card => {
                        const groupName = card.getAttribute('data-group-name');
                        const groupCode = card.getAttribute('data-group-code');
                        
                        if (groupName.includes(searchTerm) || groupCode.includes(searchTerm)) {
                            card.style.display = 'block';
                            hasVisibleCards = true;
                        } else {
                            card.style.display = 'none';
                        }
                    });

                    // Show/hide empty state
                    const emptyState = document.querySelector('.empty-groups');
                    if (emptyState) {
                        emptyState.style.display = hasVisibleCards ? 'none' : 'block';
                    }
                });
            }

            // Global Search Functionality
            const globalSearch = document.getElementById('globalSearch');
            if (globalSearch) {
                globalSearch.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        const searchTerm = this.value.trim();
                        if (searchTerm) {
                            // Redirect to search results page or filter content
                            window.location.href = `search.php?q=${encodeURIComponent(searchTerm)}`;
                        }
                    }
                });
            }

            // Add click handlers for group cards
            const groupCards = document.querySelectorAll('.group-card');
            groupCards.forEach(card => {
                card.addEventListener('click', function(e) {
                    // Don't trigger if clicking on the button inside the card
                    if (e.target.closest('.btn')) {
                        return;
                    }
                    
                    // Check if group is pending
                    const groupStatus = this.getAttribute('data-group-status');
                    if (groupStatus === 'pending') {
                        showPendingModal();
                        return;
                    }
                    
                    // Navigate to group dashboard for active groups
                    const link = this.querySelector('.group-card-link');
                    if (link) {
                        window.location.href = link.href;
                    }
                });
            });

            // User Dropdown Functionality
            const userProfileBtn = document.getElementById('userProfileBtn');
            const userProfileTop = document.getElementById('userProfileTop');
            const userDropdown = document.getElementById('userDropdown');

            function toggleUserDropdown() {
                userDropdown.classList.toggle('active');
                // Close notifications if open
                notificationDropdown.classList.remove('active');
            }

            userProfileBtn.addEventListener('click', toggleUserDropdown);
            userProfileTop.addEventListener('click', toggleUserDropdown);

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!userProfileBtn.contains(e.target) && !userProfileTop.contains(e.target) && !userDropdown.contains(e.target)) {
                    userDropdown.classList.remove('active');
                }
            });

            // Logout functionality
            const logoutBtn = document.getElementById('logoutBtn');
            logoutBtn.addEventListener('click', function() {
                if (confirm('Are you sure you want to logout?')) {
                    window.location.href = '../../views/auth/logout.php';
                }
            });

            // Notifications Functionality
            const notificationBell = document.getElementById('notificationBell');
            const notificationDropdown = document.getElementById('notificationDropdown');

            notificationBell.addEventListener('click', function(e) {
                e.stopPropagation();
                notificationDropdown.classList.toggle('active');
                // Close user dropdown if open
                userDropdown.classList.remove('active');
            });

            // Close notifications when clicking outside
            document.addEventListener('click', function(e) {
                if (!notificationBell.contains(e.target) && !notificationDropdown.contains(e.target)) {
                    notificationDropdown.classList.remove('active');
                }
            });

            // Mark notification as read when clicked
            const notificationItems = document.querySelectorAll('.notification-item');
            notificationItems.forEach(item => {
                item.addEventListener('click', function() {
                    const notificationId = this.getAttribute('data-notification-id');
                    if (notificationId) {
                        window.location.href = `main_dashboard.php?mark_notification_read=${notificationId}`;
                    }
                });
            });

            // Auto-hide scroll buttons on mobile
            function checkMobile() {
                if (window.innerWidth <= 768) {
                    scrollLeft.style.display = 'none';
                    scrollRight.style.display = 'none';
                } else {
                    scrollLeft.style.display = 'flex';
                    scrollRight.style.display = 'flex';
                    updateScrollButtons();
                }
            }

            window.addEventListener('resize', checkMobile);
            checkMobile(); // Initial check
        });

        // Pending Group Modal Functions
        function showPendingModal() {
            const modal = document.getElementById('pendingModal');
            modal.classList.add('active');
        }

        function closePendingModal() {
            const modal = document.getElementById('pendingModal');
            modal.classList.remove('active');
        }

        // Close modal when clicking outside
        document.addEventListener('click', function(e) {
            const modal = document.getElementById('pendingModal');
            if (e.target === modal) {
                closePendingModal();
            }
        });
    </script>
</body>
</html>

foreach ($groups as &$group) {
    // If the user is the creator of the group, always treat as active (if group.status = active)
    if ($group['role'] === 'admin') { 
        $group['member_status'] = 'active';
    }
}
