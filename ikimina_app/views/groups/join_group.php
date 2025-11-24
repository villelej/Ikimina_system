<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../controllers/GroupController.php';
require_once __DIR__ . '/../../models/User.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Initialize user data for partials
try {
    $userModel = new User($pdo);
    $userData = $userModel->getUserProfile($_SESSION['user_id']);
} catch (Exception $e) {
    $userData = [
        'first_name' => $_SESSION['first_name'] ?? 'User',
        'last_name' => $_SESSION['last_name'] ?? '',
        'full_name' => $_SESSION['full_name'] ?? 'User',
        'role_global' => $_SESSION['role_global'] ?? 'Member'
    ];
}

$profilePicture = isset($userData['profile_pic']) && !empty($userData['profile_pic']) 
    ? htmlspecialchars($userData['profile_pic']) 
    : '../../assets/images/default-avatar.jpg';

// Load language file
$lang = $_SESSION['lang'] ?? 'en';
$langFile = __DIR__ . "/../../lang/$lang.php";
$translations = file_exists($langFile) ? include $langFile : include __DIR__ . '/../../lang/en.php';

$groupController = new GroupController($pdo);
$error = '';
$success = '';

// Handle join by code
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['group_code'])) {
    try {
        $groupCode = trim($_POST['group_code']);
        $role = $_POST['role'] ?? 'member';
        
        if (empty($groupCode)) {
            throw new Exception("Please enter a group code");
        }
        
        $groupController->joinGroupByCode($groupCode, $_SESSION['user_id'], $role);
        $success = "Join request sent successfully! The group admin will review your request.";
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get user's pending requests
$userGroups = $groupController->getUserGroups($_SESSION['user_id']);
$pendingGroups = array_filter($userGroups, function($group) {
    return $group['status'] === 'pending';
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Group - Ikimina</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Sharp" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/main_dashboard.css">
    <style>
        /* ===== CLEAN JOIN GROUP STYLES ===== */
        .join-group-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: white;
            border-radius: 12px;
            border: 1px solid #e1e5e9;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .page-title .material-icons-sharp {
            color: #4361ee;
            font-size: 2rem;
        }

        .back-to-dashboard {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: #4361ee;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .back-to-dashboard:hover {
            background: #3a56d4;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        /* Form Sections */
        .form-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            border: 1px solid #e1e5e9;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #4361ee;
        }

        .section-title .material-icons-sharp {
            color: #4361ee;
        }

        /* Form Elements */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2d3748;
            font-size: 0.95rem;
        }

        .form-label.required::after {
            content: " *";
            color: #e53e3e;
        }

        .form-control {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
            background: white;
            color: #2d3748;
        }

        .form-control:focus {
            outline: none;
            border-color: #4361ee;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23475569'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1.25rem;
            padding-right: 3rem;
        }

        .form-text {
            display: block;
            margin-top: 0.5rem;
            font-size: 0.875rem;
            color: #718096;
        }

        /* Submit Section */
        .submit-section {
            text-align: center;
            padding: 2rem;
            background: #f8fafc;
            border-radius: 12px;
            border: 2px dashed #e1e5e9;
        }

        .btn-join-group {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 2rem;
            background: #4361ee;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(67, 97, 238, 0.3);
        }

        .btn-join-group:hover {
            background: #3a56d4;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(67, 97, 238, 0.4);
        }

        /* Alerts */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideIn 0.3s ease-out;
        }

        .alert-success {
            background: #48bb78;
            color: white;
        }

        .alert-error {
            background: #e53e3e;
            color: white;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Pending Requests */
        .pending-requests {
            margin-top: 2rem;
        }

        .request-card {
            background: #f8fafc;
            border: 1px solid #e1e5e9;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .request-info h4 {
            margin: 0 0 0.5rem 0;
            color: #2d3748;
        }

        .request-meta {
            display: flex;
            gap: 1rem;
            font-size: 0.875rem;
            color: #718096;
        }

        .status-pending {
            background: #fbbf24;
            color: #78350f;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .no-requests {
            text-align: center;
            padding: 2rem;
            color: #718096;
            background: #f8fafc;
            border-radius: 8px;
            border: 2px dashed #e1e5e9;
        }

        /* Character Counter */
        .character-counter {
            text-align: right;
            margin-top: 0.5rem;
            font-size: 0.875rem;
            color: #718096;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .join-group-container {
                padding: 1rem;
            }

            .page-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .form-section {
                padding: 1.5rem;
            }

            .btn-join-group {
                width: 100%;
                justify-content: center;
            }

            .request-card {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
        }
    </style>
</head>
<body class="<?= isset($_SESSION['theme']) && $_SESSION['theme'] === 'dark' ? 'dark-theme' : 'light-theme' ?>">
    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <span class="material-icons-sharp logo-icon">savings</span>
                    <span>Ikimina</span>
                </div>
                <button class="close-btn">
                    <span class="material-icons-sharp">close</span>
                </button>
            </div>
        
            <nav class="sidebar-menu">
                <a href="../dashboards/main_dashboard.php" class="menu-item">
                    <span class="material-icons-sharp">dashboard</span>
                    <span>Dashboard</span>
                </a>
                <a href="join_group.php" class="menu-item active">
                    <span class="material-icons-sharp">group_add</span>
                    <span>Join Group</span>
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <div class="user-profile">
                    <img src="<?= $profilePicture ?>" alt="User" class="profile-picture" id="profileBtn">
                    <div class="user-info">
                        <div class="user-name"><?= htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']) ?></div>
                        <div class="user-email">@<?= htmlspecialchars($userData['username'] ?? 'user') ?></div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Topbar -->
            <header class="topbar">
                <div class="topbar-left">
                    <button class="menu-btn" id="menu-btn">
                        <span class="material-icons-sharp">menu</span>
                    </button>
                    <div class="search-bar">
                        <span class="material-icons-sharp">search</span>
                        <input type="text" placeholder="Search..." id="globalSearch">
                    </div>
                </div>
                
                <div class="topbar-right">
                    <div class="user-profile-top">
                        <img src="<?= $profilePicture ?>" alt="User" class="profile-picture">
                        <div class="user-info-top">
                            <div class="user-name"><?= htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']) ?></div>
                            <div class="user-email">@<?= htmlspecialchars($userData['username'] ?? 'user') ?></div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Dashboard Content -->
            <section class="dashboard-container">
                <div class="join-group-container">
                    <!-- Page Header -->
                    <div class="page-header">
                        <div class="page-title">
                            <span class="material-icons-sharp">group_add</span>
                            Join a Group
                        </div>
                        <a href="../dashboards/main_dashboard.php" class="back-to-dashboard">
                            <span class="material-icons-sharp">arrow_back</span>
                            Back to Dashboard
                        </a>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-error">
                            <span class="material-icons-sharp">error</span>
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <span class="material-icons-sharp">check_circle</span>
                            <?= htmlspecialchars($success) ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Join Group Form -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <span class="material-icons-sharp">qr_code</span>
                            Join with Group Code
                        </h3>
                        <p style="margin-bottom: 1.5rem; color: #718096;">
                            Enter the group code provided by the group administrator and select your preferred role.
                        </p>
                        
                        <form method="POST" action="" id="joinForm">
                            <div class="form-group">
                                <label class="form-label required" for="group_code">Group Code</label>
                                <input type="text" id="group_code" name="group_code" class="form-control" 
                                       placeholder="Enter 6-character group code" 
                                       pattern="[A-Za-z0-9]{6}" 
                                       title="Group code should be 6 characters"
                                       required
                                       value="<?= isset($_POST['group_code']) ? htmlspecialchars($_POST['group_code']) : '' ?>">
                                <div class="form-text">Ask the group admin for the 6-character code</div>
                                <div class="character-counter" id="groupCodeCounter">0/6 characters</div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label required" for="role">Select Your Role</label>
                                <select id="role" name="role" class="form-control" required>
                                    <option value="">Choose a role...</option>
                                    <option value="member" <?= (isset($_POST['role']) && $_POST['role'] === 'member') ? 'selected' : '' ?>>Member</option>
                                    <option value="secretary" <?= (isset($_POST['role']) && $_POST['role'] === 'secretary') ? 'selected' : '' ?>>Secretary</option>
                                    <option value="treasurer" <?= (isset($_POST['role']) && $_POST['role'] === 'treasurer') ? 'selected' : '' ?>>Treasurer</option>
                                    <option value="vice_president" <?= (isset($_POST['role']) && $_POST['role'] === 'vice_president') ? 'selected' : '' ?>>Vice President</option>
                                    <option value="loan_committee" <?= (isset($_POST['role']) && $_POST['role'] === 'loan_committee') ? 'selected' : '' ?>>Loan Committee</option>
                                </select>
                                <div class="form-text">Your role will be confirmed by the group admin</div>
                            </div>
                            
                            <div class="submit-section">
                                <button type="submit" class="btn-join-group">
                                    <span class="material-icons-sharp">group_add</span>
                                    Send Join Request
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Pending Requests -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <span class="material-icons-sharp">schedule</span>
                            My Pending Requests
                        </h3>
                        
                        <?php if (!empty($pendingGroups)): ?>
                            <div class="pending-requests">
                                <?php foreach ($pendingGroups as $group): ?>
                                    <div class="request-card">
                                        <div class="request-info">
                                            <h4><?= htmlspecialchars($group['name']) ?></h4>
                                            <div class="request-meta">
                                                <span><strong>Code:</strong> <?= htmlspecialchars($group['group_code']) ?></span>
                                                <span><strong>Role:</strong> <?= ucfirst(str_replace('_', ' ', $group['role_in_group'])) ?></span>
                                                <span><strong>Requested:</strong> <?= date('M j, Y', strtotime($group['joined_at'])) ?></span>
                                            </div>
                                        </div>
                                        <div class="status-pending">
                                            Pending Approval
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="no-requests">
                                <span class="material-icons-sharp" style="font-size: 3rem; margin-bottom: 1rem; color: #cbd5e0;">inbox</span>
                                <h4>No Pending Requests</h4>
                                <p>Your join requests will appear here once submitted</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </main>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-format group code input to uppercase and limit to 6 characters
            const groupCodeInput = document.getElementById('group_code');
            const groupCodeCounter = document.getElementById('groupCodeCounter');
            
            if (groupCodeInput) {
                groupCodeInput.addEventListener('input', function(e) {
                    this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '').substring(0, 6);
                    
                    // Update character counter
                    const currentLength = this.value.length;
                    groupCodeCounter.textContent = `${currentLength}/6 characters`;
                    
                    if (currentLength === 6) {
                        groupCodeCounter.style.color = '#48bb78';
                    } else {
                        groupCodeCounter.style.color = '#718096';
                    }
                });
                
                // Initialize counter
                const initialLength = groupCodeInput.value.length;
                groupCodeCounter.textContent = `${initialLength}/6 characters`;
                if (initialLength === 6) {
                    groupCodeCounter.style.color = '#48bb78';
                }
            }

            // Form validation
            const form = document.getElementById('joinForm');
            form.addEventListener('submit', function(e) {
                const groupCode = document.getElementById('group_code').value.trim();
                const role = document.getElementById('role').value;
                
                if (!groupCode) {
                    e.preventDefault();
                    alert('Group code is required');
                    return;
                }
                
                if (groupCode.length !== 6) {
                    e.preventDefault();
                    alert('Group code must be exactly 6 characters');
                    return;
                }
                
                if (!role) {
                    e.preventDefault();
                    alert('Please select a role');
                    return;
                }
            });

            // Sidebar toggle functionality
            const menuBtn = document.getElementById('menu-btn');
            const sidebar = document.querySelector('.sidebar');
            const closeBtn = document.querySelector('.close-btn');

            if (menuBtn && sidebar) {
                menuBtn.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                });
            }

            if (closeBtn && sidebar) {
                closeBtn.addEventListener('click', function() {
                    sidebar.classList.remove('active');
                });
            }
        });
    </script>
</body>
</html>