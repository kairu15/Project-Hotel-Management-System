<?php
$pageTitle = 'User Sessions - Admin';
require_once '../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    showAlert('Access denied. Admin privileges required.', 'danger');
    redirect('../index.php');
}

$db = getDB();

// Handle delete session
if (isset($_POST['delete_session'])) {
    $sessionId = $_POST['session_id'] ?? '';
    if ($sessionId) {
        // Get user info before deletion
        $userStmt = $db->prepare("SELECT u.first_name, u.last_name, u.email FROM user_sessions us JOIN users u ON us.user_id = u.user_id WHERE us.session_id = ?");
        $userStmt->execute([$sessionId]);
        $userData = $userStmt->fetch();
        $userName = $userData ? $userData['first_name'] . ' ' . $userData['last_name'] . ' (' . $userData['email'] . ')' : 'User';
        
        $stmt = $db->prepare("DELETE FROM user_sessions WHERE session_id = ?");
        if ($stmt->execute([$sessionId])) {
            $_SESSION['success'] = 'Session for ' . $userName . ' terminated successfully';
        } else {
            $_SESSION['error'] = 'Failed to terminate session';
        }
    }
    redirect('admin-user-sessions.php');
}

// Handle delete all expired sessions
if (isset($_POST['cleanup_sessions'])) {
    $stmt = $db->prepare("DELETE FROM user_sessions WHERE expires_at < NOW() OR expires_at IS NULL");
    if ($stmt->execute()) {
        $rowCount = $stmt->rowCount();
        $_SESSION['success'] = $rowCount . ' expired session' . ($rowCount !== 1 ? 's' : '') . ' cleaned up successfully';
    } else {
        $_SESSION['error'] = 'Failed to clean up sessions';
    }
    redirect('admin-user-sessions.php');
}

// Get filter parameters
$userFilter = $_GET['user'] ?? '';
$activeOnly = isset($_GET['active_only']) ? true : false;

// Build query
$sql = "
    SELECT us.*, u.first_name, u.last_name, u.email, u.role
    FROM user_sessions us
    JOIN users u ON us.user_id = u.user_id
    WHERE 1=1
";
$params = [];

if ($userFilter) {
    $sql .= " AND us.user_id = ?";
    $params[] = $userFilter;
}

if ($activeOnly) {
    $sql .= " AND (us.expires_at > NOW() OR us.expires_at IS NULL)";
}

$sql .= " ORDER BY us.created_at DESC LIMIT 500";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$sessions = $stmt->fetchAll();

// Get stats
$totalSessions = count($sessions);
$activeSessions = count(array_filter($sessions, function($s) {
    return $s['expires_at'] === null || strtotime($s['expires_at']) > time();
}));
$expiredSessions = $totalSessions - $activeSessions;

// Get users for filter
$users = $db->query("SELECT user_id, first_name, last_name, email FROM users ORDER BY first_name, last_name")->fetchAll();

require_once '../includes/admin-header.php';
?>

