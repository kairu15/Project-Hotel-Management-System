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
        "New booking from {$guestName}",
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

// ============================================
// ADMIN NOTIFICATIONS - All 8 Categories
// ============================================

/**
 * Notify admin about booking status changes
 * Categories: Bookings
 * Processes: new, updated, confirmed, cancelled
 * 
 * @param int $bookingId Booking ID
 * @param string $processType Type of booking process (created, updated, confirmed, cancelled)
 * @param string $guestName Guest name
 * @param string $details Additional details
 * @return int Number of admins notified
 */
function notifyAdminBookingUpdate($bookingId, $processType, $guestName, $details = '') {
    $titles = [
        'created' => 'New Booking Created',
        'updated' => 'Booking Updated',
        'confirmed' => 'Booking Confirmed',
        'cancelled' => 'Booking Cancelled'
    ];
    
    $messages = [
        'created' => "New booking from {$guestName}. {$details}",
        'updated' => "Booking for {$guestName} has been updated. {$details}",
        'confirmed' => "Booking for {$guestName} has been confirmed. {$details}",
        'cancelled' => "Booking for {$guestName} has been cancelled. {$details}"
    ];
    
    return createRoleNotifications(
        ['admin', 'manager'],
        'booking',
        $titles[$processType] ?? 'Booking Update',
        $messages[$processType] ?? "Booking update for {$guestName}",
        [
            'related_id' => $bookingId,
            'related_type' => 'booking',
            'priority' => $processType === 'cancelled' ? 'high' : 'medium',
            'action_url' => '/bayawanhotel/admin/admin-bookings.php'
        ]
    );
}

/**
 * Notify admin about food order status changes
 * Categories: Food Orders
 * Processes: placed, updated, completed
 * 
 * @param int $orderId Order ID
 * @param string $processType Type of order process (placed, updated, completed)
 * @param string $guestName Guest name
 * @param string $details Order details
 * @return int Number of admins notified
 */
function notifyAdminFoodOrderUpdate($orderId, $processType, $guestName, $details = '') {
    $titles = [
        'placed' => 'New Food Order Placed',
        'updated' => 'Food Order Updated',
        'completed' => 'Food Order Completed'
    ];
    
    $messages = [
        'placed' => "New food order from {$guestName}. {$details}",
        'updated' => "Food order for {$guestName} has been updated. {$details}",
        'completed' => "Food order for {$guestName} has been completed. {$details}"
    ];
    
    return createRoleNotifications(
        ['admin', 'manager'],
        'food_order',
        $titles[$processType] ?? 'Food Order Update',
        $messages[$processType] ?? "Food order update for {$guestName}",
        [
            'related_id' => $orderId,
            'related_type' => 'food_order',
            'priority' => 'medium',
            'action_url' => '/bayawanhotel/admin/admin-foods-inventory.php'
        ]
    );
}

/**
 * Notify admin about payment status changes
 * Categories: Payments
 * Processes: made, pending, failed
 * 
 * @param int $paymentId Payment ID
 * @param string $processType Type of payment process (made, pending, failed)
 * @param string $guestName Guest name
 * @param float $amount Payment amount
 * @param string $paymentMethod Payment method used
 * @return int Number of admins notified
 */
function notifyAdminPaymentUpdate($paymentId, $processType, $guestName, $amount = 0, $paymentMethod = '') {
    $formattedAmount = '₱' . number_format($amount, 2);
    $methodText = $paymentMethod ? " via {$paymentMethod}" : '';
    
    $titles = [
        'made' => 'Payment Received',
        'pending' => 'Payment Pending',
        'failed' => 'Payment Failed'
    ];
    
    $messages = [
        'made' => "Payment of {$formattedAmount}{$methodText} received from {$guestName}.",
        'pending' => "Payment of {$formattedAmount} from {$guestName} is pending processing.",
        'failed' => "Payment of {$formattedAmount} from {$guestName} has failed."
    ];
    
    return createRoleNotifications(
        ['admin', 'manager'],
        'payment',
        $titles[$processType] ?? 'Payment Update',
        $messages[$processType] ?? "Payment update for {$guestName}",
        [
            'related_id' => $paymentId,
            'related_type' => 'payment',
            'priority' => $processType === 'failed' ? 'high' : 'medium',
            'action_url' => '/bayawanhotel/admin/admin-payments.php'
        ]
    );
}

