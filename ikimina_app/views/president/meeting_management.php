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
    $upcomingMeetings = $presidentController->getUpcomingMeetings(); // This would come from database
    $pastMeetings = []; // This would come from database
    
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
                <h1>Meeting Management - <?= htmlspecialchars($groupInfo['name']) ?></h1>
                <small>Group Code: <?= htmlspecialchars($groupInfo['group_code']) ?></small>
            </div>
        </div>
        <div class="topbar-right">
            <span class="badge president-badge">
                <span class="material-icons-sharp">admin_panel_settings</span>
                President
            </span>
            <button class="btn btn-primary" onclick="openScheduleModal()">
                <span class="material-icons-sharp">add</span>
                Schedule Meeting
            </button>
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

    <div class="meetings-container">
        <!-- Upcoming Meetings -->
        <div class="meetings-section">
            <h3>
                <span class="material-icons-sharp">event_upcoming</span>
                Upcoming Meetings
            </h3>
            
            <?php if (!empty($upcomingMeetings)): ?>
            <div class="meetings-grid">
                <?php foreach ($upcomingMeetings as $meeting): ?>
                <div class="meeting-card upcoming">
                    <div class="meeting-header">
                        <div class="meeting-date">
                            <span class="date"><?= date('j', strtotime($meeting['date'])) ?></span>
                            <span class="month"><?= date('M', strtotime($meeting['date'])) ?></span>
                            <span class="year"><?= date('Y', strtotime($meeting['date'])) ?></span>
                        </div>
                        <div class="meeting-info">
                            <h4><?= htmlspecialchars($meeting['title']) ?></h4>
                            <p class="meeting-time">
                                <span class="material-icons-sharp">schedule</span>
                                <?= $meeting['time'] ?>
                            </p>
                            <?php if (isset($meeting['location'])): ?>
                            <p class="meeting-location">
                                <span class="material-icons-sharp">location_on</span>
                                <?= htmlspecialchars($meeting['location']) ?>
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="meeting-actions">
                        <button class="btn btn-outline" onclick="editMeeting(<?= $meeting['id'] ?? 0 ?>)">
                            <span class="material-icons-sharp">edit</span>
                            Edit
                        </button>
                        <button class="btn btn-outline" onclick="cancelMeeting(<?= $meeting['id'] ?? 0 ?>)">
                            <span class="material-icons-sharp">cancel</span>
                            Cancel
                        </button>
                        <button class="btn btn-primary" onclick="takeAttendance(<?= $meeting['id'] ?? 0 ?>)">
                            <span class="material-icons-sharp">how_to_reg</span>
                            Take Attendance
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <span class="material-icons-sharp">event_available</span>
                <h3>No Upcoming Meetings</h3>
                <p>Schedule a new meeting to get started.</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Past Meetings -->
        <div class="meetings-section">
            <h3>
                <span class="material-icons-sharp">history</span>
                Past Meetings
            </h3>
            
            <?php if (!empty($pastMeetings)): ?>
            <div class="meetings-table-container">
                <table class="meetings-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Meeting</th>
                            <th>Attendance</th>
                            <th>Minutes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pastMeetings as $meeting): ?>
                        <tr>
                            <td>
                                <div class="date-cell">
                                    <strong><?= date('M j, Y', strtotime($meeting['date'])) ?></strong>
                                    <small><?= $meeting['time'] ?></small>
                                </div>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($meeting['title']) ?></strong>
                                <?php if (isset($meeting['location'])): ?>
                                <br><small><?= htmlspecialchars($meeting['location']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="attendance-rate">
                                    <?= $meeting['attendance_present'] ?? 0 ?>/<?= $meeting['attendance_total'] ?? 0 ?>
                                    <small>(<?= round((($meeting['attendance_present'] ?? 0) / max(($meeting['attendance_total'] ?? 1), 1)) * 100) ?>%)</small>
                                </span>
                            </td>
                            <td>
                                <?php if (isset($meeting['minutes_available'])): ?>
                                <span class="badge success">Available</span>
                                <?php else: ?>
                                <span class="badge warning">Not Available</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <button class="btn-icon" onclick="viewMeetingDetails(<?= $meeting['id'] ?? 0 ?>)" title="View Details">
                                        <span class="material-icons-sharp">visibility</span>
                                    </button>
                                    <button class="btn-icon" onclick="downloadMinutes(<?= $meeting['id'] ?? 0 ?>)" title="Download Minutes">
                                        <span class="material-icons-sharp">download</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <span class="material-icons-sharp">inventory_2</span>
                <h3>No Past Meetings</h3>
                <p>Past meetings will appear here once scheduled and completed.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Schedule Meeting Modal -->
<div id="scheduleModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Schedule New Meeting</h3>
            <span class="close" onclick="closeScheduleModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form id="scheduleForm" action="../../controllers/president/schedule_meeting.php" method="POST">
                <input type="hidden" name="group_id" value="<?= $groupId ?>">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="meetingTitle">Meeting Title *</label>
                        <input type="text" id="meetingTitle" name="title" required 
                               placeholder="Enter meeting title">
                    </div>
                    <div class="form-group">
                        <label for="meetingDate">Date *</label>
                        <input type="date" id="meetingDate" name="date" required 
                               min="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label for="meetingTime">Time *</label>
                        <input type="time" id="meetingTime" name="time" required>
                    </div>
                    <div class="form-group">
                        <label for="meetingLocation">Location</label>
                        <input type="text" id="meetingLocation" name="location" 
                               placeholder="Enter meeting location">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="meetingDescription">Description</label>
                    <textarea id="meetingDescription" name="description" rows="4" 
                              placeholder="Enter meeting description and agenda..."></textarea>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="send_reminder" value="1" checked>
                        <span class="checkmark"></span>
                        Send reminder to all members 24 hours before meeting
                    </label>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeScheduleModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Schedule Meeting</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openScheduleModal() {
    document.getElementById('scheduleModal').style.display = 'block';
    // Set default time to next hour
    const nextHour = new Date();
    nextHour.setHours(nextHour.getHours() + 1);
    nextHour.setMinutes(0);
    document.getElementById('meetingTime').value = nextHour.toTimeString().slice(0, 5);
}

function closeScheduleModal() {
    document.getElementById('scheduleModal').style.display = 'none';
}

function editMeeting(meetingId) {
    alert('Edit meeting functionality for meeting ID: ' + meetingId);
    // Implement edit meeting
}

function cancelMeeting(meetingId) {
    if (confirm('Are you sure you want to cancel this meeting?')) {
        alert('Cancel meeting functionality for meeting ID: ' + meetingId);
        // Implement cancel meeting
    }
}

function takeAttendance(meetingId) {
    alert('Take attendance functionality for meeting ID: ' + meetingId);
    // Implement take attendance
}

function viewMeetingDetails(meetingId) {
    alert('View meeting details for meeting ID: ' + meetingId);
    // Implement view meeting details
}

function downloadMinutes(meetingId) {
    alert('Download minutes for meeting ID: ' + meetingId);
    // Implement download minutes
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('scheduleModal');
    if (event.target == modal) {
        closeScheduleModal();
    }
}

// Form validation
document.getElementById('scheduleForm').addEventListener('submit', function(e) {
    const meetingDate = new Date(document.getElementById('meetingDate').value);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    if (meetingDate < today) {
        e.preventDefault();
        alert('Meeting date cannot be in the past');
        return false;
    }
    
    return true;
});
</script>

<style>
.meetings-container {
    margin-top: 2rem;
}

.meetings-section {
    margin-bottom: 3rem;
}

.meetings-section h3 {
    color: #2c3e50;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.meetings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 1.5rem;
}

.meeting-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 1.5rem;
    transition: transform 0.2s, box-shadow 0.2s;
}

