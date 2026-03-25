<?php
/**
 * Admin Notifications Page - Bayawan Bai Hotel
 * Full notification listing with filtering, management, and admin controls
 */
$pageTitle = 'Notifications';
require_once '../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    showAlert('Access denied. Admin privileges required.', 'danger');
    redirect('../index.php');
}

require_once '../includes/admin-header.php';
require_once '../includes/notifications.php';

$userId = getUserId();
$db = getDB();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['mark_read'])) {
        $notificationId = intval($_POST['notification_id'] ?? 0);
        if ($notificationId) {
            markAsRead($notificationId, $userId);
        }
    } elseif (isset($_POST['mark_all_read'])) {
        markAllAsRead($userId);
    } elseif (isset($_POST['delete'])) {
        $notificationId = intval($_POST['notification_id'] ?? 0);
        if ($notificationId) {
            deleteNotification($notificationId, $userId);
        }
    } elseif (isset($_POST['cleanup'])) {
        $deleted = cleanupOldNotifications(30);
        $_SESSION['success'] = $deleted . ' old notifications cleaned up.';
    }
    
    redirect('admin-notifications.php');
}

// Get filter parameters
$filter = $_GET['filter'] ?? 'all';
$typeFilter = $_GET['type'] ?? '';
$currentPage = intval($_GET['page'] ?? 1);
$perPage = 20;
$offset = ($currentPage - 1) * $perPage;

// Build query for notifications
$sql = "SELECT * FROM notifications WHERE user_id = ?";
$params = [$userId];

if ($filter !== 'all') {
    $sql .= " AND status = ?";
    $params[] = $filter;
}

if ($typeFilter) {
    $sql .= " AND type = ?";
    $params[] = $typeFilter;
}

$sql .= " ORDER BY created_at DESC LIMIT " . (int) $perPage . " OFFSET " . (int) $offset;

$stmt = $db->prepare($sql);

// Bind parameters except LIMIT and OFFSET
$paramIndex = 1;
for ($i = 0; $i < count($params); $i++) {
    $stmt->bindValue($paramIndex++, $params[$i]);
}

$stmt->execute();
$notifications = $stmt->fetchAll();

// Get total count for pagination
$countSql = "SELECT COUNT(*) FROM notifications WHERE user_id = ?";
$countParams = [$userId];
if ($filter !== 'all') {
    $countSql .= " AND status = ?";
    $countParams[] = $filter;
}
if ($typeFilter) {
    $countSql .= " AND type = ?";
    $countParams[] = $typeFilter;
}
$countStmt = $db->prepare($countSql);
$countStmt->execute($countParams);
$totalNotifications = $countStmt->fetchColumn();
$totalPages = ceil($totalNotifications / $perPage);

// Get unread count
$unreadCount = getUnreadCount($userId);

// Get notification type counts
$typeCountsSql = "SELECT type, COUNT(*) as count FROM notifications WHERE user_id = ? GROUP BY type";
$typeCountsStmt = $db->prepare($typeCountsSql);
$typeCountsStmt->execute([$userId]);
$typeCounts = $typeCountsStmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Add formatted data
foreach ($notifications as &$notification) {
    $notification['time_ago'] = getTimeAgo($notification['created_at']);
    $notification['icon'] = getNotificationIcon($notification['type']);
    $notification['color'] = getNotificationColor($notification['type'], $notification['priority']);
    $notification['formatted_date'] = date('M d, Y g:i A', strtotime($notification['created_at']));
}

$notificationTypes = [
    'booking' => 'Bookings',
    'food_order' => 'Food Orders',
    'payment' => 'Payments',
    'system' => 'System',
    'schedule' => 'Schedule',
    'maintenance' => 'Maintenance',
    'event' => 'Events',
    'promotion' => 'Promotions'
];
?>