/**
 * Notify admin about system updates, errors, or important alerts
 * Categories: System
 * Processes: updates, errors, important alerts
 * 
 * @param string $processType Type of system process (update, error, alert)
 * @param string $title Alert title
 * @param string $message Alert message
 * @param string $priority Alert priority (low, medium, high)
 * @return int Number of admins notified
 */
function notifyAdminSystemUpdate($processType, $title, $message, $priority = 'medium') {
    $defaultTitles = [
        'update' => 'System Update Available',
        'error' => 'System Error Detected',
        'alert' => 'Important System Alert'
    ];
    
    $finalTitle = $title ?: ($defaultTitles[$processType] ?? 'System Notification');
    
    return createRoleNotifications(
        ['admin'],
        'system',
        $finalTitle,
        $message,
        [
            'priority' => $priority,
            'action_url' => '/bayawanhotel/admin/admin-dashboard.php'
        ]
    );
}

/**
 * Notify admin about schedule changes or updates
 * Categories: Schedule
 * Processes: changes, updates in schedules
 * 
 * @param int $scheduleId Schedule ID
 * @param string $processType Type of schedule process (created, updated, deleted)
 * @param string $staffName Staff member name
 * @param string $scheduleDate Schedule date
 * @return int Number of admins notified
 */
function notifyAdminScheduleUpdate($scheduleId, $processType, $staffName, $scheduleDate) {
    $titles = [
        'created' => 'New Staff Schedule Created',
        'updated' => 'Staff Schedule Updated',
        'deleted' => 'Staff Schedule Deleted'
    ];
    
    $formattedDate = date('M d, Y', strtotime($scheduleDate));
    
    $messages = [
        'created' => "New schedule created for {$staffName} on {$formattedDate}.",
        'updated' => "Schedule for {$staffName} on {$formattedDate} has been updated.",
        'deleted' => "Schedule for {$staffName} on {$formattedDate} has been deleted."
    ];
    
    return createRoleNotifications(
        ['admin', 'manager'],
        'schedule',
        $titles[$processType] ?? 'Schedule Update',
        $messages[$processType] ?? "Schedule update for {$staffName}",
        [
            'related_id' => $scheduleId,
            'related_type' => 'staff_schedule',
            'priority' => 'medium',
            'action_url' => '/bayawanhotel/admin/admin-staff-schedules.php'
        ]
    );
}

/**
 * Notify admin about maintenance request submissions or updates
 * Categories: Maintenance
 * Processes: submitted, updated
 * 
 * @param int $requestId Maintenance request ID
 * @param string $processType Type of maintenance process (submitted, updated, resolved)
 * @param string $issueType Type of issue
 * @param string $priority Request priority
 * @param string $roomNumber Room number (if applicable)
 * @param string $assignedStaff Staff assigned to handle the request
 * @return int Number of admins notified
 */
function notifyAdminMaintenanceUpdate($requestId, $processType, $issueType, $priority, $roomNumber = '', $assignedStaff = '') {
    $location = $roomNumber ? " in Room {$roomNumber}" : '';
    $assignee = $assignedStaff ? " Assigned to: {$assignedStaff}." : '';
    
    $titles = [
        'submitted' => 'New Maintenance Request Submitted',
        'updated' => 'Maintenance Request Updated',
        'resolved' => 'Maintenance Request Resolved'
    ];
    
    $messages = [
        'submitted' => "New {$issueType} maintenance request submitted{$location}. Priority: {$priority}.{$assignee}",
        'updated' => "{$issueType} maintenance request{$location} has been updated. Priority: {$priority}.{$assignee}",
        'resolved' => "{$issueType} maintenance request{$location} has been resolved.{$assignee}"
    ];
    
    return createRoleNotifications(
        ['admin', 'manager'],
        'maintenance',
        $titles[$processType] ?? 'Maintenance Update',
        $messages[$processType] ?? "Maintenance update for {$issueType} issue",
        [
            'related_id' => $requestId,
            'related_type' => 'maintenance_request',
            'priority' => $priority,
            'action_url' => '/bayawanhotel/admin/admin-maintenance.php'
        ]
    );
}

