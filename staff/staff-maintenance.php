<?php
$pageTitle = 'Maintenance Requests - Staff';
require_once '../includes/config.php';

// Check permission for maintenance page
checkStaffPermission('maintenance');

$db = getDB();

// Handle add maintenance request
if (isset($_POST['add_request'])) {
    $roomId = $_POST['room_id'] ?: null;
    $issueType = $_POST['issue_type'] ?? '';
    $description = $_POST['description'] ?? '';
    $priority = $_POST['priority'] ?? 'medium';
    $userId = getUserId();

    if ($issueType && $description) {
        $stmt = $db->prepare("INSERT INTO maintenance_requests (room_id, reported_by, issue_type, description, priority, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$roomId, $userId, $issueType, $description, $priority]);
        
        // Get room number for message
        if ($roomId) {
            $roomStmt = $db->prepare("SELECT room_number FROM rooms WHERE room_id = ?");
            $roomStmt->execute([$roomId]);
            $roomNumber = $roomStmt->fetchColumn();
            $_SESSION['success'] = $issueType . ' request for Room ' . ($roomNumber ?? 'N/A') . ' submitted successfully';
        } else {
            $_SESSION['success'] = $issueType . ' request submitted successfully';
        }
    } else {
        $_SESSION['error'] = 'Please fill in all required fields';
    }
    redirect('staff-maintenance.php');
}

// Handle update status
if (isset($_POST['update_status'])) {
    $requestId = $_POST['request_id'] ?? 0;
    $status = $_POST['status'] ?? '';

    if ($requestId) {
        // Get room number and issue type for message
        $reqStmt = $db->prepare("SELECT r.room_number, mr.issue_type FROM maintenance_requests mr LEFT JOIN rooms r ON mr.room_id = r.room_id WHERE mr.request_id = ?");
        $reqStmt->execute([$requestId]);
        $reqData = $reqStmt->fetch();
        $roomNumber = $reqData['room_number'] ?? 'N/A';
        $issueType = $reqData['issue_type'] ?? 'Maintenance';
        
        if ($status === 'completed') {
            $stmt = $db->prepare("UPDATE maintenance_requests SET status = ?, resolved_at = NOW() WHERE request_id = ?");
        } else {
            $stmt = $db->prepare("UPDATE maintenance_requests SET status = ?, resolved_at = NULL WHERE request_id = ?");
        }
        $stmt->execute([$status, $requestId]);
        $_SESSION['success'] = $issueType . ' request for Room ' . $roomNumber . ' updated to ' . ucfirst($status);
    }
    redirect('staff-maintenance.php');
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? '';
$priorityFilter = $_GET['priority'] ?? '';

// Build query
$sql = "
    SELECT mr.*, r.room_number, u.first_name, u.last_name
    FROM maintenance_requests mr
    LEFT JOIN rooms r ON mr.room_id = r.room_id
    LEFT JOIN users u ON mr.reported_by = u.user_id
    WHERE 1=1
";
$params = [];

if ($statusFilter) {
    $sql .= " AND mr.status = ?";
    $params[] = $statusFilter;
}

if ($priorityFilter) {
    $sql .= " AND mr.priority = ?";
    $params[] = $priorityFilter;
}

$sql .= " ORDER BY FIELD(mr.priority, 'urgent', 'high', 'medium', 'low'), mr.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

// Get rooms for dropdown
$rooms = $db->query("SELECT room_id, room_number FROM rooms ORDER BY room_number")->fetchAll();

// Issue types
$issueTypes = ['plumbing', 'electrical', 'hvac', 'furniture', 'appliance', 'other'];

require_once '../includes/staff-header.php';
?>

<section style="padding: 30px 0; background-color: var(--gray-light); min-height: calc(100vh - 250px);">
    <div class="container">
        <div style="display: flex; justify-content: flex-end; gap: 15px; margin-bottom: 20px;">
            <a href="staff-dashboard.php" class="btn btn-outline">Back to Dashboard</a>
            <button type="button" onclick="document.getElementById('requestModal').style.display='flex'" class="btn btn-primary">New Request</button>
        </div>

        <!-- Filters -->
        <div style="background-color: white; padding: 20px 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
            <form method="GET" action="" style="display: flex; gap: 15px; align-items: end;">
                <div style="flex: 1;">
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Status</label>
                    <select name="status" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="in_progress" <?php echo $statusFilter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div style="flex: 1;">
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Priority</label>
                    <select name="priority" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                        <option value="">All Priorities</option>
                        <option value="urgent" <?php echo $priorityFilter === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                        <option value="high" <?php echo $priorityFilter === 'high' ? 'selected' : ''; ?>>High</option>
                        <option value="medium" <?php echo $priorityFilter === 'medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="low" <?php echo $priorityFilter === 'low' ? 'selected' : ''; ?>>Low</option>
                    </select>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">Filter</button>
                    <a href="staff-maintenance.php" class="btn btn-secondary" style="padding: 10px 20px;">Reset</a>
                </div>
            </form>
        </div>

        <!-- Requests Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px;">
            <?php foreach ($requests as $request):
            $priorityColors = [
                'urgent' => ['#dc3545', '#fff'],
                'high' => ['#fd7e14', '#fff'],
                'medium' => ['#ffc107', '#856404'],
                'low' => ['#6c757d', '#fff']
            ];
            $priorityColor = $priorityColors[$request['priority']] ?? ['#6c757d', '#fff'];
            $statusColors = [
                'pending' => ['#fff3cd', '#856404'],
                'in_progress' => ['#cce5ff', '#004085'],
                'completed' => ['#d4edda', '#155724'],
                'cancelled' => ['#f8d7da', '#721c24']
            ];
            $statusColor = $statusColors[$request['status']] ?? ['#e2e3e5', '#383d41'];
            ?>
            <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); padding: 25px;">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                    <span style="padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: <?php echo $priorityColor[0]; ?>; color: <?php echo $priorityColor[1]; ?>; text-transform: uppercase;">
                        <?php echo $request['priority']; ?>
                    </span>
                    <span style="padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: <?php echo $statusColor[0]; ?>; color: <?php echo $statusColor[1]; ?>; text-transform: capitalize;">
                        <?php echo str_replace('_', ' ', $request['status']); ?>
                    </span>
                </div>

                <div style="margin-bottom: 15px;">
                    <div style="font-size: 12px; color: #666; margin-bottom: 5px;">
                        <i class="fas fa-door-open"></i> Room: <?php echo $request['room_number'] ?: 'General'; ?> | <i class="fas fa-wrench"></i> Type: <?php echo ucfirst($request['issue_type']); ?>
                    </div>
                    <p style="font-size: 14px; margin: 0;"><?php echo htmlspecialchars($request['description']); ?></p>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 15px; border-top: 1px solid var(--gray-light);">
                    <div style="font-size: 12px; color: #666;">
                        <div>By: <?php echo htmlspecialchars(($request['first_name'] ?: 'System') . ' ' . ($request['last_name'] ?: '')); ?></div>
                        <div><?php echo formatDate($request['created_at'], 'M d, Y g:i A'); ?></div>
                        <?php if ($request['resolved_at']): ?>
                        <div style="color: var(--success-color);"><i class="fas fa-check"></i> Resolved: <?php echo formatDate($request['resolved_at'], 'M d, Y'); ?></div>
                        <?php endif; ?>
                    </div>
                    <form method="POST" action="" style="display: flex; gap: 8px;">
                        <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                        <?php if ($request['status'] === 'in_progress'): ?>
                        <button type="submit" name="update_status" value="completed" class="btn btn-sm btn-success" style="padding: 5px 12px;">Complete</button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (count($requests) === 0): ?>
        <div style="text-align: center; padding: 60px;">
            <i class="fas fa-tools" style="font-size: 48px; color: var(--gray-light); margin-bottom: 20px;"></i>
            <h3 style="color: #666;">No maintenance requests found</h3>
            <p style="color: #999;">All systems are running smoothly</p>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Add Request Modal -->
<div id="requestModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background-color: white; border-radius: 10px; width: 90%; max-width: 500px;">
        <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 20px; margin: 0;">New Maintenance Request</h3>
            <button onclick="document.getElementById('requestModal').style.display='none'" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" action="" style="padding: 30px;">
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Room (Optional)</label>
                <select name="room_id" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                    <option value="">General / No specific room</option>
                    <?php foreach ($rooms as $room): ?>
                    <option value="<?php echo $room['room_id']; ?>">Room <?php echo htmlspecialchars($room['room_number']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Issue Type *</label>
                <select name="issue_type" required style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                    <option value="">Select Issue Type</option>
                    <?php foreach ($issueTypes as $type): ?>
                    <option value="<?php echo $type; ?>"><?php echo ucfirst($type); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Priority</label>
                <select name="priority" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                    <option value="low">Low</option>
                    <option value="medium" selected>Medium</option>
                    <option value="high">High</option>
                    <option value="urgent">Urgent</option>
                </select>
            </div>

            <div style="margin-bottom: 25px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Description *</label>
                <textarea name="description" required rows="4" placeholder="Describe the issue in detail..." style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;"></textarea>
            </div>

            <div style="display: flex; gap: 15px; justify-content: flex-end;">
                <button type="button" onclick="document.getElementById('requestModal').style.display='none'" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="add_request" class="btn btn-primary">Submit Request</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/staff-footer.php'; ?>
