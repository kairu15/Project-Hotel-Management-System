<?php
/**
 * Active Staff - Bayawan Bai Hotel
 * Monitor online/active staff members
 */
$pageTitle = 'Active Staff';
require_once '../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    showAlert('Access denied. Admin privileges required.', 'danger');
    redirect('../index.php');
}

$db = getDB();
$userId = getUserId();
$userRole = getUserRole();

// Handle add schedule form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_schedule'])) {
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
    redirect('admin-active.php');
}

// Get staff list for dropdown
$staffList = $db->query("SELECT user_id, first_name, last_name, role FROM users WHERE role IN ('admin', 'manager', 'receptionist', 'staff') ORDER BY first_name")
    ->fetchAll();

// Update active_status based on last_login (1 = active if online in last 15 min, 0 = offline)
$onlineThreshold = date('Y-m-d H:i:s', strtotime('-15 minutes'));
$db->query("UPDATE users SET active_status = CASE WHEN last_login >= '$onlineThreshold' THEN 1 ELSE 0 END WHERE role IN ('admin', 'manager', 'receptionist', 'staff')");

$today = date('Y-m-d');

$staffQuery = $db->query("
    SELECT 
        u.user_id,
        u.first_name,
        u.last_name,
        u.email,
        u.role,
        u.last_login,
        u.phone,
        CASE 
            WHEN u.last_login >= '$onlineThreshold' THEN 'online'
            WHEN u.last_login >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 'recent'
            ELSE 'offline'
        END as status,
        ss.shift_start,
        ss.shift_end,
        ss.status as schedule_status
    FROM users u
    LEFT JOIN staff_schedules ss ON u.user_id = ss.user_id 
        AND ss.work_date = '$today'
    WHERE u.role IN ('admin', 'manager', 'receptionist', 'staff')
    ORDER BY 
        CASE 
            WHEN u.last_login >= '$onlineThreshold' THEN 1
            WHEN u.last_login >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 2
            ELSE 3
        END,
        u.first_name
");

$staffList = $staffQuery->fetchAll();

// Calculate statistics
$stats = [
    'online' => 0,
    'recent' => 0,
    'offline' => 0,
    'on_duty' => 0
];

foreach ($staffList as $staff) {
    if ($staff['status'] === 'online') {
        $stats['online']++;
    } elseif ($staff['status'] === 'recent') {
        $stats['recent']++;
    } else {
        $stats['offline']++;
    }
    
    if ($staff['schedule_status'] === 'scheduled') {
        $stats['on_duty']++;
    }
}

require_once '../includes/admin-header.php';
?>

<section style="padding: 30px; background-color: var(--gray-light); min-height: calc(100vh - 250px);">
    <div class="container">
        <!-- Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h2>Active Staff Monitor</h2>
            <div style="display: flex; gap: 15px;">
                <button onclick="openAddModal()" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Schedule
                </button>
                <a href="admin-tasks.php" class="btn btn-outline">
                    <i class="fas fa-tasks"></i> Manage Schedules
                </a>
                <a href="admin-dashboard.php" class="btn btn-outline">Back to Dashboard</a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <div style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #28a745, #20c997); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-circle" style="color: white; font-size: 20px;"></i>
                    </div>
                    <div>
                        <p style="color: #666; font-size: 13px; margin: 0;">Online Now</p>
                        <h3 style="margin: 5px 0 0 0; font-size: 24px;"><?php echo $stats['online']; ?></h3>
                    </div>
                </div>
            </div>
            <div style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #17a2b8, #138496); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-clock" style="color: white; font-size: 20px;"></i>
                    </div>
                    <div>
                        <p style="color: #666; font-size: 13px; margin: 0;">Recent (24h)</p>
                        <h3 style="margin: 5px 0 0 0; font-size: 24px;"><?php echo $stats['recent']; ?></h3>
                    </div>
                </div>
            </div>
            <div style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #ffc107, #ff9800); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-calendar-check" style="color: white; font-size: 20px;"></i>
                    </div>
                    <div>
                        <p style="color: #666; font-size: 13px; margin: 0;">On Duty Today</p>
                        <h3 style="margin: 5px 0 0 0; font-size: 24px;"><?php echo $stats['on_duty']; ?></h3>
                    </div>
                </div>
            </div>
            <div style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="width: 50px; height: 50px; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-users" style="color: white; font-size: 20px;"></i>
                    </div>
                    <div>
                        <p style="color: #666; font-size: 13px; margin: 0;">Total Staff</p>
                        <h3 style="margin: 5px 0 0 0; font-size: 24px;"><?php echo count($staffList); ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Legend -->
        <div style="background: white; padding: 15px 25px; border-radius: 10px; margin-bottom: 20px; display: flex; gap: 30px; align-items: center;">
            <span style="font-size: 13px; color: #666;">Status Legend:</span>
            <span style="display: flex; align-items: center; gap: 8px; font-size: 13px;">
                <span style="width: 10px; height: 10px; background: #28a745; border-radius: 50%;"></span>
                Online (active in last 15 min)
            </span>
            <span style="display: flex; align-items: center; gap: 8px; font-size: 13px;">
                <span style="width: 10px; height: 10px; background: #17a2b8; border-radius: 50%;"></span>
                Recent (active in last 24h)
            </span>
            <span style="display: flex; align-items: center; gap: 8px; font-size: 13px;">
                <span style="width: 10px; height: 10px; background: #6c757d; border-radius: 50%;"></span>
                Offline (>24h)
            </span>
        </div>

        <!-- Staff Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
            <?php foreach ($staffList as $staff): 
                $statusColors = [
                    'online' => '#28a745',
                    'recent' => '#17a2b8',
                    'offline' => '#6c757d'
                ];
                $statusLabels = [
                    'online' => 'Online',
                    'recent' => 'Recent',
                    'offline' => 'Offline'
                ];
            ?>
            <div style="background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); padding: 25px; position: relative;">
                <!-- Status Indicator -->
                <div style="position: absolute; top: 15px; right: 15px; display: flex; align-items: center; gap: 8px; background: <?php echo $statusColors[$staff['status']] . '20'; ?>; padding: 5px 12px; border-radius: 20px;">
                    <span style="width: 8px; height: 8px; background: <?php echo $statusColors[$staff['status']]; ?>; border-radius: 50%;"></span>
                    <span style="font-size: 12px; color: <?php echo $statusColors[$staff['status']]; ?>; font-weight: 600;">
                        <?php echo $statusLabels[$staff['status']]; ?>
                    </span>
                </div>

                <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
                    <div style="width: 60px; height: 60px; min-width: 60px; min-height: 60px; background: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 20px; font-weight: 600; flex-shrink: 0; aspect-ratio: 1;">
                        <?php echo strtoupper(substr($staff['first_name'], 0, 1) . substr($staff['last_name'], 0, 1)); ?>
                    </div>
                    <div>
                        <h4 style="margin: 0 0 5px 0;"><?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?></h4>
                        <span style="background: var(--gray-light); padding: 3px 10px; border-radius: 15px; font-size: 12px; text-transform: capitalize;">
                            <?php echo $staff['role']; ?>
                        </span>
                    </div>
                </div>

                <div style="border-top: 1px solid var(--gray-light); padding-top: 15px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                        <div>
                            <p style="font-size: 11px; color: #999; margin: 0 0 3px 0;">Last Active</p>
                            <p style="font-size: 13px; color: #333; margin: 0;">
                                <?php 
                                if ($staff['last_login']) {
                                    $lastLogin = strtotime($staff['last_login']);
                                    $now = time();
                                    $diff = $now - $lastLogin;
                                    
                                    if ($diff < 60) {
                                        echo 'Just now';
                                    } elseif ($diff < 3600) {
                                        echo floor($diff / 60) . ' min ago';
                                    } elseif ($diff < 86400) {
                                        echo floor($diff / 3600) . ' hours ago';
                                    } else {
                                        echo date('M d, H:i', $lastLogin);
                                    }
                                } else {
                                    echo 'Never';
                                }
                                ?>
                            </p>
                        </div>
                        <div>
                            <p style="font-size: 11px; color: #999; margin: 0 0 3px 0;">Today's Shift</p>
                            <p style="font-size: 13px; color: #333; margin: 0;">
                                <?php 
                                if ($staff['shift_start'] && $staff['shift_end']) {
                                    echo date('h:i A', strtotime($staff['shift_start'])) . ' - ' . date('h:i A', strtotime($staff['shift_end']));
                                } else {
                                    echo '<span style="color: #999;">Not scheduled</span>';
                                }
                                ?>
                            </p>
                        </div>
                    </div>

                    <?php if ($staff['phone']): ?>
                    <div style="margin-bottom: 10px;">
                        <p style="font-size: 11px; color: #999; margin: 0 0 3px 0;">Phone</p>
                        <p style="font-size: 13px; color: #333; margin: 0;">
                            <i class="fas fa-phone" style="color: var(--primary-color); margin-right: 5px;"></i>
                            <?php echo htmlspecialchars($staff['phone']); ?>
                        </p>
                    </div>
                    <?php endif; ?>

                    <div>
                        <p style="font-size: 11px; color: #999; margin: 0 0 3px 0;">Email</p>
                        <p style="font-size: 13px; color: #333; margin: 0; word-break: break-all;">
                            <i class="fas fa-envelope" style="color: var(--primary-color); margin-right: 5px;"></i>
                            <?php echo htmlspecialchars($staff['email']); ?>
                        </p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($staffList)): ?>
        <div style="background: white; padding: 60px; border-radius: 10px; text-align: center;">
            <i class="fas fa-users-slash" style="font-size: 48px; color: var(--gray-medium); margin-bottom: 15px;"></i>
            <h3 style="color: #666; margin-bottom: 10px;">No Staff Found</h3>
            <p style="color: #999;">There are no staff members in the system.</p>
        </div>
        <?php endif; ?>
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

// Auto-refresh every 2 minutes to update status
setInterval(function() {
    location.reload();
}, 120000);
</script>

<?php require_once '../includes/admin-footer.php'; ?>