/**
 * Notify admin about new events created or modified
 * Categories: Events
 * Processes: created, modified
 * 
 * @param int $eventId Event ID
 * @param string $processType Type of event process (created, modified, cancelled)
 * @param string $eventName Event name
 * @param string $eventDate Event date
 * @param string $bookedBy Person who booked the event
 * @return int Number of admins notified
 */
function notifyAdminEventUpdate($eventId, $processType, $eventName, $eventDate, $bookedBy = '') {
    $titles = [
        'created' => 'New Event Created',
        'modified' => 'Event Modified',
        'cancelled' => 'Event Cancelled'
    ];
    
    $formattedDate = date('M d, Y', strtotime($eventDate));
    $bookerText = $bookedBy ? " by {$bookedBy}" : '';
    
    $messages = [
        'created' => "New event '{$eventName}' scheduled for {$formattedDate}{$bookerText}.",
        'modified' => "Event '{$eventName}' on {$formattedDate} has been modified{$bookerText}.",
        'cancelled' => "Event '{$eventName}' scheduled for {$formattedDate} has been cancelled{$bookerText}."
    ];
    
    return createRoleNotifications(
        ['admin', 'manager'],
        'event',
        $titles[$processType] ?? 'Event Update',
        $messages[$processType] ?? "Event update for {$eventName}",
        [
            'related_id' => $eventId,
            'related_type' => 'event',
            'priority' => $processType === 'cancelled' ? 'high' : 'medium',
            'action_url' => '/bayawanhotel/admin/admin-event-bookings.php'
        ]
    );
}

/**
 * Notify admin about promotions added, updated, or expired
 * Categories: Promotions
 * Processes: added, updated, expired
 * 
 * @param int $promoId Promotion ID
 * @param string $processType Type of promotion process (added, updated, expired, activated)
 * @param string $promoTitle Promotion title
 * @param string $promoCode Promotion code
 * @return int Number of admins notified
 */
function notifyAdminPromotionUpdate($promoId, $processType, $promoTitle, $promoCode = '') {
    $codeText = $promoCode ? " (Code: {$promoCode})" : '';
    
    $titles = [
        'added' => 'New Promotion Added',
        'updated' => 'Promotion Updated',
        'expired' => 'Promotion Expired',
        'activated' => 'Promotion Activated'
    ];
    
    $messages = [
        'added' => "New promotion '{$promoTitle}'{$codeText} has been added.",
        'updated' => "Promotion '{$promoTitle}'{$codeText} has been updated.",
        'expired' => "Promotion '{$promoTitle}'{$codeText} has expired and is no longer active.",
        'activated' => "Promotion '{$promoTitle}'{$codeText} is now active and available for guests."
    ];
    
    return createRoleNotifications(
        ['admin', 'manager'],
        'promotion',
        $titles[$processType] ?? 'Promotion Update',
        $messages[$processType] ?? "Promotion update for {$promoTitle}",
        [
            'related_id' => $promoId,
            'related_type' => 'promotion',
            'priority' => 'low',
            'action_url' => '/bayawanhotel/admin/admin-promotions.php'
        ]
    );
}

// ============================================
// STAFF NOTIFICATIONS - 5 Categories
// ============================================

/**
 * Notify staff about bookings assigned to them
 * Categories: Bookings
 * Processes: new, updated, cancelled bookings assigned to staff
 * 
 * @param int $bookingId Booking ID
 * @param string $processType Type of booking process (assigned, updated, cancelled)
 * @param string $guestName Guest name
 * @param string $checkInDate Check-in date
 * @param array $staffIds Array of staff user IDs to notify
 * @return int Number of staff notified
 */
