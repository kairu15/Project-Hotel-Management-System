<?php
require_once '../includes/config.php';
require_once '../includes/notifications.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

require_once '../includes/user-header.php';
$pageTitle = __('My Dashboard');

$db = getDB();
$userId = getUserId();

// Get user's bookings
$bookingsStmt = $db->prepare("
    SELECT b.*, rc.category_name, r.room_number 
    FROM bookings b 
    JOIN room_categories rc ON b.category_id = rc.category_id 
    LEFT JOIN rooms r ON b.room_id = r.room_id 
    WHERE b.user_id = ? AND b.is_deleted = 0 AND b.is_archived = 0
    ORDER BY b.created_at DESC
");
$bookingsStmt->execute([$userId]);
$bookings = $bookingsStmt->fetchAll();

// Count statistics
$totalBookings = count($bookings);
$upcomingStays = array_filter($bookings, function($b) { 
    return $b['status'] === 'confirmed' && strtotime($b['check_in']) >= strtotime('today'); 
});
$completedStays = array_filter($bookings, function($b) { 
    return $b['status'] === 'checked_out'; 
});
$cancelledBookings = array_filter($bookings, function($b) { 
    return $b['status'] === 'cancelled'; 
});
$checkedInStays = array_filter($bookings, function($b) { 
    return $b['status'] === 'checked_in'; 
});

// Calculate total spent on room bookings
$totalRoomSpent = array_sum(array_map(function($b) { 
    return in_array($b['status'], ['confirmed', 'checked_in', 'checked_out']) ? $b['total_amount'] : 0; 
}, $bookings));

// Get event bookings statistics
$eventStmt = $db->prepare("
    SELECT status FROM event_bookings WHERE user_id = ?
");
$eventStmt->execute([$userId]);
$eventBookings = $eventStmt->fetchAll();

$eventPending = count(array_filter($eventBookings, function($b) { return $b['status'] === 'pending'; }));
$eventConfirmed = count(array_filter($eventBookings, function($b) { return $b['status'] === 'confirmed'; }));
$eventCompleted = count(array_filter($eventBookings, function($b) { return $b['status'] === 'completed'; }));
$eventCancelled = count(array_filter($eventBookings, function($b) { return $b['status'] === 'cancelled'; }));

// Get food orders statistics
$foodStmt = $db->prepare("
    SELECT status, total_price FROM food_orders WHERE user_id = ?
");
$foodStmt->execute([$userId]);
$foodOrders = $foodStmt->fetchAll();

$foodPending = count(array_filter($foodOrders, function($o) { return in_array($o['status'], ['pending', 'preparing']); }));
$foodDelivered = count(array_filter($foodOrders, function($o) { return $o['status'] === 'delivered'; }));
$foodTotalSpent = array_sum(array_map(function($o) { return $o['status'] !== 'cancelled' ? $o['total_price'] : 0; }, $foodOrders));

// Get recent event bookings for display
$recentEventStmt = $db->prepare("
    SELECT eb.*, es.space_name, es.capacity
    FROM event_bookings eb
    JOIN event_spaces es ON eb.space_id = es.space_id
    WHERE eb.user_id = ?
    ORDER BY eb.created_at DESC
    LIMIT 5
");
$recentEventStmt->execute([$userId]);
$recentEventBookings = $recentEventStmt->fetchAll();

// Get recent food orders for display
$recentFoodStmt = $db->prepare("
    SELECT fo.*, mi.item_name, mi.image, mc.category_name
    FROM food_orders fo
    JOIN menu_items mi ON fo.food_id = mi.item_id
    JOIN menu_categories mc ON mi.cat_id = mc.cat_id
    WHERE fo.user_id = ?
    ORDER BY fo.created_at DESC
    LIMIT 5
");
$recentFoodStmt->execute([$userId]);
$recentFoodOrders = $recentFoodStmt->fetchAll();

// Get user profile
$userStmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
$userStmt->execute([$userId]);
$user = $userStmt->fetch();

// Get notifications data
$unreadNotificationsCount = getUnreadCount($userId);
$recentNotifications = getNotifications($userId, 'all', 5, 0);
foreach ($recentNotifications as &$notification) {
    $notification['time_ago'] = getTimeAgo($notification['created_at']);
    $notification['icon'] = getNotificationIcon($notification['type']);
    $notification['color'] = getNotificationColor($notification['type'], $notification['priority']);
}

// Get inbox unread count (recent reservations)
$inboxCount = $db->prepare("
    SELECT COUNT(*) FROM (
        SELECT booking_id as id FROM bookings 
        WHERE user_id = ? AND is_archived = 0 AND is_deleted = 0 
        AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        UNION ALL
        SELECT event_booking_id as id FROM event_bookings 
        WHERE user_id = ? AND is_archived = 0 AND is_deleted = 0 
        AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ) as recent_items
");
$inboxCount->execute([$userId, $userId]);
$newInboxItems = $inboxCount->fetchColumn();

// Get recent inbox items
$recentInboxStmt = $db->prepare("
    SELECT 'room' as type, b.booking_id as id, b.status, b.created_at, rc.category_name as item_name, b.total_amount
    FROM bookings b
    JOIN room_categories rc ON b.category_id = rc.category_id
    WHERE b.user_id = ? AND b.is_archived = 0 AND b.is_deleted = 0
    ORDER BY b.created_at DESC LIMIT 3
");
$recentInboxStmt->execute([$userId]);
$recentInboxItems = $recentInboxStmt->fetchAll();

// Get upcoming check-ins (next 48 hours)
$upcomingCheckinsStmt = $db->prepare("
    SELECT b.*, rc.category_name, r.room_number 
    FROM bookings b 
    JOIN room_categories rc ON b.category_id = rc.category_id 
    LEFT JOIN rooms r ON b.room_id = r.room_id 
    WHERE b.user_id = ? AND b.status = 'confirmed' 
    AND b.check_in BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 2 DAY)
    ORDER BY b.check_in ASC
");
$upcomingCheckinsStmt->execute([$userId]);
$upcomingCheckins = $upcomingCheckinsStmt->fetchAll();

// Get upcoming check-outs (next 48 hours)
$upcomingCheckoutsStmt = $db->prepare("
    SELECT b.*, rc.category_name, r.room_number 
    FROM bookings b 
    JOIN room_categories rc ON b.category_id = rc.category_id 
    LEFT JOIN rooms r ON b.room_id = r.room_id 
    WHERE b.user_id = ? AND b.status = 'checked_in' 
    AND b.check_out BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 2 DAY)
    ORDER BY b.check_out ASC
");
$upcomingCheckoutsStmt->execute([$userId]);
$upcomingCheckouts = $upcomingCheckoutsStmt->fetchAll();

// Get upcoming calendar events for mini calendar
$today = date('Y-m-d');
$calendarEventsStmt = $db->prepare("
    SELECT 
        'booking' as event_type,
        b.check_in as event_date,
        b.category_id as item_id,
        rc.category_name as item_name,
        b.status,
        CONCAT('Check-in: ', rc.category_name) as title
    FROM bookings b
    JOIN room_categories rc ON b.category_id = rc.category_id
    WHERE b.user_id = ? AND b.status IN ('confirmed', 'checked_in') AND b.check_in >= ?
    UNION ALL
    SELECT 
        'event' as event_type,
        eb.event_date as event_date,
        eb.space_id as item_id,
        es.space_name as item_name,
        eb.status,
        CONCAT('Event: ', eb.event_type) as title
    FROM event_bookings eb
    JOIN event_spaces es ON eb.space_id = es.space_id
    WHERE eb.user_id = ? AND eb.status IN ('pending', 'confirmed') AND eb.event_date >= ?
    UNION ALL
    SELECT 
        'food' as event_type,
        DATE(fo.created_at) as event_date,
        fo.food_id as item_id,
        mi.item_name as item_name,
        fo.status,
        CONCAT('Food Order: ', mi.item_name) as title
    FROM food_orders fo
    JOIN menu_items mi ON fo.food_id = mi.item_id
    WHERE fo.user_id = ? AND fo.status IN ('pending', 'preparing', 'ready') 
    AND DATE(fo.created_at) >= ?
    ORDER BY event_date ASC
    LIMIT 5
");
$calendarEventsStmt->execute([$userId, $today, $userId, $today, $userId, $today]);
$upcomingCalendarEvents = $calendarEventsStmt->fetchAll();

// Get archive and trash counts
$archivedCount = $db->prepare("
    SELECT 
        (SELECT COUNT(*) FROM bookings WHERE user_id = ? AND is_archived = 1 AND is_deleted = 0) +
        (SELECT COUNT(*) FROM event_bookings WHERE user_id = ? AND is_archived = 1 AND is_deleted = 0) as total
");
$archivedCount->execute([$userId, $userId]);
$totalArchived = $archivedCount->fetchColumn() ?: 0;

$trashedCount = $db->prepare("
    SELECT 
        (SELECT COUNT(*) FROM bookings WHERE user_id = ? AND is_deleted = 1) +
        (SELECT COUNT(*) FROM event_bookings WHERE user_id = ? AND is_deleted = 1) as total
");
$trashedCount->execute([$userId, $userId]);
$totalTrashed = $trashedCount->fetchColumn() ?: 0;

// Calculate profile completion percentage
$profileFields = [
    'first_name' => $user['first_name'] ?? '',
    'last_name' => $user['last_name'] ?? '',
    'email' => $user['email'] ?? '',
    'phone' => $user['phone'] ?? '',
    'address' => $user['address'] ?? '',
    'city' => $user['city'] ?? '',
    'country' => $user['country'] ?? '',
    'profile_picture' => $user['profile_picture'] ?? ''
];
$filledFields = count(array_filter($profileFields, function($v) { return !empty(trim($v)); }));
$profileCompletion = round(($filledFields / count($profileFields)) * 100);

// Get account statistics
$memberSince = date('M Y', strtotime($user['created_at'] ?? 'now'));
$lastLogin = isset($user['last_login']) ? date('M d, Y g:i A', strtotime($user['last_login'])) : 'Never';

// Calculate total spending across all categories
$totalSpent = $totalRoomSpent + $foodTotalSpent;
?>

<!-- Dashboard Overview -->
<div style="margin-bottom: 30px;">
    <h1 style="font-size: 32px; margin-bottom: 10px;"><?php echo __('Welcome back'); ?>, <?php echo htmlspecialchars($user['first_name']); ?>!</h1>
    <p style="color: #666;"><?php echo __('Here\'s what\'s happening with your account'); ?></p>
</div>

<!-- Profile Completion Progress -->
<?php if ($profileCompletion < 100): ?>
<div style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border: 1px solid #bae6fd; border-radius: 12px; padding: 20px 25px; margin-bottom: 30px; display: flex; align-items: center; gap: 20px; flex-wrap: wrap;">
    <div style="width: 60px; height: 60px; background: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
        <i class="fas fa-user-check" style="font-size: 24px; color: white;"></i>
    </div>
    <div style="flex: 1; min-width: 250px;">
        <h3 style="margin: 0 0 8px 0; font-size: 18px; color: var(--dark-color);"><?php echo __('Complete Your Profile'); ?></h3>
        <p style="margin: 0 0 12px 0; font-size: 14px; color: #666;"><?php echo __('Your profile is'); ?> <strong><?php echo $profileCompletion; ?>%</strong> <?php echo __('complete. Add missing information for a better experience.'); ?></p>
        <div style="background: #e5e7eb; height: 8px; border-radius: 4px; overflow: hidden;">
            <div style="background: linear-gradient(90deg, var(--primary-color), var(--secondary-color)); height: 100%; width: <?php echo $profileCompletion; ?>%; border-radius: 4px; transition: width 0.5s ease;"></div>
        </div>
    </div>
    <a href="profile.php" class="btn btn-primary" style="flex-shrink: 0;">
        <i class="fas fa-edit" style="margin-right: 8px;"></i><?php echo __('Update Profile'); ?>
    </a>
</div>
<?php endif; ?>

<!-- Upcoming Check-in/Check-out Alerts -->
<?php if (count($upcomingCheckins) > 0 || count($upcomingCheckouts) > 0): ?>
<div style="margin-bottom: 30px;">
    <h3 style="font-size: 20px; margin-bottom: 20px;"><i class="fas fa-bell" style="color: var(--warning-color); margin-right: 10px;"></i><?php echo __('Upcoming Alerts'); ?></h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
        
        <?php foreach ($upcomingCheckins as $checkin): 
            $daysUntil = ceil((strtotime($checkin['check_in']) - strtotime('today')) / 86400);
            $daysText = $daysUntil == 0 ? 'Today' : ($daysUntil == 1 ? 'Tomorrow' : $daysUntil . ' days');
        ?>
        <div style="background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); border-left: 4px solid var(--success-color); border-radius: 10px; padding: 20px; display: flex; align-items: center; gap: 15px;">
            <div style="width: 50px; height: 50px; background: var(--success-color); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <i class="fas fa-sign-in-alt" style="font-size: 20px; color: white;"></i>
            </div>
            <div style="flex: 1;">
                <h4 style="margin: 0 0 5px 0; font-size: 16px; color: var(--dark-color);"><?php echo __('Check-in'); ?> - <?php echo htmlspecialchars($checkin['category_name']); ?></h4>
                <p style="margin: 0; font-size: 13px; color: #666;">
                    <i class="fas fa-calendar" style="margin-right: 5px;"></i><?php echo formatDate($checkin['check_in']); ?>
                    <?php if ($checkin['room_number']): ?>• Room <?php echo htmlspecialchars($checkin['room_number']); ?><?php endif; ?>
                </p>
                <span style="display: inline-block; margin-top: 8px; padding: 4px 10px; background: var(--success-color); color: white; border-radius: 12px; font-size: 12px; font-weight: 600;">
                    <?php echo $daysText; ?>
                </span>
            </div>
            <a href="checkin.php" class="btn btn-sm btn-outline" style="flex-shrink: 0;"><?php echo __('Details'); ?></a>
        </div>
        <?php endforeach; ?>
        
        <?php foreach ($upcomingCheckouts as $checkout): 
            $daysUntil = ceil((strtotime($checkout['check_out']) - strtotime('today')) / 86400);
            $daysText = $daysUntil == 0 ? 'Today' : ($daysUntil == 1 ? 'Tomorrow' : $daysUntil . ' days');
        ?>
        <div style="background: linear-gradient(135deg, #fff3cd 0%, #ffeeba 100%); border-left: 4px solid var(--warning-color); border-radius: 10px; padding: 20px; display: flex; align-items: center; gap: 15px;">
            <div style="width: 50px; height: 50px; background: var(--warning-color); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <i class="fas fa-sign-out-alt" style="font-size: 20px; color: #333;"></i>
            </div>
            <div style="flex: 1;">
                <h4 style="margin: 0 0 5px 0; font-size: 16px; color: var(--dark-color);"><?php echo __('Check-out'); ?> - <?php echo htmlspecialchars($checkout['category_name']); ?></h4>
                <p style="margin: 0; font-size: 13px; color: #666;">
                    <i class="fas fa-calendar" style="margin-right: 5px;"></i><?php echo formatDate($checkout['check_out']); ?>
                    <?php if ($checkout['room_number']): ?>• Room <?php echo htmlspecialchars($checkout['room_number']); ?><?php endif; ?>
                </p>
                <span style="display: inline-block; margin-top: 8px; padding: 4px 10px; background: var(--warning-color); color: #333; border-radius: 12px; font-size: 12px; font-weight: 600;">
                    <?php echo $daysText; ?>
                </span>
            </div>
            <a href="checkout.php" class="btn btn-sm btn-outline" style="flex-shrink: 0;"><?php echo __('Details'); ?></a>
        </div>
        <?php endforeach; ?>
        
    </div>
</div>
<?php endif; ?>

<!-- Quick Actions -->
<div style="margin-bottom: 30px;">
    <h3 style="font-size: 20px; margin-bottom: 20px;"><i class="fas fa-bolt" style="color: var(--warning-color); margin-right: 10px;"></i>Quick Actions</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
        <a href="../booking.php" class="quick-action-card primary">
            <i class="fas fa-plus-circle" style="font-size: 30px; margin-bottom: 15px;"></i>
            <h4>New Booking</h4>
            <p>Book your next stay.</p>
        </a>

        <a href="../events.php" class="quick-action-card white">
            <i class="fas fa-calendar-plus" style="font-size: 30px; margin-bottom: 15px; color: var(--primary-color);"></i>
            <h4>Book Event</h4>
            <p>Reserve event space.</p>
        </a>

        <a href="../dining.php" class="quick-action-card white">
            <i class="fas fa-utensils" style="font-size: 30px; margin-bottom: 15px; color: var(--primary-color);"></i>
            <h4>Order Food</h4>
            <p>Browse the dining menu.</p>
        </a>

        <a href="profile.php" class="quick-action-card white">
            <i class="fas fa-user-edit" style="font-size: 30px; margin-bottom: 15px; color: var(--primary-color);"></i>
            <h4>Update Profile</h4>
            <p>Manage your details.</p>
        </a>

        <a href="../contact.php" class="quick-action-card white">
            <i class="fas fa-headset" style="font-size: 30px; margin-bottom: 15px; color: var(--primary-color);"></i>
            <h4>Support</h4>
            <p>Get help anytime.</p>
        </a>

        <a href="inbox.php" class="quick-action-card white" style="position: relative;">
            <i class="fas fa-inbox" style="font-size: 30px; margin-bottom: 15px; color: var(--primary-color);"></i>
            <h4>My Inbox</h4>
            <p>View all reservations.</p>
            <?php if ($newInboxItems > 0): ?>
            <span style="position: absolute; top: 15px; right: 15px; background: var(--danger-color); color: white; font-size: 12px; padding: 3px 8px; border-radius: 10px; font-weight: 600;"><?php echo $newInboxItems; ?> new</span>
            <?php endif; ?>
        </a>

        <a href="user-calendar.php" class="quick-action-card white">
            <i class="fas fa-calendar-alt" style="font-size: 30px; margin-bottom: 15px; color: var(--primary-color);"></i>
            <h4>My Calendar</h4>
            <p>View your schedule.</p>
        </a>
    </div>
</div>

<!-- Stats Cards - Enhanced -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon" style="background-color: rgba(54,125,138,0.1); color: var(--primary-color);">
                <i class="fas fa-calendar"></i>
            </div>
            <span class="stat-label"><?php echo __('Lifetime'); ?></span>
        </div>
        <div class="stat-value"><?php echo $totalBookings; ?></div>
        <p class="stat-desc"><?php echo __('Total Bookings'); ?></p>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon" style="background-color: rgba(255,193,7,0.1); color: var(--warning-color);">
                <i class="fas fa-clock"></i>
            </div>
            <span class="stat-label" style="color: var(--warning-color); background-color: rgba(255,193,7,0.1);"><?php echo __('Active'); ?></span>
        </div>
        <div class="stat-value" style="color: var(--warning-color);"><?php echo count($upcomingStays); ?></div>
        <p class="stat-desc"><?php echo __('Upcoming Stays'); ?></p>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon" style="background-color: rgba(40,167,69,0.1); color: var(--success-color);">
                <i class="fas fa-check-circle"></i>
            </div>
            <span class="stat-label"><?php echo __('History'); ?></span>
        </div>
        <div class="stat-value" style="color: var(--success-color);"><?php echo count($completedStays); ?></div>
        <p class="stat-desc"><?php echo __('Completed Stays'); ?></p>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon" style="background-color: rgba(220,53,69,0.1); color: var(--danger-color);">
                <i class="fas fa-gift"></i>
            </div>
            <span class="stat-label"><?php echo __('Rewards'); ?></span>
        </div>
        <div class="stat-value" style="color: var(--danger-color);"><?php echo number_format($user['loyalty_points']); ?></div>
        <p class="stat-desc"><?php echo __('Loyalty Points'); ?></p>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon" style="background-color: rgba(108,117,125,0.1); color: #6c757d;">
                <i class="fas fa-times-circle"></i>
            </div>
            <span class="stat-label" style="color: #6c757d; background-color: rgba(108,117,125,0.1);"><?php echo __('Cancelled'); ?></span>
        </div>
        <div class="stat-value" style="color: #6c757d;"><?php echo count($cancelledBookings); ?></div>
        <p class="stat-desc"><?php echo __('Cancelled Bookings'); ?></p>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon" style="background-color: rgba(23,162,184,0.1); color: var(--info-color);">
                <i class="fas fa-door-open"></i>
            </div>
            <span class="stat-label" style="color: var(--info-color); background-color: rgba(23,162,184,0.1);"><?php echo __('Current'); ?></span>
        </div>
        <div class="stat-value" style="color: var(--info-color);"><?php echo count($checkedInStays); ?></div>
        <p class="stat-desc"><?php echo __('Checked In Now'); ?></p>
    </div>
</div>

<!-- Account Overview Stats -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <div class="stat-card" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); color: white;">
        <div class="stat-header">
            <div class="stat-icon" style="background-color: rgba(255,255,255,0.2); color: white;">
                <i class="fas fa-wallet"></i>
            </div>
            <span class="stat-label" style="color: white; background-color: rgba(255,255,255,0.2);"><?php echo __('Total Spent'); ?></span>
        </div>
        <div class="stat-value" style="color: white;"><?php echo formatPrice($totalSpent); ?></div>
        <p class="stat-desc" style="color: rgba(255,255,255,0.8);"><?php echo __('On all bookings & orders'); ?></p>
    </div>
    
    <div class="stat-card" style="background: linear-gradient(135deg, #28a745 0%, #218838 100%); color: white;">
        <div class="stat-header">
            <div class="stat-icon" style="background-color: rgba(255,255,255,0.2); color: white;">
                <i class="fas fa-user-clock"></i>
            </div>
            <span class="stat-label" style="color: white; background-color: rgba(255,255,255,0.2);"><?php echo __('Member Since'); ?></span>
        </div>
        <div class="stat-value" style="color: white; font-size: 22px;"><?php echo $memberSince; ?></div>
        <p class="stat-desc" style="color: rgba(255,255,255,0.8);"><?php echo __('Last login: '); ?><?php echo $lastLogin; ?></p>
    </div>
</div>

<!-- Event Bookings Overview -->
<div style="margin-bottom: 30px;">
    <h3 style="font-size: 20px; margin-bottom: 20px;"><i class="fas fa-calendar-check" style="color: var(--primary-color); margin-right: 10px;"></i><?php echo __('Event Bookings Overview'); ?></h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon" style="background-color: rgba(255,193,7,0.1); color: var(--warning-color);">
                    <i class="fas fa-clock"></i>
                </div>
                <span class="stat-label" style="color: var(--warning-color); background-color: rgba(255,193,7,0.1);"><?php echo __('Pending'); ?></span>
            </div>
            <div class="stat-value" style="color: var(--warning-color);"><?php echo $eventPending; ?></div>
            <p class="stat-desc"><?php echo __('Awaiting confirmation'); ?></p>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon" style="background-color: rgba(40,167,69,0.1); color: var(--success-color);">
                    <i class="fas fa-check-circle"></i>
                </div>
                <span class="stat-label" style="color: var(--success-color); background-color: rgba(40,167,69,0.1);"><?php echo __('Confirmed'); ?></span>
            </div>
            <div class="stat-value" style="color: var(--success-color);"><?php echo $eventConfirmed; ?></div>
            <p class="stat-desc"><?php echo __('Upcoming events'); ?></p>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon" style="background-color: rgba(54,125,138,0.1); color: var(--primary-color);">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <span class="stat-label"><?php echo __('Completed'); ?></span>
            </div>
            <div class="stat-value"><?php echo $eventCompleted; ?></div>
            <p class="stat-desc"><?php echo __('Past events'); ?></p>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon" style="background-color: rgba(220,53,69,0.1); color: var(--danger-color);">
                    <i class="fas fa-times-circle"></i>
                </div>
                <span class="stat-label" style="color: var(--danger-color); background-color: rgba(220,53,69,0.1);"><?php echo __('Cancelled'); ?></span>
            </div>
            <div class="stat-value" style="color: var(--danger-color);"><?php echo $eventCancelled; ?></div>
            <p class="stat-desc"><?php echo __('Cancelled events'); ?></p>
        </div>
    </div>
</div>

<!-- Food Orders Overview -->
<div style="margin-bottom: 30px;">
    <h3 style="font-size: 20px; margin-bottom: 20px;"><i class="fas fa-utensils" style="color: var(--primary-color); margin-right: 10px;"></i><?php echo __('Food Orders Overview'); ?></h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px;">
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon" style="background-color: rgba(255,193,7,0.1); color: var(--warning-color);">
                    <i class="fas fa-clock"></i>
                </div>
                <span class="stat-label" style="color: var(--warning-color); background-color: rgba(255,193,7,0.1);"><?php echo __('Pending'); ?></span>
            </div>
            <div class="stat-value" style="color: var(--warning-color);"><?php echo $foodPending; ?></div>
            <p class="stat-desc"><?php echo __('Orders in progress'); ?></p>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon" style="background-color: rgba(40,167,69,0.1); color: var(--success-color);">
                    <i class="fas fa-check-circle"></i>
                </div>
                <span class="stat-label" style="color: var(--success-color); background-color: rgba(40,167,69,0.1);"><?php echo __('Delivered'); ?></span>
            </div>
            <div class="stat-value" style="color: var(--success-color);"><?php echo $foodDelivered; ?></div>
            <p class="stat-desc"><?php echo __('Completed orders'); ?></p>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon" style="background-color: rgba(54,125,138,0.1); color: var(--primary-color);">
                    <i class="fas fa-wallet"></i>
                </div>
                <span class="stat-label"><?php echo __('Total Spent'); ?></span>
            </div>
            <div class="stat-value"><?php echo formatPrice($foodTotalSpent); ?></div>
            <p class="stat-desc"><?php echo __('On food orders'); ?></p>
        </div>
    </div>
</div>

<!-- Recent Bookings -->
<div class="card" style="margin-bottom: 30px;">
    <div class="card-header">
        <h3><i class="fas fa-history" style="color: var(--primary-color); margin-right: 10px;"></i><?php echo __('Recent Bookings'); ?></h3>
        <a href="my-bookings.php" style="color: var(--primary-color); text-decoration: none; font-size: 14px; font-weight: 600;">
            <?php echo __('View All'); ?> <i class="fas fa-arrow-right" style="margin-left: 5px;"></i>
        </a>
    </div>
    
    <?php if (count($bookings) > 0): ?>
    <div style="padding: 0;">
        <?php foreach (array_slice($bookings, 0, 5) as $booking): 
            $statusColors = [
                'pending' => ['bg' => '#fff3cd', 'text' => '#856404', 'icon' => 'clock'],
                'confirmed' => ['bg' => '#d4edda', 'text' => '#155724', 'icon' => 'check-circle'],
                'checked_in' => ['bg' => '#cce5ff', 'text' => '#004085', 'icon' => 'door-open'],
                'checked_out' => ['bg' => '#e2e3e5', 'text' => '#383d41', 'icon' => 'sign-out-alt'],
                'cancelled' => ['bg' => '#f8d7da', 'text' => '#721c24', 'icon' => 'times-circle']
            ];
            $statusStyle = $statusColors[$booking['status']] ?? $statusColors['pending'];
        ?>
        <div style="padding: 20px 30px; border-bottom: 1px solid var(--gray-light); display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; align-items: center; gap: 20px;">
            <div>
                <h4 style="font-size: 16px; margin-bottom: 5px;"><?php echo htmlspecialchars($booking['category_name']); ?></h4>
                <p style="font-size: 13px; color: #666;">
                    <?php if ($booking['room_number']): ?>
                    Room <?php echo htmlspecialchars($booking['room_number']); ?> • 
                    <?php endif; ?>
                    <?php echo formatDate($booking['check_in']); ?> - <?php echo formatDate($booking['check_out']); ?>
                </p>
            </div>
            <div>
                <p style="font-size: 13px; color: #666; margin-bottom: 3px;"><?php echo __('Booking Ref'); ?></p>
                <p style="font-size: 14px; font-weight: 600;">#BBH-<?php echo $booking['booking_id']; ?></p>
            </div>
            <div>
                <p style="font-size: 13px; color: #666; margin-bottom: 3px;"><?php echo __('Amount'); ?></p>
                <p style="font-size: 14px; font-weight: 600; color: var(--primary-color);"><?php echo formatPrice($booking['total_amount']); ?></p>
            </div>
            <div>
                <span style="display: inline-flex; align-items: center; gap: 5px; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: <?php echo $statusStyle['bg']; ?>; color: <?php echo $statusStyle['text']; ?>;">
                    <i class="fas fa-<?php echo $statusStyle['icon']; ?>"></i>
                    <?php echo ucfirst(str_replace('_', ' ', $booking['status'])); ?>
                </span>
            </div>
            <div>
                <a href="booking-details.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-outline btn-sm">
                    <i class="fas fa-eye"></i> <?php echo __('View'); ?>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div style="padding: 60px; text-align: center;">
        <i class="fas fa-calendar-times" style="font-size: 60px; color: var(--gray-medium); margin-bottom: 20px;"></i>
        <h4 style="font-size: 20px; margin-bottom: 10px;"><?php echo __('No Bookings Yet'); ?></h4>
        <p style="color: #666; margin-bottom: 25px;"><?php echo __('Start exploring our rooms and make your first reservation!'); ?></p>
        <a href="../rooms.php" class="btn btn-primary"><?php echo __('Browse Rooms'); ?></a>
    </div>
    <?php endif; ?>
</div>

<!-- Recent Event Bookings -->
<div class="card" style="margin-bottom: 30px;">
    <div class="card-header">
        <h3><i class="fas fa-calendar-check" style="color: var(--primary-color); margin-right: 10px;"></i><?php echo __('Recent Event Bookings'); ?></h3>
        <a href="my-event-bookings.php" style="color: var(--primary-color); text-decoration: none; font-size: 14px; font-weight: 600;">
            <?php echo __('View All'); ?> <i class="fas fa-arrow-right" style="margin-left: 5px;"></i>
        </a>
    </div>
    
    <?php if (count($recentEventBookings) > 0): ?>
    <div style="padding: 0;">
        <?php foreach ($recentEventBookings as $eventBooking): 
            $eventStatusColors = [
                'pending' => ['bg' => '#fff3cd', 'text' => '#856404', 'icon' => 'clock'],
                'confirmed' => ['bg' => '#d4edda', 'text' => '#155724', 'icon' => 'check-circle'],
                'completed' => ['bg' => '#cce5ff', 'text' => '#004085', 'icon' => 'calendar-check'],
                'cancelled' => ['bg' => '#f8d7da', 'text' => '#721c24', 'icon' => 'times-circle']
            ];
            $eventStatusStyle = $eventStatusColors[$eventBooking['status']] ?? $eventStatusColors['pending'];
        ?>
        <div style="padding: 20px 30px; border-bottom: 1px solid var(--gray-light); display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; align-items: center; gap: 20px;">
            <div>
                <h4 style="font-size: 16px; margin-bottom: 5px;"><?php echo htmlspecialchars($eventBooking['space_name']); ?></h4>
                <p style="font-size: 13px; color: #666;">
                    <i class="fas fa-users" style="margin-right: 5px;"></i><?php echo number_format($eventBooking['capacity']); ?> guests capacity
                    <?php if ($eventBooking['event_type']): ?> • <?php echo htmlspecialchars($eventBooking['event_type']); ?><?php endif; ?>
                </p>
            </div>
            <div>
                <p style="font-size: 13px; color: #666; margin-bottom: 3px;"><?php echo __('Event Date'); ?></p>
                <p style="font-size: 14px; font-weight: 600;"><?php echo formatDate($eventBooking['event_date']); ?></p>
            </div>
            <div>
                <p style="font-size: 13px; color: #666; margin-bottom: 3px;"><?php echo __('Guests'); ?></p>
                <p style="font-size: 14px; font-weight: 600;"><?php echo $eventBooking['guests_count'] ? number_format($eventBooking['guests_count']) : 'N/A'; ?></p>
            </div>
            <div>
                <span style="display: inline-flex; align-items: center; gap: 5px; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: <?php echo $eventStatusStyle['bg']; ?>; color: <?php echo $eventStatusStyle['text']; ?>;">
                    <i class="fas fa-<?php echo $eventStatusStyle['icon']; ?>"></i>
                    <?php echo ucfirst($eventBooking['status']); ?>
                </span>
            </div>
            <div>
                <a href="my-event-bookings.php" class="btn btn-outline btn-sm">
                    <i class="fas fa-eye"></i> <?php echo __('View'); ?>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div style="padding: 60px; text-align: center;">
        <i class="fas fa-calendar-times" style="font-size: 60px; color: var(--gray-medium); margin-bottom: 20px;"></i>
        <h4 style="font-size: 20px; margin-bottom: 10px;"><?php echo __('No Event Bookings Yet'); ?></h4>
        <p style="color: #666; margin-bottom: 25px;"><?php echo __('Book an event space for your next special occasion!'); ?></p>
        <a href="../events.php" class="btn btn-primary"><?php echo __('Browse Event Spaces'); ?></a>
    </div>
    <?php endif; ?>
</div>

<!-- Recent Food Orders -->
<div class="card" style="margin-bottom: 30px;">
    <div class="card-header">
        <h3><i class="fas fa-utensils" style="color: var(--primary-color); margin-right: 10px;"></i><?php echo __('Recent Food Orders'); ?></h3>
        <a href="my-food-orders.php" style="color: var(--primary-color); text-decoration: none; font-size: 14px; font-weight: 600;">
            <?php echo __('View All'); ?> <i class="fas fa-arrow-right" style="margin-left: 5px;"></i>
        </a>
    </div>
    
    <?php if (count($recentFoodOrders) > 0): ?>
    <div style="padding: 0;">
        <?php foreach ($recentFoodOrders as $foodOrder): 
            $foodStatusColors = [
                'pending' => ['bg' => '#fff3cd', 'text' => '#856404', 'icon' => 'clock'],
                'preparing' => ['bg' => '#cce5ff', 'text' => '#004085', 'icon' => 'fire'],
                'ready' => ['bg' => '#d4edda', 'text' => '#155724', 'icon' => 'check'],
                'delivered' => ['bg' => '#e2e3e5', 'text' => '#383d41', 'icon' => 'check-circle'],
                'cancelled' => ['bg' => '#f8d7da', 'text' => '#721c24', 'icon' => 'times-circle']
            ];
            $foodStatusStyle = $foodStatusColors[$foodOrder['status']] ?? $foodStatusColors['pending'];
        ?>
        <div style="padding: 20px 30px; border-bottom: 1px solid var(--gray-light); display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; align-items: center; gap: 20px;">
            <div>
                <h4 style="font-size: 16px; margin-bottom: 5px;"><?php echo htmlspecialchars($foodOrder['item_name']); ?></h4>
                <p style="font-size: 13px; color: #666;">
                    <i class="fas fa-tag" style="margin-right: 5px;"></i><?php echo htmlspecialchars($foodOrder['category_name']); ?>
                </p>
            </div>
            <div>
                <p style="font-size: 13px; color: #666; margin-bottom: 3px;"><?php echo __('Quantity'); ?></p>
                <p style="font-size: 14px; font-weight: 600;"><?php echo $foodOrder['quantity']; ?></p>
            </div>
            <div>
                <p style="font-size: 13px; color: #666; margin-bottom: 3px;"><?php echo __('Total'); ?></p>
                <p style="font-size: 14px; font-weight: 600; color: var(--primary-color);"><?php echo formatPrice($foodOrder['total_price']); ?></p>
            </div>
            <div>
                <span style="display: inline-flex; align-items: center; gap: 5px; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: <?php echo $foodStatusStyle['bg']; ?>; color: <?php echo $foodStatusStyle['text']; ?>;">
                    <i class="fas fa-<?php echo $foodStatusStyle['icon']; ?>"></i>
                    <?php echo ucfirst(str_replace('_', ' ', $foodOrder['status'])); ?>
                </span>
            </div>
            <div>
                <a href="my-food-orders.php" class="btn btn-outline btn-sm">
                    <i class="fas fa-eye"></i> View
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div style="padding: 60px; text-align: center;">
        <i class="fas fa-utensils" style="font-size: 60px; color: var(--gray-medium); margin-bottom: 20px;"></i>
        <h4 style="font-size: 20px; margin-bottom: 10px;">No Food Orders Yet</h4>
        <p style="color: #666; margin-bottom: 25px;">Order delicious meals from our dining menu!</p>
        <a href="../dining.php" class="btn btn-primary">Browse Menu</a>
    </div>
    <?php endif; ?>
</div>

<!-- Inbox Widget Row -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 30px; margin-bottom: 30px;">
    
    <!-- Inbox Preview Widget -->
    <div class="card">
        <div class="card-header" style="padding: 20px 25px;">
            <h3 style="font-size: 18px; margin: 0;"><i class="fas fa-inbox" style="color: var(--primary-color); margin-right: 10px;"></i><?php echo __('Recent Inbox'); ?></h3>
            <div style="display: flex; align-items: center; gap: 10px;">
                <?php if ($newInboxItems > 0): ?>
                <span style="background: var(--warning-color); color: #333; font-size: 12px; padding: 3px 10px; border-radius: 12px; font-weight: 600;"><?php echo $newInboxItems; ?> <?php echo __('new'); ?></span>
                <?php endif; ?>
                <a href="inbox.php" style="color: var(--primary-color); text-decoration: none; font-size: 13px; font-weight: 600;">
                    <?php echo __('View All'); ?> <i class="fas fa-arrow-right" style="margin-left: 5px;"></i>
                </a>
            </div>
        </div>
        <div style="padding: 0; max-height: 320px; overflow-y: auto;">
            <?php if (count($recentInboxItems) > 0): ?>
                <?php foreach ($recentInboxItems as $item): 
                    $statusColors = [
                        'pending' => ['bg' => '#fff3cd', 'text' => '#856404'],
                        'confirmed' => ['bg' => '#d4edda', 'text' => '#155724'],
                        'checked_in' => ['bg' => '#cce5ff', 'text' => '#004085'],
                        'checked_out' => ['bg' => '#e2e3e5', 'text' => '#383d41'],
                        'cancelled' => ['bg' => '#f8d7da', 'text' => '#721c24']
                    ];
                    $statusStyle = $statusColors[$item['status']] ?? $statusColors['pending'];
                ?>
                <div style="padding: 15px 20px; border-bottom: 1px solid var(--gray-light); display: flex; align-items: center; gap: 15px;">
                    <div style="width: 45px; height: 45px; border-radius: 10px; background: var(--gray-light); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fas fa-<?php echo $item['type'] === 'room' ? 'bed' : 'calendar'; ?>" style="font-size: 18px; color: var(--primary-color);"></i>
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <h4 style="margin: 0 0 4px 0; font-size: 14px; font-weight: 600; color: var(--dark-color); font-family: 'Lato', sans-serif;"><?php echo htmlspecialchars($item['item_name']); ?></h4>
                        <p style="margin: 0; font-size: 12px; color: #666;">
                            <?php echo formatDate($item['created_at']); ?> • <?php echo formatPrice($item['total_amount']); ?>
                        </p>
                    </div>
                    <span style="padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; background-color: <?php echo $statusStyle['bg']; ?>; color: <?php echo $statusStyle['text']; ?>;">
                        <?php echo ucfirst(str_replace('_', ' ', $item['status'])); ?>
                    </span>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="padding: 40px 20px; text-align: center; color: #999;">
                    <i class="fas fa-inbox" style="font-size: 36px; margin-bottom: 15px; color: var(--gray-medium);"></i>
                    <p style="margin: 0; font-size: 14px;"><?php echo __('No recent items'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
</div>

<!-- Mini Calendar & Archive/Trash Row -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 30px; margin-bottom: 30px;">
    
    <!-- Mini Calendar Widget -->
    <div class="card">
        <div class="card-header" style="padding: 20px 25px;">
            <h3 style="font-size: 18px; margin: 0;"><i class="fas fa-calendar-alt" style="color: var(--primary-color); margin-right: 10px;"></i><?php echo __('Upcoming Schedule'); ?></h3>
            <a href="user-calendar.php" style="color: var(--primary-color); text-decoration: none; font-size: 13px; font-weight: 600;">
                <?php echo __('Full Calendar'); ?> <i class="fas fa-arrow-right" style="margin-left: 5px;"></i>
            </a>
        </div>
        <div style="padding: 20px 25px;">
            <?php if (count($upcomingCalendarEvents) > 0): ?>
                <div style="display: flex; flex-direction: column; gap: 15px;">
                    <?php foreach ($upcomingCalendarEvents as $event): 
                        $eventTypeIcons = [
                            'booking' => ['icon' => 'bed', 'color' => '#28a745', 'bg' => '#d4edda'],
                            'event' => ['icon' => 'calendar-check', 'color' => '#17a2b8', 'bg' => '#d1ecf1'],
                            'food' => ['icon' => 'utensils', 'color' => '#ffc107', 'bg' => '#fff3cd']
                        ];
                        $eventStyle = $eventTypeIcons[$event['event_type']] ?? $eventTypeIcons['booking'];
                        $eventDate = new DateTime($event['event_date']);
                        $today = new DateTime();
                        $interval = $today->diff($eventDate);
                        $daysUntil = $interval->days;
                        $daysText = $daysUntil == 0 ? __('Today') : ($daysUntil == 1 ? __('Tomorrow') : $daysUntil . ' ' . __('days'));
                    ?>
                    <div style="display: flex; align-items: center; gap: 15px; padding: 15px; background: <?php echo $eventStyle['bg']; ?>; border-radius: 10px; border-left: 4px solid <?php echo $eventStyle['color']; ?>">
                        <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; width: 55px; height: 55px; background: white; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                            <span style="font-size: 11px; font-weight: 600; color: <?php echo $eventStyle['color']; ?>; text-transform: uppercase;"><?php echo $eventDate->format('M'); ?></span>
                            <span style="font-size: 20px; font-weight: 700; color: var(--dark-color);"><?php echo $eventDate->format('d'); ?></span>
                        </div>
                        <div style="flex: 1;">
                            <h4 style="margin: 0 0 4px 0; font-size: 15px; font-weight: 600; color: var(--dark-color); font-family: 'Lato', sans-serif;">
                                <i class="fas fa-<?php echo $eventStyle['icon']; ?>" style="margin-right: 8px; color: <?php echo $eventStyle['color']; ?>"></i>
                                <?php echo htmlspecialchars($event['item_name']); ?>
                            </h4>
                            <p style="margin: 0; font-size: 13px; color: #666;"><?php echo htmlspecialchars($event['title']); ?></p>
                        </div>
                        <span style="padding: 4px 10px; background: white; border-radius: 12px; font-size: 11px; font-weight: 600; color: <?php echo $eventStyle['color']; ?>;">
                            <?php echo $daysText; ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 40px 20px; color: #999;">
                    <i class="fas fa-calendar-day" style="font-size: 48px; margin-bottom: 15px; color: var(--gray-medium);"></i>
                    <p style="margin: 0 0 15px 0; font-size: 14px;"><?php echo __('No upcoming events scheduled'); ?></p>
                    <a href="../booking.php" class="btn btn-sm btn-outline"><?php echo __('Book Now'); ?></a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Archive & Trash Quick Links -->
    <div class="card">
        <div class="card-header" style="padding: 20px 25px;">
            <h3 style="font-size: 18px; margin: 0;"><i class="fas fa-archive" style="color: var(--primary-color); margin-right: 10px;"></i><?php echo __('Manage Reservations'); ?></h3>
        </div>
        <div style="padding: 20px 25px;">
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                
                <!-- Archive -->
                <a href="archive.php" style="text-decoration: none; padding: 20px; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 10px; border: 1px solid #dee2e6; display: flex; flex-direction: column; align-items: center; gap: 10px; transition: all 0.3s; hover: { transform: translateY(-3px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }">
                    <div style="width: 50px; height: 50px; background: rgba(108,117,125,0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-archive" style="font-size: 22px; color: #6c757d;"></i>
                    </div>
                    <div style="text-align: center;">
                        <h4 style="margin: 0 0 4px 0; font-size: 14px; font-weight: 600; color: var(--dark-color);"><?php echo __('Archive'); ?></h4>
                        <p style="margin: 0; font-size: 12px; color: #666;"><?php echo $totalArchived; ?> <?php echo __('items'); ?></p>
                    </div>
                </a>
                
                <!-- Trash -->
                <a href="trash.php" style="text-decoration: none; padding: 20px; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 10px; border: 1px solid #dee2e6; display: flex; flex-direction: column; align-items: center; gap: 10px;">
                    <div style="width: 50px; height: 50px; background: rgba(220,53,69,0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-trash-alt" style="font-size: 22px; color: var(--danger-color);"></i>
                    </div>
                    <div style="text-align: center;">
                        <h4 style="margin: 0 0 4px 0; font-size: 14px; font-weight: 600; color: var(--dark-color);"><?php echo __('Trash'); ?></h4>
                        <p style="margin: 0; font-size: 12px; color: #666;"><?php echo $totalTrashed; ?> <?php echo __('items'); ?></p>
                    </div>
                </a>
                
                <!-- Notification Settings -->
                <a href="notification-settings.php" style="text-decoration: none; padding: 20px; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 10px; border: 1px solid #dee2e6; display: flex; flex-direction: column; align-items: center; gap: 10px;">
                    <div style="width: 50px; height: 50px; background: rgba(54,125,138,0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-cog" style="font-size: 22px; color: var(--primary-color);"></i>
                    </div>
                    <div style="text-align: center;">
                        <h4 style="margin: 0 0 4px 0; font-size: 14px; font-weight: 600; color: var(--dark-color);"><?php echo __('Settings'); ?></h4>
                        <p style="margin: 0; font-size: 12px; color: #666;"><?php echo __('Notifications'); ?></p>
                    </div>
                </a>
                
                <!-- Help/FAQ -->
                <a href="../faq.php" style="text-decoration: none; padding: 20px; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 10px; border: 1px solid #dee2e6; display: flex; flex-direction: column; align-items: center; gap: 10px;">
                    <div style="width: 50px; height: 50px; background: rgba(255,193,7,0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-question-circle" style="font-size: 22px; color: var(--warning-color);"></i>
                    </div>
                    <div style="text-align: center;">
                        <h4 style="margin: 0 0 4px 0; font-size: 14px; font-weight: 600; color: var(--dark-color);"><?php echo __('Help & FAQ'); ?></h4>
                        <p style="margin: 0; font-size: 12px; color: #666;"><?php echo __('Get support'); ?></p>
                    </div>
                </a>
                
            </div>
        </div>
    </div>
    
</div>

<?php require_once '../includes/user-footer.php'; ?>
