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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $groupData = [
            'name' => $_POST['name'],
            'description' => $_POST['description'] ?? '',
            'province' => $_POST['province'] ?? '',
            'district' => $_POST['district'] ?? '',
            'sector' => $_POST['sector'] ?? '',
            'cell' => $_POST['cell'] ?? '',
            'village' => $_POST['village'] ?? '',
            'term_duration' => $_POST['term_duration'] ?? '',
            'objective' => $_POST['objective'] ?? '',
            'contribution_amount' => $_POST['contribution_amount'] ?? null,
            'contribution_method' => $_POST['contribution_method'] ?? '',
            'loan_rules' => $_POST['loan_rules'] ?? '',
            'investment_rules' => $_POST['investment_rules'] ?? '',
            'founding_document' => $_POST['founding_document'] ?? '',
            'rca_number' => $_POST['rca_number'] ?? '',
            'created_by' => $_SESSION['user_id']
        ];
        
        $groupId = $groupController->createGroup($groupData);
        $success = "Group created successfully! Your group code has been sent to your email.";
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Group - Ikimina</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Sharp" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/main_dashboard.css">
    <style>
        /* ===== CLEAN CREATE GROUP STYLES ===== */
        .create-group-container {
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

        textarea.form-control {
            resize: vertical;
            min-height: 120px;
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23475569'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1.25rem;
            padding-right: 3rem;
        }

        /* Location Grid */
        .location-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        /* Submit Section */
        .submit-section {
            text-align: center;
            padding: 2rem;
            background: #f8fafc;
            border-radius: 12px;
            border: 2px dashed #e1e5e9;
        }

        .btn-create-group {
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

        .btn-create-group:hover {
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

        /* Character Counter */
        .character-counter {
            text-align: right;
            margin-top: 0.5rem;
            font-size: 0.875rem;
            color: #718096;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .create-group-container {
                padding: 1rem;
            }

            .page-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .location-grid {
                grid-template-columns: 1fr;
            }

            .form-section {
                padding: 1.5rem;
            }

            .btn-create-group {
                width: 100%;
                justify-content: center;
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
                <a href="../dashboards/main_dashboard.php" class="menu-item active">
                    <span class="material-icons-sharp">dashboard</span>
                    <span>Dashboard</span>
                </a>
                 <a href="create_group.php" class="menu-item active">
                    <span class="material-icons-sharp">group_add</span>
                    <span>Create Group</span>
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
                <div class="create-group-container">
                    <!-- Page Header -->
                    <div class="page-header">
                        <div class="page-title">
                            <span class="material-icons-sharp">group_add</span>
                            Create New Group
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
                    
                    <form method="POST" action="" id="groupForm">
                        <!-- Basic Information -->
                        <div class="form-section">
                            <h3 class="section-title">
                                <span class="material-icons-sharp">info</span>
                                Basic Information
                            </h3>
                            
                            <div class="form-group">
                                <label class="form-label required" for="name">Group Name</label>
                                <input type="text" id="name" name="name" class="form-control" required 
                                       placeholder="Enter group name">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="description">Group Description</label>
                                <textarea id="description" name="description" class="form-control" rows="3" 
                                          placeholder="Enter group description" maxlength="500"></textarea>
                                <div class="character-counter">0/500 characters</div>
                            </div>
                        </div>
                        
                        <!-- Location -->
                        <div class="form-section">
                            <h3 class="section-title">
                                <span class="material-icons-sharp">location_on</span>
                                Group Location
                            </h3>
                            <div class="location-grid">
                                <div class="form-group">
                                    <label class="form-label required" for="province">Province</label>
                                    <select id="province" name="province" class="form-control" required>
                                        <option value="">Select Province</option>
                                        <option value="Kigali City">Kigali City</option>
                                        <option value="Eastern Province">Eastern Province</option>
                                        <option value="Western Province">Western Province</option>
                                        <option value="Northern Province">Northern Province</option>
                                        <option value="Southern Province">Southern Province</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label required" for="district">District</label>
                                    <select id="district" name="district" class="form-control" required disabled>
                                        <option value="">Select District</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label required" for="sector">Sector</label>
                                    <select id="sector" name="sector" class="form-control" required disabled>
                                        <option value="">Select Sector</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label required" for="cell">Cell</label>
                                    <select id="cell" name="cell" class="form-control" required disabled>
                                        <option value="">Select Cell</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label required" for="village">Village</label>
                                    <select id="village" name="village" class="form-control" required disabled>
                                        <option value="">Select Village</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Group Details -->
                        <div class="form-section">
                            <h3 class="section-title">
                                <span class="material-icons-sharp">settings</span>
                                Group Details
                            </h3>
                            
                            <div class="form-group">
                                <label class="form-label" for="term_duration">Term Duration</label>
                                <input type="text" id="term_duration" name="term_duration" class="form-control" 
                                       placeholder="Enter term duration">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="objective">Objectives / Purpose</label>
                                <textarea id="objective" name="objective" class="form-control" rows="3" 
                                          placeholder="Enter group objectives" maxlength="500"></textarea>
                                <div class="character-counter">0/500 characters</div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="contribution_amount">Contribution Amount (RWF)</label>
                                <input type="number" id="contribution_amount" name="contribution_amount" class="form-control" 
                                       placeholder="Enter amount" step="100" min="0">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="contribution_method">Contribution Method</label>
                                <select id="contribution_method" name="contribution_method" class="form-control">
                                    <option value="">Select contribution method</option>
                                    <option value="bank">Bank Transfer</option>
                                    <option value="mobile_money">Mobile Money</option>
                                    <option value="cash">Cash Collection</option>
                                    <option value="mobile_banking">Mobile Banking</option>
                                    <option value="mixed">Mixed Methods</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Rules and Regulations -->
                        <div class="form-section">
                            <h3 class="section-title">
                                <span class="material-icons-sharp">gavel</span>
                                Rules and Regulations
                            </h3>
                            
                            <div class="form-group">
                                <label class="form-label" for="loan_rules">Loan Rules</label>
                                <textarea id="loan_rules" name="loan_rules" class="form-control" rows="3" 
                                          placeholder="Enter loan rules" maxlength="1000"></textarea>
                                <div class="character-counter">0/1000 characters</div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="investment_rules">Investment Rules</label>
                                <textarea id="investment_rules" name="investment_rules" class="form-control" rows="3" 
                                          placeholder="Enter investment rules" maxlength="1000"></textarea>
                                <div class="character-counter">0/1000 characters</div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="founding_document">Founding Document Reference</label>
                                <input type="text" id="founding_document" name="founding_document" class="form-control" 
                                       placeholder="Enter document reference">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="rca_number">RCA Registration Number</label>
                                <input type="text" id="rca_number" name="rca_number" class="form-control" 
                                       placeholder="Enter RCA number">
                            </div>
                        </div>
                        
                        <!-- Submit Section -->
                        <div class="form-section submit-section">
                            <button type="submit" class="btn-create-group" id="submitBtn">
                                <span class="material-icons-sharp">group_add</span>
                                Create Group
                            </button>
                        </div>
                    </form>
                </div>
            </section>
        </main>
    </div>
    
    <script>
        // Rwanda Administrative Areas Data
        const rwandaLocations = {
            "Kigali City": {
                "Gasabo": {
                    "Gisozi": {
                        "Kinyaga": ["Gasharu", "Kabeza", "Kinyaga", "Nyarufunzo"],
                        "Kinyinya": ["Gatare", "Kinyinya", "Nyarutarama"],
                        "Ndera": ["Gatenga", "Ndera", "Tumba"]
                    },
                    "Kacyiru": {
                        "Gishushu": ["Gishushu", "Jali", "Rugando"],
                        "Kacyiru": ["Kacyiru", "Kibenga", "Rukiri"]
                    }
                },
                "Kicukiro": {
                    "Gatenga": {
                        "Gatenga": ["Gatenga", "Karambo", "Nyanza"],
                        "Kanserege": ["Kanserege", "Kigarama", "Rwampara"]
                    }
                },
                "Nyarugenge": {
                    "Kanyinya": {
                        "Kanyinya": ["Kabagali", "Kanyinya", "Rwesero"],
                        "Rwezamenyo": ["Gasharu", "Rwezamenyo", "Shimo"]
                    }
                }
            },
            "Eastern Province": {
                "Bugesera": {
                    "Juru": {
                        "Juru": ["Gahima", "Juru", "Kigina"],
                        "Shyara": ["Gasharu", "Shyara", "Rwimbogo"]
                    }
                },
                "Kayonza": {
                    "Gahini": {
                        "Gahini": ["Gahini", "Kabarondo", "Rukara"]
                    }
                }
            },
            "Western Province": {
                "Karongi": {
                    "Bwishyura": {
                        "Bwishyura": ["Gashari", "Bwishyura", "Rugobagoba"]
                    }
                }
            },
            "Northern Province": {
                "Musanze": {
                    "Cyuve": {
                        "Cyuve": ["Bikara", "Cyuve", "Shingiro"]
                    }
                }
            },
            "Southern Province": {
                "Huye": {
                    "Ngoma": {
                        "Ngoma": ["Gishamvu", "Ngoma", "Rukira"]
                    }
                }
            }
        };

        // Utility Functions
        function resetDropdown(selectElement) {
            selectElement.innerHTML = '<option value="">Select ' + selectElement.name + '</option>';
            selectElement.disabled = true;
        }

        function populateDropdown(selectElement, options) {
            options.forEach(option => {
                const optionElement = document.createElement('option');
                optionElement.value = option;
                optionElement.textContent = option;
                selectElement.appendChild(optionElement);
            });
        }

        // Initialize Location Dropdowns
        document.addEventListener('DOMContentLoaded', function() {
            const provinceSelect = document.getElementById('province');
            const districtSelect = document.getElementById('district');
            const sectorSelect = document.getElementById('sector');
            const cellSelect = document.getElementById('cell');
            const villageSelect = document.getElementById('village');

            // Province change event
            provinceSelect.addEventListener('change', function() {
                const selectedProvince = this.value;
                
                resetDropdown(districtSelect);
                resetDropdown(sectorSelect);
                resetDropdown(cellSelect);
                resetDropdown(villageSelect);
                
                if (selectedProvince && rwandaLocations[selectedProvince]) {
                    districtSelect.disabled = false;
                    populateDropdown(districtSelect, Object.keys(rwandaLocations[selectedProvince]));
                } else {
                    districtSelect.disabled = true;
                    sectorSelect.disabled = true;
                    cellSelect.disabled = true;
                    villageSelect.disabled = true;
                }
            });

            // District change event
            districtSelect.addEventListener('change', function() {
                const selectedProvince = provinceSelect.value;
                const selectedDistrict = this.value;
                
                resetDropdown(sectorSelect);
                resetDropdown(cellSelect);
                resetDropdown(villageSelect);
                
                if (selectedProvince && selectedDistrict && rwandaLocations[selectedProvince][selectedDistrict]) {
                    sectorSelect.disabled = false;
                    populateDropdown(sectorSelect, Object.keys(rwandaLocations[selectedProvince][selectedDistrict]));
                } else {
                    sectorSelect.disabled = true;
                    cellSelect.disabled = true;
                    villageSelect.disabled = true;
                }
            });

            // Sector change event
            sectorSelect.addEventListener('change', function() {
                const selectedProvince = provinceSelect.value;
                const selectedDistrict = districtSelect.value;
                const selectedSector = this.value;
                
                resetDropdown(cellSelect);
                resetDropdown(villageSelect);
                
                if (selectedProvince && selectedDistrict && selectedSector && 
                    rwandaLocations[selectedProvince][selectedDistrict][selectedSector]) {
                    cellSelect.disabled = false;
                    populateDropdown(cellSelect, Object.keys(rwandaLocations[selectedProvince][selectedDistrict][selectedSector]));
                } else {
                    cellSelect.disabled = true;
                    villageSelect.disabled = true;
                }
            });

            // Cell change event
            cellSelect.addEventListener('change', function() {
                const selectedProvince = provinceSelect.value;
                const selectedDistrict = districtSelect.value;
                const selectedSector = sectorSelect.value;
                const selectedCell = this.value;
                
                resetDropdown(villageSelect);
                
                if (selectedProvince && selectedDistrict && selectedSector && selectedCell && 
                    rwandaLocations[selectedProvince][selectedDistrict][selectedSector][selectedCell]) {
                    villageSelect.disabled = false;
                    populateDropdown(villageSelect, rwandaLocations[selectedProvince][selectedDistrict][selectedSector][selectedCell]);
                } else {
                    villageSelect.disabled = true;
                }
            });

            // Character counters
            document.querySelectorAll('textarea[maxlength]').forEach(textarea => {
                const counter = textarea.nextElementSibling;
                if (counter && counter.classList.contains('character-counter')) {
                    const updateCounter = () => {
                        const maxLength = parseInt(textarea.getAttribute('maxlength'));
                        const currentLength = textarea.value.length;
                        counter.textContent = `${currentLength}/${maxLength} characters`;
                    };
                    
                    textarea.addEventListener('input', updateCounter);
                    updateCounter();
                }
            });

            // Form submission
            const form = document.getElementById('groupForm');
            form.addEventListener('submit', function(e) {
                const name = document.getElementById('name').value.trim();
                const province = document.getElementById('province').value;
                
                if (!name) {
                    e.preventDefault();
                    alert('Group name is required');
                    return;
                }
                
                if (!province) {
                    e.preventDefault();
                    alert('Please select a province');
                    return;
                }
            });
        });
    </script>
</body>
</html>