function notifyStaffBookingAssignment($bookingId, $processType, $guestName, $checkInDate, $staffIds = []) {
    $titles = [
        'assigned' => 'New Booking Assigned to You',
        'updated' => 'Assigned Booking Updated',
        'cancelled' => 'Assigned Booking Cancelled'
    ];
    
    $formattedDate = date('M d, Y', strtotime($checkInDate));
    
    $messages = [
        'assigned' => "You have been assigned to handle the booking for {$guestName} on {$formattedDate}.",
        'updated' => "The booking for {$guestName} on {$formattedDate} (assigned to you) has been updated.",
        'cancelled' => "The booking for {$guestName} on {$formattedDate} (assigned to you) has been cancelled."
    ];
    
    $count = 0;
    foreach ($staffIds as $staffId) {
        $result = createNotification($staffId, 'booking', 
            $titles[$processType] ?? 'Booking Assignment Update',
            $messages[$processType] ?? "Booking update for {$guestName}",
            [
                'related_id' => $bookingId,
                'related_type' => 'booking',
                'priority' => $processType === 'cancelled' ? 'high' : 'medium',
                'action_url' => '/bayawanhotel/staff/staff-bookings.php'
            ]
        );
        if ($result) $count++;
    }
    return $count;
}

/**
 * Notify staff about new food orders placed
 * Categories: Food Orders
 * Processes: new orders placed, need preparation or delivery
 * 
 * @param int $orderId Order ID
 * @param string $processType Type of order process (new_order, preparation_ready, delivered)
 * @param string $guestName Guest name
 * @param string $roomNumber Room number
 * @param string $foodItems Summary of food items ordered
 * @return int Number of staff notified
 */
function notifyStaffFoodOrderAssignment($orderId, $processType, $guestName, $roomNumber = '', $foodItems = '') {
    $location = $roomNumber ? " (Room {$roomNumber})" : ' (Dine-in)';
    $itemsText = $foodItems ? " Order: {$foodItems}" : '';
    
    $titles = [
        'new_order' => 'New Food Order - Needs Preparation',
        'preparation_ready' => 'Food Order Ready for Delivery',
        'delivered' => 'Food Order Delivered'
    ];
    
    $messages = [
        'new_order' => "New order from {$guestName}{$location}.{$itemsText} Please prepare immediately.",
        'preparation_ready' => "Order from {$guestName}{$location} is ready. Please deliver to guest.",
        'delivered' => "Order from {$guestName}{$location} has been successfully delivered."
    ];
    
    return createRoleNotifications(
        ['admin', 'manager', 'receptionist'],
        'food_order',
        $titles[$processType] ?? 'Food Order Update',
        $messages[$processType] ?? "Food order update from {$guestName}",
        [
            'related_id' => $orderId,
            'related_type' => 'food_order',
            'priority' => 'medium',
            'action_url' => '/bayawanhotel/staff/staff-foods-orders.php'
        ]
    );
}

/**
 * Notify staff about payment confirmations or verifications needed
 * Categories: Payments
 * Processes: payments confirmed, require verification
 * 
 * @param int $paymentId Payment ID
 * @param string $processType Type of payment process (confirmed, needs_verification, verified)
 * @param string $guestName Guest name
 * @param float $amount Payment amount
 * @param string $paymentMethod Payment method
 * @param array $staffIds Specific staff to notify (optional)
 * @return int Number of staff notified
 */
function notifyStaffPaymentUpdate($paymentId, $processType, $guestName, $amount = 0, $paymentMethod = '', $staffIds = []) {
    $formattedAmount = '₱' . number_format($amount, 2);
    $methodText = $paymentMethod ? " via {$paymentMethod}" : '';
    
    $titles = [
        'confirmed' => 'Payment Confirmed - Action May Be Needed',
        'needs_verification' => 'Payment Requires Verification',
        'verified' => 'Payment Verified Successfully'
    ];
    
    $messages = [
        'confirmed' => "Payment of {$formattedAmount}{$methodText} from {$guestName} has been confirmed. Please update records if needed.",
        'needs_verification' => "URGENT: Payment of {$formattedAmount}{$methodText} from {$guestName} requires manual verification.",
        'verified' => "Payment of {$formattedAmount} from {$guestName} has been verified and approved."
    ];
    
    $priority = ($processType === 'needs_verification') ? 'high' : 'medium';
    
    if (!empty($staffIds)) {
        $count = 0;
        foreach ($staffIds as $staffId) {
            $result = createNotification($staffId, 'payment',
                $titles[$processType] ?? 'Payment Update',
                $messages[$processType] ?? "Payment update for {$guestName}",
                [
                    'related_id' => $paymentId,
                    'related_type' => 'payment',
                    'priority' => $priority,
                    'action_url' => '/bayawanhotel/staff/staff-dashboard.php'
                ]
            );
            if ($result) $count++;
        }
        return $count;
    }
    
    return createRoleNotifications(
        ['admin', 'manager', 'receptionist'],
        'payment',
        $titles[$processType] ?? 'Payment Update',
        $messages[$processType] ?? "Payment update for {$guestName}",
        [
            'related_id' => $paymentId,
            'related_type' => 'payment',
            'priority' => $priority,
            'action_url' => '/bayawanhotel/staff/staff-dashboard.php'
        ]
    );
}

