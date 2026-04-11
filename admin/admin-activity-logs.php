<?php
/**
 * Activity Logs - Bayawan Bai Hotel
 * View all system activity logs
 */
$pageTitle = 'Activity Logs';
require_once '../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    showAlert('Access denied. Admin privileges required.', 'danger');
    redirect('../index.php');
}

$logFile = '../includes/logs/activity.log';
$logs = [];

// Read and parse log file
if (file_exists($logFile) && is_readable($logFile)) {
    $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $lines = array_reverse($lines); // Show newest first
    
    foreach ($lines as $line) {
        // Parse log format: [2026-03-17 10:56:02] [User: 4] User login - User ID: 4
        if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] \[User: ([^\]]+)\] (.+)/', $line, $matches)) {
            $logs[] = [
                'datetime' => $matches[1],
                'user' => $matches[2],
                'action' => $matches[3]
            ];
        }
    }
}

// Get filter parameters
$search = $_GET['search'] ?? '';
$userFilter = $_GET['user'] ?? '';
$dateFilter = $_GET['date'] ?? '';

// Filter logs
$filteredLogs = $logs;
if ($search) {
    $filteredLogs = array_filter($filteredLogs, function($log) use ($search) {
        return stripos($log['action'], $search) !== false;
    });
}
if ($userFilter) {
    $filteredLogs = array_filter($filteredLogs, function($log) use ($userFilter) {
        return $log['user'] === $userFilter;
    });
}
if ($dateFilter) {
    $filteredLogs = array_filter($filteredLogs, function($log) use ($dateFilter) {
        return stripos($log['datetime'], $dateFilter) !== false;
    });
}

// Get unique users for filter dropdown
$uniqueUsers = array_unique(array_column($logs, 'user'));
sort($uniqueUsers);

// Pagination
$perPage = 50;
$totalLogs = count($filteredLogs);
$totalPages = ceil($totalPages / $perPage);
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$totalPages = max(1, ceil($totalLogs / $perPage));
$currentPage = min($currentPage, $totalPages);
$offset = ($currentPage - 1) * $perPage;
$paginatedLogs = array_slice($filteredLogs, $offset, $perPage);

require_once '../includes/admin-header.php';
?>

