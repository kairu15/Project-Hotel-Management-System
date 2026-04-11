<?php
/**
 * Staff Tasks - Bayawan Bai Hotel
 * Manage staff schedules and daily tasks
 */
$pageTitle = 'Staff Tasks';
require_once '../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    showAlert('Access denied. Admin privileges required.', 'danger');
    redirect('../index.php');
}

$db = getDB();
$userId = getUserId();
$userRole = getUserRole();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_schedule'])) {
        $staffId = $_POST['staff_id'] ?? 0;
        $workDate = $_POST['work_date'] ?? '';
        $shiftStart = $_POST['shift_start'] ?? '';
        $shiftEnd = $_POST['shift_end'] ?? '';
        $role = $_POST['role'] ?? '';
        $notes = $_POST['notes'] ?? '';
        
        if ($staffId && $workDate) {
            $stmt = $db->prepare("INSERT INTO staff_schedules (user_id, work_date, shift_start, shift_end, role, notes) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$staffId, $workDate, $shiftStart, $shiftEnd, $role, $notes]);
            $_SESSION['success'] = 'Schedule added successfully';
            logActivity("Added schedule for staff ID: $staffId on $workDate");
        } else {
            $_SESSION['error'] = 'Please fill in all required fields';
        }
        redirect('admin-tasks.php');
    }
    
    if (isset($_POST['update_schedule'])) {
        $scheduleId = $_POST['schedule_id'] ?? 0;
        $status = $_POST['status'] ?? '';
        
        if ($scheduleId && $status) {
            $stmt = $db->prepare("UPDATE staff_schedules SET status = ? WHERE schedule_id = ?");
            $stmt->execute([$status, $scheduleId]);
            $_SESSION['success'] = 'Schedule updated successfully';
            logActivity("Updated schedule ID: $scheduleId to status: $status");
        }
        redirect('admin-tasks.php');
    }
    
    if (isset($_POST['delete_schedule'])) {
        $scheduleId = $_POST['schedule_id'] ?? 0;
        if ($scheduleId) {
            $stmt = $db->prepare("DELETE FROM staff_schedules WHERE schedule_id = ?");
            $stmt->execute([$scheduleId]);
            $_SESSION['success'] = 'Schedule deleted successfully';
            logActivity("Deleted schedule ID: $scheduleId");
        }
        redirect('admin-tasks.php');
    }
}

// Get filter parameters
$dateFilter = $_GET['date'] ?? date('Y-m-d');
$statusFilter = $_GET['status'] ?? '';
$staffFilter = $_GET['staff'] ?? '';

// Build query for schedules
$sql = "
    SELECT ss.*, u.first_name, u.last_name, u.role as user_role
    FROM staff_schedules ss
    JOIN users u ON ss.user_id = u.user_id
    WHERE 1=1
";
$params = [];

if ($dateFilter) {
    $sql .= " AND ss.work_date = ?";
    $params[] = $dateFilter;
}

if ($statusFilter) {
    $sql .= " AND ss.status = ?";
    $params[] = $statusFilter;
}

if ($staffFilter) {
    $sql .= " AND ss.user_id = ?";
    $params[] = $staffFilter;
}

$sql .= " ORDER BY ss.work_date DESC, ss.shift_start ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$schedules = $stmt->fetchAll();

// Get staff list for dropdown
$staffList = $db->query("SELECT user_id, first_name, last_name, role FROM users WHERE role IN ('admin', 'manager', 'receptionist', 'staff') ORDER BY first_name")
    ->fetchAll();

// Get statistics
$today = date('Y-m-d');
$stats = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN work_date = '$today' AND status = 'scheduled' THEN 1 ELSE 0 END) as today_scheduled,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent
    FROM staff_schedules
")->fetch();

require_once '../includes/admin-header.php';
?>