/**
 * Notify staff about assigned maintenance tasks or updates
 * Categories: Maintenance
 * Processes: assigned maintenance tasks, updates
 * 
 * @param int $requestId Maintenance request ID
 * @param string $processType Type of maintenance process (assigned, in_progress, completed)
 * @param string $issueType Type of issue
 * @param string $roomNumber Room number
 * @param array $staffIds Array of staff IDs to notify
 * @param string $priority Request priority
 * @return int Number of staff notified
 */
function notifyStaffMaintenanceAssignment($requestId, $processType, $issueType, $roomNumber, $staffIds, $priority = 'medium') {
    $location = $roomNumber ? " in Room {$roomNumber}" : '';
    
    $titles = [
        'assigned' => 'New Maintenance Task Assigned to You',
        'in_progress' => 'Maintenance Task In Progress',
        'completed' => 'Maintenance Task Completed'
    ];
    
    $messages = [
        'assigned' => "You have been assigned to handle a {$issueType} issue{$location}. Priority: {$priority}. Please attend to this as soon as possible.",
        'in_progress' => "The {$issueType} maintenance{$location} you are working on is now in progress.",
        'completed' => "The {$issueType} maintenance{$location} has been marked as completed. Thank you for your work."
    ];
    
    $count = 0;
    foreach ($staffIds as $staffId) {
        $result = createNotification($staffId, 'maintenance',
            $titles[$processType] ?? 'Maintenance Task Update',
            $messages[$processType] ?? "Maintenance update for {$issueType}",
            [
                'related_id' => $requestId,
                'related_type' => 'maintenance_request',
                'priority' => $priority,
                'action_url' => '/bayawanhotel/staff/staff-maintenance.php'
            ]
        );
        if ($result) $count++;
    }
    return $count;
}

/**
 * Notify staff about event schedules, updates, and assigned responsibilities
 * Categories: Events
 * Processes: event schedules, updates, assigned responsibilities
 * 
 * @param int $eventId Event ID
 * @param string $processType Type of event process (scheduled, updated, assigned_responsibility, cancelled)
 * @param string $eventName Event name
 * @param string $eventDate Event date
 * @param array $staffIds Staff IDs to notify (for specific assignments)
 * @param string $responsibility Specific responsibility assigned
 * @return int Number of staff notified
 */
function notifyStaffEventAssignment($eventId, $processType, $eventName, $eventDate, $staffIds = [], $responsibility = '') {
    $formattedDate = date('M d, Y', strtotime($eventDate));
    $responsibilityText = $responsibility ? " Your responsibility: {$responsibility}." : '';
    
    $titles = [
        'scheduled' => 'New Event Scheduled',
        'updated' => 'Event Schedule Updated',
        'assigned_responsibility' => 'Event Responsibility Assigned to You',
        'cancelled' => 'Event Cancelled'
    ];
    
    $messages = [
        'scheduled' => "New event '{$eventName}' scheduled for {$formattedDate}.{$responsibilityText}",
        'updated' => "Event '{$eventName}' on {$formattedDate} has been updated. Please review the changes.{$responsibilityText}",
        'assigned_responsibility' => "You have been assigned responsibilities for event '{$eventName}' on {$formattedDate}.{$responsibilityText}",
        'cancelled' => "Event '{$eventName}' scheduled for {$formattedDate} has been cancelled.{$responsibilityText}"
    ];
    
    $priority = ($processType === 'cancelled') ? 'high' : 'medium';
    
    if (!empty($staffIds)) {
        $count = 0;
        foreach ($staffIds as $staffId) {
            $result = createNotification($staffId, 'event',
                $titles[$processType] ?? 'Event Update',
                $messages[$processType] ?? "Event update for {$eventName}",
                [
                    'related_id' => $eventId,
                    'related_type' => 'event',
                    'priority' => $priority,
                    'action_url' => '/bayawanhotel/staff/staff-event-bookings.php'
                ]
            );
            if ($result) $count++;
        }
        return $count;
    }
    
    return createRoleNotifications(
        ['admin', 'manager', 'receptionist'],
        'event',
        $titles[$processType] ?? 'Event Update',
        $messages[$processType] ?? "Event update for {$eventName}",
        [
            'related_id' => $eventId,
            'related_type' => 'event',
            'priority' => $priority,
            'action_url' => '/bayawanhotel/staff/staff-event-bookings.php'
        ]
    );
}

