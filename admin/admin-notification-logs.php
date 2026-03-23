<?php
$pageTitle = 'Notification Logs - Admin';
require_once '../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    showAlert('Access denied. Admin privileges required.', 'danger');
    redirect('../index.php');
}

$db = getDB();

// Handle delete log
if (isset($_POST['delete_log'])) {
    $logId = $_POST['log_id'] ?? 0;
    if ($logId) {
        $stmt = $db->prepare("DELETE FROM notification_logs WHERE log_id = ?");
        if ($stmt->execute([$logId])) {
            $_SESSION['success'] = 'Log entry deleted successfully';
        } else {
            $_SESSION['error'] = 'Failed to delete log entry';
        }
    }
    redirect('admin-notification-logs.php');
}

// Handle cleanup old logs
if (isset($_POST['cleanup_logs'])) {
    $stmt = $db->prepare("DELETE FROM notification_logs WHERE sent_at < DATE_SUB(NOW(), INTERVAL 90 DAY)");
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Old logs cleaned up successfully';
    } else {
        $_SESSION['error'] = 'Failed to clean up logs';
    }
    redirect('admin-notification-logs.php');
}

// Get filter parameters
$typeFilter = $_GET['type'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$sql = "
    SELECT nl.*, u.first_name, u.last_name, u.email
    FROM notification_logs nl
    LEFT JOIN users u ON nl.user_id = u.user_id
    WHERE 1=1
";
$params = [];

if ($typeFilter) {
    $sql .= " AND nl.type = ?";
    $params[] = $typeFilter;
}

if ($statusFilter) {
    $sql .= " AND nl.status = ?";
    $params[] = $statusFilter;
}

if ($search) {
    $sql .= " AND (nl.subject LIKE ? OR nl.content LIKE ? OR u.email LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$sql .= " ORDER BY nl.sent_at DESC LIMIT 500";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Get stats
$totalLogs = count($logs);
$sentCount = count(array_filter($logs, function($l) { return $l['status'] === 'sent'; }));
$failedCount = count(array_filter($logs, function($l) { return $l['status'] === 'failed'; }));
$pendingCount = count(array_filter($logs, function($l) { return $l['status'] === 'pending'; }));

// Get counts by type
$typeCounts = $db->query("SELECT type, COUNT(*) as count FROM notification_logs GROUP BY type")->fetchAll(PDO::FETCH_KEY_PAIR);

require_once '../includes/admin-header.php';
?>

<section style="padding: 30px 0; background-color: var(--gray-light); min-height: calc(100vh - 250px);">
    <div class="container">
        <div style="display: flex; justify-content: flex-end; gap: 15px; margin-bottom: 20px;">
            <a href="admin-dashboard.php" class="btn btn-outline">Back to Dashboard</a>
            <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Delete logs older than 90 days?');">
                <button type="submit" name="cleanup_logs" class="btn btn-danger">Clean Up Old Logs</button>
            </form>
        </div>

        <!-- Stats Cards -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 30px;">
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--primary-color);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="font-size: 28px; margin: 0;"><?php echo $totalLogs; ?></h3>
                        <p style="color: #666; margin: 5px 0 0;">Total Logs</p>
                    </div>
                    <i class="fas fa-envelope" style="font-size: 32px; color: var(--primary-color);"></i>
                </div>
            </div>
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--success-color);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="font-size: 28px; margin: 0;"><?php echo $sentCount; ?></h3>
                        <p style="color: #666; margin: 5px 0 0;">Sent</p>
                    </div>
                    <i class="fas fa-check-circle" style="font-size: 32px; color: var(--success-color);"></i>
                </div>
            </div>
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--danger-color);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="font-size: 28px; margin: 0;"><?php echo $failedCount; ?></h3>
                        <p style="color: #666; margin: 5px 0 0;">Failed</p>
                    </div>
                    <i class="fas fa-times-circle" style="font-size: 32px; color: var(--danger-color);"></i>
                </div>
            </div>
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--warning-color);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="font-size: 28px; margin: 0;"><?php echo $pendingCount; ?></h3>
                        <p style="color: #666; margin: 5px 0 0;">Pending</p>
                    </div>
                    <i class="fas fa-clock" style="font-size: 32px; color: var(--warning-color);"></i>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div style="background-color: white; padding: 20px 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
            <form method="GET" action="" style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 15px; align-items: end;">
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by subject, content, email..." style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Type</label>
                    <select name="type" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                        <option value="">All Types</option>
                        <option value="email" <?php echo $typeFilter === 'email' ? 'selected' : ''; ?>>Email</option>
                        <option value="sms" <?php echo $typeFilter === 'sms' ? 'selected' : ''; ?>>SMS</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Status</label>
                    <select name="status" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                        <option value="">All Status</option>
                        <option value="sent" <?php echo $statusFilter === 'sent' ? 'selected' : ''; ?>>Sent</option>
                        <option value="failed" <?php echo $statusFilter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                        <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    </select>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">Filter</button>
                    <a href="admin-notification-logs.php" class="btn btn-secondary" style="padding: 10px 20px;">Reset</a>
                </div>
            </form>
        </div>

        <!-- Logs Table -->
        <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden;">
            <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 20px; margin: 0;">Notification Logs (<?php echo count($logs); ?>)</h3>
            </div>

            <?php if (count($logs) > 0): ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color: var(--gray-light);">
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Log ID</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Type</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Recipient</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Subject</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Sent At</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Status</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log):
                        $statusColors = [
                            'sent' => ['#d4edda', '#155724'],
                            'failed' => ['#f8d7da', '#721c24'],
                            'pending' => ['#fff3cd', '#856404']
                        ];
                        $color = $statusColors[$log['status']] ?? ['#e2e3e5', '#383d41'];
                        ?>
                        <tr style="border-bottom: 1px solid var(--gray-light);">
                            <td style="padding: 15px 20px;">
                                <span style="font-family: monospace; font-size: 12px;">#<?php echo $log['log_id']; ?></span>
                            </td>
                            <td style="padding: 15px 20px;">
                                <span style="text-transform: uppercase; font-size: 12px; padding: 4px 8px; border-radius: 4px; background-color: #e3f2fd; color: #1976d2;">
                                    <i class="fas fa-<?php echo $log['type'] === 'email' ? 'envelope' : 'comment'; ?>"></i> <?php echo $log['type']; ?>
                                </span>
                            </td>
                            <td style="padding: 15px 20px;">
                                <?php if ($log['user_id']): ?>
                                    <div style="font-weight: 500;"><?php echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name']); ?></div>
                                    <div style="font-size: 12px; color: #666;"><?php echo htmlspecialchars($log['email']); ?></div>
                                <?php else: ?>
                                    <span style="color: #999;">System/General</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 15px 20px;">
                                <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?php echo htmlspecialchars($log['subject'] ?: 'No subject'); ?>
                                </div>
                            </td>
                            <td style="padding: 15px 20px;">
                                <?php if ($log['sent_at']): ?>
                                    <?php echo formatDate($log['sent_at'], 'M d, Y'); ?>
                                    <div style="font-size: 11px; color: #666;"><?php echo date('g:i A', strtotime($log['sent_at'])); ?></div>
                                <?php else: ?>
                                    <span style="color: #999;">Not sent yet</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 15px 20px;">
                                <span style="padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: <?php echo $color[0]; ?>; color: <?php echo $color[1]; ?>; text-transform: capitalize;">
                                    <?php echo $log['status']; ?>
                                </span>
                                <?php if ($log['status'] === 'failed' && $log['error_message']): ?>
                                    <div style="font-size: 11px; color: #dc3545; max-width: 150px; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($log['error_message']); ?>">
                                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars(substr($log['error_message'], 0, 30)); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 15px 20px;">
                                <div style="display: flex; gap: 10px;">
                                    <button type="button" onclick="viewContent(<?php echo htmlspecialchars(json_encode($log)); ?>)" class="btn btn-sm btn-primary" style="padding: 5px 12px; font-size: 12px;">View</button>
                                    <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Delete this log entry?');">
                                        <input type="hidden" name="log_id" value="<?php echo $log['log_id']; ?>">
                                        <button type="submit" name="delete_log" class="btn btn-sm btn-danger" style="padding: 5px 12px; font-size: 12px;"><i class="fas fa-trash"></i></button>
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
                <i class="fas fa-envelope" style="font-size: 48px; color: var(--gray-light); margin-bottom: 20px;"></i>
                <h3 style="color: #666;">No notification logs found</h3>
                <p style="color: #999;">Try adjusting your search filters</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- View Content Modal -->
<div id="contentModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background-color: white; border-radius: 10px; width: 90%; max-width: 700px; max-height: 80vh; overflow-y: auto;">
        <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 20px; margin: 0;">Message Content</h3>
            <button onclick="document.getElementById('contentModal').style.display='none'" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        <div style="padding: 30px;">
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px; color: #666;">Subject</label>
                <div id="modalSubject" style="padding: 12px; background-color: var(--gray-light); border-radius: 5px; font-size: 14px;"></div>
            </div>
            <div>
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px; color: #666;">Content</label>
                <div id="modalContent" style="padding: 15px; background-color: var(--gray-light); border-radius: 5px; font-size: 14px; max-height: 300px; overflow-y: auto; white-space: pre-wrap;"></div>
            </div>
        </div>
    </div>
</div>

<script>
function viewContent(log) {
    document.getElementById('contentModal').style.display = 'flex';
    document.getElementById('modalSubject').textContent = log.subject || 'No subject';
    document.getElementById('modalContent').textContent = log.content || 'No content';
}

document.getElementById('contentModal').addEventListener('click', function(e) {
    if (e.target === this) {
        this.style.display = 'none';
    }
});
</script>

<?php require_once '../includes/admin-footer.php'; ?>
