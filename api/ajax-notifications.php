<?php
/**
 * Bayawan Bai Hotel - AJAX Notification API
 * Handles real-time notification fetching and management
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/notifications.php';

// Set JSON content type
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$userId = getUserId();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

$response = ['success' => false];

switch ($action) {
    case 'get_notifications':
        $limit = intval($_GET['limit'] ?? 10);
        $notifications = getRecentNotificationsForWidget($userId, $limit);
        $unreadCount = getUnreadCount($userId);
        
        $response = [
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ];
        break;
        
    case 'mark_read':
        $notificationId = intval($_POST['notification_id'] ?? 0);
        if ($notificationId > 0) {
            $result = markAsRead($notificationId, $userId);
            $response = [
                'success' => $result,
                'unread_count' => getUnreadCount($userId)
            ];
        } else {
            $response['error'] = 'Invalid notification ID';
        }
        break;
        
    case 'mark_all_read':
        $result = markAllAsRead($userId);
        $response = [
            'success' => $result,
            'unread_count' => 0
        ];
        break;
        
    case 'delete':
        $notificationId = intval($_POST['notification_id'] ?? 0);
        if ($notificationId > 0) {
            $result = deleteNotification($notificationId, $userId);
            $response = [
                'success' => $result,
                'unread_count' => getUnreadCount($userId)
            ];
        } else {
            $response['error'] = 'Invalid notification ID';
        }
        break;
        
    case 'get_settings':
        $settings = getNotificationSettings($userId);
        $response = [
            'success' => true,
            'settings' => $settings
        ];
        break;
        
    case 'update_settings':
        $type = $_POST['type'] ?? '';
        $emailEnabled = filter_var($_POST['email_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $popupEnabled = filter_var($_POST['popup_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN);
        
        if ($type) {
            $result = updateNotificationSetting($userId, $type, $emailEnabled, $popupEnabled);
            $response = [
                'success' => $result,
                'message' => $result ? 'Settings updated successfully' : 'Failed to update settings'
            ];
        } else {
            $response['error'] = 'Invalid notification type';
        }
        break;
        
    case 'get_unread_count':
        $response = [
            'success' => true,
            'unread_count' => getUnreadCount($userId)
        ];
        break;
        
    case 'load_more':
        $page = intval($_GET['page'] ?? 1);
        $limit = intval($_GET['limit'] ?? 20);
        $offset = ($page - 1) * $limit;
        $status = $_GET['status'] ?? 'all';
        
        $notifications = getNotifications($userId, $status, $limit, $offset);
        
        // Add formatted data
        foreach ($notifications as &$notification) {
            $notification['time_ago'] = getTimeAgo($notification['created_at']);
            $notification['icon'] = getNotificationIcon($notification['type']);
            $notification['color'] = getNotificationColor($notification['type'], $notification['priority']);
            $notification['formatted_date'] = date('M d, Y g:i A', strtotime($notification['created_at']));
        }
        
        $response = [
            'success' => true,
            'notifications' => $notifications,
            'has_more' => count($notifications) === $limit
        ];
        break;
        
    default:
        $response['error'] = 'Invalid action';
}

echo json_encode($response);