// ============================================
// USER NOTIFICATIONS - 3 Categories
// ============================================

/**
 * Notify users when their bookings are confirmed, updated, or cancelled
 * Categories: Bookings
 * Processes: confirmed, updated, cancelled
 * 
 * @param int $userId User ID
 * @param int $bookingId Booking ID
 * @param string $processType Type of booking process (confirmed, updated, cancelled, reminder)
 * @param string $details Additional details about the booking
 * @param string $checkInDate Check-in date (for reminders)
 * @return bool|int Notification ID
 */
function notifyUserBookingUpdate($userId, $bookingId, $processType, $details = '', $checkInDate = '') {
    $titles = [
        'confirmed' => 'Your Booking is Confirmed!',
        'updated' => 'Your Booking Has Been Updated',
        'cancelled' => 'Your Booking Has Been Cancelled',
        'reminder' => 'Upcoming Stay Reminder'
    ];
    
    $messages = [
        'confirmed' => "Great news! Your booking has been confirmed. {$details} We look forward to welcoming you to Bayawan Bai Hotel!",
        'updated' => "Your booking details have been updated. {$details} Please review the changes.",
        'cancelled' => "Your booking has been cancelled. {$details} If you have any questions, please contact us.",
        'reminder' => $checkInDate ? "Reminder: Your stay at Bayawan Bai Hotel is coming up on " . date('M d, Y', strtotime($checkInDate)) . ". {$details} We can't wait to see you!" : "Your stay is coming up soon!"
    ];
    
    $priority = ($processType === 'cancelled') ? 'high' : 'medium';
    
    return createNotification($userId, 'booking',
        $titles[$processType] ?? 'Booking Update',
        $messages[$processType] ?? "Your booking has been updated. {$details}",
        [
            'related_id' => $bookingId,
            'related_type' => 'booking',
            'priority' => $priority,
            'action_url' => '/bayawanhotel/user/my-bookings.php'
        ]
    );
}

/**
 * Notify users about food order status (placed, preparing, delivered)
 * Categories: Food Orders
 * Processes: placed, preparing, ready, delivered
 * 
 * @param int $userId User ID
 * @param int $orderId Order ID
 * @param string $processType Type of order process (placed, preparing, ready, delivered, cancelled)
 * @param string $foodItems Summary of ordered items
 * @param string $roomNumber Room number for delivery
 * @return bool|int Notification ID
 */
