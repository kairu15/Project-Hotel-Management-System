<?php
/**
 * Bayawan Bai Hotel - Notification System Helper Functions
 * Handles creating, fetching, and managing notifications
 */

// Require config for database access
require_once __DIR__ . '/config.php';

/**
 * Create a new notification for a user
 * 
 * @param int $userId The user ID to notify
 * @param string $type Notification type (booking, food_order, payment, system, schedule, maintenance, event, promotion)
 * @param string $title Notification title
 * @param string $message Notification message
 * @param array $options Optional parameters: related_id, related_type, priority, action_url
 * @return bool|int Returns notification ID on success, false on failure
 */
function createNotification($userId, $type, $title, $message, $options = []) {
    $db = getDB();
    
    // Check user notification settings before creating
    if (!shouldNotifyUser($userId, $type, 'popup')) {
        return false;
    }
    
    $sql = "INSERT INTO notifications (user_id, type, title, message, related_id, related_type, priority, action_url) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $db->prepare($sql);
    
    $relatedId = $options['related_id'] ?? null;
    $relatedType = $options['related_type'] ?? null;
    $priority = $options['priority'] ?? 'medium';
    $actionUrl = $options['action_url'] ?? null;
    
    try {
        $stmt->execute([$userId, $type, $title, $message, $relatedId, $relatedType, $priority, $actionUrl]);
        return $db->lastInsertId();
    } catch (PDOException $e) {
        error_log("Error creating notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Create notifications for multiple users at once (for staff/admin notifications)
 * 
 * @param array $userIds Array of user IDs to notify
 * @param string $type Notification type
 * @param string $title Notification title
 * @param string $message Notification message
 * @param array $options Optional parameters
 * @return int Number of notifications created
 */
function createBulkNotifications($userIds, $type, $title, $message, $options = []) {
    $count = 0;
    foreach ($userIds as $userId) {
        $result = createNotification($userId, $type, $title, $message, $options);
        if ($result) {
            $count++;
        }
    }
    return $count;
}

/**
 * Create notification for all users with specific role
 * 
 * @param string|array $roles Role(s) to notify ('admin', 'manager', 'receptionist', 'guest')
 * @param string $type Notification type
 * @param string $title Notification title
 * @param string $message Notification message
 * @param array $options Optional parameters
 * @return int Number of notifications created
 */
function createRoleNotifications($roles, $type, $title, $message, $options = []) {
    $db = getDB();
    
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    
    $placeholders = implode(',', array_fill(0, count($roles), '?'));
    $sql = "SELECT user_id FROM users WHERE role IN ($placeholders) AND status = 'active'";
    $stmt = $db->prepare($sql);
    $stmt->execute($roles);
    $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    return createBulkNotifications($users, $type, $title, $message, $options);
}

/**
 * Get notifications for a user
 * 
 * @param int $userId The user ID
 * @param string $status Filter by status: 'all', 'unread', 'read'
 * @param int $limit Maximum number of notifications to return
 * @param int $offset Offset for pagination
 * @return array Array of notifications
 */
function getNotifications($userId, $status = 'all', $limit = 20, $offset = 0) {
    $db = getDB();
    
    $sql = "SELECT * FROM notifications WHERE user_id = ?";
    $params = [$userId];
    
    if ($status !== 'all') {
        $sql .= " AND status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY 
        CASE priority 
            WHEN 'high' THEN 1 
            WHEN 'medium' THEN 2 
            WHEN 'low' THEN 3 
        END,
        created_at DESC 
        LIMIT " . (int) $limit . " OFFSET " . (int) $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Get unread notification count for a user
 * 
 * @param int $userId The user ID
 * @return int Count of unread notifications
 */
function getUnreadCount($userId) {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND status = 'unread'");
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

/**
 * Mark notification as read
 * 
 * @param int $notificationId The notification ID
 * @param int $userId The user ID (for security verification)
 * @return bool Success status
 */
function markAsRead($notificationId, $userId) {
    $db = getDB();
    
    $stmt = $db->prepare("UPDATE notifications SET status = 'read', read_at = NOW() 
                          WHERE notification_id = ? AND user_id = ?");
    return $stmt->execute([$notificationId, $userId]);
}

/**
 * Mark all notifications as read for a user
 * 
 * @param int $userId The user ID
 * @return bool Success status
 */
function markAllAsRead($userId) {
    $db = getDB();
    
    $stmt = $db->prepare("UPDATE notifications SET status = 'read', read_at = NOW() 
                          WHERE user_id = ? AND status = 'unread'");
    return $stmt->execute([$userId]);
}

/**
 * Delete a notification
 * 
 * @param int $notificationId The notification ID
 * @param int $userId The user ID (for security verification)
 * @return bool Success status
 */
function deleteNotification($notificationId, $userId) {
    $db = getDB();
    
    $stmt = $db->prepare("DELETE FROM notifications WHERE notification_id = ? AND user_id = ?");
    return $stmt->execute([$notificationId, $userId]);
}

/**
 * Get notification settings for a user
 * 
 * @param int $userId The user ID
 * @return array Notification settings
 */
function getNotificationSettings($userId) {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT * FROM notification_settings WHERE user_id = ?");
    $stmt->execute([$userId]);
    $settings = $stmt->fetchAll();
    
    // If no settings exist, create defaults
    if (empty($settings)) {
        createDefaultNotificationSettings($userId);
        return getNotificationSettings($userId);
    }
    
    return $settings;
}

/**
 * Create default notification settings for a user
 * 
 * @param int $userId The user ID
 * @return bool Success status
 */
function createDefaultNotificationSettings($userId) {
    $db = getDB();
    
    $types = ['booking', 'food_order', 'payment', 'system', 'schedule', 'maintenance', 'event', 'promotion', 'all'];
    
    $stmt = $db->prepare("INSERT IGNORE INTO notification_settings (user_id, notification_type, email_enabled, popup_enabled) 
                          VALUES (?, ?, TRUE, TRUE)");
    
    foreach ($types as $type) {
        $stmt->execute([$userId, $type]);
    }
    
    return true;
}

/**
 * Update notification settings for a user
 * 
 * @param int $userId The user ID
 * @param string $type Notification type
 * @param bool $emailEnabled Whether email notifications are enabled
 * @param bool $popupEnabled Whether popup notifications are enabled
 * @return bool Success status
 */
function updateNotificationSetting($userId, $type, $emailEnabled, $popupEnabled) {
    $db = getDB();
    
    $stmt = $db->prepare("UPDATE notification_settings 
                          SET email_enabled = ?, popup_enabled = ? 
                          WHERE user_id = ? AND notification_type = ?");
    return $stmt->execute([$emailEnabled ? 1 : 0, $popupEnabled ? 1 : 0, $userId, $type]);
}

/**
 * Check if user should be notified based on their settings
 * 
 * @param int $userId The user ID
 * @param string $type Notification type
 * @param string $method Notification method ('email' or 'popup')
 * @return bool Whether to notify the user
 */
function shouldNotifyUser($userId, $type, $method) {
    $db = getDB();
    
    // First check if there's a specific setting for this type
    $stmt = $db->prepare("SELECT * FROM notification_settings 
                          WHERE user_id = ? AND notification_type = ?");
    $stmt->execute([$userId, $type]);
    $setting = $stmt->fetch();
    
    if ($setting) {
        $column = $method . '_enabled';
        return (bool) $setting[$column];
    }
    
    // Check 'all' setting as fallback
    $stmt = $db->prepare("SELECT * FROM notification_settings 
                          WHERE user_id = ? AND notification_type = 'all'");
    $stmt->execute([$userId]);
    $allSetting = $stmt->fetch();
    
    if ($allSetting) {
        $column = $method . '_enabled';
        return (bool) $allSetting[$column];
    }
    
    // Default to true if no settings found
    return true;
}

/**
 * Get recent notifications formatted for the floating widget
 * 
 * @param int $userId The user ID
 * @param int $limit Maximum number to return
 * @return array Formatted notifications
 */
function getRecentNotificationsForWidget($userId, $limit = 10) {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT 
                            notification_id,
                            type,
                            title,
                            message,
                            status,
                            priority,
                            action_url,
                            created_at,
                            read_at
                          FROM notifications 
                          WHERE user_id = ? 
                          ORDER BY 
                            CASE priority 
                                WHEN 'high' THEN 1 
                                WHEN 'medium' THEN 2 
                                WHEN 'low' THEN 3 
                            END,
                            created_at DESC 
                          LIMIT " . (int) $limit);
    
    $stmt->execute([$userId]);
    $notifications = $stmt->fetchAll();
    
    // Add human-readable time and icon class
    foreach ($notifications as &$notification) {
        $notification['time_ago'] = getTimeAgo($notification['created_at']);
        $notification['icon'] = getNotificationIcon($notification['type']);
        $notification['color'] = getNotificationColor($notification['type'], $notification['priority']);
    }
    
    return $notifications;
}

/**
 * Get human-readable time ago
 * 
 * @param string $datetime The datetime string
 * @return string Human-readable time
 */
function getTimeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M d, Y', $time);
    }
}

/**
 * Get Font Awesome icon class for notification type
 * 
 * @param string $type Notification type
 * @return string Icon class
 */
function getNotificationIcon($type) {
    $icons = [
        'booking' => 'calendar-check',
        'food_order' => 'utensils',
        'payment' => 'credit-card',
        'system' => 'cog',
        'schedule' => 'clock',
        'maintenance' => 'wrench',
        'event' => 'star',
        'promotion' => 'tag'
    ];
    
    return $icons[$type] ?? 'bell';
}

/**
 * Get color class for notification type/priority
 * 
 * @param string $type Notification type
 * @param string $priority Notification priority
 * @return string Color code
 */
function getNotificationColor($type, $priority) {
    if ($priority === 'high') {
        return '#dc3545'; // Red for high priority
    }
    
    $colors = [
        'booking' => '#28a745',
        'food_order' => '#fd7e14',
        'payment' => '#17a2b8',
        'system' => '#6c757d',
        'schedule' => '#ffc107',
        'maintenance' => '#6f42c1',
        'event' => '#e83e8c',
        'promotion' => '#20c997'
    ];
    
    return $colors[$type] ?? '#6c757d';
}

/**
 * Clean up old notifications (keep last 90 days)
 * 
 * @param int $days Number of days to keep
 * @return int Number of notifications deleted
 */
function cleanupOldNotifications($days = 90) {
    $db = getDB();
    
    $stmt = $db->prepare("DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY) AND status = 'read'");
    $stmt->execute([$days]);
    return $stmt->rowCount();
}

// ============================================
// Role-Specific Notification Helper Functions
// ============================================

/**
 * Notify user about booking updates
 * 
 * @param int $userId User to notify
 * @param int $bookingId Related booking ID
 * @param string $status New booking status
 * @param string $additionalInfo Additional information
 * @return bool|int Notification ID
 */
function notifyBookingUpdate($userId, $bookingId, $status, $additionalInfo = '') {
    $titles = [
        'confirmed' => 'Booking Confirmed',
        'checked_in' => 'Checked In Successfully',
        'checked_out' => 'Checked Out - Thank You!',
        'cancelled' => 'Booking Cancelled',
        'pending' => 'Booking Received'
    ];
    
    $messages = [
        'confirmed' => 'Your booking has been confirmed. We look forward to welcoming you!',
        'checked_in' => 'You have successfully checked in. Enjoy your stay!',
        'checked_out' => 'Thank you for staying with us. We hope to see you again soon!',
        'cancelled' => 'Your booking has been cancelled. ' . $additionalInfo,
        'pending' => 'Your booking request has been received and is pending confirmation.'
    ];
    
    return createNotification($userId, 'booking', $titles[$status] ?? 'Booking Update', 
        $messages[$status] ?? 'Your booking status has been updated.', [
        'related_id' => $bookingId,
        'related_type' => 'booking',
        'priority' => $status === 'cancelled' ? 'high' : 'medium',
        'action_url' => '/bayawanhotel/user/my-bookings.php'
    ]);
}

/**
 * Notify user about food order updates
 * 
 * @param int $userId User to notify
 * @param int $orderId Related order ID
 * @param string $status New order status
 * @return bool|int Notification ID
 */
function notifyFoodOrderUpdate($userId, $orderId, $status) {
    $titles = [
        'preparing' => 'Order Being Prepared',
        'ready' => 'Order Ready for Pickup/Delivery',
        'delivered' => 'Order Delivered',
        'cancelled' => 'Order Cancelled'
    ];
    
    $messages = [
        'preparing' => 'Your food order is now being prepared by our kitchen.',
        'ready' => 'Your food order is ready! Please collect it or wait for delivery.',
        'delivered' => 'Your food order has been delivered. Enjoy your meal!',
        'cancelled' => 'Your food order has been cancelled. Please contact us for assistance.'
    ];
    
    return createNotification($userId, 'food_order', $titles[$status] ?? 'Order Update', 
        $messages[$status] ?? 'Your order status has been updated.', [
        'related_id' => $orderId,
        'related_type' => 'food_order',
        'priority' => $status === 'cancelled' ? 'high' : 'medium',
        'action_url' => '/bayawanhotel/user/dashboard.php'
    ]);
}

/**
 * Notify user about payment updates
 * 
 * @param int $userId User to notify
 * @param int $paymentId Related payment ID
 * @param string $status Payment status
 * @param float $amount Payment amount
 * @return bool|int Notification ID
 */
function notifyPaymentUpdate($userId, $paymentId, $status, $amount = 0) {
    $titles = [
        'completed' => 'Payment Successful',
        'failed' => 'Payment Failed',
        'refunded' => 'Refund Processed',
        'pending' => 'Payment Pending'
    ];
    
    $formattedAmount = '₱' . number_format($amount, 2);
    
    $messages = [
        'completed' => "Your payment of {$formattedAmount} has been received. Thank you!",
        'failed' => "Your payment of {$formattedAmount} could not be processed. Please try again.",
        'refunded' => "A refund of {$formattedAmount} has been processed to your account.",
        'pending' => "Your payment of {$formattedAmount} is being processed."
    ];
    
    return createNotification($userId, 'payment', $titles[$status] ?? 'Payment Update', 
        $messages[$status] ?? 'Your payment status has been updated.', [
        'related_id' => $paymentId,
        'related_type' => 'payment',
        'priority' => $status === 'failed' ? 'high' : 'medium',
        'action_url' => '/bayawanhotel/user/my-bookings.php'
    ]);
}

/**
 * Notify staff about new booking
 * 
 * @param int $bookingId New booking ID
 * @param string $guestName Guest name
 * @param string $checkInDate Check-in date
 * @return int Number of staff notified
 */
function notifyStaffNewBooking($bookingId, $guestName, $checkInDate) {
    return createRoleNotifications(
        ['admin', 'manager', 'receptionist'],
        'booking',
        'New Booking Received',
        "New booking from {$guestName} for " . date('M d, Y', strtotime($checkInDate)),
        [
            'related_id' => $bookingId,
            'related_type' => 'booking',
            'priority' => 'medium',
            'action_url' => '/bayawanhotel/staff/confirm-booking.php'
        ]
    );
}

/**
 * Notify staff about new food order
 * 
 * @param int $orderId New order ID
 * @param string $guestName Guest name
 * @param string $roomNumber Room number
 * @return int Number of staff notified
 */
function notifyStaffNewFoodOrder($orderId, $guestName, $roomNumber) {
    $location = $roomNumber ? "Room {$roomNumber}" : 'Dine-in';
    
    return createRoleNotifications(
        ['admin', 'manager', 'receptionist'],
        'food_order',
        'New Food Order',
        "New order from {$guestName} ({$location})",
        [
            'related_id' => $orderId,
            'related_type' => 'food_order',
            'priority' => 'medium',
            'action_url' => '/bayawanhotel/staff/staff-foods-orders.php'
        ]
    );
}

/**
 * Notify admin about system alerts
 * 
 * @param string $title Alert title
 * @param string $message Alert message
 * @param string $priority Alert priority
 * @return int Number of admins notified
 */
function notifyAdminSystemAlert($title, $message, $priority = 'medium') {
    return createRoleNotifications(
        ['admin', 'manager'],
        'system',
        $title,
        $message,
        [
            'priority' => $priority,
            'action_url' => '/bayawanhotel/admin/admin-dashboard.php'
        ]
    );
}

/**
 * Notify staff about schedule changes
 * 
 * @param int $userId Staff user ID
 * @param string $changeType Type of change (added, modified, cancelled)
 * @param string $scheduleDate Date of schedule
 * @return bool|int Notification ID
 */
function notifyScheduleChange($userId, $changeType, $scheduleDate) {
    $titles = [
        'added' => 'New Schedule Added',
        'modified' => 'Schedule Modified',
        'cancelled' => 'Schedule Cancelled'
    ];
    
    $messages = [
        'added' => "You have been scheduled for " . date('M d, Y', strtotime($scheduleDate)),
        'modified' => "Your schedule for " . date('M d, Y', strtotime($scheduleDate)) . " has been modified.",
        'cancelled' => "Your schedule for " . date('M d, Y', strtotime($scheduleDate)) . " has been cancelled."
    ];
    
    return createNotification($userId, 'schedule', $titles[$changeType] ?? 'Schedule Update', 
        $messages[$changeType] ?? 'Your schedule has been updated.', [
        'priority' => $changeType === 'cancelled' ? 'high' : 'medium',
        'action_url' => '/bayawanhotel/staff/staff-dashboard.php'
    ]);
}

/**
 * Notify admin about maintenance requests
 * 
 * @param int $requestId Maintenance request ID
 * @param string $issueType Type of issue
 * @param string $priority Request priority
 * @param string $roomNumber Room number (if applicable)
 * @return int Number of staff notified
 */
function notifyMaintenanceRequest($requestId, $issueType, $priority, $roomNumber = '') {
    $location = $roomNumber ? " in Room {$roomNumber}" : '';
    
    return createRoleNotifications(
        ['admin', 'manager'],
        'maintenance',
        'New Maintenance Request',
        ucfirst($issueType) . " issue reported{$location}",
        [
            'related_id' => $requestId,
            'related_type' => 'maintenance_request',
            'priority' => $priority,
            'action_url' => '/bayawanhotel/admin/admin-maintenance.php'
        ]
    );
}
