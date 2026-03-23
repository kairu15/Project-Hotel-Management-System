<?php
$pageTitle = 'Manage Staff Schedules - Admin';
require_once '../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    showAlert('Access denied. Admin privileges required.', 'danger');
    redirect('../index.php');
}

$db = getDB();

// Handle add/edit schedule
if (isset($_POST['save_schedule'])) {
    $scheduleId = $_POST['schedule_id'] ?? null;
    $userId = $_POST['user_id'] ?? '';
    $workDate = $_POST['work_date'] ?? '';
    $shiftStart = $_POST['shift_start'] ?? '';
    $shiftEnd = $_POST['shift_end'] ?? '';
    $role = $_POST['role'] ?? '';
    $status = $_POST['status'] ?? 'scheduled';
    $notes = $_POST['notes'] ?? '';

    if ($userId && $workDate && $shiftStart && $shiftEnd) {
        if ($scheduleId) {
            // Update existing schedule
            $stmt = $db->prepare("UPDATE staff_schedules SET user_id = ?, work_date = ?, shift_start = ?, shift_end = ?, role = ?, status = ?, notes = ? WHERE schedule_id = ?");
            $stmt->execute([$userId, $workDate, $shiftStart, $shiftEnd, $role, $status, $notes, $scheduleId]);
            $_SESSION['success'] = 'Schedule updated successfully';
        } else {
            // Add new schedule
            $stmt = $db->prepare("INSERT INTO staff_schedules (user_id, work_date, shift_start, shift_end, role, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $workDate, $shiftStart, $shiftEnd, $role, $status, $notes]);
            $_SESSION['success'] = 'Schedule added successfully';
        }
    } else {
        $_SESSION['error'] = 'Please fill in all required fields';
    }
    redirect('admin-staff-schedules.php');
}

// Handle delete schedule
if (isset($_POST['delete_schedule'])) {
    $scheduleId = $_POST['schedule_id'] ?? 0;
    if ($scheduleId) {
        $stmt = $db->prepare("DELETE FROM staff_schedules WHERE schedule_id = ?");
        if ($stmt->execute([$scheduleId])) {
            $_SESSION['success'] = 'Schedule deleted successfully';
        } else {
            $_SESSION['error'] = 'Failed to delete schedule';
        }
    }
    redirect('admin-staff-schedules.php');
}

// Get filter parameters
$dateFrom = $_GET['date_from'] ?? date('Y-m-d');
$dateTo = $_GET['date_to'] ?? date('Y-m-d', strtotime('+7 days'));
$staffFilter = $_GET['staff'] ?? '';
$statusFilter = $_GET['status'] ?? '';

// Build query
$sql = "
    SELECT ss.*, u.first_name, u.last_name, u.email
    FROM staff_schedules ss
    JOIN users u ON ss.user_id = u.user_id
    WHERE ss.work_date BETWEEN ? AND ?
";
$params = [$dateFrom, $dateTo];

if ($staffFilter) {
    $sql .= " AND ss.user_id = ?";
    $params[] = $staffFilter;
}

if ($statusFilter) {
    $sql .= " AND ss.status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY ss.work_date, ss.shift_start";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$schedules = $stmt->fetchAll();

// Get staff for filter
$staff = $db->query("SELECT user_id, first_name, last_name FROM users WHERE role IN ('admin', 'manager', 'receptionist') ORDER BY first_name, last_name")->fetchAll();

// Status counts
$statusCounts = $db->query("
    SELECT status, COUNT(*) as count FROM staff_schedules WHERE work_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) GROUP BY status
")->fetchAll(PDO::FETCH_KEY_PAIR);

$statuses = ['scheduled', 'completed', 'absent', 'leave'];

require_once '../includes/admin-header.php';
?>

<section style="padding: 30px 0; background-color: var(--gray-light); min-height: calc(100vh - 250px);">
    <div class="container">
        <div style="display: flex; justify-content: flex-end; gap: 15px; margin-bottom: 20px;">
            <a href="admin-dashboard.php" class="btn btn-outline">Back to Dashboard</a>
            <button type="button" onclick="openScheduleModal()" class="btn btn-primary">Add Schedule</button>
        </div>

        <!-- Stats Cards -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 30px;">
            <?php
            $statusColors = [
                'scheduled' => ['#cce5ff', '#004085'],
                'completed' => ['#d4edda', '#155724'],
                'absent' => ['#f8d7da', '#721c24'],
                'leave' => ['#fff3cd', '#856404']
            ];
            foreach ($statuses as $status):
                $count = $statusCounts[$status] ?? 0;
                $color = $statusColors[$status];
            ?>
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid <?php echo $color[1]; ?>;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="font-size: 28px; margin: 0;"><?php echo $count; ?></h3>
                        <p style="color: #666; margin: 5px 0 0; text-transform: capitalize;"><?php echo $status; ?></p>
                    </div>
                    <i class="fas fa-calendar" style="font-size: 32px; color: <?php echo $color[1]; ?>"></i>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Filters -->
        <div style="background-color: white; padding: 20px 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
            <form method="GET" action="" style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr auto; gap: 15px; align-items: end;">
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">From Date</label>
                    <input type="date" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">To Date</label>
                    <input type="date" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Staff</label>
                    <select name="staff" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                        <option value="">All Staff</option>
                        <?php foreach ($staff as $s): ?>
                        <option value="<?php echo $s['user_id']; ?>" <?php echo $staffFilter == $s['user_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Status</label>
                    <select name="status" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                        <option value="">All Status</option>
                        <?php foreach ($statuses as $s): ?>
                        <option value="<?php echo $s; ?>" <?php echo $statusFilter === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">Filter</button>
                    <a href="admin-staff-schedules.php" class="btn btn-secondary" style="padding: 10px 20px;">Reset</a>
                </div>
            </form>
        </div>

        <!-- Schedules Table -->
        <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden;">
            <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 20px; margin: 0;">Staff Schedules (<?php echo count($schedules); ?>)</h3>
            </div>

            <?php if (count($schedules) > 0): ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color: var(--gray-light);">
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Staff</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Date</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Shift</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Role</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Notes</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Status</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schedules as $schedule):
                        $color = $statusColors[$schedule['status']] ?? ['#e2e3e5', '#383d41'];
                        ?>
                        <tr style="border-bottom: 1px solid var(--gray-light);">
                            <td style="padding: 15px 20px;">
                                <div style="font-weight: 500;"><?php echo htmlspecialchars($schedule['first_name'] . ' ' . $schedule['last_name']); ?></div>
                                <div style="font-size: 12px; color: #666;"><?php echo htmlspecialchars($schedule['email']); ?></div>
                            </td>
                            <td style="padding: 15px 20px;">
                                <?php echo formatDate($schedule['work_date'], 'M d, Y (D)'); ?>
                            </td>
                            <td style="padding: 15px 20px;">
                                <div style="font-weight: 500;">
                                    <?php echo date('g:i A', strtotime($schedule['shift_start'])); ?> - <?php echo date('g:i A', strtotime($schedule['shift_end'])); ?>
                                </div>
                            </td>
                            <td style="padding: 15px 20px;">
                                <?php echo htmlspecialchars($schedule['role'] ?: 'N/A'); ?>
                            </td>
                            <td style="padding: 15px 20px;">
                                <div style="font-size: 13px; color: #666; max-width: 150px;"><?php echo htmlspecialchars($schedule['notes'] ?: '-'); ?></div>
                            </td>
                            <td style="padding: 15px 20px;">
                                <span style="padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: <?php echo $color[0]; ?>; color: <?php echo $color[1]; ?>; text-transform: capitalize;">
                                    <?php echo $schedule['status']; ?>
                                </span>
                            </td>
                            <td style="padding: 15px 20px;">
                                <div style="display: flex; gap: 10px;">
                                    <button type="button" onclick="editSchedule(<?php echo htmlspecialchars(json_encode($schedule)); ?>)" class="btn btn-sm btn-primary" style="padding: 5px 12px; font-size: 12px;">Edit</button>
                                    <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this schedule?');">
                                        <input type="hidden" name="schedule_id" value="<?php echo $schedule['schedule_id']; ?>">
                                        <button type="submit" name="delete_schedule" class="btn btn-sm btn-danger" style="padding: 5px 12px; font-size: 12px;"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div style="padding: 60px; text-align: center;">
                <i class="fas fa-calendar-alt" style="font-size: 48px; color: var(--gray-light); margin-bottom: 20px;"></i>
                <h3 style="color: #666;">No schedules found</h3>
                <p style="color: #999;">Add your first staff schedule</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Schedule Modal -->
<div id="scheduleModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background-color: white; border-radius: 10px; width: 90%; max-width: 500px; max-height: 90vh; overflow-y: auto;">
        <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
            <h3 id="modalTitle" style="font-size: 20px; margin: 0;">Add New Schedule</h3>
            <button onclick="closeScheduleModal()" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" action="" style="padding: 30px;">
            <input type="hidden" name="schedule_id" id="schedule_id">

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Staff Member *</label>
                <select name="user_id" id="user_id" required style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                    <option value="">Select Staff</option>
                    <?php foreach ($staff as $s): ?>
                    <option value="<?php echo $s['user_id']; ?>"><?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Work Date *</label>
                <input type="date" name="work_date" id="work_date" required style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Shift Start *</label>
                    <input type="time" name="shift_start" id="shift_start" required style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                </div>
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Shift End *</label>
                    <input type="time" name="shift_end" id="shift_end" required style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Role</label>
                    <input type="text" name="role" id="role" placeholder="e.g., Receptionist" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                </div>
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Status</label>
                    <select name="status" id="status" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                        <option value="scheduled">Scheduled</option>
                        <option value="completed">Completed</option>
                        <option value="absent">Absent</option>
                        <option value="leave">Leave</option>
                    </select>
                </div>
            </div>

            <div style="margin-bottom: 25px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Notes</label>
                <textarea name="notes" id="notes" rows="2" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;"></textarea>
            </div>

            <div style="display: flex; gap: 15px; justify-content: flex-end;">
                <button type="button" onclick="closeScheduleModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="save_schedule" class="btn btn-primary">Save Schedule</button>
            </div>
        </form>
    </div>
</div>

<script>
function openScheduleModal() {
    document.getElementById('scheduleModal').style.display = 'flex';
    document.getElementById('modalTitle').textContent = 'Add New Schedule';
    document.getElementById('schedule_id').value = '';
    document.getElementById('user_id').value = '';
    document.getElementById('work_date').value = '';
    document.getElementById('shift_start').value = '';
    document.getElementById('shift_end').value = '';
    document.getElementById('role').value = '';
    document.getElementById('status').value = 'scheduled';
    document.getElementById('notes').value = '';
}

function editSchedule(schedule) {
    document.getElementById('scheduleModal').style.display = 'flex';
    document.getElementById('modalTitle').textContent = 'Edit Schedule';
    document.getElementById('schedule_id').value = schedule.schedule_id;
    document.getElementById('user_id').value = schedule.user_id;
    document.getElementById('work_date').value = schedule.work_date;
    document.getElementById('shift_start').value = schedule.shift_start;
    document.getElementById('shift_end').value = schedule.shift_end;
    document.getElementById('role').value = schedule.role || '';
    document.getElementById('status').value = schedule.status;
    document.getElementById('notes').value = schedule.notes || '';
}

function closeScheduleModal() {
    document.getElementById('scheduleModal').style.display = 'none';
}

document.getElementById('scheduleModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeScheduleModal();
    }
});
</script>

<?php require_once '../includes/admin-footer.php'; ?>