<section style="padding: 30px; background-color: var(--gray-light); min-height: calc(100vh - 250px);">
    <div class="container">
        <!-- Header Actions -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h2>Staff Tasks & Schedules</h2>
            <div style="display: flex; gap: 15px;">
                <button onclick="openAddModal()" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Schedule
                </button>
                <a href="admin-dashboard.php" class="btn btn-outline">Back to Dashboard</a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <div style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="width: 50px; height: 50px; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-calendar" style="color: white; font-size: 20px;"></i>
                    </div>
                    <div>
                        <p style="color: #666; font-size: 13px; margin: 0;">Total Schedules</p>
                        <h3 style="margin: 5px 0 0 0; font-size: 24px;"><?php echo $stats['total']; ?></h3>
                    </div>
                </div>
            </div>
            <div style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #ffc107, #ff9800); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-clock" style="color: white; font-size: 20px;"></i>
                    </div>
                    <div>
                        <p style="color: #666; font-size: 13px; margin: 0;">Today Scheduled</p>
                        <h3 style="margin: 5px 0 0 0; font-size: 24px;"><?php echo $stats['today_scheduled']; ?></h3>
                    </div>
                </div>
            </div>
            <div style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #28a745, #20c997); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-check-circle" style="color: white; font-size: 20px;"></i>
                    </div>
                    <div>
                        <p style="color: #666; font-size: 13px; margin: 0;">Completed</p>
                        <h3 style="margin: 5px 0 0 0; font-size: 24px;"><?php echo $stats['completed']; ?></h3>
                    </div>
                </div>
            </div>
            <div style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #dc3545, #c82333); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-times-circle" style="color: white; font-size: 20px;"></i>
                    </div>
                    <div>
                        <p style="color: #666; font-size: 13px; margin: 0;">Absent</p>
                        <h3 style="margin: 5px 0 0 0; font-size: 24px;"><?php echo $stats['absent']; ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div style="background-color: white; padding: 20px 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
            <form method="GET" action="" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; align-items: end;">
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Date</label>
                    <input type="date" name="date" value="<?php echo htmlspecialchars($dateFilter); ?>" style="width: 100%; padding: 10px; border: 1px solid var(--gray-medium); border-radius: 5px;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Status</label>
                    <select name="status" style="width: 100%; padding: 10px; border: 1px solid var(--gray-medium); border-radius: 5px;">
                        <option value="">All Status</option>
                        <option value="scheduled" <?php echo $statusFilter === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                        <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="absent" <?php echo $statusFilter === 'absent' ? 'selected' : ''; ?>>Absent</option>
                        <option value="leave" <?php echo $statusFilter === 'leave' ? 'selected' : ''; ?>>Leave</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Staff</label>
                    <select name="staff" style="width: 100%; padding: 10px; border: 1px solid var(--gray-medium); border-radius: 5px;">
                        <option value="">All Staff</option>
                        <?php foreach ($staffList as $staff): ?>
                        <option value="<?php echo $staff['user_id']; ?>" <?php echo $staffFilter == $staff['user_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">Filter</button>
                    <a href="admin-tasks.php" class="btn btn-secondary" style="padding: 10px 20px;">Reset</a>
                </div>
            </form>
        </div>

        <!-- Schedules Table -->
        <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background-color: var(--dark-color); color: white;">
                        <th style="padding: 15px; text-align: left;">Staff</th>
                        <th style="padding: 15px; text-align: left;">Work Date</th>
                        <th style="padding: 15px; text-align: left;">Shift</th>
                        <th style="padding: 15px; text-align: left;">Role</th>
                        <th style="padding: 15px; text-align: left;">Status</th>
                        <th style="padding: 15px; text-align: left;">Notes</th>
                        <th style="padding: 15px; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($schedules)): ?>
                    <tr>
                        <td colspan="7" style="padding: 40px; text-align: center; color: #666;">
                            <i class="fas fa-calendar-times" style="font-size: 48px; color: var(--gray-medium); margin-bottom: 15px; display: block;"></i>
                            No schedules found
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($schedules as $schedule): ?>
                    <tr style="border-bottom: 1px solid var(--gray-light);">
                        <td style="padding: 15px;">
                            <strong><?php echo htmlspecialchars($schedule['first_name'] . ' ' . $schedule['last_name']); ?></strong>
                            <br><small style="color: #666;"><?php echo ucfirst($schedule['user_role']); ?></small>
                        </td>
                        <td style="padding: 15px;">
                            <?php echo date('M d, Y', strtotime($schedule['work_date'])); ?>
                            <?php if ($schedule['work_date'] == $today): ?>
                                <span style="background: var(--primary-color); color: white; padding: 2px 8px; border-radius: 10px; font-size: 11px; margin-left: 5px;">Today</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 15px;">
                            <?php if ($schedule['shift_start'] && $schedule['shift_end']): ?>
                                <?php echo date('h:i A', strtotime($schedule['shift_start'])); ?> - 
                                <?php echo date('h:i A', strtotime($schedule['shift_end'])); ?>
                            <?php else: ?>
                                <span style="color: #999;">Not set</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 15px;">
                            <?php echo $schedule['role'] ? htmlspecialchars($schedule['role']) : '-'; ?>
                        </td>
                        <td style="padding: 15px;">
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="schedule_id" value="<?php echo $schedule['schedule_id']; ?>">
                                <select name="status" onchange="this.form.submit()" style="padding: 5px 10px; border-radius: 5px; border: 1px solid var(--gray-medium); font-size: 13px;">
                                    <option value="scheduled" <?php echo $schedule['status'] === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                    <option value="completed" <?php echo $schedule['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="absent" <?php echo $schedule['status'] === 'absent' ? 'selected' : ''; ?>>Absent</option>
                                    <option value="leave" <?php echo $schedule['status'] === 'leave' ? 'selected' : ''; ?>>Leave</option>
                                </select>
                                <input type="hidden" name="update_schedule" value="1">
                            </form>
                        </td>
                        <td style="padding: 15px; color: #666; font-size: 13px;">
                            <?php echo $schedule['notes'] ? htmlspecialchars(substr($schedule['notes'], 0, 50)) . (strlen($schedule['notes']) > 50 ? '...' : '') : '-'; ?>
                        </td>
                        <td style="padding: 15px; text-align: center;">
                            <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Delete this schedule?');">
                                <input type="hidden" name="schedule_id" value="<?php echo $schedule['schedule_id']; ?>">
                                <button type="submit" name="delete_schedule" style="background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer;">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- Add Schedule Modal -->
<div id="addModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 10px; width: 90%; max-width: 500px; max-height: 90vh; overflow-y: auto;">
        <div style="padding: 25px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0;">Add New Schedule</h3>
            <button onclick="closeAddModal()" style="background: none; border: none; font-size: 20px; cursor: pointer;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" action="" style="padding: 25px;">
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Staff Member *</label>
                <select name="staff_id" required style="width: 100%; padding: 10px; border: 1px solid var(--gray-medium); border-radius: 5px;">
                    <option value="">Select Staff</option>
                    <?php foreach ($staffList as $staff): ?>
                    <option value="<?php echo $staff['user_id']; ?>">
                        <?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name'] . ' (' . ucfirst($staff['role']) . ')'); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Work Date *</label>
                <input type="date" name="work_date" required style="width: 100%; padding: 10px; border: 1px solid var(--gray-medium); border-radius: 5px;">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Shift Start</label>
                    <input type="time" name="shift_start" style="width: 100%; padding: 10px; border: 1px solid var(--gray-medium); border-radius: 5px;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Shift End</label>
                    <input type="time" name="shift_end" style="width: 100%; padding: 10px; border: 1px solid var(--gray-medium); border-radius: 5px;">
                </div>
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Assigned Role</label>
                <input type="text" name="role" placeholder="e.g., Reception, Housekeeping" style="width: 100%; padding: 10px; border: 1px solid var(--gray-medium); border-radius: 5px;">
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Notes</label>
                <textarea name="notes" rows="3" placeholder="Additional notes..." style="width: 100%; padding: 10px; border: 1px solid var(--gray-medium); border-radius: 5px;"></textarea>
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" onclick="closeAddModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="add_schedule" class="btn btn-primary">Add Schedule</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('addModal').style.display = 'flex';
}

function closeAddModal() {
    document.getElementById('addModal').style.display = 'none';
}

// Close modal when clicking outside
document.getElementById('addModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeAddModal();
    }
});
</script>

<?php require_once '../includes/admin-footer.php'; ?>