<style>
    .notifications-container {
        max-width: 1000px;
        margin: 0 auto;
    }
    
    .notifications-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .notifications-header h2 {
        margin: 0;
        font-size: 24px;
    }
    
    .notification-stats {
        display: flex;
        gap: 15px;
    }
    
    .stat-badge {
        padding: 8px 16px;
        border-radius: 20px;
        background: white;
        font-size: 14px;
        font-weight: 500;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    
    .stat-badge.unread {
        background: var(--primary-color);
        color: white;
    }
    
    .notifications-filters {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        flex-wrap: wrap;
        align-items: center;
        background: white;
        padding: 15px;
        border-radius: 10px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    
    .filter-btn {
        padding: 8px 16px;
        border-radius: 20px;
        border: 1px solid var(--gray-medium);
        background: white;
        color: var(--text-color);
        text-decoration: none;
        font-size: 13px;
        transition: all 0.3s;
    }
    
    .filter-btn:hover,
    .filter-btn.active {
        background: var(--primary-color);
        color: white;
        border-color: var(--primary-color);
    }
    
    .filter-separator {
        width: 1px;
        height: 25px;
        background: var(--gray-medium);
        margin: 0 5px;
    }
    
    .mark-all-btn {
        margin-left: auto;
        padding: 8px 16px;
        background: none;
        border: 1px solid var(--primary-color);
        color: var(--primary-color);
        border-radius: 5px;
        cursor: pointer;
        font-size: 13px;
        transition: all 0.3s;
    }
    
    .mark-all-btn:hover {
        background: var(--primary-color);
        color: white;
    }
    
    .cleanup-btn {
        padding: 8px 16px;
        background: none;
        border: 1px solid var(--danger-color);
        color: var(--danger-color);
        border-radius: 5px;
        cursor: pointer;
        font-size: 13px;
        transition: all 0.3s;
    }
    
    .cleanup-btn:hover {
        background: var(--danger-color);
        color: white;
    }
    
    .notifications-list {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        overflow: hidden;
    }
    
    .notification-row {
        display: flex;
        align-items: flex-start;
        gap: 15px;
        padding: 20px;
        border-bottom: 1px solid var(--gray-light);
        transition: background-color 0.2s;
        position: relative;
    }
    
    .notification-row:last-child {
        border-bottom: none;
    }
    
    .notification-row:hover {
        background-color: var(--gray-light);
    }
    
    .notification-row.unread {
        background-color: #f0f9ff;
    }
    
    .notification-row.unread:hover {
        background-color: #e0f2fe;
    }
    
    .notification-icon-large {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    
    .notification-icon-large i {
        font-size: 18px;
        color: white;
    }
    
    .notification-body {
        flex: 1;
        min-width: 0;
    }
    
    .notification-body h4 {
        margin: 0 0 8px 0;
        font-size: 16px;
        font-weight: 600;
        color: var(--dark-color);
    }
    
    .notification-body p {
        margin: 0 0 10px 0;
        font-size: 14px;
        color: #666;
        line-height: 1.5;
    }
    
    .notification-meta-row {
        display: flex;
        align-items: center;
        gap: 15px;
        font-size: 12px;
        color: #999;
    }
    
    .notification-type-badge {
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 500;
        text-transform: uppercase;
    }
    
    .notification-actions {
        display: flex;
        gap: 8px;
    }
    
    .action-btn {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        border: none;
        background: var(--gray-light);
        color: var(--text-color);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s;
    }
    
    .action-btn:hover {
        background: var(--primary-color);
        color: white;
    }
    
    .action-btn.delete:hover {
        background: var(--danger-color);
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
    }
    
    .empty-state i {
        font-size: 64px;
        color: var(--gray-medium);
        margin-bottom: 20px;
    }
    
    .empty-state h3 {
        margin: 0 0 10px 0;
        color: var(--dark-color);
    }
    
    .empty-state p {
        margin: 0;
        color: #999;
    }
    
    /* Type Summary */
    .type-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }
    
    .type-card {
        background: white;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .type-card i {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
    }
    
    .type-card .count {
        font-size: 20px;
        font-weight: 700;
    }
    
    .type-card .label {
        font-size: 12px;
        color: #666;
    }
    
    /* Pagination */
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 8px;
        margin-top: 25px;
    }
    
    .pagination a,
    .pagination span {
        padding: 8px 14px;
        border-radius: 5px;
        text-decoration: none;
        font-size: 14px;
        transition: all 0.3s;
    }
    
    .pagination a {
        background: white;
        color: var(--text-color);
        border: 1px solid var(--gray-medium);
    }
    
    .pagination a:hover {
        background: var(--primary-color);
        color: white;
        border-color: var(--primary-color);
    }
    
    .pagination .current {
        background: var(--primary-color);
        color: white;
    }
    
    .pagination .disabled {
        color: #ccc;
        cursor: not-allowed;
    }
    
    @media (max-width: 768px) {
        .notifications-header {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .notification-stats {
            width: 100%;
        }
        
        .notifications-filters {
            flex-direction: column;
            align-items: stretch;
        }
        
        .filter-separator {
            display: none;
        }
        
        .mark-all-btn,
        .cleanup-btn {
            margin-left: 0;
            width: 100%;
        }
        
        .notification-row {
            flex-wrap: wrap;
        }
        
        .notification-actions {
            width: 100%;
            justify-content: flex-end;
            margin-top: 10px;
        }
        
        .type-summary {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>

<div class="notifications-container">
    <div class="notifications-header">
        <h2><i class="fas fa-bell" style="margin-right: 10px; color: var(--primary-color);"></i>Notifications</h2>
        <div class="notification-stats">
            <span class="stat-badge unread">
                <i class="fas fa-envelope" style="margin-right: 5px;"></i><?php echo $unreadCount; ?> Unread
            </span>
            <span class="stat-badge">
                <i class="fas fa-list" style="margin-right: 5px;"></i><?php echo $totalNotifications; ?> Total
            </span>
        </div>
    </div>
    
    <!-- Type Summary -->
    <?php if (!empty($typeCounts)): ?>
    <div class="type-summary">
        <?php foreach ($typeCounts as $type => $count): ?>
            <?php $color = getNotificationColor($type, 'medium'); ?>
            <div class="type-card">
                <i class="fas fa-<?php echo getNotificationIcon($type); ?>" style="background: <?php echo $color; ?>20; color: <?php echo $color; ?>"></i>
                <div>
                    <div class="count" style="color: <?php echo $color; ?>"><?php echo $count; ?></div>
                    <div class="label"><?php echo $notificationTypes[$type] ?? ucfirst($type); ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <div class="notifications-filters">
        <a href="admin-notifications.php" class="filter-btn <?php echo $filter === 'all' && !$typeFilter ? 'active' : ''; ?>">All</a>
        <a href="admin-notifications.php?filter=unread" class="filter-btn <?php echo $filter === 'unread' ? 'active' : ''; ?>">Unread</a>
        <a href="admin-notifications.php?filter=read" class="filter-btn <?php echo $filter === 'read' ? 'active' : ''; ?>">Read</a>
        
        <div class="filter-separator"></div>
        
        <?php foreach ($notificationTypes as $type => $label): ?>
            <a href="admin-notifications.php?type=<?php echo $type; ?>" class="filter-btn <?php echo $typeFilter === $type ? 'active' : ''; ?>">
                <?php echo $label; ?>
            </a>
        <?php endforeach; ?>
        
        <form method="POST" style="margin-left: auto; display: flex; gap: 10px;">
            <?php if ($unreadCount > 0): ?>
            <button type="submit" name="mark_all_read" class="mark-all-btn">
                <i class="fas fa-check-double" style="margin-right: 5px;"></i>Mark All as Read
            </button>
            <?php endif; ?>
            <button type="submit" name="cleanup" class="cleanup-btn" onclick="return confirm('Clean up notifications older than 30 days?');">
                <i class="fas fa-broom" style="margin-right: 5px;"></i>Clean Up Old
            </button>
        </form>
    </div>
    
    <div class="notifications-list">
        <?php if (empty($notifications)): ?>
            <div class="empty-state">
                <i class="fas fa-bell-slash"></i>
                <h3>No notifications found</h3>
                <p>You don't have any <?php echo $filter !== 'all' ? $filter : ''; ?> notifications yet.</p>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $notification): ?>
                <div class="notification-row <?php echo $notification['status']; ?>">
                    <div class="notification-icon-large" style="background-color: <?php echo $notification['color']; ?>;">
                        <i class="fas fa-<?php echo $notification['icon']; ?>"></i>
                    </div>
                    <div class="notification-body">
                        <h4><?php echo htmlspecialchars($notification['title']); ?></h4>
                        <p><?php echo htmlspecialchars($notification['message']); ?></p>
                        <div class="notification-meta-row">
                            <span class="notification-type-badge" style="background: <?php echo $notification['color']; ?>20; color: <?php echo $notification['color']; ?>;">
                                <?php echo $notificationTypes[$notification['type']] ?? ucfirst($notification['type']); ?>
                            </span>
                            <span><i class="far fa-clock" style="margin-right: 5px;"></i><?php echo $notification['formatted_date']; ?></span>
                            <?php if ($notification['priority'] === 'high'): ?>
                                <span style="color: var(--danger-color);"><i class="fas fa-exclamation-circle" style="margin-right: 5px;"></i>High Priority</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="notification-actions">
                        <?php if ($notification['status'] === 'unread'): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="notification_id" value="<?php echo $notification['notification_id']; ?>">
                                <button type="submit" name="mark_read" class="action-btn" title="Mark as read">
                                    <i class="fas fa-check"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                        <?php if ($notification['action_url']): ?>
                            <a href="<?php echo htmlspecialchars($notification['action_url']); ?>" class="action-btn" title="View details">
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                        <?php endif; ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="notification_id" value="<?php echo $notification['notification_id']; ?>">
                            <button type="submit" name="delete" class="action-btn delete" title="Delete" onclick="return confirm('Delete this notification?');">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($currentPage > 1): ?>
            <a href="?filter=<?php echo $filter; ?>&type=<?php echo $typeFilter; ?>&page=<?php echo $currentPage - 1; ?>"><i class="fas fa-chevron-left"></i></a>
        <?php else: ?>
            <span class="disabled"><i class="fas fa-chevron-left"></i></span>
        <?php endif; ?>
        
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php if ($i == $currentPage): ?>
                <span class="current"><?php echo $i; ?></span>
            <?php else: ?>
                <a href="?filter=<?php echo $filter; ?>&type=<?php echo $typeFilter; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        
        <?php if ($currentPage < $totalPages): ?>
            <a href="?filter=<?php echo $filter; ?>&type=<?php echo $typeFilter; ?>&page=<?php echo $currentPage + 1; ?>"><i class="fas fa-chevron-right"></i></a>
        <?php else: ?>
            <span class="disabled"><i class="fas fa-chevron-right"></i></span>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
