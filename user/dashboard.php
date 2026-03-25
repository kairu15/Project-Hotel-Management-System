<?php
$pageTitle = 'My Dashboard';
require_once '../includes/config.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

require_once '../includes/user-header.php';

$db = getDB();
$userId = getUserId();

// Get user's bookings
$bookingsStmt = $db->prepare("
    SELECT b.*, rc.category_name, r.room_number 
    FROM bookings b 
    JOIN room_categories rc ON b.category_id = rc.category_id 
    LEFT JOIN rooms r ON b.room_id = r.room_id 
    WHERE b.user_id = ? 
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
?>

<!-- Dashboard Overview -->
<div style="margin-bottom: 30px;">
    <h2 style="font-size: 28px; margin-bottom: 10px;">Dashboard Overview</h2>
    <p style="color: #666;">Here's what's happening with your account</p>
</div>

<!-- Stats Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon" style="background-color: rgba(54,125,138,0.1); color: var(--primary-color);">
                <i class="fas fa-calendar"></i>
            </div>
            <span class="stat-label">Lifetime</span>
        </div>
        <div class="stat-value"><?php echo $totalBookings; ?></div>
        <p class="stat-desc">Total Bookings</p>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon" style="background-color: rgba(255,193,7,0.1); color: var(--warning-color);">
                <i class="fas fa-clock"></i>
            </div>
            <span class="stat-label" style="color: var(--warning-color); background-color: rgba(255,193,7,0.1);">Active</span>
        </div>
        <div class="stat-value" style="color: var(--warning-color);"><?php echo count($upcomingStays); ?></div>
        <p class="stat-desc">Upcoming Stays</p>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon" style="background-color: rgba(40,167,69,0.1); color: var(--success-color);">
                <i class="fas fa-check-circle"></i>
            </div>
            <span class="stat-label">History</span>
        </div>
        <div class="stat-value" style="color: var(--success-color);"><?php echo count($completedStays); ?></div>
        <p class="stat-desc">Completed Stays</p>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon" style="background-color: rgba(220,53,69,0.1); color: var(--danger-color);">
                <i class="fas fa-gift"></i>
            </div>
            <span class="stat-label">Rewards</span>
        </div>
        <div class="stat-value" style="color: var(--danger-color);"><?php echo number_format($user['loyalty_points']); ?></div>
        <p class="stat-desc">Loyalty Points</p>
    </div>
</div>

<!-- Event Bookings Overview -->
<div style="margin-bottom: 30px;">
    <h3 style="font-size: 20px; margin-bottom: 20px;"><i class="fas fa-calendar-check" style="color: var(--primary-color); margin-right: 10px;"></i>Event Bookings Overview</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon" style="background-color: rgba(255,193,7,0.1); color: var(--warning-color);">
                    <i class="fas fa-clock"></i>
                </div>
                <span class="stat-label" style="color: var(--warning-color); background-color: rgba(255,193,7,0.1);">Pending</span>
            </div>
            <div class="stat-value" style="color: var(--warning-color);"><?php echo $eventPending; ?></div>
            <p class="stat-desc">Awaiting confirmation</p>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon" style="background-color: rgba(40,167,69,0.1); color: var(--success-color);">
                    <i class="fas fa-check-circle"></i>
                </div>
                <span class="stat-label" style="color: var(--success-color); background-color: rgba(40,167,69,0.1);">Confirmed</span>
            </div>
            <div class="stat-value" style="color: var(--success-color);"><?php echo $eventConfirmed; ?></div>
            <p class="stat-desc">Upcoming events</p>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon" style="background-color: rgba(54,125,138,0.1); color: var(--primary-color);">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <span class="stat-label">Completed</span>
            </div>
            <div class="stat-value"><?php echo $eventCompleted; ?></div>
            <p class="stat-desc">Past events</p>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon" style="background-color: rgba(220,53,69,0.1); color: var(--danger-color);">
                    <i class="fas fa-times-circle"></i>
                </div>
                <span class="stat-label" style="color: var(--danger-color); background-color: rgba(220,53,69,0.1);">Cancelled</span>
            </div>
            <div class="stat-value" style="color: var(--danger-color);"><?php echo $eventCancelled; ?></div>
            <p class="stat-desc">Cancelled events</p>
        </div>
    </div>
</div>

<!-- Food Orders Overview -->
<div style="margin-bottom: 30px;">
    <h3 style="font-size: 20px; margin-bottom: 20px;"><i class="fas fa-utensils" style="color: var(--primary-color); margin-right: 10px;"></i>Food Orders Overview</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px;">
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon" style="background-color: rgba(255,193,7,0.1); color: var(--warning-color);">
                    <i class="fas fa-clock"></i>
                </div>
                <span class="stat-label" style="color: var(--warning-color); background-color: rgba(255,193,7,0.1);">Pending</span>
            </div>
            <div class="stat-value" style="color: var(--warning-color);"><?php echo $foodPending; ?></div>
            <p class="stat-desc">Orders in progress</p>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon" style="background-color: rgba(40,167,69,0.1); color: var(--success-color);">
                    <i class="fas fa-check-circle"></i>
                </div>
                <span class="stat-label" style="color: var(--success-color); background-color: rgba(40,167,69,0.1);">Delivered</span>
            </div>
            <div class="stat-value" style="color: var(--success-color);"><?php echo $foodDelivered; ?></div>
            <p class="stat-desc">Completed orders</p>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon" style="background-color: rgba(54,125,138,0.1); color: var(--primary-color);">
                    <i class="fas fa-wallet"></i>
                </div>
                <span class="stat-label">Total Spent</span>
            </div>
            <div class="stat-value"><?php echo formatPrice($foodTotalSpent); ?></div>
            <p class="stat-desc">On food orders</p>
        </div>
    </div>
</div>

<!-- Recent Bookings -->
<div class="card" style="margin-bottom: 30px;">
    <div class="card-header">
        <h3><i class="fas fa-history" style="color: var(--primary-color); margin-right: 10px;"></i>Recent Bookings</h3>
        <a href="my-bookings.php" style="color: var(--primary-color); text-decoration: none; font-size: 14px; font-weight: 600;">
            View All <i class="fas fa-arrow-right" style="margin-left: 5px;"></i>
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
                <p style="font-size: 13px; color: #666; margin-bottom: 3px;">Booking Ref</p>
                <p style="font-size: 14px; font-weight: 600;">#BBH-<?php echo $booking['booking_id']; ?></p>
            </div>
            <div>
                <p style="font-size: 13px; color: #666; margin-bottom: 3px;">Amount</p>
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
                    <i class="fas fa-eye"></i> View
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div style="padding: 60px; text-align: center;">
        <i class="fas fa-calendar-times" style="font-size: 60px; color: var(--gray-medium); margin-bottom: 20px;"></i>
        <h4 style="font-size: 20px; margin-bottom: 10px;">No Bookings Yet</h4>
        <p style="color: #666; margin-bottom: 25px;">Start exploring our rooms and make your first reservation!</p>
        <a href="../rooms.php" class="btn btn-primary">Browse Rooms</a>
    </div>
    <?php endif; ?>
</div>

<!-- Recent Event Bookings -->
<div class="card" style="margin-bottom: 30px;">
    <div class="card-header">
        <h3><i class="fas fa-calendar-check" style="color: var(--primary-color); margin-right: 10px;"></i>Recent Event Bookings</h3>
        <a href="my-event-bookings.php" style="color: var(--primary-color); text-decoration: none; font-size: 14px; font-weight: 600;">
            View All <i class="fas fa-arrow-right" style="margin-left: 5px;"></i>
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
                <p style="font-size: 13px; color: #666; margin-bottom: 3px;">Event Date</p>
                <p style="font-size: 14px; font-weight: 600;"><?php echo formatDate($eventBooking['event_date']); ?></p>
            </div>
            <div>
                <p style="font-size: 13px; color: #666; margin-bottom: 3px;">Guests</p>
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
                    <i class="fas fa-eye"></i> View
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div style="padding: 60px; text-align: center;">
        <i class="fas fa-calendar-times" style="font-size: 60px; color: var(--gray-medium); margin-bottom: 20px;"></i>
        <h4 style="font-size: 20px; margin-bottom: 10px;">No Event Bookings Yet</h4>
        <p style="color: #666; margin-bottom: 25px;">Book an event space for your next special occasion!</p>
        <a href="../events.php" class="btn btn-primary">Browse Event Spaces</a>
    </div>
    <?php endif; ?>
</div>

<!-- Recent Food Orders -->
<div class="card" style="margin-bottom: 30px;">
    <div class="card-header">
        <h3><i class="fas fa-utensils" style="color: var(--primary-color); margin-right: 10px;"></i>Recent Food Orders</h3>
        <a href="my-food-orders.php" style="color: var(--primary-color); text-decoration: none; font-size: 14px; font-weight: 600;">
            View All <i class="fas fa-arrow-right" style="margin-left: 5px;"></i>
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
                <p style="font-size: 13px; color: #666; margin-bottom: 3px;">Quantity</p>
                <p style="font-size: 14px; font-weight: 600;"><?php echo $foodOrder['quantity']; ?></p>
            </div>
            <div>
                <p style="font-size: 13px; color: #666; margin-bottom: 3px;">Total</p>
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

<!-- Quick Actions -->
<div style="margin-bottom: 20px;">
    <h3 style="font-size: 20px; margin-bottom: 20px;"><i class="fas fa-bolt" style="color: var(--warning-color); margin-right: 10px;"></i>Quick Actions</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
        <a href="../booking.php" class="quick-action-card primary">
            <i class="fas fa-plus-circle" style="font-size: 30px; margin-bottom: 15px;"></i>
            <h4>New Booking</h4>
            <p>Book your next stay</p>
        </a>
        
        <a href="../events.php" class="quick-action-card white">
            <i class="fas fa-calendar-plus" style="font-size: 30px; margin-bottom: 15px; color: var(--primary-color);"></i>
            <h4>Book Event</h4>
            <p>Reserve event space</p>
        </a>
        
        <a href="../dining.php" class="quick-action-card white">
            <i class="fas fa-utensils" style="font-size: 30px; margin-bottom: 15px; color: var(--primary-color);"></i>
            <h4>Order Food</h4>
            <p>Browse dining menu</p>
        </a>
        
        <a href="profile.php" class="quick-action-card white">
            <i class="fas fa-user-edit" style="font-size: 30px; margin-bottom: 15px; color: var(--primary-color);"></i>
            <h4>Update Profile</h4>
            <p>Manage your details</p>
        </a>
        
        <a href="../contact.php" class="quick-action-card white">
            <i class="fas fa-headset" style="font-size: 30px; margin-bottom: 15px; color: var(--primary-color);"></i>
            <h4>Support</h4>
            <p>Get help anytime</p>
        </a>
    </div>
</div>

<?php require_once '../includes/user-footer.php'; ?>
