<?php
// views/dashboards/secretary_dashboard.php
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
    
    if ($groupDetails['user_role'] !== 'secretary') {
        header('Location: main_dashboard.php?error=not_authorized');
        exit;
    }

    // Get upcoming meetings
    $meetingsStmt = $pdo->prepare("
        SELECT * FROM meetings 
        WHERE group_id = ? AND meeting_date >= CURDATE()
        ORDER BY meeting_date ASC 
        LIMIT 5
    ");
    $meetingsStmt->execute([$groupId]);
    $upcomingMeetings = $meetingsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get recent minutes
    $minutesStmt = $pdo->prepare("
        SELECT mm.*, u.first_name, u.last_name 
        FROM meeting_minutes mm
        JOIN users u ON mm.recorded_by = u.id
        WHERE mm.group_id = ?
        ORDER BY mm.meeting_date DESC 
        LIMIT 5
    ");
    $minutesStmt->execute([$groupId]);
    $recentMinutes = $minutesStmt->fetchAll(PDO::FETCH_ASSOC);

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
            <h1>Secretary - <?= htmlspecialchars($groupDetails['name']) ?></h1>
            <small>Group Code: <?= htmlspecialchars($groupDetails['group_code']) ?></small>
        </div>
        <div class="topbar-right">
            <span class="badge" style="background: #d97706; color: white; padding: 0.5rem 1rem; border-radius: 20px;">
                <span class="material-icons-sharp">description</span>
                Secretary
            </span>
        </div>
    </header>

    <div class="dashboard-container">
        <!-- Upcoming Meetings -->
        <div class="meetings-section">
            <h3>
                <span class="material-icons-sharp">event</span>
                Upcoming Meetings
            </h3>
            <div class="meetings-list">
                <?php if (!empty($upcomingMeetings)): ?>
                    <?php foreach ($upcomingMeetings as $meeting): ?>
                    <div class="meeting-card">
                        <div class="meeting-date">
                            <strong><?= date('M j', strtotime($meeting['meeting_date'])) ?></strong>
                            <small><?= date('g:i A', strtotime($meeting['meeting_time'])) ?></small>
                        </div>
                        <div class="meeting-info">
                            <h4><?= htmlspecialchars($meeting['title']) ?></h4>
                            <p><?= htmlspecialchars($meeting['description']) ?></p>
                            <small>Location: <?= htmlspecialchars($meeting['location']) ?></small>
                        </div>
                        <div class="meeting-actions">
                            <button class="btn btn-outline" onclick="viewMeeting(<?= $meeting['id'] ?>)">
                                <span class="material-icons-sharp">visibility</span>
                                View
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-meetings">
                        <span class="material-icons-sharp">event_available</span>
                        <p>No upcoming meetings scheduled</p>
                        <button class="btn btn-primary" onclick="location.href='../groups/schedule_meeting.php?group_id=<?= $groupId ?>'">
                            Schedule Meeting
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Secretary Controls -->
        <div class="admin-controls">
            <div class="admin-card" onclick="location.href='../groups/schedule_meeting.php?group_id=<?= $groupId ?>'">
                <span class="material-icons-sharp">event</span>
                <h3>Schedule Meeting</h3>
                <p>Organize group meetings</p>
            </div>
            
            <div class="admin-card" onclick="location.href='../groups/record_minutes.php?group_id=<?= $groupId ?>'">
                <span class="material-icons-sharp">description</span>
                <h3>Record Minutes</h3>
                <p>Document meeting discussions</p>
            </div>
            
            <div class="admin-card" onclick="location.href='../groups/group_communication.php?group_id=<?= $groupId ?>'">
                <span class="material-icons-sharp">announcement</span>
                <h3>Send Announcements</h3>
                <p>Communicate with members</p>
            </div>
            
            <div class="admin-card" onclick="location.href='../groups/member_records.php?group_id=<?= $groupId ?>'">
                <span class="material-icons-sharp">folder</span>
                <h3>Member Records</h3>
                <p>Manage member documentation</p>
            </div>
        </div>

        <!-- Recent Meeting Minutes -->
        <div class="minutes-section">
            <h3>
                <span class="material-icons-sharp">history</span>
                Recent Meeting Minutes
            </h3>
            <div class="minutes-list">
                <?php if (!empty($recentMinutes)): ?>
                    <?php foreach ($recentMinutes as $minute): ?>
                    <div class="minute-card">
                        <div class="minute-header">
                            <h4><?= htmlspecialchars($minute['meeting_title']) ?></h4>
                            <span class="minute-date"><?= date('M j, Y', strtotime($minute['meeting_date'])) ?></span>
                        </div>
                        <p class="minute-summary"><?= htmlspecialchars(substr($minute['minutes_content'], 0, 150)) ?>...</p>
                        <div class="minute-footer">
                            <small>Recorded by: <?= htmlspecialchars($minute['first_name'] . ' ' . $minute['last_name']) ?></small>
                            <button class="btn btn-outline btn-sm" onclick="viewMinutes(<?= $minute['id'] ?>)">
                                Read More
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-minutes">
                        <span class="material-icons-sharp">description</span>
                        <p>No meeting minutes recorded yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function viewMeeting(meetingId) {
    window.location.href = `../groups/view_meeting.php?group_id=<?= $groupId ?>&meeting_id=${meetingId}`;
}

function viewMinutes(minuteId) {
    window.location.href = `../groups/view_minutes.php?group_id=<?= $groupId ?>&minute_id=${minuteId}`;
}
</script>

<?php include_once __DIR__ . '/../partials/footer.php'; ?>