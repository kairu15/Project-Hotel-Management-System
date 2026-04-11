<?php
$pageTitle = 'Staff Dashboard';
require_once '../includes/config.php';

// Check if user is staff (admin, manager, or receptionist)
if (!isStaff()) {
    showAlert('Access denied. Staff privileges required.', 'danger');
    redirect('../index.php');
}

require_once '../includes/staff-header.php';

$db = getDB();
$userRole = getUserRole();

// Get today's stats
$today = date('Y-m-d');
$stats = [
    'today_checkins' => $db->query("SELECT COUNT(*) FROM bookings WHERE check_in = '$today' AND status IN ('confirmed', 'checked_in')")->fetchColumn(),
    'today_checkouts' => $db->query("SELECT COUNT(*) FROM bookings WHERE check_out = '$today' AND status IN ('confirmed', 'checked_in')")->fetchColumn(),
    'pending_bookings' => $db->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn(),
    'occupied_rooms' => $db->query("SELECT COUNT(*) FROM rooms WHERE status = 'occupied'")->fetchColumn(),
];

// Get today's arrivals (first 3 for summary)
$arrivals = $db->query("
    SELECT b.*, u.first_name, u.last_name, u.phone, rc.category_name, r.room_number
    FROM bookings b
    JOIN users u ON b.user_id = u.user_id
    JOIN room_categories rc ON b.category_id = rc.category_id
    LEFT JOIN rooms r ON b.room_id = r.room_id
    WHERE b.check_in = '$today'
    AND b.status IN ('confirmed', 'checked_in')
    ORDER BY b.check_in
    LIMIT 3
")->fetchAll();

// Get today's departures (first 3 for summary)
$departures = $db->query("
    SELECT b.*, u.first_name, u.last_name, u.phone, rc.category_name, r.room_number
    FROM bookings b
    JOIN users u ON b.user_id = u.user_id
    JOIN room_categories rc ON b.category_id = rc.category_id
    LEFT JOIN rooms r ON b.room_id = r.room_id
    WHERE b.check_out = '$today'
    AND b.status IN ('confirmed', 'checked_in')
    ORDER BY b.check_out
    LIMIT 3
")->fetchAll();

// Get pending bookings (first 3 for summary)
$pendingBookings = $db->query("
    SELECT b.*, u.first_name, u.last_name, rc.category_name
    FROM bookings b
    JOIN users u ON b.user_id = u.user_id
    JOIN room_categories rc ON b.category_id = rc.category_id
    WHERE b.status = 'pending'
    ORDER BY b.created_at DESC
    LIMIT 3
")->fetchAll();

// Get current checked-in guests (first 3 for quick checkout summary)
$currentGuests = $db->query("
    SELECT b.*, u.first_name, u.last_name, u.phone, rc.category_name, r.room_number
    FROM bookings b
    JOIN users u ON b.user_id = u.user_id
    JOIN room_categories rc ON b.category_id = rc.category_id
    LEFT JOIN rooms r ON b.room_id = r.room_id
    WHERE b.status = 'checked_in'
    ORDER BY b.check_out ASC
    LIMIT 3
")->fetchAll();

// Get room status
$roomStatus = $db->query("
    SELECT r.*, rc.category_name
    FROM rooms r
    JOIN room_categories rc ON r.category_id = rc.category_id
    ORDER BY r.floor, r.room_number
")->fetchAll();

// Get event space status
$eventSpaceStatus = $db->query("
    SELECT * FROM events ORDER BY floor, event_name
")->fetchAll();

// Get pending event bookings count
$pendingEventBookings = $db->query("SELECT COUNT(*) FROM event_bookings WHERE status = 'pending'")->fetchColumn();

// Get event bookings statistics
$eventStats = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN event_date >= CURDATE() AND status IN ('confirmed', 'pending') THEN 1 ELSE 0 END) as upcoming
    FROM event_bookings
")->fetch();

// Get recent/upcoming event bookings (first 3)
$recentEventBookings = $db->query("
    SELECT eb.*, es.space_name, es.capacity as space_capacity
    FROM event_bookings eb
    JOIN event_spaces es ON eb.space_id = es.space_id
    WHERE eb.event_date >= CURDATE() - INTERVAL 7 DAY
    ORDER BY 
        CASE WHEN eb.status = 'pending' THEN 0 ELSE 1 END,
        eb.event_date ASC,
        eb.created_at DESC
    LIMIT 3
")->fetchAll();

// Get food orders statistics
$foodOrderStats = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'preparing' THEN 1 ELSE 0 END) as preparing,
        SUM(CASE WHEN status = 'ready' THEN 1 ELSE 0 END) as ready,
        SUM(CASE WHEN status = 'delivered' AND DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as delivered_today,
        SUM(CASE WHEN status != 'cancelled' THEN total_price ELSE 0 END) as total_revenue
    FROM food_orders
    WHERE created_at >= CURDATE() - INTERVAL 30 DAY
")->fetch();

// Get maintenance requests statistics
$maintenanceStats = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN priority = 'urgent' AND status IN ('pending', 'in_progress') THEN 1 ELSE 0 END) as urgent,
        SUM(CASE WHEN priority = 'high' AND status IN ('pending', 'in_progress') THEN 1 ELSE 0 END) as `high_priority`
    FROM maintenance_requests
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
")->fetch();

// Get recent maintenance requests
$recentMaintenance = $db->query("
    SELECT mr.*, r.room_number, u.first_name, u.last_name
    FROM maintenance_requests mr
    LEFT JOIN rooms r ON mr.room_id = r.room_id
    LEFT JOIN users u ON mr.reported_by = u.user_id
    WHERE mr.status IN ('pending', 'in_progress')
    ORDER BY FIELD(mr.priority, 'urgent', 'high', 'medium', 'low'), mr.created_at DESC
    LIMIT 3
")->fetchAll();

// Get inventory alerts (low stock items)
$lowStockItems = $db->query("
    SELECT ii.*, ic.category_name
    FROM inventory_items ii
    JOIN inventory_categories ic ON ii.inv_cat_id = ic.inv_cat_id
    WHERE ii.quantity <= ii.reorder_level
    ORDER BY (ii.quantity / ii.reorder_level) ASC
    LIMIT 5
")->fetchAll();

// Get inventory stats
$inventoryStats = $db->query("
    SELECT 
        COUNT(*) as total_items,
        SUM(CASE WHEN quantity <= reorder_level THEN 1 ELSE 0 END) as low_stock_count
    FROM inventory_items
")->fetch();

// Get booking charges summary
$chargesStats = $db->query("
    SELECT 
        COUNT(*) as total_charges,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_charges,
        SUM(CASE WHEN status = 'active' THEN amount ELSE 0 END) as outstanding_amount
    FROM booking_charges
    WHERE created_at >= CURDATE() - INTERVAL 30 DAY
")->fetch();

// Get recent booking charges
$recentCharges = $db->query("
    SELECT bc.*, b.booking_id, u.first_name, u.last_name
    FROM booking_charges bc
    JOIN bookings b ON bc.booking_id = b.booking_id
    JOIN users u ON b.user_id = u.user_id
    WHERE bc.status = 'active'
    ORDER BY bc.created_at DESC
    LIMIT 3
")->fetchAll();

// Get walk-in booking stats for today
$walkinStats = $db->query("
    SELECT COUNT(*) as today_walkins
    FROM bookings
    WHERE booking_source = 'walk_in'
    AND DATE(created_at) = CURDATE()
")->fetchColumn();

// Get unread notifications count and recent notifications
require_once '../includes/notifications.php';
$userId = getUserId();
$unreadNotificationsCount = getUnreadCount($userId);
$recentNotifications = getRecentNotificationsForWidget($userId, 5);

// Get today's calendar events
$calendarEvents = $db->query("
    SELECT 
        'booking' as event_type,
        b.check_in as event_date,
        b.check_in as event_time,
        CONCAT(u.first_name, ' ', u.last_name) as guest_name,
        r.room_number,
        rc.category_name,
        b.status
    FROM bookings b
    JOIN users u ON b.user_id = u.user_id
    LEFT JOIN rooms r ON b.room_id = r.room_id
    JOIN room_categories rc ON b.category_id = rc.category_id
    WHERE b.check_in = CURDATE()
    AND b.status IN ('confirmed', 'checked_in')
    
    UNION ALL
    
    SELECT 
        'checkout' as event_type,
        b.check_out as event_date,
        b.check_out as event_time,
        CONCAT(u.first_name, ' ', u.last_name) as guest_name,
        r.room_number,
        rc.category_name,
        b.status
    FROM bookings b
    JOIN users u ON b.user_id = u.user_id
    LEFT JOIN rooms r ON b.room_id = r.room_id
    JOIN room_categories rc ON b.category_id = rc.category_id
    WHERE b.check_out = CURDATE()
    AND b.status IN ('confirmed', 'checked_in')
    
    UNION ALL
    
    SELECT 
        'event' as event_type,
        eb.event_date as event_date,
        eb.start_time as event_time,
        eb.inquiry_name as guest_name,
        es.space_name as room_number,
        eb.event_type as category_name,
        eb.status
    FROM event_bookings eb
    JOIN event_spaces es ON eb.space_id = es.space_id
    WHERE eb.event_date = CURDATE()
    AND eb.status IN ('confirmed', 'pending')
    
    ORDER BY event_time
    LIMIT 5
")->fetchAll();

// Get recent food orders (first 3)
$recentFoodOrders = $db->query("
    SELECT fo.*, mi.item_name, u.first_name, u.last_name, r.room_number as booking_room
    FROM food_orders fo
    JOIN menu_items mi ON fo.food_id = mi.item_id
    JOIN users u ON fo.user_id = u.user_id
    LEFT JOIN bookings b ON fo.booking_id = b.booking_id
    LEFT JOIN rooms r ON b.room_id = r.room_id
    ORDER BY FIELD(fo.status, 'pending', 'preparing', 'ready', 'delivered', 'cancelled'), fo.created_at DESC
    LIMIT 3
")->fetchAll();

// Status color mappings for display
$eventStatusColors = [
    'pending' => ['#fff3cd', '#856404'],
    'confirmed' => ['#d4edda', '#155724'],
    'completed' => ['#cce5ff', '#004085'],
    'cancelled' => ['#f8d7da', '#721c24']
];

$foodStatusColors = [
    'pending' => ['#fff3cd', '#856404'],
    'preparing' => ['#cce5ff', '#004085'],
    'ready' => ['#d4edda', '#155724'],
    'delivered' => ['#e2e3e5', '#383d41'],
    'cancelled' => ['#f8d7da', '#721c24']
];
$maintenanceStatusColors = [
    'pending' => ['#fff3cd', '#856404'],
    'in_progress' => ['#cce5ff', '#004085'],
    'completed' => ['#d4edda', '#155724'],
    'cancelled' => ['#f8d7da', '#721c24']
];

$priorityColors = [
    'urgent' => ['#dc3545', '#fff'],
    'high' => ['#fd7e14', '#fff'],
    'medium' => ['#ffc107', '#856404'],
    'low' => ['#6c757d', '#fff']
];

$chargeTypeIcons = [
    'minibar' => 'fa-wine-bottle',
    'room_service' => 'fa-concierge-bell',
    'laundry' => 'fa-tshirt',
    'damage' => 'fa-tools',
    'late_checkout' => 'fa-clock',
    'other' => 'fa-file-invoice'
];

$chargeStatusColors = [
    'active' => ['#fff3cd', '#856404'],
    'paid' => ['#d4edda', '#155724'],
    'waived' => ['#f8d7da', '#721c24']
];
?>
<!-- Staff Content -->
<section style="padding: 30px 0; background-color: var(--gray-light); min-height: calc(100vh - 250px);">
    <div class="container">
        <!-- Quick Actions Toolbar -->
        <div style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); border-radius: 15px; padding: 25px; margin-bottom: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
                <div>
                    <h3 style="color: white; margin: 0 0 5px 0; font-size: 20px;"><i class="fas fa-bolt" style="margin-right: 10px;"></i>Quick Actions</h3>
                    <p style="color: rgba(255,255,255,0.8); margin: 0; font-size: 14px;">Access common tasks instantly</p>
                </div>
                <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                    <a href="walkin-booking.php" class="btn" style="background-color: white; color: var(--primary-color); padding: 12px 20px; font-weight: 600;">
                        <i class="fas fa-plus" style="margin-right: 8px;"></i>Walk-in Booking
                    </a>
                    <a href="staff-qr-scanner.php" class="btn" style="background-color: rgba(255,255,255,0.2); color: white; padding: 12px 20px; font-weight: 600; border: 1px solid rgba(255,255,255,0.3);">
                        <i class="fas fa-qrcode" style="margin-right: 8px;"></i>Check-in QR
                    </a>
                    <a href="staff-qr-scanner-food.php" class="btn" style="background-color: rgba(255,255,255,0.2); color: white; padding: 12px 20px; font-weight: 600; border: 1px solid rgba(255,255,255,0.3);">
                        <i class="fas fa-utensils" style="margin-right: 8px;"></i>Food QR
                    </a>
                    <a href="staff-qr-scanner-event.php" class="btn" style="background-color: rgba(255,255,255,0.2); color: white; padding: 12px 20px; font-weight: 600; border: 1px solid rgba(255,255,255,0.3);">
                        <i class="fas fa-calendar-check" style="margin-right: 8px;"></i>Event QR
                    </a>
                    <a href="staff-maintenance.php" class="btn" style="background-color: rgba(255,255,255,0.2); color: white; padding: 12px 20px; font-weight: 600; border: 1px solid rgba(255,255,255,0.3);">
                        <i class="fas fa-wrench" style="margin-right: 8px;"></i>Maintenance
                    </a>
                </div>
            </div>
        </div>
        <!-- Quick Stats Row 1: Room Bookings -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 20px;">
            <div style="background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--info-color);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <p style="font-size: 14px; color: #666; margin: 0 0 5px 0;">Today's Arrivals</p>
                        <h3 style="font-size: 32px; margin: 0;"><?php echo $stats['today_checkins']; ?></h3>
                    </div>
                    <i class="fas fa-sign-in-alt" style="font-size: 40px; color: var(--info-color); opacity: 0.3;"></i>
                </div>
            </div>
            
            <div style="background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--warning-color);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <p style="font-size: 14px; color: #666; margin: 0 0 5px 0;">Today's Departures</p>
                        <h3 style="font-size: 32px; margin: 0;"><?php echo $stats['today_checkouts']; ?></h3>
                    </div>
                    <i class="fas fa-sign-out-alt" style="font-size: 40px; color: var(--warning-color); opacity: 0.3;"></i>
                </div>
            </div>
            
            <div style="background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--danger-color);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <p style="font-size: 14px; color: #666; margin: 0 0 5px 0;">Pending Bookings</p>
                        <h3 style="font-size: 32px; margin: 0;"><?php echo $stats['pending_bookings']; ?></h3>
                    </div>
                    <i class="fas fa-clock" style="font-size: 40px; color: var(--danger-color); opacity: 0.3;"></i>
                </div>
            </div>
            
            <div style="background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--success-color);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <p style="font-size: 14px; color: #666; margin: 0 0 5px 0;">Occupied Rooms</p>
                        <h3 style="font-size: 32px; margin: 0;"><?php echo $stats['occupied_rooms']; ?></h3>
                    </div>
                    <i class="fas fa-bed" style="font-size: 40px; color: var(--success-color); opacity: 0.3;"></i>
                </div>
            </div>
        </div>
        
        <!-- Quick Stats Row 2: Events & Food Orders -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px;">
            <!-- Pending Event Bookings -->
            <div style="background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid #6f42c1;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <p style="font-size: 14px; color: #666; margin: 0 0 5px 0;">Pending Event Bookings</p>
                        <h3 style="font-size: 32px; margin: 0;"><?php echo $eventStats['pending'] ?? 0; ?></h3>
                    </div>
                    <i class="fas fa-calendar-check" style="font-size: 40px; color: #6f42c1; opacity: 0.3;"></i>
                </div>
            </div>
            
            <!-- Upcoming Events -->
            <div style="background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid #17a2b8;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <p style="font-size: 14px; color: #666; margin: 0 0 5px 0;">Upcoming Events</p>
                        <h3 style="font-size: 32px; margin: 0;"><?php echo $eventStats['upcoming'] ?? 0; ?></h3>
                    </div>
                    <i class="fas fa-calendar-alt" style="font-size: 40px; color: #17a2b8; opacity: 0.3;"></i>
                </div>
            </div>
            
            <!-- Pending Food Orders -->
            <div style="background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--warning-color);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <p style="font-size: 14px; color: #666; margin: 0 0 5px 0;">Pending Food Orders</p>
                        <h3 style="font-size: 32px; margin: 0;"><?php echo $foodOrderStats['pending'] ?? 0; ?></h3>
                    </div>
                    <i class="fas fa-utensils" style="font-size: 40px; color: var(--warning-color); opacity: 0.3;"></i>
                </div>
            </div>
            
            <!-- Food Orders Being Prepared -->
            <div style="background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--info-color);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <p style="font-size: 14px; color: #666; margin: 0 0 5px 0;">Being Prepared</p>
                        <h3 style="font-size: 32px; margin: 0;"><?php echo $foodOrderStats['preparing'] ?? 0; ?></h3>
                    </div>
                    <i class="fas fa-fire" style="font-size: 40px; color: var(--info-color); opacity: 0.3;"></i>
                </div>
            </div>
        </div>
        
        <!-- Quick Stats Row 3: Maintenance, Inventory, Charges, Walk-ins -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px;">
            <!-- Urgent Maintenance -->
            <div style="background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid #dc3545;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <p style="font-size: 14px; color: #666; margin: 0 0 5px 0;">Urgent Maintenance</p>
                        <h3 style="font-size: 32px; margin: 0; color: #dc3545;"><?php echo $maintenanceStats['urgent'] ?? 0; ?></h3>
                    </div>
                    <i class="fas fa-exclamation-triangle" style="font-size: 40px; color: #dc3545; opacity: 0.3;"></i>
                </div>
            </div>
            
            <!-- Inventory Alerts -->
            <div style="background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid #fd7e14;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <p style="font-size: 14px; color: #666; margin: 0 0 5px 0;">Low Stock Items</p>
                        <h3 style="font-size: 32px; margin: 0; color: #fd7e14;"><?php echo $inventoryStats['low_stock_count'] ?? 0; ?></h3>
                    </div>
                    <i class="fas fa-boxes" style="font-size: 40px; color: #fd7e14; opacity: 0.3;"></i>
                </div>
            </div>
            
            <!-- Outstanding Charges -->
            <div style="background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid #6f42c1;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <p style="font-size: 14px; color: #666; margin: 0 0 5px 0;">Outstanding Charges</p>
                        <h3 style="font-size: 24px; margin: 0; color: #6f42c1;"><?php echo formatPrice($chargesStats['outstanding_amount'] ?? 0); ?></h3>
                    </div>
                    <i class="fas fa-file-invoice-dollar" style="font-size: 40px; color: #6f42c1; opacity: 0.3;"></i>
                </div>
            </div>
            
            <!-- Today's Walk-ins -->
            <div style="background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid #20c997;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <p style="font-size: 14px; color: #666; margin: 0 0 5px 0;">Today's Walk-ins</p>
                        <h3 style="font-size: 32px; margin: 0; color: #20c997;"><?php echo $walkinStats; ?></h3>
                    </div>
                    <i class="fas fa-walking" style="font-size: 40px; color: #20c997; opacity: 0.3;"></i>
                </div>
            </div>
        </div>
        
        <!-- Summary Panels Grid: Event Bookings & Food Orders -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
            <!-- Recent Event Bookings Summary -->
            <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="padding: 20px 25px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="font-size: 18px; margin: 0;"><i class="fas fa-calendar-check" style="color: #6f42c1; margin-right: 10px;"></i>Recent Event Bookings</h3>
                    <span style="background-color: #6f42c1; color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px;"><?php echo $eventStats['pending'] ?? 0; ?> pending</span>
                </div>
                <div style="padding: 0;">
                    <?php if (count($recentEventBookings) > 0): ?>
                        <?php foreach ($recentEventBookings as $event): 
                            $color = $eventStatusColors[$event['status']] ?? ['#e2e3e5', '#383d41'];
                        ?>
                        <div style="padding: 15px 25px; border-bottom: 1px solid var(--gray-light);">
                            <div style="display: flex; justify-content: space-between; align-items: start;">
                                <div>
                                    <h4 style="font-size: 15px; margin: 0 0 3px 0;"><?php echo htmlspecialchars(ucfirst($event['event_type'] ?: 'General Event')); ?></h4>
                                    <p style="font-size: 13px; color: #666; margin: 0;">
                                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($event['inquiry_name']); ?> |
                                        <i class="fas fa-users"></i> <?php echo number_format($event['guests_count'] ?: 0); ?> guests
                                    </p>
                                    <p style="font-size: 12px; color: #999; margin: 3px 0 0 0;">
                                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['space_name']); ?> |
                                        <i class="fas fa-calendar"></i> <?php echo formatDate($event['event_date']); ?>
                                    </p>
                                </div>
                                <span style="padding: 4px 10px; border-radius: 15px; font-size: 11px; font-weight: 600; background-color: <?php echo $color[0]; ?>; color: <?php echo $color[1]; ?>; text-transform: capitalize;">
                                    <?php echo $event['status']; ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="padding: 40px; text-align: center;">
                            <p style="color: #666; margin: 0;">No recent event bookings</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div style="padding: 15px 25px; border-top: 1px solid var(--gray-light); text-align: center;">
                    <a href="staff-event-bookings.php" class="btn btn-primary" style="padding: 8px 20px; font-size: 13px;"><i class="fas fa-list"></i> View All Event Bookings</a>
                </div>
            </div>
            
            <!-- Recent Food Orders Summary -->
            <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="padding: 20px 25px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="font-size: 18px; margin: 0;"><i class="fas fa-utensils" style="color: var(--primary-color); margin-right: 10px;"></i>Recent Food Orders</h3>
                    <span style="background-color: var(--primary-color); color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px;"><?php echo $foodOrderStats['ready'] ?? 0; ?> ready</span>
                </div>
                <div style="padding: 0;">
                    <?php if (count($recentFoodOrders) > 0): ?>
                        <?php foreach ($recentFoodOrders as $order): 
                            $status = $foodStatusColors[$order['status']] ?? $foodStatusColors['pending'];
                            $roomDisplay = $order['room_number'] ?? ($order['booking_room'] ?? 'N/A');
                        ?>
                        <div style="padding: 15px 25px; border-bottom: 1px solid var(--gray-light);">
                            <div style="display: flex; justify-content: space-between; align-items: start;">
                                <div style="flex: 1;">
                                    <div style="display: flex; justify-content: space-between; align-items: start;">
                                        <div>
                                            <h4 style="font-size: 15px; margin: 0 0 3px 0;"><?php echo htmlspecialchars($order['item_name']); ?></h4>
                                            <p style="font-size: 13px; color: #666; margin: 0;">
                                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?> |
                                                Qty: <?php echo $order['quantity']; ?>
                                            </p>
                                            <p style="font-size: 12px; color: #999; margin: 3px 0 0 0;">
                                                <i class="fas fa-<?php echo $order['order_type'] === 'room_service' ? 'hotel' : ($order['order_type'] === 'dine_in' ? 'utensils' : 'shopping-bag'); ?>"></i>
                                                <?php echo str_replace('_', ' ', $order['order_type']); ?>
                                                <?php if ($order['order_type'] === 'room_service' && $roomDisplay !== 'N/A'): ?>
                                                | Room: <?php echo htmlspecialchars($roomDisplay); ?>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div style="text-align: right;">
                                    <p style="font-size: 14px; font-weight: 600; color: var(--primary-color); margin: 0;"><?php echo formatPrice($order['total_price']); ?></p>
                                    <span style="display: inline-block; margin-top: 5px; padding: 3px 10px; border-radius: 15px; font-size: 11px; font-weight: 600; background-color: <?php echo $status[0]; ?>; color: <?php echo $status[1]; ?>;">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="padding: 40px; text-align: center;">
                            <p style="color: #666; margin: 0;">No recent food orders</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div style="padding: 15px 25px; border-top: 1px solid var(--gray-light); text-align: center;">
                    <a href="staff-foods-orders.php" class="btn btn-primary" style="padding: 8px 20px; font-size: 13px;"><i class="fas fa-list"></i> View All Food Orders</a>
                </div>
            </div>
        </div>
        
        <!-- Summary Panels Grid -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
            <!-- Today's Arrivals Summary -->
            <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="padding: 20px 25px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="font-size: 18px; margin: 0;"><i class="fas fa-sign-in-alt" style="color: var(--info-color); margin-right: 10px;"></i>Today's Arrivals</h3>
                    <span style="background-color: var(--info-color); color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px;"><?php echo $stats['today_checkins']; ?> guest<?php echo $stats['today_checkins'] != 1 ? 's' : ''; ?></span>
                </div>
                <div style="padding: 0;">
                    <?php if (count($arrivals) > 0): ?>
                        <?php foreach ($arrivals as $arrival): ?>
                        <div style="padding: 15px 25px; border-bottom: 1px solid var(--gray-light);">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <h4 style="font-size: 15px; margin: 0 0 3px 0;"><?php echo htmlspecialchars($arrival['first_name'] . ' ' . $arrival['last_name']); ?></h4>
                                    <p style="font-size: 13px; color: #666; margin: 0;">
                                        <?php echo htmlspecialchars($arrival['category_name']); ?>
                                        <?php echo $arrival['room_number'] ? ' – Room ' . $arrival['room_number'] : ''; ?>
                                    </p>
                                    <?php if ($arrival['status'] === 'checked_in'): ?>
                                    <span style="display: inline-block; margin-top: 5px; background-color: #d4edda; color: #155724; padding: 3px 10px; border-radius: 15px; font-size: 11px;">Checked In</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="padding: 40px; text-align: center;">
                            <p style="color: #666; margin: 0;">No arrivals scheduled for today</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div style="padding: 15px 25px; border-top: 1px solid var(--gray-light); text-align: center;">
                    <a href="checkin.php" class="btn btn-primary" style="padding: 8px 20px; font-size: 13px;"><i class="fas fa-list"></i> View All Arrivals</a>
                </div>
            </div>
            
            <!-- Today's Departures Summary -->
            <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="padding: 20px 25px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="font-size: 18px; margin: 0;"><i class="fas fa-sign-out-alt" style="color: var(--warning-color); margin-right: 10px;"></i>Today's Departures</h3>
                    <span style="background-color: var(--warning-color); color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px;"><?php echo $stats['today_checkouts']; ?> guest<?php echo $stats['today_checkouts'] != 1 ? 's' : ''; ?></span>
                </div>
                <div style="padding: 0;">
                    <?php if (count($departures) > 0): ?>
                        <?php foreach ($departures as $departure): ?>
                        <div style="padding: 15px 25px; border-bottom: 1px solid var(--gray-light);">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <h4 style="font-size: 15px; margin: 0 0 3px 0;"><?php echo htmlspecialchars($departure['first_name'] . ' ' . $departure['last_name']); ?></h4>
                                    <p style="font-size: 13px; color: #666; margin: 0;">Room <?php echo htmlspecialchars($departure['room_number']); ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="padding: 40px; text-align: center;">
                            <p style="color: #666; margin: 0;">No departures scheduled for today</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div style="padding: 15px 25px; border-top: 1px solid var(--gray-light); text-align: center;">
                    <a href="checkout.php" class="btn btn-primary" style="padding: 8px 20px; font-size: 13px;"><i class="fas fa-list"></i> View All Departures</a>
                </div>
            </div>
        </div>
        
        <!-- Current Guests Summary -->
        <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
            <div style="padding: 20px 25px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 18px; margin: 0;"><i class="fas fa-users" style="color: var(--info-color); margin-right: 10px;"></i>Current Guests</h3>
                <span style="background-color: var(--info-color); color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px;"><?php echo $db->query("SELECT COUNT(*) FROM bookings WHERE status = 'checked_in'")->fetchColumn(); ?> total guests</span>
            </div>
            <div style="padding: 0;">
                <?php if (count($currentGuests) > 0): ?>
                    <?php foreach ($currentGuests as $guest): ?>
                    <div style="padding: 15px 25px; border-bottom: 1px solid var(--gray-light);">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div style="flex: 1;">
                                <div style="display: flex; justify-content: space-between; align-items: start;">
                                    <div>
                                        <h4 style="font-size: 15px; margin: 0 0 3px 0;"><?php echo htmlspecialchars($guest['first_name'] . ' ' . $guest['last_name']); ?></h4>
                                        <p style="font-size: 13px; color: #666; margin: 0;">
                                            Room <?php echo htmlspecialchars($guest['room_number'] ?: 'Not Assigned'); ?> | 
                                            <?php echo htmlspecialchars($guest['category_name']); ?>
                                        </p>
                                        <p style="font-size: 12px; color: #999; margin: 3px 0 0 0;">
                                            <i class="fas fa-calendar-times"></i> Checkout: <?php echo formatDate($guest['check_out']); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="padding: 40px; text-align: center;">
                        <i class="fas fa-users" style="font-size: 48px; color: #ccc; margin-bottom: 15px;"></i>
                        <p style="color: #666; margin: 0;">No guests currently checked in</p>
                    </div>
                <?php endif; ?>
            </div>
            <div style="padding: 15px 25px; border-top: 1px solid var(--gray-light); text-align: center;">
                <a href="checkout.php" class="btn btn-primary" style="padding: 8px 20px; font-size: 13px;"><i class="fas fa-list"></i> Quick Checkout – View All Guests</a>
            </div>
        </div>
        
        <!-- Pending Bookings Summary -->
        <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
            <div style="padding: 20px 25px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 18px; margin: 0;"><i class="fas fa-clock" style="color: var(--danger-color); margin-right: 10px;"></i>Pending Bookings (Requires Confirmation)</h3>
                <span style="background-color: var(--danger-color); color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px;"><?php echo $stats['pending_bookings']; ?> pending</span>
            </div>
            <div style="padding: 0;">
                <?php if (count($pendingBookings) > 0): ?>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color: var(--gray-light);">
                            <th style="padding: 15px 25px; text-align: left; font-size: 14px;">Guest</th>
                            <th style="padding: 15px 25px; text-align: left; font-size: 14px;">Room Type</th>
                            <th style="padding: 15px 25px; text-align: left; font-size: 14px;">Dates</th>
                            <th style="padding: 15px 25px; text-align: right; font-size: 14px;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingBookings as $booking): ?>
                        <tr style="border-bottom: 1px solid var(--gray-light);">
                            <td style="padding: 15px 25px;">
                                <strong><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></strong>
                            </td>
                            <td style="padding: 15px 25px;"><?php echo htmlspecialchars($booking['category_name']); ?></td>
                            <td style="padding: 15px 25px;">
                                <?php echo formatDate($booking['check_in']); ?> - <?php echo formatDate($booking['check_out']); ?>
                            </td>
                            <td style="padding: 15px 25px; text-align: right; font-weight: 600; color: var(--primary-color);">
                                <?php echo formatPrice($booking['total_amount']); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div style="padding: 40px; text-align: center;">
                    <p style="color: #666; margin: 0;">No pending bookings to confirm</p>
                </div>
                <?php endif; ?>
            </div>
            <div style="padding: 15px 25px; border-top: 1px solid var(--gray-light); text-align: center;">
                <a href="confirm-booking.php" class="btn btn-primary" style="padding: 8px 20px; font-size: 13px;"><i class="fas fa-list"></i> View All Pending Bookings</a>
            </div>
        </div>
        
        <!-- New Comprehensive Panels Grid -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
            <!-- Notifications Panel -->
            <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="padding: 20px 25px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="font-size: 18px; margin: 0;"><i class="fas fa-bell" style="color: #ffc107; margin-right: 10px;"></i>Notifications</h3>
                    <?php if ($unreadNotificationsCount > 0): ?>
                    <span style="background-color: #dc3545; color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px;"><?php echo $unreadNotificationsCount; ?> unread</span>
                    <?php endif; ?>
                </div>
                <div style="padding: 0; max-height: 300px; overflow-y: auto;">
                    <?php if (count($recentNotifications) > 0): ?>
                        <?php foreach ($recentNotifications as $notification): ?>
                        <div style="padding: 15px 25px; border-bottom: 1px solid var(--gray-light); <?php echo $notification['status'] === 'unread' ? 'background-color: #f0f9ff;' : ''; ?>">
                            <div style="display: flex; gap: 12px; align-items: start;">
                                <div style="width: 36px; height: 36px; border-radius: 50%; background-color: <?php echo $notification['color']; ?>; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <i class="fas fa-<?php echo $notification['icon']; ?>" style="color: white; font-size: 14px;"></i>
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <h4 style="font-size: 14px; margin: 0 0 3px 0; font-weight: 600;"><?php echo htmlspecialchars($notification['title']); ?></h4>
                                    <p style="font-size: 12px; color: #666; margin: 0; line-height: 1.4;"><?php echo htmlspecialchars(substr($notification['message'], 0, 60)) . (strlen($notification['message']) > 60 ? '...' : ''); ?></p>
                                    <span style="font-size: 11px; color: #999;"><i class="far fa-clock" style="margin-right: 4px;"></i><?php echo $notification['time_ago']; ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="padding: 40px; text-align: center;">
                            <i class="fas fa-bell-slash" style="font-size: 36px; color: #ddd; margin-bottom: 10px;"></i>
                            <p style="color: #666; margin: 0; font-size: 14px;">No notifications</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div style="padding: 15px 25px; border-top: 1px solid var(--gray-light); text-align: center;">
                    <a href="notifications.php" class="btn btn-primary" style="padding: 8px 20px; font-size: 13px;"><i class="fas fa-list"></i> View All Notifications</a>
                </div>
            </div>
            
            <!-- Today's Calendar Mini-View -->
            <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="padding: 20px 25px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="font-size: 18px; margin: 0;"><i class="fas fa-calendar-day" style="color: #17a2b8; margin-right: 10px;"></i>Today's Schedule</h3>
                    <span style="background-color: #17a2b8; color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px;"><?php echo date('M d, Y'); ?></span>
                </div>
                <div style="padding: 0; max-height: 300px; overflow-y: auto;">
                    <?php if (count($calendarEvents) > 0): ?>
                        <?php foreach ($calendarEvents as $event): 
                            $eventColors = [
                                'booking' => ['#d4edda', '#155724', '#28a745', 'fa-sign-in-alt'],
                                'checkout' => ['#fff3cd', '#856404', '#ffc107', 'fa-sign-out-alt'],
                                'event' => ['#e2e3e5', '#6f42c1', '#6f42c1', 'fa-calendar-check']
                            ];
                            $color = $eventColors[$event['event_type']] ?? $eventColors['booking'];
                        ?>
                        <div style="padding: 15px 25px; border-bottom: 1px solid var(--gray-light);">
                            <div style="display: flex; gap: 12px; align-items: center;">
                                <div style="width: 40px; height: 40px; border-radius: 8px; background-color: <?php echo $color[0]; ?>; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <i class="fas <?php echo $color[3]; ?>" style="color: <?php echo $color[2]; ?>; font-size: 16px;"></i>
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <h4 style="font-size: 14px; margin: 0; font-weight: 600;"><?php echo htmlspecialchars($event['guest_name']); ?></h4>
                                        <span style="font-size: 11px; padding: 3px 8px; border-radius: 10px; background-color: <?php echo $color[0]; ?>; color: <?php echo $color[1]; ?>; text-transform: capitalize;"><?php echo $event['event_type']; ?></span>
                                    </div>
                                    <p style="font-size: 12px; color: #666; margin: 3px 0 0 0;">
                                        <?php echo htmlspecialchars($event['room_number'] ?? 'N/A'); ?> | 
                                        <?php echo htmlspecialchars($event['category_name']); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="padding: 40px; text-align: center;">
                            <i class="fas fa-calendar" style="font-size: 36px; color: #ddd; margin-bottom: 10px;"></i>
                            <p style="color: #666; margin: 0; font-size: 14px;">No events scheduled for today</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div style="padding: 15px 25px; border-top: 1px solid var(--gray-light); text-align: center;">
                    <a href="staff-calendar.php" class="btn btn-primary" style="padding: 8px 20px; font-size: 13px;"><i class="fas fa-calendar-alt"></i> View Full Calendar</a>
                </div>
            </div>
        </div>
        
        <!-- Maintenance & Inventory Panels -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
            <!-- Maintenance Requests Panel -->
            <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="padding: 20px 25px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="font-size: 18px; margin: 0;"><i class="fas fa-wrench" style="color: #6f42c1; margin-right: 10px;"></i>Maintenance Requests</h3>
                    <?php if (($maintenanceStats['urgent'] ?? 0) > 0): ?>
                    <span style="background-color: #dc3545; color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px;"><?php echo $maintenanceStats['urgent']; ?> urgent</span>
                    <?php elseif (($maintenanceStats['pending'] ?? 0) > 0): ?>
                    <span style="background-color: #ffc107; color: #856404; padding: 5px 12px; border-radius: 20px; font-size: 12px;"><?php echo $maintenanceStats['pending']; ?> pending</span>
                    <?php endif; ?>
                </div>
                <div style="padding: 0;">
                    <?php if (count($recentMaintenance) > 0): ?>
                        <?php foreach ($recentMaintenance as $request): 
                            $priorityColor = $priorityColors[$request['priority']] ?? $priorityColors['medium'];
                            $statusColor = $maintenanceStatusColors[$request['status']] ?? $maintenanceStatusColors['pending'];
                        ?>
                        <div style="padding: 15px 25px; border-bottom: 1px solid var(--gray-light);">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                                <div>
                                    <h4 style="font-size: 14px; margin: 0 0 5px 0; font-weight: 600;">
                                        <i class="fas fa-door-open" style="margin-right: 5px; color: #666;"></i>
                                        Room <?php echo htmlspecialchars($request['room_number'] ?? 'General'); ?>
                                    </h4>
                                    <p style="font-size: 13px; color: #666; margin: 0;"><?php echo htmlspecialchars(ucfirst($request['issue_type'])); ?></p>
                                </div>
                                <div style="text-align: right;">
                                    <span style="display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; background-color: <?php echo $priorityColor[0]; ?>; color: <?php echo $priorityColor[1]; ?>; margin-bottom: 4px;">
                                        <?php echo strtoupper($request['priority']); ?>
                                    </span>
                                    <span style="display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; background-color: <?php echo $statusColor[0]; ?>; color: <?php echo $statusColor[1]; ?>; text-transform: capitalize;">
                                        <?php echo str_replace('_', ' ', $request['status']); ?>
                                    </span>
                                </div>
                            </div>
                            <p style="font-size: 12px; color: #999; margin: 0;">
                                <?php echo htmlspecialchars(substr($request['description'], 0, 50)) . (strlen($request['description']) > 50 ? '...' : ''); ?>
                            </p>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="padding: 40px; text-align: center;">
                            <i class="fas fa-check-circle" style="font-size: 36px; color: #28a745; margin-bottom: 10px;"></i>
                            <p style="color: #666; margin: 0; font-size: 14px;">No pending maintenance requests</p>
                            <p style="color: #999; margin: 5px 0 0 0; font-size: 12px;">All systems operational</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div style="padding: 15px 25px; border-top: 1px solid var(--gray-light); text-align: center;">
                    <a href="staff-maintenance.php" class="btn btn-primary" style="padding: 8px 20px; font-size: 13px;"><i class="fas fa-list"></i> View All Maintenance</a>
                </div>
            </div>
            
            <!-- Inventory Alerts Panel -->
            <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="padding: 20px 25px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="font-size: 18px; margin: 0;"><i class="fas fa-boxes" style="color: #fd7e14; margin-right: 10px;"></i>Inventory Alerts</h3>
                    <?php if (count($lowStockItems) > 0): ?>
                    <span style="background-color: #dc3545; color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px;"><?php echo count($lowStockItems); ?> low stock</span>
                    <?php else: ?>
                    <span style="background-color: #28a745; color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px;"><i class="fas fa-check"></i> Stock OK</span>
                    <?php endif; ?>
                </div>
                <div style="padding: 0;">
                    <?php if (count($lowStockItems) > 0): ?>
                        <?php foreach ($lowStockItems as $item): 
                            $stockRatio = $item['quantity'] / $item['reorder_level'];
                            $alertColor = $stockRatio <= 0.5 ? '#dc3545' : ($stockRatio <= 1 ? '#fd7e14' : '#ffc107');
                        ?>
                        <div style="padding: 15px 25px; border-bottom: 1px solid var(--gray-light);">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <div>
                                    <h4 style="font-size: 14px; margin: 0; font-weight: 600;"><?php echo htmlspecialchars($item['item_name']); ?></h4>
                                    <p style="font-size: 12px; color: #666; margin: 3px 0 0 0;"><?php echo htmlspecialchars($item['category_name']); ?></p>
                                </div>
                                <div style="text-align: right;">
                                    <span style="font-size: 18px; font-weight: 700; color: <?php echo $alertColor; ?>"><?php echo $item['quantity']; ?></span>
                                    <span style="font-size: 11px; color: #999; display: block;">of <?php echo $item['reorder_level']; ?> min</span>
                                </div>
                            </div>
                            <div style="background-color: var(--gray-light); border-radius: 10px; height: 6px; overflow: hidden;">
                                <div style="width: <?php echo min(100, ($item['quantity'] / $item['reorder_level']) * 100); ?>%; background-color: <?php echo $alertColor; ?>; height: 100%; border-radius: 10px;"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="padding: 40px; text-align: center;">
                            <i class="fas fa-check-circle" style="font-size: 36px; color: #28a745; margin-bottom: 10px;"></i>
                            <p style="color: #666; margin: 0; font-size: 14px;">All inventory items are well stocked</p>
                            <p style="color: #999; margin: 5px 0 0 0; font-size: 12px;"><?php echo $inventoryStats['total_items']; ?> items tracked</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div style="padding: 15px 25px; border-top: 1px solid var(--gray-light); text-align: center;">
                    <a href="staff-inventory.php" class="btn btn-primary" style="padding: 8px 20px; font-size: 13px;"><i class="fas fa-list"></i> Manage Inventory</a>
                </div>
            </div>
        </div>
        
        <!-- Booking Charges Panel -->
        <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
            <div style="padding: 20px 25px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 18px; margin: 0;"><i class="fas fa-file-invoice-dollar" style="color: #6f42c1; margin-right: 10px;"></i>Outstanding Booking Charges</h3>
                <?php if (($chargesStats['active_charges'] ?? 0) > 0): ?>
                <span style="background-color: #6f42c1; color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px;"><?php echo $chargesStats['active_charges']; ?> charges | <?php echo formatPrice($chargesStats['outstanding_amount'] ?? 0); ?></span>
                <?php endif; ?>
            </div>
            <div style="padding: 0;">
                <?php if (count($recentCharges) > 0): ?>
                    <?php foreach ($recentCharges as $charge): 
                        $statusColor = $chargeStatusColors[$charge['status']] ?? $chargeStatusColors['active'];
                        $iconClass = $chargeTypeIcons[$charge['charge_type']] ?? 'fa-file-invoice';
                    ?>
                    <div style="padding: 15px 25px; border-bottom: 1px solid var(--gray-light);">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div style="display: flex; gap: 12px; align-items: center;">
                                <div style="width: 36px; height: 36px; border-radius: 8px; background-color: <?php echo $statusColor[0]; ?>; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas <?php echo $iconClass; ?>" style="color: <?php echo $statusColor[1]; ?>; font-size: 14px;"></i>
                                </div>
                                <div>
                                    <h4 style="font-size: 14px; margin: 0; font-weight: 600;"><?php echo htmlspecialchars($charge['description']); ?></h4>
                                    <p style="font-size: 12px; color: #666; margin: 3px 0 0 0;">
                                        <?php echo htmlspecialchars($charge['first_name'] . ' ' . $charge['last_name']); ?> | 
                                        <span style="text-transform: capitalize;"><?php echo str_replace('_', ' ', $charge['charge_type']); ?></span>
                                    </p>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <p style="font-size: 16px; font-weight: 600; color: #6f42c1; margin: 0;"><?php echo formatPrice($charge['amount']); ?></p>
                                <span style="padding: 3px 10px; border-radius: 12px; font-size: 11px; background-color: <?php echo $statusColor[0]; ?>; color: <?php echo $statusColor[1]; ?>; text-transform: capitalize;">
                                    <?php echo $charge['status']; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="padding: 40px; text-align: center;">
                        <i class="fas fa-check-circle" style="font-size: 36px; color: #28a745; margin-bottom: 10px;"></i>
                        <p style="color: #666; margin: 0; font-size: 14px;">No outstanding charges</p>
                        <p style="color: #999; margin: 5px 0 0 0; font-size: 12px;">All booking charges are settled</p>
                    </div>
                <?php endif; ?>
            </div>
            <div style="padding: 15px 25px; border-top: 1px solid var(--gray-light); text-align: center;">
                <a href="staff-booking-charges.php" class="btn btn-primary" style="padding: 8px 20px; font-size: 13px;"><i class="fas fa-list"></i> Manage All Charges</a>
            </div>
        </div>
        
        <!-- Room Status Grid -->
        <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
            <div style="padding: 20px 25px; border-bottom: 1px solid var(--gray-light);">
                <h3 style="font-size: 18px; margin: 0;"><i class="fas fa-th-large" style="color: var(--primary-color); margin-right: 10px;"></i>Room Status Overview</h3>
            </div>
            <div style="padding: 25px;">
                <div style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 15px;">
                    <?php foreach ($roomStatus as $room): 
                        $statusColors = [
                            'available' => ['#d4edda', '#155724', '#28a745'],
                            'occupied' => ['#f8d7da', '#721c24', '#dc3545'],
                            'maintenance' => ['#fff3cd', '#856404', '#ffc107'],
                            'cleaning' => ['#cce5ff', '#004085', '#17a2b8'],
                            'reserved' => ['#e2e3e5', '#383d41', '#6c757d']
                        ];
                        $color = $statusColors[$room['status']] ?? $statusColors['available'];
                    ?>
                    <div style="background-color: <?php echo $color[0]; ?>; border: 2px solid <?php echo $color[2]; ?>; border-radius: 8px; padding: 15px; text-align: center;">
                        <h4 style="font-size: 18px; margin: 0 0 5px 0; color: <?php echo $color[1]; ?>"><?php echo htmlspecialchars($room['room_number']); ?></h4>
                        <p style="font-size: 12px; color: <?php echo $color[1]; ?>; margin: 0 0 10px 0;"><?php echo htmlspecialchars($room['category_name']); ?></p>
                        <span style="font-size: 11px; padding: 3px 10px; border-radius: 20px; background-color: <?php echo $color[2]; ?>; color: white; text-transform: uppercase;">
                            <?php echo $room['status']; ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Event Space Status Overview -->
        <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
            <div style="padding: 20px 25px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 18px; margin: 0;"><i class="fas fa-calendar-alt" style="color: var(--primary-color); margin-right: 10px;"></i>Event Space Status Overview</h3>
                <a href="staff-event-bookings.php" class="btn btn-primary" style="padding: 8px 16px; font-size: 13px;">
                    <i class="fas fa-list"></i> View Event Bookings (<?php echo $pendingEventBookings; ?> pending)
                </a>
            </div>
            <div style="padding: 25px;">
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">
                    <?php foreach ($eventSpaceStatus as $space): 
                        $eventStatusColors = [
                            'available' => ['#d4edda', '#155724', '#28a745'],
                            'reserved' => ['#fff3cd', '#856404', '#ffc107'],
                            'occupied' => ['#f8d7da', '#721c24', '#dc3545']
                        ];
                        $color = $eventStatusColors[$space['status']] ?? $eventStatusColors['available'];
                    ?>
                    <div style="background-color: <?php echo $color[0]; ?>; border: 2px solid <?php echo $color[2]; ?>; border-radius: 8px; padding: 15px; text-align: center;">
                        <h4 style="font-size: 16px; margin: 0 0 5px 0; color: <?php echo $color[1]; ?>"><?php echo htmlspecialchars($space['event_name']); ?></h4>
                        <p style="font-size: 12px; color: <?php echo $color[1]; ?>; margin: 0 0 10px 0;">
                            Floor: <?php echo $space['floor'] ?: 'Ground'; ?> | 
                            <?php echo $space['maintenance_status'] === 'clean' ? 'Clean' : 'Under Maintenance'; ?>
                        </p>
                        <span style="font-size: 11px; padding: 3px 10px; border-radius: 20px; background-color: <?php echo $color[2]; ?>; color: white; text-transform: uppercase;">
                            <?php echo $space['status']; ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once '../includes/staff-footer.php'; ?>