<section style="padding: 30px; background-color: var(--gray-light); min-height: calc(100vh - 250px);">
    <div class="container">
        <!-- Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h2>Activity Logs</h2>
            <a href="admin-operations.php" class="btn btn-outline">Back to Operations</a>
        </div>

        <!-- Statistics -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <div style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="width: 50px; height: 50px; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-list" style="color: white; font-size: 20px;"></i>
                    </div>
                    <div>
                        <p style="color: #666; font-size: 13px; margin: 0;">Total Logs</p>
                        <h3 style="margin: 5px 0 0 0; font-size: 24px;"><?php echo count($logs); ?></h3>
                    </div>
                </div>
            </div>
            <div style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #28a745, #20c997); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-filter" style="color: white; font-size: 20px;"></i>
                    </div>
                    <div>
                        <p style="color: #666; font-size: 13px; margin: 0;">Filtered</p>
                        <h3 style="margin: 5px 0 0 0; font-size: 24px;"><?php echo $totalLogs; ?></h3>
                    </div>
                </div>
            </div>
            <div style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #ffc107, #ff9800); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-users" style="color: white; font-size: 20px;"></i>
                    </div>
                    <div>
                        <p style="color: #666; font-size: 13px; margin: 0;">Unique Users</p>
                        <h3 style="margin: 5px 0 0 0; font-size: 24px;"><?php echo count($uniqueUsers); ?></h3>
                    </div>
                </div>
            </div>
            <div style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #17a2b8, #138496); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-file-alt" style="color: white; font-size: 20px;"></i>
                    </div>
                    <div>
                        <p style="color: #666; font-size: 13px; margin: 0;">File Size</p>
                        <h3 style="margin: 5px 0 0 0; font-size: 24px;"><?php echo file_exists($logFile) ? round(filesize($logFile) / 1024, 1) : 0; ?> KB</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div style="background-color: white; padding: 20px 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
            <form method="GET" action="" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end;">
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Search Action</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search activities..." style="width: 100%; padding: 10px; border: 1px solid var(--gray-medium); border-radius: 5px;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">User</label>
                    <select name="user" style="width: 100%; padding: 10px; border: 1px solid var(--gray-medium); border-radius: 5px;">
                        <option value="">All Users</option>
                        <?php foreach ($uniqueUsers as $user): ?>
                        <option value="<?php echo $user; ?>" <?php echo $userFilter === $user ? 'selected' : ''; ?>>
                            <?php echo $user === 'guest' ? 'Guest' : 'User #' . htmlspecialchars($user); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Date</label>
                    <input type="date" name="date" value="<?php echo htmlspecialchars($dateFilter); ?>" style="width: 100%; padding: 10px; border: 1px solid var(--gray-medium); border-radius: 5px;">
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">Filter</button>
                    <a href="admin-activity-logs.php" class="btn btn-secondary" style="padding: 10px 20px;">Reset</a>
                </div>
            </form>
        </div>

        <!-- Logs Table -->
        <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background-color: var(--dark-color); color: white;">
                        <th style="padding: 15px; text-align: left;">Date & Time</th>
                        <th style="padding: 15px; text-align: left;">User</th>
                        <th style="padding: 15px; text-align: left;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($paginatedLogs)): ?>
                    <tr>
                        <td colspan="3" style="padding: 40px; text-align: center; color: #666;">
                            <i class="fas fa-inbox" style="font-size: 48px; color: var(--gray-medium); margin-bottom: 15px; display: block;"></i>
                            No activity logs found
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($paginatedLogs as $log): 
                        $dateObj = new DateTime($log['datetime']);
                        $formattedDate = $dateObj->format('M d, Y');
                        $formattedTime = $dateObj->format('h:i A');
                        
                        // Determine icon based on action
                        $icon = 'fa-info-circle';
                        $iconColor = '#17a2b8';
                        if (stripos($log['action'], 'login') !== false) {
                            $icon = 'fa-sign-in-alt';
                            $iconColor = '#28a745';
                        } elseif (stripos($log['action'], 'logout') !== false) {
                            $icon = 'fa-sign-out-alt';
                            $iconColor = '#dc3545';
                        } elseif (stripos($log['action'], 'payment') !== false) {
                            $icon = 'fa-credit-card';
                            $iconColor = '#ffc107';
                        } elseif (stripos($log['action'], 'booking') !== false) {
                            $icon = 'fa-calendar-check';
                            $iconColor = '#6f42c1';
                        } elseif (stripos($log['action'], 'registration') !== false || stripos($log['action'], 'register') !== false) {
                            $icon = 'fa-user-plus';
                            $iconColor = '#17a2b8';
                        } elseif (stripos($log['action'], 'password') !== false) {
                            $icon = 'fa-key';
                            $iconColor = '#fd7e14';
                        } elseif (stripos($log['action'], 'profile') !== false) {
                            $icon = 'fa-user-edit';
                            $iconColor = '#20c997';
                        }
                    ?>
                    <tr style="border-bottom: 1px solid var(--gray-light);">
                        <td style="padding: 15px;">
                            <div style="display: flex; flex-direction: column;">
                                <span style="font-weight: 600;"><?php echo $formattedDate; ?></span>
                                <span style="font-size: 12px; color: #666;"><?php echo $formattedTime; ?></span>
                            </div>
                        </td>
                        <td style="padding: 15px;">
                            <?php if ($log['user'] === 'guest'): ?>
                                <span style="background: var(--gray-light); padding: 4px 12px; border-radius: 15px; font-size: 12px;">
                                    <i class="fas fa-user" style="margin-right: 5px; color: var(--primary-color);"></i>Guest
                                </span>
                            <?php else: ?>
                                <span style="background: #e3f2fd; padding: 4px 12px; border-radius: 15px; font-size: 12px;">
                                    <i class="fas fa-user" style="margin-right: 5px; color: #1976d2;"></i>User #<?php echo htmlspecialchars($log['user']); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 15px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <i class="fas <?php echo $icon; ?>" style="color: <?php echo $iconColor; ?>; font-size: 16px;"></i>
                                <span><?php echo htmlspecialchars($log['action']); ?></span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div style="display: flex; justify-content: center; margin-top: 30px; gap: 5px;">
            <?php if ($currentPage > 1): ?>
            <a href="?page=<?php echo $currentPage - 1; ?>&search=<?php echo urlencode($search); ?>&user=<?php echo urlencode($userFilter); ?>&date=<?php echo urlencode($dateFilter); ?>" 
               style="padding: 10px 15px; background: white; border-radius: 5px; text-decoration: none; color: var(--text-color); box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                <i class="fas fa-chevron-left"></i>
            </a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php if ($i == $currentPage): ?>
                <span style="padding: 10px 15px; background: var(--primary-color); color: white; border-radius: 5px;"><?php echo $i; ?></span>
                <?php else: ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&user=<?php echo urlencode($userFilter); ?>&date=<?php echo urlencode($dateFilter); ?>" 
                   style="padding: 10px 15px; background: white; border-radius: 5px; text-decoration: none; color: var(--text-color); box-shadow: 0 2px 5px rgba(0,0,0,0.1);"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($currentPage < $totalPages): ?>
            <a href="?page=<?php echo $currentPage + 1; ?>&search=<?php echo urlencode($search); ?>&user=<?php echo urlencode($userFilter); ?>date=<?php echo urlencode($dateFilter); ?>" 
               style="padding: 10px 15px; background: white; border-radius: 5px; text-decoration: none; color: var(--text-color); box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                <i class="fas fa-chevron-right"></i>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once '../includes/admin-footer.php'; ?>