<section style="padding: 30px 0; background-color: var(--gray-light); min-height: calc(100vh - 250px);">
    <div class="container">
        <div style="display: flex; justify-content: flex-end; gap: 15px; margin-bottom: 20px;">
            <a href="admin-dashboard.php" class="btn btn-outline">Back to Dashboard</a>
            <form method="POST" action="" style="display: inline;" id="cleanupSessionsForm">
                <button type="button" onclick="openDeleteModal('cleanupSessionsForm', 'Clean Up Expired Sessions', 'Are you sure you want to clean up all expired sessions?', null, 'cleanup_sessions')" class="btn btn-danger">Clean Up Expired Sessions</button>
            </form>
        </div>

        <!-- Stats Cards -->
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 30px;">
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--primary-color);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="font-size: 28px; margin: 0;"><?php echo $totalSessions; ?></h3>
                        <p style="color: #666; margin: 5px 0 0;">Total Sessions (Last 500)</p>
                    </div>
                    <i class="fas fa-users" style="font-size: 32px; color: var(--primary-color);"></i>
                </div>
            </div>
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--success-color);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="font-size: 28px; margin: 0;"><?php echo $activeSessions; ?></h3>
                        <p style="color: #666; margin: 5px 0 0;">Active Sessions</p>
                    </div>
                    <i class="fas fa-user-check" style="font-size: 32px; color: var(--success-color);"></i>
                </div>
            </div>
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--warning-color);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="font-size: 28px; margin: 0;"><?php echo $expiredSessions; ?></h3>
                        <p style="color: #666; margin: 5px 0 0;">Expired Sessions</p>
                    </div>
                    <i class="fas fa-user-clock" style="font-size: 32px; color: var(--warning-color);"></i>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div style="background-color: white; padding: 20px 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
            <form method="GET" action="" style="display: flex; gap: 15px; align-items: end;">
                <div style="flex: 1;">
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">User</label>
                    <select name="user" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                        <option value="">All Users</option>
                        <?php foreach ($users as $u): ?>
                        <option value="<?php echo $u['user_id']; ?>" <?php echo $userFilter == $u['user_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name'] . ' (' . $u['email'] . ')'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="padding-bottom: 10px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="active_only" value="1" <?php echo $activeOnly ? 'checked' : ''; ?> style="width: 18px; height: 18px;">
                        <span style="font-size: 14px;">Active only</span>
                    </label>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">Filter</button>
                    <a href="admin-user-sessions.php" class="btn btn-secondary" style="padding: 10px 20px;">Reset</a>
                </div>
            </form>
        </div>

        <!-- Sessions Table -->
        <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden;">
            <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 20px; margin: 0;">User Sessions (<?php echo count($sessions); ?>)</h3>
            </div>

            <?php if (count($sessions) > 0): ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color: var(--gray-light);">
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">User</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Role</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">IP Address</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">User Agent</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Created</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Expires</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Status</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sessions as $session):
                        $isActive = $session['expires_at'] === null || strtotime($session['expires_at']) > time();
                        ?>
                        <tr style="border-bottom: 1px solid var(--gray-light);">
                            <td style="padding: 15px 20px;">
                                <div style="font-weight: 500;"><?php echo htmlspecialchars($session['first_name'] . ' ' . $session['last_name']); ?></div>
                                <div style="font-size: 12px; color: #666;"><?php echo htmlspecialchars($session['email']); ?></div>
                            </td>
                            <td style="padding: 15px 20px;">
                                <span style="padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: #e3f2fd; color: #1976d2; text-transform: capitalize;">
                                    <?php echo $session['role']; ?>
                                </span>
                            </td>
                            <td style="padding: 15px 20px; font-family: monospace; font-size: 13px;">
                                <?php echo htmlspecialchars($session['ip_address'] ?: 'N/A'); ?>
                            </td>
                            <td style="padding: 15px 20px;">
                                <div style="font-size: 12px; color: #666; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?php echo htmlspecialchars(substr($session['user_agent'] ?: 'N/A', 0, 50)); ?>
                                </div>
                            </td>
                            <td style="padding: 15px 20px; font-size: 13px;">
                                <?php echo formatDate($session['created_at'], 'M d, Y'); ?>
                                <div style="font-size: 11px; color: #666;"><?php echo date('g:i A', strtotime($session['created_at'])); ?></div>
                            </td>
                            <td style="padding: 15px 20px; font-size: 13px;">
                                <?php if ($session['expires_at']): ?>
                                    <?php echo formatDate($session['expires_at'], 'M d, Y'); ?>
                                    <div style="font-size: 11px; color: #666;"><?php echo date('g:i A', strtotime($session['expires_at'])); ?></div>
                                <?php else: ?>
                                    <span style="color: #999;">Session-based</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 15px 20px;">
                                <span style="padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: <?php echo $isActive ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $isActive ? '#155724' : '#721c24'; ?>;">
                                    <?php echo $isActive ? 'Active' : 'Expired'; ?>
                                </span>
                            </td>
                            <td style="padding: 15px 20px;">
                                <?php if ($isActive): ?>
                                <form method="POST" action="" style="display: inline;" id="terminateSessionForm<?php echo htmlspecialchars($session['session_id']); ?>">
                                    <input type="hidden" name="session_id" value="<?php echo htmlspecialchars($session['session_id']); ?>">
                                    <button type="button" onclick="openDeleteModal('terminateSessionForm<?php echo htmlspecialchars($session['session_id']); ?>', 'Terminate Session', 'Are you sure you want to terminate this session for <?php echo htmlspecialchars($session['first_name'] . ' ' . $session['last_name']); ?>?', null, 'delete_session')" class="btn btn-sm btn-danger" style="padding: 5px 12px; font-size: 12px;">
                                        <i class="fas fa-sign-out-alt"></i> Terminate
                                    </button>
                                </form>
                                <?php else: ?>
                                <span style="color: #999; font-size: 12px;">Already expired</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div style="padding: 60px; text-align: center;">
                <i class="fas fa-users" style="font-size: 48px; color: var(--gray-light); margin-bottom: 20px;"></i>
                <h3 style="color: #666;">No sessions found</h3>
                <p style="color: #999;">Try adjusting your filters</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once '../includes/admin-footer.php'; ?>