function notifyUserFoodOrderStatus($userId, $orderId, $processType, $foodItems = '', $roomNumber = '') {
    $itemsText = $foodItems ? " Items: {$foodItems}." : '';
    $roomText = $roomNumber ? " We'll deliver to Room {$roomNumber}." : '';
    
    $titles = [
        'placed' => 'Your Order Has Been Placed',
        'preparing' => 'Your Order is Being Prepared',
        'ready' => 'Your Order is Ready!',
        'delivered' => 'Your Order Has Been Delivered',
        'cancelled' => 'Your Order Has Been Cancelled'
    ];
    
    $messages = [
        'placed' => "Thank you for your order!{$itemsText} Our kitchen has received it and will start preparing soon.",
        'preparing' => "Good news! Our chefs are now preparing your order.{$itemsText} It will be ready shortly.",
        'ready' => "Your order is ready!{$itemsText}{$roomText} Please collect it or wait for delivery to your room.",
        'delivered' => "Enjoy your meal! Your order has been delivered to you.{$itemsText} Thank you for dining with us!",
        'cancelled' => "Your order has been cancelled.{$itemsText} If you did not request this, please contact the front desk."
    ];
    
    $priority = ($processType === 'cancelled') ? 'high' : 'low';
    
    return createNotification($userId, 'food_order',
        $titles[$processType] ?? 'Order Update',
        $messages[$processType] ?? "Your order status has been updated.{$itemsText}",
        [
            'related_id' => $orderId,
            'related_type' => 'food_order',
            'priority' => $priority,
            'action_url' => '/bayawanhotel/user/my-food-orders.php'
        ]
    );
}

/**
 * Notify users about new events, updates, or reminders
 * Categories: Events
 * Processes: new events, updates, reminders
 * 
 * @param int $userId User ID
 * @param int $eventId Event ID
 * @param string $processType Type of event process (invitation, reminder, cancelled, updated)
 * @param string $eventName Event name
 * @param string $eventDate Event date
 * @param string $eventDetails Event details/location
 * @return bool|int Notification ID
 */
function notifyUserEventUpdate($userId, $eventId, $processType, $eventName, $eventDate = '', $eventDetails = '') {
    $dateText = $eventDate ? " on " . date('M d, Y', strtotime($eventDate)) : '';
    $detailsText = $eventDetails ? " {$eventDetails}" : '';
    
    $titles = [
        'invitation' => "You're Invited: {$eventName}",
        'reminder' => "Reminder: {$eventName} Coming Up",
        'cancelled' => "Event Cancelled: {$eventName}",
        'updated' => "Event Updated: {$eventName}"
    ];
    
    $messages = [
        'invitation' => "You're invited to '{$eventName}'{$dateText}!{$detailsText} We hope you can join us for this special event.",
        'reminder' => "Just a friendly reminder that '{$eventName}' is coming up{$dateText}.{$detailsText} Don't forget to attend!",
        'cancelled' => "We regret to inform you that '{$eventName}'{$dateText} has been cancelled.{$detailsText} We apologize for any inconvenience.",
        'updated' => "There have been some changes to '{$eventName}'{$dateText}.{$detailsText} Please review the updated details."
    ];
    
    $priority = ($processType === 'cancelled') ? 'high' : 'medium';
    
    return createNotification($userId, 'event',
        $titles[$processType] ?? 'Event Update',
        $messages[$processType] ?? "Event update for {$eventName}",
        [
            'related_id' => $eventId,
            'related_type' => 'event',
            'priority' => $priority,
            'action_url' => '/bayawanhotel/user/my-event-bookings.php'
        ]
    );
}

/**
 * Send promotional notifications to users
 * Categories: Promotions (for users - optional)
 * Processes: new promotions available
 * 
 * @param int $userId User ID (or array of user IDs)
 * @param int $promoId Promotion ID
 * @param string $promoTitle Promotion title
 * @param string $promoDescription Promotion description
 * @param string $promoCode Promotion code
 * @return bool|int Notification ID or count
 */
function notifyUserNewPromotion($userId, $promoId, $promoTitle, $promoDescription = '', $promoCode = '') {
    $codeText = $promoCode ? " Use code: {$promoCode}." : '';
    
    $notification = [
        'related_id' => $promoId,
        'related_type' => 'promotion',
        'priority' => 'low',
        'action_url' => '/bayawanhotel/promotions.php'
    ];
    
    if (is_array($userId)) {
        $count = 0;
        foreach ($userId as $uid) {
            $result = createNotification($uid, 'promotion',
                "Special Offer: {$promoTitle}",
                "{$promoDescription}{$codeText} Book now and enjoy exclusive savings!",
                $notification
            );
            if ($result) $count++;
        }
        return $count;
    }
    
    return createNotification($userId, 'promotion',
        "Special Offer: {$promoTitle}",
        "{$promoDescription}{$codeText} Book now and enjoy exclusive savings!",
        $notification
    );
}
