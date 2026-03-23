<?php
$pageTitle = 'Manage Maintenance Requests - Admin';
require_once '../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    showAlert('Access denied. Admin privileges required.', 'danger');
    redirect('../index.php');
}

$db = getDB();

// Handle update status
if (isset($_POST['update_status'])) {
    $requestId = $_POST['request_id'] ?? 0;
    $status = $_POST['status'] ?? '';
    $priority = $_POST['priority'] ?? '';

    if ($requestId) {
        if ($status === 'completed') {
            $stmt = $db->prepare("UPDATE maintenance_requests SET status = ?, priority = ?, resolved_at = NOW() WHERE request_id = ?");
        } else {
            $stmt = $db->prepare("UPDATE maintenance_requests SET status = ?, priority = ?, resolved_at = NULL WHERE request_id = ?");
        }
        $stmt->execute([$status, $priority, $requestId]);
        $_SESSION['success'] = 'Maintenance request updated successfully';
    }
    redirect('admin-maintenance.php');
}

// Handle delete request
if (isset($_POST['delete_request'])) {
    $requestId = $_POST['request_id'] ?? 0;
    if ($requestId) {
        $stmt = $db->prepare("DELETE FROM maintenance_requests WHERE request_id = ?");
        if ($stmt->execute([$requestId])) {
            $_SESSION['success'] = 'Maintenance request deleted successfully';
        } else {
            $_SESSION['error'] = 'Failed to delete request';
        }
    }
    redirect('admin-maintenance.php');
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? '';
$priorityFilter = $_GET['priority'] ?? '';
$issueTypeFilter = $_GET['issue_type'] ?? '';

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

if ($issueTypeFilter) {
    $sql .= " AND mr.issue_type = ?";
    $params[] = $issueTypeFilter;
}

$sql .= " ORDER BY FIELD(mr.priority, 'urgent', 'high', 'medium', 'low'), mr.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

// Get counts
$statusCounts = $db->query("SELECT status, COUNT(*) as count FROM maintenance_requests GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);

// Issue types
$issueTypes = ['plumbing', 'electrical', 'hvac', 'furniture', 'appliance', 'other'];

require_once '../includes/admin-header.php';
?>

<section style="padding: 30px 0; background-color: var(--gray-light); min-height: calc(100vh - 250px);">
    <div class="container">
        <div style="display: flex; justify-content: flex-end; gap: 15px; margin-bottom: 20px;">
            <a href="admin-dashboard.php" class="btn btn-outline">Back to Dashboard</a>
            <a href="admin-rooms.php" class="btn btn-outline">Manage Rooms</a>
        </div>

        <!-- Status Cards -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 30px;">
            <?php
            $statuses = ['pending', 'in_progress', 'completed', 'cancelled'];
            $statusColors = [
                'pending' => ['#fff3cd', '#856404'],
                'in_progress' => ['#cce5ff', '#004085'],
                'completed' => ['#d4edda', '#155724'],
                'cancelled' => ['#f8d7da', '#721c24']
            ];
            $statusIcons = ['clock', 'tools', 'check-circle', 'times-circle'];
            foreach ($statuses as $index => $status):
                $count = $statusCounts[$status] ?? 0;
                $color = $statusColors[$status];
            ?>
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid <?php echo $color[1]; ?>;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="font-size: 28px; margin: 0;"><?php echo $count; ?></h3>
                        <p style="color: #666; margin: 5px 0 0; text-transform: capitalize;"><?php echo str_replace('_', ' ', $status); ?></p>
                    </div>
                    <i class="fas fa-<?php echo $statusIcons[$index]; ?>" style="font-size: 32px; color: <?php echo $color[1]; ?>"></i>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Filters -->
        <div style="background-color: white; padding: 20px 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
            <form method="GET" action="" style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 15px; align-items: end;">
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Status</label>
                    <select name="status" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="in_progress" <?php echo $statusFilter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Priority</label>
                    <select name="priority" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                        <option value="">All Priorities</option>
                        <option value="urgent" <?php echo $priorityFilter === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                        <option value="high" <?php echo $priorityFilter === 'high' ? 'selected' : ''; ?>>High</option>
                        <option value="medium" <?php echo $priorityFilter === 'medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="low" <?php echo $priorityFilter === 'low' ? 'selected' : ''; ?>>Low</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Issue Type</label>
                    <select name="issue_type" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                        <option value="">All Types</option>
                        <?php foreach ($issueTypes as $type): ?>
                        <option value="<?php echo $type; ?>" <?php echo $issueTypeFilter === $type ? 'selected' : ''; ?>><?php echo ucfirst($type); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">Filter</button>
                    <a href="admin-maintenance.php" class="btn btn-secondary" style="padding: 10px 20px;">Reset</a>
                </div>
            </form>
        </div>

        <!-- Requests Table -->
        <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden;">
            <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 20px; margin: 0;">Maintenance Requests (<?php echo count($requests); ?>)</h3>
            </div>

            <?php if (count($requests) > 0): ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color: var(--gray-light);">
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Room</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Issue Type</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Description</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Priority</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Status</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Reported</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $request):
                        $priorityColors = [
                            'urgent' => ['#dc3545', '#fff'],
                            'high' => ['#fd7e14', '#fff'],
                            'medium' => ['#ffc107', '#856404'],
                            'low' => ['#6c757d', '#fff']
                        ];
                        $priorityColor = $priorityColors[$request['priority']] ?? ['#6c757d', '#fff'];
                        $statusColor = $statusColors[$request['status']] ?? ['#e2e3e5', '#383d41'];
                        ?>
                        <tr style="border-bottom: 1px solid var(--gray-light);">
                            <td style="padding: 15px 20px;">
                                <?php if ($request['room_number']): ?>
                                <span style="font-weight: 600;">Room <?php echo htmlspecialchars($request['room_number']); ?></span>
                                <?php else: ?>
                                <span style="color: #999;">General</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 15px 20px;">
                                <span style="text-transform: capitalize;"><?php echo htmlspecialchars($request['issue_type']); ?></span>
                            </td>
                            <td style="padding: 15px 20px;">
                                <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?php echo htmlspecialchars($request['description']); ?>
                                </div>
                            </td>
                            <td style="padding: 15px 20px;">
                                <span style="padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: <?php echo $priorityColor[0]; ?>; color: <?php echo $priorityColor[1]; ?>; text-transform: capitalize;">
                                    <?php echo $request['priority']; ?>
                                </span>
                            </td>
                            <td style="padding: 15px 20px;">
                                <span style="padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: <?php echo $statusColor[0]; ?>; color: <?php echo $statusColor[1]; ?>; text-transform: capitalize;">
                                    <?php echo str_replace('_', ' ', $request['status']); ?>
                                </span>
                            </td>
                            <td style="padding: 15px 20px;">
                                <div style="font-size: 13px;"><?php echo formatDate($request['created_at'], 'M d, Y'); ?></div>
                                <div style="font-size: 11px; color: #666;">by <?php echo htmlspecialchars(($request['first_name'] ?: 'System') . ' ' . ($request['last_name'] ?: '')); ?></div>
                                <?php if ($request['resolved_at']): ?>
                                <div style="font-size: 11px; color: var(--success-color);"><i class="fas fa-check"></i> Resolved: <?php echo formatDate($request['resolved_at'], 'M d'); ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 15px 20px;">
                                <div style="display: flex; gap: 10px;">
                                    <button type="button" onclick="editRequest(<?php echo htmlspecialchars(json_encode($request)); ?>)" class="btn btn-sm btn-primary" style="padding: 5px 12px; font-size: 12px;">Update</button>
                                    <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this request?');">
                                        <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                        <button type="submit" name="delete_request" class="btn btn-sm btn-danger" style="padding: 5px 12px; font-size: 12px;"><i class="fas fa-trash"></i></button>
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
                <i class="fas fa-tools" style="font-size: 48px; color: var(--gray-light); margin-bottom: 20px;"></i>
                <h3 style="color: #666;">No maintenance requests found</h3>
                <p style="color: #999;">All systems are running smoothly</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Request Modal -->
<div id="requestModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background-color: white; border-radius: 10px; width: 90%; max-width: 500px;">
        <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 20px; margin: 0;">Update Maintenance Request</h3>
            <button onclick="closeRequestModal()" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" action="" style="padding: 30px;">
            <input type="hidden" name="request_id" id="request_id">

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Status</label>
                <select name="status" id="request_status" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>

            <div style="margin-bottom: 25px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Priority</label>
                <select name="priority" id="request_priority" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                    <option value="urgent">Urgent</option>
                </select>
            </div>

            <div style="display: flex; gap: 15px; justify-content: flex-end;">
                <button type="button" onclick="closeRequestModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="update_status" class="btn btn-primary">Update Request</button>
            </div>
        </form>
    </div>
</div>

<script>
function editRequest(request) {
    document.getElementById('requestModal').style.display = 'flex';
    document.getElementById('request_id').value = request.request_id;
    document.getElementById('request_status').value = request.status;
    document.getElementById('request_priority').value = request.priority;
}

function closeRequestModal() {
    document.getElementById('requestModal').style.display = 'none';
}

document.getElementById('requestModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeRequestModal();
    }
});
</script>

<?php require_once '../includes/admin-footer.php'; ?>