.meeting-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.meeting-card.upcoming {
    border-left: 4px solid #007bff;
}

.meeting-header {
    display: flex;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.meeting-date {
    background: #007bff;
    color: white;
    padding: 1rem;
    border-radius: 8px;
    text-align: center;
    min-width: 80px;
}

.meeting-date .date {
    display: block;
    font-size: 2rem;
    font-weight: bold;
    line-height: 1;
}

.meeting-date .month {
    display: block;
    font-size: 1rem;
    text-transform: uppercase;
    margin: 0.25rem 0;
}

.meeting-date .year {
    display: block;
    font-size: 0.8rem;
    opacity: 0.9;
}

.meeting-info {
    flex: 1;
}

.meeting-info h4 {
    margin: 0 0 1rem 0;
    color: #2c3e50;
}

.meeting-time, .meeting-location {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0.5rem 0;
    color: #6c757d;
}

.meeting-actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.meetings-table-container {
    background: white;
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid #e9ecef;
}

.meetings-table {
    width: 100%;
    border-collapse: collapse;
}

.meetings-table th {
    background: #f8f9fa;
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: #2c3e50;
    border-bottom: 1px solid #e9ecef;
}

.meetings-table td {
    padding: 1rem;
    border-bottom: 1px solid #f1f3f4;
}

.meetings-table tr:last-child td {
    border-bottom: none;
}

.date-cell {
    display: flex;
    flex-direction: column;
}

.date-cell small {
    color: #6c757d;
    font-size: 0.8rem;
}

.attendance-rate {
    font-weight: 500;
    color: #2c3e50;
}

.attendance-rate small {
    color: #6c757d;
    font-size: 0.8rem;
}

.badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.badge.success {
    background: #d1fae5;
    color: #065f46;
}

.badge.warning {
    background: #fef3c7;
    color: #92400e;
}

.table-actions {
    display: flex;
    gap: 0.5rem;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.checkbox-label {
    display: flex;
    align-items: center;
    cursor: pointer;
    font-weight: normal;
    margin: 1rem 0;
}

.checkbox-label input {
    width: auto;
    margin-right: 0.5rem;
}

@media (max-width: 768px) {
    .meetings-grid {
        grid-template-columns: 1fr;
    }
    
    .meeting-header {
        flex-direction: column;
        text-align: center;
    }
    
    .meeting-actions {
        justify-content: center;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .meetings-table-container {
        overflow-x: auto;
    }
}
</style>

<?php include_once __DIR__ . '/../partials/footer.php'; ?>