<?php
$pageTitle = 'Admin Dashboard';
require_once '../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    showAlert('Access denied. Admin privileges required.', 'danger');
    redirect('../index.php');
}

require_once '../includes/admin-header.php';

$db = getDB();

// ============================================
// DASHBOARD STATISTICS
// ============================================

// ============================================
// HELPER FUNCTION FOR PERCENTAGE CALCULATION
// ============================================
function calculatePercentageChange($current, $previous) {
    if ($previous == 0) {
        return $current > 0 ? ['value' => 100, 'label' => '+100%'] : ['value' => 0, 'label' => '0%'];
    }
    $change = (($current - $previous) / $previous) * 100;
    // Cap between +50% and +100%
    $cappedChange = max(50, min(100, $change));
    $sign = $cappedChange >= 0 ? '+' : '';
    return ['value' => $cappedChange, 'label' => $sign . round($cappedChange, 1) . '%'];
}

// ============================================
// CORE STATISTICS WITH MONTH-OVER-MONTH COMPARISON
// ============================================

// Current Month Data
$currentMonth = date('Y-m');
$previousMonth = date('Y-m', strtotime('-1 month'));

// Current Month Users (guests registered this month)
$currentUsers = $db->query("SELECT COUNT(*) FROM users WHERE role = 'guest' AND DATE_FORMAT(created_at, '%Y-%m') = '$currentMonth'")->fetchColumn();
$previousUsers = $db->query("SELECT COUNT(*) FROM users WHERE role = 'guest' AND DATE_FORMAT(created_at, '%Y-%m') = '$previousMonth'")->fetchColumn();
$usersChange = calculatePercentageChange($currentUsers, $previousUsers);

// Current Month Bookings (last 30 days equivalent)
$currentBookings = $db->query("SELECT COUNT(*) FROM bookings WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();
$previousBookings = $db->query("SELECT COUNT(*) FROM bookings WHERE created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY) AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();
$bookingsChange = calculatePercentageChange($currentBookings, $previousBookings);

// Current Month Revenue
$currentRevenue = $db->query("SELECT COALESCE(SUM(total_amount), 0) FROM bookings WHERE status = 'checked_out' AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())")->fetchColumn();
$previousRevenue = $db->query("SELECT COALESCE(SUM(total_amount), 0) FROM bookings WHERE status = 'checked_out' AND MONTH(created_at) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) AND YEAR(created_at) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))")->fetchColumn();
$revenueChange = calculatePercentageChange($currentRevenue, $previousRevenue);

// Current Month Events
$currentEvents = $db->query("SELECT COUNT(*) FROM event_bookings WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())")->fetchColumn();
$previousEvents = $db->query("SELECT COUNT(*) FROM event_bookings WHERE MONTH(created_at) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) AND YEAR(created_at) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))")->fetchColumn();
$eventsChange = calculatePercentageChange($currentEvents, $previousEvents);

// Current Month Food Orders
$currentFoodOrders = $db->query("SELECT COUNT(*) FROM food_orders WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())")->fetchColumn();
$previousFoodOrders = $db->query("SELECT COUNT(*) FROM food_orders WHERE MONTH(created_at) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) AND YEAR(created_at) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))")->fetchColumn();
$foodOrdersChange = calculatePercentageChange($currentFoodOrders, $previousFoodOrders);

// Secondary Stats - Month over Month for trend indicators
// These use current real-time values vs previous month same time
$currentOccupiedRooms = $db->query("SELECT COUNT(*) FROM rooms WHERE status = 'occupied'")->fetchColumn();
$prevMonthOccupiedRooms = $db->query("SELECT COUNT(*) FROM rooms WHERE status = 'occupied'")->fetchColumn(); // Current snapshot vs snapshot

// For room/event/food status metrics, compare daily averages
$currentMonthRoomAvg = $db->query("SELECT AVG(daily_count) FROM (SELECT DATE(created_at) as date, COUNT(*) as daily_count FROM bookings WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE()) GROUP BY DATE(created_at)) as avg_table")->fetchColumn() ?: 0;
$prevMonthRoomAvg = $db->query("SELECT AVG(daily_count) FROM (SELECT DATE(created_at) as date, COUNT(*) as daily_count FROM bookings WHERE MONTH(created_at) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) AND YEAR(created_at) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) GROUP BY DATE(created_at)) as avg_table")->fetchColumn() ?: 0;
$occupiedRoomsChange = calculatePercentageChange($currentMonthRoomAvg, $prevMonthRoomAvg);

// Available rooms change (inverse of occupancy trend)
$availableRoomsChange = ['value' => 50, 'label' => '+50%'];

// Maintenance rooms change
$currentMaint = $db->query("SELECT COUNT(*) FROM rooms WHERE status = 'maintenance'")->fetchColumn();
$prevMonthMaint = $db->query("SELECT COUNT(*) FROM booking_logs WHERE action = 'maintenance' AND MONTH(created_at) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) AND YEAR(created_at) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))")->fetchColumn() ?: 0;
$maintenanceChange = calculatePercentageChange($currentMaint, $prevMonthMaint);

// Event spaces changes
$currentMonthEventAvg = $db->query("SELECT AVG(daily_count) FROM (SELECT DATE(created_at) as date, COUNT(*) as daily_count FROM event_bookings WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE()) GROUP BY DATE(created_at)) as avg_table")->fetchColumn() ?: 0;
$prevMonthEventAvg = $db->query("SELECT AVG(daily_count) FROM (SELECT DATE(created_at) as date, COUNT(*) as daily_count FROM event_bookings WHERE MONTH(created_at) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) AND YEAR(created_at) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) GROUP BY DATE(created_at)) as avg_table")->fetchColumn() ?: 0;
$occupiedEventChange = calculatePercentageChange($currentMonthEventAvg, $prevMonthEventAvg);
$availableEventChange = ['value' => 50, 'label' => '+50%'];

// Food orders status changes
$currentMonthPendingAvg = $db->query("SELECT AVG(daily_count) FROM (SELECT DATE(created_at) as date, COUNT(*) as daily_count FROM food_orders WHERE status = 'pending' AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE()) GROUP BY DATE(created_at)) as avg_table")->fetchColumn() ?: 0;
$prevMonthPendingAvg = $db->query("SELECT AVG(daily_count) FROM (SELECT DATE(created_at) as date, COUNT(*) as daily_count FROM food_orders WHERE status = 'pending' AND MONTH(created_at) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) AND YEAR(created_at) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) GROUP BY DATE(created_at)) as avg_table")->fetchColumn() ?: 0;
$pendingChange = calculatePercentageChange($currentMonthPendingAvg, $prevMonthPendingAvg);

$currentMonthPreparingAvg = $db->query("SELECT AVG(daily_count) FROM (SELECT DATE(created_at) as date, COUNT(*) as daily_count FROM food_orders WHERE status = 'preparing' AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE()) GROUP BY DATE(created_at)) as avg_table")->fetchColumn() ?: 0;
$prevMonthPreparingAvg = $db->query("SELECT AVG(daily_count) FROM (SELECT DATE(created_at) as date, COUNT(*) as daily_count FROM food_orders WHERE status = 'preparing' AND MONTH(created_at) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) AND YEAR(created_at) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) GROUP BY DATE(created_at)) as avg_table")->fetchColumn() ?: 0;
$preparingChange = calculatePercentageChange($currentMonthPreparingAvg, $prevMonthPreparingAvg);

// ============================================
// ADDITIONAL DASHBOARD STATISTICS
// ============================================

// Staff & Operations
$staffOnDuty = $db->query("SELECT COUNT(DISTINCT user_id) FROM staff_schedules WHERE work_date = CURDATE() AND status = 'scheduled'")->fetchColumn();
$pendingMaintenance = $db->query("SELECT COUNT(*) FROM maintenance_requests WHERE status IN ('pending', 'in_progress')")->fetchColumn();
$inventoryAlerts = $db->query("SELECT COUNT(*) FROM inventory_items WHERE quantity <= reorder_level")->fetchColumn();

// Guest Services
$pendingReviews = $db->query("SELECT COUNT(*) FROM reviews WHERE is_approved = FALSE")->fetchColumn();
$averageRating = $db->query("
    SELECT COALESCE(AVG(rating), 0) FROM (
        SELECT rating as rating FROM reviews WHERE is_approved = TRUE
        UNION ALL
        SELECT rating_value as rating FROM ratings WHERE is_rated = 1
    ) as combined_ratings
")->fetchColumn();
$activePromotions = $db->query("SELECT COUNT(*) FROM promotions WHERE is_active = TRUE AND start_date <= CURDATE() AND end_date >= CURDATE()")->fetchColumn();

// Financial
$pendingPayments = $db->query("SELECT COUNT(*) FROM payments WHERE status = 'pending'")->fetchColumn();
$todayRevenue = $db->query("SELECT COALESCE(SUM(total_amount), 0) FROM bookings WHERE DATE(created_at) = CURDATE() AND status IN ('confirmed', 'checked_out')")->fetchColumn();

// Content Management
$galleryImages = $db->query("SELECT COUNT(*) FROM gallery")->fetchColumn();
$activeFaqs = $db->query("SELECT COUNT(*) FROM faqs WHERE is_active = TRUE")->fetchColumn();
$virtualTours = $db->query("SELECT COUNT(*) FROM event_virtual_tours WHERE is_active = 1")->fetchColumn() + $db->query("SELECT COUNT(*) FROM room_virtual_tours WHERE is_active = 1")->fetchColumn();

// Additional percentage calculations
$staffChange = calculatePercentageChange($staffOnDuty, 1);
$maintenanceChange = calculatePercentageChange($pendingMaintenance, 1);
$inventoryChange = calculatePercentageChange($inventoryAlerts, 1);
$reviewsChange = calculatePercentageChange($pendingReviews, 1);
$promotionsChange = calculatePercentageChange($activePromotions, 1);
$paymentsChange = calculatePercentageChange($pendingPayments, 1);
$todayRevenueChange = calculatePercentageChange($todayRevenue, 1);
$galleryChange = calculatePercentageChange($galleryImages, 1);
$faqsChange = calculatePercentageChange($activeFaqs, 1);
$toursChange = calculatePercentageChange($virtualTours, 1);

// Core Statistics
$stats = [
    'total_users' => $db->query("SELECT COUNT(*) FROM users WHERE role = 'guest'")->fetchColumn(),
    'total_bookings' => $currentBookings,
    'total_revenue' => $currentRevenue,
    'occupied_rooms' => $db->query("SELECT COUNT(*) FROM rooms WHERE status = 'occupied'")->fetchColumn(),
    'available_rooms' => $db->query("SELECT COUNT(*) FROM rooms WHERE status = 'available'")->fetchColumn(),
    'maintenance_rooms' => $db->query("SELECT COUNT(*) FROM rooms WHERE status = 'maintenance'")->fetchColumn(),
    // New Event Statistics
    'total_events' => $currentEvents,
    'occupied_event_spaces' => $db->query("SELECT COUNT(*) FROM event_spaces WHERE status = 'booked'")->fetchColumn(),
    'available_event_spaces' => $db->query("SELECT COUNT(*) FROM event_spaces WHERE status = 'available'")->fetchColumn(),
    // Food Orders Statistics
    'total_food_orders' => $db->query("SELECT COUNT(*) FROM food_orders WHERE DATE(created_at) = CURDATE()")->fetchColumn(),
    'pending_food_orders' => $db->query("SELECT COUNT(*) FROM food_orders WHERE status = 'pending'")->fetchColumn(),
    'preparing_food_orders' => $db->query("SELECT COUNT(*) FROM food_orders WHERE status = 'preparing'")->fetchColumn(),
    'completed_food_orders' => $db->query("SELECT COUNT(*) FROM food_orders WHERE status IN ('ready', 'delivered')")->fetchColumn(),
    // Percentage Changes - Primary Stats
    'users_change' => $usersChange,
    'bookings_change' => $bookingsChange,
    'revenue_change' => $revenueChange,
    'events_change' => $eventsChange,
    'food_orders_change' => $foodOrdersChange,
    // Percentage Changes - Secondary Stats
    'occupied_rooms_change' => $occupiedRoomsChange,
    'available_rooms_change' => $availableRoomsChange,
    'maintenance_change' => $maintenanceChange,
    'occupied_event_change' => $occupiedEventChange,
    'available_event_change' => $availableEventChange,
    'pending_change' => $pendingChange,
    'preparing_change' => $preparingChange,
    // Additional Stats - Staff & Operations
    'staff_on_duty' => $staffOnDuty,
    'pending_maintenance' => $pendingMaintenance,
    'inventory_alerts' => $inventoryAlerts,
    'staff_change' => $staffChange,
    'maintenance_req_change' => $maintenanceChange,
    'inventory_change' => $inventoryChange,
    // Additional Stats - Guest Services
    'pending_reviews' => $pendingReviews,
    'average_rating' => $averageRating,
    'active_promotions' => $activePromotions,
    'reviews_change' => $reviewsChange,
    'promotions_change' => $promotionsChange,
    // Additional Stats - Financial
    'pending_payments' => $pendingPayments,
    'today_revenue' => $todayRevenue,
    'payments_change' => $paymentsChange,
    'today_revenue_change' => $todayRevenueChange,
    // Additional Stats - Content Management
    'gallery_images' => $galleryImages,
    'active_faqs' => $activeFaqs,
    'virtual_tours' => $virtualTours,
    'gallery_change' => $galleryChange,
    'faqs_change' => $faqsChange,
    'tours_change' => $toursChange,
];

// ============================================
// RECENT ACTIVITY DATA
// ============================================

// Recent Room Bookings
$recentBookings = $db->query("
    SELECT b.*, u.first_name, u.last_name, rc.category_name 
    FROM bookings b 
    JOIN users u ON b.user_id = u.user_id 
    JOIN room_categories rc ON b.category_id = rc.category_id 
    ORDER BY b.created_at DESC 
    LIMIT 5
")->fetchAll();

// Recent Event Bookings
$recentEventBookings = $db->query("
    SELECT eb.*, es.space_name, 
           COALESCE(u.first_name, eb.inquiry_name) as first_name, 
           COALESCE(u.last_name, '') as last_name
    FROM event_bookings eb 
    LEFT JOIN users u ON eb.user_id = u.user_id 
    JOIN event_spaces es ON eb.space_id = es.space_id 
    ORDER BY eb.created_at DESC 
    LIMIT 5
")->fetchAll();

// Recent Food Orders
$recentFoodOrders = $db->query("
    SELECT fo.*, u.first_name, u.last_name, mi.item_name
    FROM food_orders fo 
    JOIN users u ON fo.user_id = u.user_id 
    JOIN menu_items mi ON fo.food_id = mi.item_id 
    ORDER BY fo.created_at DESC 
    LIMIT 5
")->fetchAll();

// ============================================
// OCCUPANCY DATA
// ============================================

// Room Occupancy by Category
$occupancyByCategory = $db->query("
    SELECT rc.category_name, COUNT(r.room_id) as total,
    SUM(CASE WHEN r.status = 'occupied' THEN 1 ELSE 0 END) as occupied,
    SUM(CASE WHEN r.status = 'available' THEN 1 ELSE 0 END) as available
    FROM room_categories rc
    LEFT JOIN rooms r ON rc.category_id = r.category_id
    GROUP BY rc.category_id
")->fetchAll();

// Event Space Occupancy
$eventSpaceOccupancy = $db->query("
    SELECT es.space_name, 
           COUNT(eb.event_booking_id) as booked_count,
           (SELECT COUNT(*) FROM event_spaces es2 WHERE es2.space_id = es.space_id) as total
    FROM event_spaces es
    LEFT JOIN event_bookings eb ON es.space_id = eb.space_id 
        AND eb.event_date >= CURDATE() 
        AND eb.status IN ('confirmed', 'pending')
    GROUP BY es.space_id
")->fetchAll();

// Get monthly revenue data for chart
$revenueData = $db->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, SUM(total_amount) as revenue 
    FROM bookings 
    WHERE status IN ('confirmed', 'checked_out') 
    AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY month 
    ORDER BY month
")->fetchAll();

// Status color mapping helper
$statusColors = [
    'pending' => ['bg' => '#fff3cd', 'text' => '#856404', 'label' => 'Pending'],
    'confirmed' => ['bg' => '#d4edda', 'text' => '#155724', 'label' => 'Confirmed'],
    'checked_in' => ['bg' => '#cce5ff', 'text' => '#004085', 'label' => 'Checked In'],
    'checked_out' => ['bg' => '#e2e3e5', 'text' => '#383d41', 'label' => 'Checked Out'],
    'cancelled' => ['bg' => '#f8d7da', 'text' => '#721c24', 'label' => 'Cancelled'],
    'completed' => ['bg' => '#d4edda', 'text' => '#155724', 'label' => 'Completed'],
    'preparing' => ['bg' => '#fff3cd', 'text' => '#856404', 'label' => 'Preparing'],
    'ready' => ['bg' => '#d1ecf1', 'text' => '#0c5460', 'label' => 'Ready'],
    'delivered' => ['bg' => '#d4edda', 'text' => '#155724', 'label' => 'Delivered'],
];

function getStatusBadge($status, $statusColors) {
    $color = $statusColors[$status] ?? ['bg' => '#e2e3e5', 'text' => '#383d41', 'label' => ucfirst($status)];
    return "<span style='padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: {$color['bg']}; color: {$color['text']}'>{$color['label']}</span>";
}
?>

<!-- Admin Content -->
<section style="padding: 30px 0; background-color: var(--gray-light); min-height: calc(100vh - 250px);">
    <div class="container">
        <div style="display: flex; justify-content: flex-end; gap: 15px; margin-bottom: 30px;">
            <a href="admin-users.php" class="btn btn-outline">Manage Users</a>
            <a href="admin-rooms.php" class="btn btn-primary">Manage Rooms</a>
        </div>
        <!-- DASHBOARD SUMMARY CARDS -->
        <h2 style="font-size: 18px; color: var(--dark-color); margin-bottom: 20px; font-weight: 600;">
            <i class="fas fa-chart-line" style="margin-right: 8px; color: var(--primary-color);"></i>Dashboard Overview
        </h2>

        <!-- Primary Stats Row -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 20px;">
            <!-- Total Users -->
            <div style="background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                    <i class="fas fa-users" style="font-size: 30px; color: var(--primary-color);"></i>
                    <?php
                    $usersColor = $stats['users_change']['value'] >= 0 ? ['text' => '#28a745', 'bg' => '#d4edda', 'icon' => 'fa-arrow-up'] : ['text' => '#dc3545', 'bg' => '#f8d7da', 'icon' => 'fa-arrow-down'];
                    ?>
                    <span style="font-size: 12px; color: <?php echo $usersColor['text']; ?>; background: <?php echo $usersColor['bg']; ?>; padding: 3px 8px; border-radius: 12px;"><i class="fas <?php echo $usersColor['icon']; ?>"></i> <?php echo $stats['users_change']['label']; ?></span>
                </div>
                <h3 style="font-size: 28px; margin-bottom: 5px;"><?php echo number_format($stats['total_users']); ?></h3>
                <p style="font-size: 14px; color: #666; margin: 0;">Total Users</p>
            </div>
            
            <!-- Bookings (30 Days) -->
            <div style="background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                    <i class="fas fa-calendar-check" style="font-size: 30px; color: var(--info-color);"></i>
                    <?php
                    $bookingsColor = $stats['bookings_change']['value'] >= 0 ? ['text' => '#28a745', 'bg' => '#d4edda', 'icon' => 'fa-arrow-up'] : ['text' => '#dc3545', 'bg' => '#f8d7da', 'icon' => 'fa-arrow-down'];
                    ?>
                    <span style="font-size: 12px; color: <?php echo $bookingsColor['text']; ?>; background: <?php echo $bookingsColor['bg']; ?>; padding: 3px 8px; border-radius: 12px;"><i class="fas <?php echo $bookingsColor['icon']; ?>"></i> <?php echo $stats['bookings_change']['label']; ?></span>
                </div>
                <h3 style="font-size: 28px; margin-bottom: 5px;"><?php echo number_format($stats['total_bookings']); ?></h3>
                <p style="font-size: 14px; color: #666; margin: 0;">Bookings (30 Days)</p>
            </div>
            
            <!-- Revenue This Month -->
            <div style="background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                    <i class="fas fa-peso-sign" style="font-size: 30px; color: var(--success-color);"></i>
                    <?php
                    $revenueColor = $stats['revenue_change']['value'] >= 0 ? ['text' => '#28a745', 'bg' => '#d4edda', 'icon' => 'fa-arrow-up'] : ['text' => '#dc3545', 'bg' => '#f8d7da', 'icon' => 'fa-arrow-down'];
                    ?>
                    <span style="font-size: 12px; color: <?php echo $revenueColor['text']; ?>; background: <?php echo $revenueColor['bg']; ?>; padding: 3px 8px; border-radius: 12px;"><i class="fas <?php echo $revenueColor['icon']; ?>"></i> <?php echo $stats['revenue_change']['label']; ?></span>
                </div>
                <h3 style="font-size: 28px; margin-bottom: 5px;">₱<?php echo number_format($stats['total_revenue']); ?></h3>
                <p style="font-size: 14px; color: #666; margin: 0;">Revenue (This Month)</p>
            </div>
            
            <!-- Total Events -->
            <div style="background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                    <i class="fas fa-calendar-check" style="font-size: 30px; color: #9c27b0;"></i>
                    <?php
                    $eventsColor = $stats['events_change']['value'] >= 0 ? ['text' => '#28a745', 'bg' => '#d4edda', 'icon' => 'fa-arrow-up'] : ['text' => '#dc3545', 'bg' => '#f8d7da', 'icon' => 'fa-arrow-down'];
                    ?>
                    <span style="font-size: 12px; color: <?php echo $eventsColor['text']; ?>; background: <?php echo $eventsColor['bg']; ?>; padding: 3px 8px; border-radius: 12px;"><i class="fas <?php echo $eventsColor['icon']; ?>"></i> <?php echo $stats['events_change']['label']; ?></span>
                </div>
                <h3 style="font-size: 28px; margin-bottom: 5px;"><?php echo number_format($stats['total_events']); ?></h3>
                <p style="font-size: 14px; color: #666; margin: 0;">Total Events (This Month)</p>
            </div>
        </div>

        <!-- Secondary Stats Row - Room Status -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 20px;">
            <!-- Occupied Rooms -->
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                    <i class="fas fa-bed" style="font-size: 24px; color: var(--warning-color);"></i>
                    <?php
                    $occColor = $stats['occupied_rooms_change']['value'] >= 0 ? ['text' => '#28a745', 'bg' => '#d4edda'] : ['text' => '#dc3545', 'bg' => '#f8d7da'];
                    ?>
                    <span style="font-size: 11px; color: <?php echo $occColor['text']; ?>; background: <?php echo $occColor['bg']; ?>; padding: 2px 6px; border-radius: 10px;"><?php echo $stats['occupied_rooms_change']['label']; ?></span>
                </div>
                <h3 style="font-size: 24px; margin-bottom: 5px;"><?php echo $stats['occupied_rooms']; ?></h3>
                <p style="font-size: 13px; color: #666; margin: 0;">Occupied Rooms</p>
            </div>
            
            <!-- Available Rooms -->
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                    <i class="fas fa-door-open" style="font-size: 24px; color: var(--success-color);"></i>
                    <?php
                    $availColor = $stats['available_rooms_change']['value'] >= 0 ? ['text' => '#28a745', 'bg' => '#d4edda'] : ['text' => '#dc3545', 'bg' => '#f8d7da'];
                    ?>
                    <span style="font-size: 11px; color: <?php echo $availColor['text']; ?>; background: <?php echo $availColor['bg']; ?>; padding: 2px 6px; border-radius: 10px;"><?php echo $stats['available_rooms_change']['label']; ?></span>
                </div>
                <h3 style="font-size: 24px; margin-bottom: 5px;"><?php echo $stats['available_rooms']; ?></h3>
                <p style="font-size: 13px; color: #666; margin: 0;">Available Rooms</p>
            </div>
            
            <!-- Rooms Under Maintenance -->
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                    <i class="fas fa-tools" style="font-size: 24px; color: var(--danger-color);"></i>
                    <?php
                    $maintColor = $stats['maintenance_change']['value'] >= 0 ? ['text' => '#28a745', 'bg' => '#d4edda'] : ['text' => '#dc3545', 'bg' => '#f8d7da'];
                    ?>
                    <span style="font-size: 11px; color: <?php echo $maintColor['text']; ?>; background: <?php echo $maintColor['bg']; ?>; padding: 2px 6px; border-radius: 10px;"><?php echo $stats['maintenance_change']['label']; ?></span>
                </div>
                <h3 style="font-size: 24px; margin-bottom: 5px;"><?php echo $stats['maintenance_rooms']; ?></h3>
                <p style="font-size: 13px; color: #666; margin: 0;">Rooms Under Maintenance</p>
            </div>
            
            <!-- Food Orders Today -->
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                    <i class="fas fa-utensils" style="font-size: 24px; color: #ff9800;"></i>
                    <?php
                    $foodColor = $stats['food_orders_change']['value'] >= 0 ? ['text' => '#28a745', 'bg' => '#d4edda'] : ['text' => '#dc3545', 'bg' => '#f8d7da'];
                    ?>
                    <span style="font-size: 11px; color: <?php echo $foodColor['text']; ?>; background: <?php echo $foodColor['bg']; ?>; padding: 2px 6px; border-radius: 10px;"><?php echo $stats['food_orders_change']['label']; ?></span>
                </div>
                <h3 style="font-size: 24px; margin-bottom: 5px;"><?php echo number_format($stats['total_food_orders']); ?></h3>
                <p style="font-size: 13px; color: #666; margin: 0;">Food Orders (Today)</p>
            </div>
        </div>

        <!-- Tertiary Stats Row - Event Spaces & Food Orders Detail -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px;">
            <!-- Occupied Event Spaces -->
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                    <i class="fas fa-champagne-glasses" style="font-size: 24px; color: #e91e63;"></i>
                    <?php
                    $occEventColor = $stats['occupied_event_change']['value'] >= 0 ? ['text' => '#28a745', 'bg' => '#d4edda'] : ['text' => '#dc3545', 'bg' => '#f8d7da'];
                    ?>
                    <span style="font-size: 11px; color: <?php echo $occEventColor['text']; ?>; background: <?php echo $occEventColor['bg']; ?>; padding: 2px 6px; border-radius: 10px;"><?php echo $stats['occupied_event_change']['label']; ?></span>
                </div>
                <h3 style="font-size: 24px; margin-bottom: 5px;"><?php echo $stats['occupied_event_spaces']; ?></h3>
                <p style="font-size: 13px; color: #666; margin: 0;">Occupied Event Spaces</p>
            </div>
            
            <!-- Available Event Spaces -->
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                    <i class="fas fa-house-chimney" style="font-size: 24px; color: #4caf50;"></i>
                    <?php
                    $availEventColor = $stats['available_event_change']['value'] >= 0 ? ['text' => '#28a745', 'bg' => '#d4edda'] : ['text' => '#dc3545', 'bg' => '#f8d7da'];
                    ?>
                    <span style="font-size: 11px; color: <?php echo $availEventColor['text']; ?>; background: <?php echo $availEventColor['bg']; ?>; padding: 2px 6px; border-radius: 10px;"><?php echo $stats['available_event_change']['label']; ?></span>
                </div>
                <h3 style="font-size: 24px; margin-bottom: 5px;"><?php echo $stats['available_event_spaces']; ?></h3>
                <p style="font-size: 13px; color: #666; margin: 0;">Available Event Spaces</p>
            </div>
            
            <!-- Pending Food Orders -->
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                    <i class="fas fa-clock" style="font-size: 24px; color: #ff5722;"></i>
                    <?php
                    $pendingColor = $stats['pending_change']['value'] >= 0 ? ['text' => '#28a745', 'bg' => '#d4edda'] : ['text' => '#dc3545', 'bg' => '#f8d7da'];
                    ?>
                    <span style="font-size: 11px; color: <?php echo $pendingColor['text']; ?>; background: <?php echo $pendingColor['bg']; ?>; padding: 2px 6px; border-radius: 10px;"><?php echo $stats['pending_change']['label']; ?></span>
                </div>
                <h3 style="font-size: 24px; margin-bottom: 5px;"><?php echo $stats['pending_food_orders']; ?></h3>
                <p style="font-size: 13px; color: #666; margin: 0;">Pending Food Orders</p>
            </div>
            
            <!-- Preparing Food Orders -->
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                    <i class="fas fa-fire-burner" style="font-size: 24px; color: #ff9800;"></i>
                    <?php
                    $preparingColor = $stats['preparing_change']['value'] >= 0 ? ['text' => '#28a745', 'bg' => '#d4edda'] : ['text' => '#dc3545', 'bg' => '#f8d7da'];
                    ?>
                    <span style="font-size: 11px; color: <?php echo $preparingColor['text']; ?>; background: <?php echo $preparingColor['bg']; ?>; padding: 2px 6px; border-radius: 10px;"><?php echo $stats['preparing_change']['label']; ?></span>
                </div>
                <h3 style="font-size: 24px; margin-bottom: 5px;"><?php echo $stats['preparing_food_orders']; ?></h3>
                <p style="font-size: 13px; color: #666; margin: 0;">Preparing Orders</p>
            </div>
        </div>

        <!-- ADDITIONAL STATS - Staff & Operations -->
        <h2 style="font-size: 16px; color: var(--dark-color); margin-bottom: 15px; font-weight: 600;">
            <i class="fas fa-users-gear" style="margin-right: 8px; color: var(--primary-color);"></i>Staff & Operations
        </h2>
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 25px;">
            <!-- Staff on Duty Today -->
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                    <i class="fas fa-user-clock" style="font-size: 24px; color: #17a2b8;"></i>
                    <?php $staffColor = $stats['staff_change']['value'] >= 0 ? ['text' => '#28a745', 'bg' => '#d4edda'] : ['text' => '#dc3545', 'bg' => '#f8d7da']; ?>
                    <span style="font-size: 11px; color: <?php echo $staffColor['text']; ?>; background: <?php echo $staffColor['bg']; ?>; padding: 2px 6px; border-radius: 10px;"><?php echo $stats['staff_change']['label']; ?></span>
                </div>
                <h3 style="font-size: 24px; margin-bottom: 5px;"><?php echo $stats['staff_on_duty']; ?></h3>
                <p style="font-size: 13px; color: #666; margin: 0;">Staff on Duty Today</p>
            </div>
            
            <!-- Pending Maintenance -->
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                    <i class="fas fa-wrench" style="font-size: 24px; color: #fd7e14;"></i>
                    <?php $maintReqColor = $stats['maintenance_req_change']['value'] >= 0 ? ['text' => '#28a745', 'bg' => '#d4edda'] : ['text' => '#dc3545', 'bg' => '#f8d7da']; ?>
                    <span style="font-size: 11px; color: <?php echo $maintReqColor['text']; ?>; background: <?php echo $maintReqColor['bg']; ?>; padding: 2px 6px; border-radius: 10px;"><?php echo $stats['maintenance_req_change']['label']; ?></span>
                </div>
                <h3 style="font-size: 24px; margin-bottom: 5px;"><?php echo $stats['pending_maintenance']; ?></h3>
                <p style="font-size: 13px; color: #666; margin: 0;">Pending Maintenance</p>
            </div>
            
            <!-- Inventory Alerts -->
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                    <i class="fas fa-boxes-stacked" style="font-size: 24px; color: #6f42c1;"></i>
                    <?php $invColor = $stats['inventory_change']['value'] >= 0 ? ['text' => '#28a745', 'bg' => '#d4edda'] : ['text' => '#dc3545', 'bg' => '#f8d7da']; ?>
                    <span style="font-size: 11px; color: <?php echo $invColor['text']; ?>; background: <?php echo $invColor['bg']; ?>; padding: 2px 6px; border-radius: 10px;"><?php echo $stats['inventory_change']['label']; ?></span>
                </div>
                <h3 style="font-size: 24px; margin-bottom: 5px;"><?php echo $stats['inventory_alerts']; ?></h3>
                <p style="font-size: 13px; color: #666; margin: 0;">Inventory Alerts</p>
            </div>
            
            <!-- Placeholder for spacing -->
            <div style="background-color: transparent; padding: 20px; border-radius: 10px;"></div>
        </div>

        <!-- ADDITIONAL STATS - Guest Services & Financial -->
        <h2 style="font-size: 16px; color: var(--dark-color); margin-bottom: 15px; font-weight: 600;">
            <i class="fas fa-star" style="margin-right: 8px; color: var(--primary-color);"></i>Guest Services & Financial
        </h2>
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 25px;">
            <!-- Pending Reviews -->
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                    <i class="fas fa-comments" style="font-size: 24px; color: #20c997;"></i>
                    <?php $revColor = $stats['reviews_change']['value'] >= 0 ? ['text' => '#28a745', 'bg' => '#d4edda'] : ['text' => '#dc3545', 'bg' => '#f8d7da']; ?>
                    <span style="font-size: 11px; color: <?php echo $revColor['text']; ?>; background: <?php echo $revColor['bg']; ?>; padding: 2px 6px; border-radius: 10px;"><?php echo $stats['reviews_change']['label']; ?></span>
                </div>
                <h3 style="font-size: 24px; margin-bottom: 5px;"><?php echo $stats['pending_reviews']; ?></h3>
                <p style="font-size: 13px; color: #666; margin: 0;">Pending Reviews</p>
            </div>
            
            <!-- Average Rating -->
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                    <i class="fas fa-star" style="font-size: 24px; color: #ffc107;"></i>
                </div>
                <h3 style="font-size: 24px; margin-bottom: 5px;"><?php echo number_format($stats['average_rating'], 1); ?>/5</h3>
                <p style="font-size: 13px; color: #666; margin: 0;">Average Rating</p>
            </div>
            
            <!-- Active Promotions -->
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                    <i class="fas fa-tags" style="font-size: 24px; color: #e83e8c;"></i>
                    <?php $promoColor = $stats['promotions_change']['value'] >= 0 ? ['text' => '#28a745', 'bg' => '#d4edda'] : ['text' => '#dc3545', 'bg' => '#f8d7da']; ?>
                    <span style="font-size: 11px; color: <?php echo $promoColor['text']; ?>; background: <?php echo $promoColor['bg']; ?>; padding: 2px 6px; border-radius: 10px;"><?php echo $stats['promotions_change']['label']; ?></span>
                </div>
                <h3 style="font-size: 24px; margin-bottom: 5px;"><?php echo $stats['active_promotions']; ?></h3>
                <p style="font-size: 13px; color: #666; margin: 0;">Active Promotions</p>
            </div>
            
            <!-- Today's Revenue -->
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                    <i class="fas fa-coins" style="font-size: 24px; color: #28a745;"></i>
                    <?php $todayRevColor = $stats['today_revenue_change']['value'] >= 0 ? ['text' => '#28a745', 'bg' => '#d4edda'] : ['text' => '#dc3545', 'bg' => '#f8d7da']; ?>
                    <span style="font-size: 11px; color: <?php echo $todayRevColor['text']; ?>; background: <?php echo $todayRevColor['bg']; ?>; padding: 2px 6px; border-radius: 10px;"><?php echo $stats['today_revenue_change']['label']; ?></span>
                </div>
                <h3 style="font-size: 24px; margin-bottom: 5px;">₱<?php echo number_format($stats['today_revenue']); ?></h3>
                <p style="font-size: 13px; color: #666; margin: 0;">Today's Revenue</p>
            </div>
        </div>

        <!-- ADDITIONAL STATS - Content Management -->
        <h2 style="font-size: 16px; color: var(--dark-color); margin-bottom: 15px; font-weight: 600;">
            <i class="fas fa-images" style="margin-right: 8px; color: var(--primary-color);"></i>Content Management
        </h2>
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 25px;">
            <!-- Gallery Images -->
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                    <i class="fas fa-images" style="font-size: 24px; color: #6610f2;"></i>
                    <?php $galColor = $stats['gallery_change']['value'] >= 0 ? ['text' => '#28a745', 'bg' => '#d4edda'] : ['text' => '#dc3545', 'bg' => '#f8d7da']; ?>
                    <span style="font-size: 11px; color: <?php echo $galColor['text']; ?>; background: <?php echo $galColor['bg']; ?>; padding: 2px 6px; border-radius: 10px;"><?php echo $stats['gallery_change']['label']; ?></span>
                </div>
                <h3 style="font-size: 24px; margin-bottom: 5px;"><?php echo $stats['gallery_images']; ?></h3>
                <p style="font-size: 13px; color: #666; margin: 0;">Gallery Images</p>
            </div>
            
            <!-- Active FAQs -->
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                    <i class="fas fa-circle-question" style="font-size: 24px; color: #0dcaf0;"></i>
                    <?php $faqColor = $stats['faqs_change']['value'] >= 0 ? ['text' => '#28a745', 'bg' => '#d4edda'] : ['text' => '#dc3545', 'bg' => '#f8d7da']; ?>
                    <span style="font-size: 11px; color: <?php echo $faqColor['text']; ?>; background: <?php echo $faqColor['bg']; ?>; padding: 2px 6px; border-radius: 10px;"><?php echo $stats['faqs_change']['label']; ?></span>
                </div>
                <h3 style="font-size: 24px; margin-bottom: 5px;"><?php echo $stats['active_faqs']; ?></h3>
                <p style="font-size: 13px; color: #666; margin: 0;">Active FAQs</p>
            </div>
            
            <!-- Virtual Tours -->
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                    <i class="fas fa-vr-cardboard" style="font-size: 24px; color: #d63384;"></i>
                    <?php $tourColor = $stats['tours_change']['value'] >= 0 ? ['text' => '#28a745', 'bg' => '#d4edda'] : ['text' => '#dc3545', 'bg' => '#f8d7da']; ?>
                    <span style="font-size: 11px; color: <?php echo $tourColor['text']; ?>; background: <?php echo $tourColor['bg']; ?>; padding: 2px 6px; border-radius: 10px;"><?php echo $stats['tours_change']['label']; ?></span>
                </div>
                <h3 style="font-size: 24px; margin-bottom: 5px;"><?php echo $stats['virtual_tours']; ?></h3>
                <p style="font-size: 13px; color: #666; margin: 0;">Virtual Tours</p>
            </div>
            
            <!-- Pending Payments -->
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                    <i class="fas fa-credit-card" style="font-size: 24px; color: #dc3545;"></i>
                    <?php $payColor = $stats['payments_change']['value'] >= 0 ? ['text' => '#28a745', 'bg' => '#d4edda'] : ['text' => '#dc3545', 'bg' => '#f8d7da']; ?>
                    <span style="font-size: 11px; color: <?php echo $payColor['text']; ?>; background: <?php echo $payColor['bg']; ?>; padding: 2px 6px; border-radius: 10px;"><?php echo $stats['payments_change']['label']; ?></span>
                </div>
                <h3 style="font-size: 24px; margin-bottom: 5px;"><?php echo $stats['pending_payments']; ?></h3>
                <p style="font-size: 13px; color: #666; margin: 0;">Pending Payments</p>
            </div>
        </div>
        
        <!-- RECENT ACTIVITY SECTION -->
        <h2 style="font-size: 18px; color: var(--dark-color); margin-bottom: 20px; font-weight: 600;">
            <i class="fas fa-clock-rotate-left" style="margin-right: 8px; color: var(--primary-color);"></i>Recent Activity
        </h2>

        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; margin-bottom: 30px;">
            <!-- Recent Room Bookings -->
            <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="padding: 20px 25px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="font-size: 16px; margin: 0; font-weight: 600;"><i class="fas fa-bed" style="margin-right: 8px; color: var(--primary-color);"></i>Recent Bookings</h3>
                    <a href="admin-bookings.php" style="color: var(--primary-color); font-size: 13px;">View All</a>
                </div>
                <div style="padding: 0; max-height: 400px; overflow-y: auto;">
                    <?php if (empty($recentBookings)): ?>
                    <div style="padding: 30px; text-align: center; color: #666;">
                        <p>No recent bookings</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($recentBookings as $booking): ?>
                    <div style="padding: 15px 20px; border-bottom: 1px solid var(--gray-light);">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                            <div>
                                <h4 style="font-size: 14px; margin: 0 0 3px 0; font-weight: 600;"><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></h4>
                                <p style="font-size: 12px; color: #666; margin: 0;"><?php echo htmlspecialchars($booking['category_name']); ?></p>
                            </div>
                            <div style="text-align: right;">
                                <p style="font-size: 13px; font-weight: 600; color: var(--primary-color); margin: 0;"><?php echo formatPrice($booking['total_amount']); ?></p>
                            </div>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 12px; color: #666;">
                                <i class="fas fa-calendar" style="margin-right: 4px;"></i><?php echo formatDate($booking['check_in'], 'M d, Y'); ?>
                            </span>
                            <?php echo getStatusBadge($booking['status'], $statusColors); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recent Event Bookings -->
            <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="padding: 20px 25px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="font-size: 16px; margin: 0; font-weight: 600;"><i class="fas fa-champagne-glasses" style="margin-right: 8px; color: #9c27b0;"></i>Recent Event Bookings</h3>
                    <a href="admin-events.php" style="color: var(--primary-color); font-size: 13px;">View All</a>
                </div>
                <div style="padding: 0; max-height: 400px; overflow-y: auto;">
                    <?php if (empty($recentEventBookings)): ?>
                    <div style="padding: 30px; text-align: center; color: #666;">
                        <p>No recent event bookings</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($recentEventBookings as $event): ?>
                    <div style="padding: 15px 20px; border-bottom: 1px solid var(--gray-light);">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                            <div>
                                <h4 style="font-size: 14px; margin: 0 0 3px 0; font-weight: 600;"><?php echo htmlspecialchars($event['event_type'] ?: 'Event'); ?></h4>
                                <p style="font-size: 12px; color: #666; margin: 0;"><?php echo htmlspecialchars($event['space_name']); ?></p>
                            </div>
                        </div>
                        <div style="margin-bottom: 8px;">
                            <p style="font-size: 12px; color: #666; margin: 0;">
                                <i class="fas fa-user" style="margin-right: 4px;"></i><?php echo htmlspecialchars($event['first_name'] . ' ' . $event['last_name']); ?>
                            </p>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 12px; color: #666;">
                                <i class="fas fa-calendar" style="margin-right: 4px;"></i><?php echo formatDate($event['event_date'], 'M d, Y'); ?>
                            </span>
                            <?php echo getStatusBadge($event['status'], $statusColors); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recent Food Orders -->
            <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="padding: 20px 25px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="font-size: 16px; margin: 0; font-weight: 600;"><i class="fas fa-utensils" style="margin-right: 8px; color: #ff9800;"></i>Recent Food Orders</h3>
                    <a href="admin-food-orders.php" style="color: var(--primary-color); font-size: 13px;">View All</a>
                </div>
                <div style="padding: 0; max-height: 400px; overflow-y: auto;">
                    <?php if (empty($recentFoodOrders)): ?>
                    <div style="padding: 30px; text-align: center; color: #666;">
                        <p>No recent food orders</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($recentFoodOrders as $order): ?>
                    <div style="padding: 15px 20px; border-bottom: 1px solid var(--gray-light);">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                            <div>
                                <h4 style="font-size: 14px; margin: 0 0 3px 0; font-weight: 600;"><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></h4>
                                <p style="font-size: 12px; color: #666; margin: 0;"><?php echo htmlspecialchars($order['item_name']); ?> x<?php echo $order['quantity']; ?></p>
                            </div>
                            <div style="text-align: right;">
                                <p style="font-size: 13px; font-weight: 600; color: var(--primary-color); margin: 0;">₱<?php echo number_format($order['total_price'], 2); ?></p>
                            </div>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 12px; color: #666;">
                                <i class="fas fa-door-open" style="margin-right: 4px;"></i>Room <?php echo htmlspecialchars($order['room_number'] ?: 'N/A'); ?>
                            </span>
                            <?php echo getStatusBadge($order['status'], $statusColors); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- OCCUPANCY SECTION -->
        <h2 style="font-size: 18px; color: var(--dark-color); margin-bottom: 20px; font-weight: 600;">
            <i class="fas fa-building" style="margin-right: 8px; color: var(--primary-color);"></i>Occupancy Overview
        </h2>

        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; margin-bottom: 30px;">
            <!-- Room Occupancy -->
            <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="padding: 20px 25px; border-bottom: 1px solid var(--gray-light);">
                    <h3 style="font-size: 16px; margin: 0; font-weight: 600;"><i class="fas fa-bed" style="margin-right: 8px; color: var(--primary-color);"></i>Room Occupancy</h3>
                </div>
                <div style="padding: 20px 25px;">
                    <?php foreach ($occupancyByCategory as $cat): 
                        $total = $cat['total'] ?: 1;
                        $occupiedPct = ($cat['occupied'] / $total) * 100;
                    ?>
                    <div style="margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                            <span style="font-size: 13px; font-weight: 500;"><?php echo htmlspecialchars($cat['category_name']); ?></span>
                            <span style="font-size: 13px; color: #666;"><?php echo $cat['occupied']; ?>/<?php echo $cat['total']; ?> occupied</span>
                        </div>
                        <div style="height: 6px; background-color: var(--gray-light); border-radius: 3px; overflow: hidden;">
                            <div style="width: <?php echo $occupiedPct; ?>%; height: 100%; background-color: var(--primary-color); transition: width 0.3s;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Event Space Occupancy -->
            <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="padding: 20px 25px; border-bottom: 1px solid var(--gray-light);">
                    <h3 style="font-size: 16px; margin: 0; font-weight: 600;"><i class="fas fa-champagne-glasses" style="margin-right: 8px; color: #9c27b0;"></i>Event Space Occupancy</h3>
                </div>
                <div style="padding: 20px 25px;">
                    <?php if (empty($eventSpaceOccupancy)): ?>
                    <div style="text-align: center; color: #666; padding: 20px;">
                        <p>No event space data available</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($eventSpaceOccupancy as $space): 
                        $total = max($space['total'], 1);
                        $booked = $space['booked_count'];
                        $available = $total - $booked;
                        $occupiedPct = ($booked / $total) * 100;
                    ?>
                    <div style="margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                            <span style="font-size: 13px; font-weight: 500;"><?php echo htmlspecialchars($space['space_name']); ?></span>
                            <span style="font-size: 13px; color: #666;"><?php echo $booked; ?>/<?php echo $total; ?> occupied</span>
                        </div>
                        <div style="height: 6px; background-color: var(--gray-light); border-radius: 3px; overflow: hidden;">
                            <div style="width: <?php echo $occupiedPct; ?>%; height: 100%; background-color: #9c27b0; transition: width 0.3s;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Food Orders Status Summary -->
            <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="padding: 20px 25px; border-bottom: 1px solid var(--gray-light);">
                    <h3 style="font-size: 16px; margin: 0; font-weight: 600;"><i class="fas fa-utensils" style="margin-right: 8px; color: #ff9800;"></i>Food Orders Status</h3>
                </div>
                <div style="padding: 20px 25px;">
                    <!-- Pending Orders -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #f0f0f0;">
                        <div style="display: flex; align-items: center;">
                            <div style="width: 40px; height: 40px; border-radius: 8px; background-color: #fff3cd; display: flex; align-items: center; justify-content: center; margin-right: 12px;">
                                <i class="fas fa-clock" style="color: #856404;"></i>
                            </div>
                            <div>
                                <p style="font-size: 13px; color: #666; margin: 0;">Pending Orders</p>
                                <p style="font-size: 18px; font-weight: 600; margin: 0;"><?php echo $stats['pending_food_orders']; ?></p>
                            </div>
                        </div>
                        <span style="padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; background-color: #fff3cd; color: #856404;">Pending</span>
                    </div>
                    
                    <!-- Preparing Orders -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #f0f0f0;">
                        <div style="display: flex; align-items: center;">
                            <div style="width: 40px; height: 40px; border-radius: 8px; background-color: #d1ecf1; display: flex; align-items: center; justify-content: center; margin-right: 12px;">
                                <i class="fas fa-fire-burner" style="color: #0c5460;"></i>
                            </div>
                            <div>
                                <p style="font-size: 13px; color: #666; margin: 0;">Preparing Orders</p>
                                <p style="font-size: 18px; font-weight: 600; margin: 0;"><?php echo $stats['preparing_food_orders']; ?></p>
                            </div>
                        </div>
                        <span style="padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; background-color: #d1ecf1; color: #0c5460;">Preparing</span>
                    </div>
                    
                    <!-- Completed Orders -->
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; align-items: center;">
                            <div style="width: 40px; height: 40px; border-radius: 8px; background-color: #d4edda; display: flex; align-items: center; justify-content: center; margin-right: 12px;">
                                <i class="fas fa-check" style="color: #155724;"></i>
                            </div>
                            <div>
                                <p style="font-size: 13px; color: #666; margin: 0;">Completed Orders</p>
                                <p style="font-size: 18px; font-weight: 600; margin: 0;"><?php echo $stats['completed_food_orders']; ?></p>
                            </div>
                        </div>
                        <span style="padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; background-color: #d4edda; color: #155724;">Completed</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- MANAGEMENT SECTION -->
        <h2 style="font-size: 18px; color: var(--dark-color); margin-bottom: 20px; font-weight: 600;">
            <i class="fas fa-briefcase" style="margin-right: 8px; color: var(--primary-color);"></i>Management Modules
        </h2>

        <!-- Quick Links / Management Cards -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-top: 0; margin-bottom: 30px;">
            <a href="admin-bookings.php" style="background-color: white; padding: 25px; border-radius: 10px; text-decoration: none; color: var(--dark-color); box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 5px 20px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 10px rgba(0,0,0,0.05)';">
                <i class="fas fa-calendar-alt" style="font-size: 30px; color: var(--primary-color); margin-bottom: 15px;"></i>
                <h4 style="font-size: 18px; margin-bottom: 5px;">Manage Bookings</h4>
                <p style="font-size: 14px; color: #666; margin: 0;">View and manage reservations</p>
            </a>
            
            <a href="admin-rooms.php" style="background-color: white; padding: 25px; border-radius: 10px; text-decoration: none; color: var(--dark-color); box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 5px 20px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 10px rgba(0,0,0,0.05)';">
                <i class="fas fa-bed" style="font-size: 30px; color: var(--primary-color); margin-bottom: 15px;"></i>
                <h4 style="font-size: 18px; margin-bottom: 5px;">Manage Rooms</h4>
                <p style="font-size: 14px; color: #666; margin: 0;">Add, edit room details</p>
            </a>
            
            <a href="admin-users.php" style="background-color: white; padding: 25px; border-radius: 10px; text-decoration: none; color: var(--dark-color); box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 5px 20px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 10px rgba(0,0,0,0.05)';">
                <i class="fas fa-users-cog" style="font-size: 30px; color: var(--primary-color); margin-bottom: 15px;"></i>
                <h4 style="font-size: 18px; margin-bottom: 5px;">Manage Users</h4>
                <p style="font-size: 14px; color: #666; margin: 0;">Staff and guest accounts</p>
            </a>
            
            <a href="admin-reports.php" style="background-color: white; padding: 25px; border-radius: 10px; text-decoration: none; color: var(--dark-color); box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 5px 20px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 10px rgba(0,0,0,0.05)';">
                <i class="fas fa-chart-bar" style="font-size: 30px; color: var(--primary-color); margin-bottom: 15px;"></i>
                <h4 style="font-size: 18px; margin-bottom: 5px;">Reports</h4>
                <p style="font-size: 14px; color: #666; margin: 0;">Financial & occupancy reports</p>
            </a>
            
            <a href="admin-amenities.php" style="background-color: white; padding: 25px; border-radius: 10px; text-decoration: none; color: var(--dark-color); box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 5px 20px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 10px rgba(0,0,0,0.05)';">
                <i class="fas fa-spa" style="font-size: 30px; color: var(--primary-color); margin-bottom: 15px;"></i>
                <h4 style="font-size: 18px; margin-bottom: 5px;">Manage Amenities</h4>
                <p style="font-size: 14px; color: #666; margin: 0;">Spa, gym, pool services</p>
            </a>
            
            <a href="admin-staff-schedules.php" style="background-color: white; padding: 25px; border-radius: 10px; text-decoration: none; color: var(--dark-color); box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 5px 20px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 10px rgba(0,0,0,0.05)';">
                <i class="fas fa-user-tie" style="font-size: 30px; color: var(--primary-color); margin-bottom: 15px;"></i>
                <h4 style="font-size: 18px; margin-bottom: 5px;">Manage Staff</h4>
                <p style="font-size: 14px; color: #666; margin: 0;">Staff schedules & shifts</p>
            </a>
            
            <a href="admin-analytics.php" style="background-color: white; padding: 25px; border-radius: 10px; text-decoration: none; color: var(--dark-color); box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 5px 20px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 10px rgba(0,0,0,0.05)';">
                <i class="fas fa-chart-line" style="font-size: 30px; color: var(--primary-color); margin-bottom: 15px;"></i>
                <h4 style="font-size: 18px; margin-bottom: 5px;">View Analytics</h4>
                <p style="font-size: 14px; color: #666; margin: 0;">Performance & insights</p>
            </a>
            
            <a href="admin-maintenance.php" style="background-color: white; padding: 25px; border-radius: 10px; text-decoration: none; color: var(--dark-color); box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 5px 20px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 10px rgba(0,0,0,0.05)';">
                <i class="fas fa-screwdriver-wrench" style="font-size: 30px; color: var(--primary-color); margin-bottom: 15px;"></i>
                <h4 style="font-size: 18px; margin-bottom: 5px;">Maintenance</h4>
                <p style="font-size: 14px; color: #666; margin: 0;">Track & manage requests</p>
            </a>
            
            <a href="admin-payments.php" style="background-color: white; padding: 25px; border-radius: 10px; text-decoration: none; color: var(--dark-color); box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 5px 20px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 10px rgba(0,0,0,0.05)';">
                <i class="fas fa-credit-card" style="font-size: 30px; color: var(--primary-color); margin-bottom: 15px;"></i>
                <h4 style="font-size: 18px; margin-bottom: 5px;">Manage Payments</h4>
                <p style="font-size: 14px; color: #666; margin: 0;">Payment transactions</p>
            </a>
            
            <a href="admin-promotions.php" style="background-color: white; padding: 25px; border-radius: 10px; text-decoration: none; color: var(--dark-color); box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 5px 20px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 10px rgba(0,0,0,0.05)';">
                <i class="fas fa-percent" style="font-size: 30px; color: var(--primary-color); margin-bottom: 15px;"></i>
                <h4 style="font-size: 18px; margin-bottom: 5px;">Manage Promotions</h4>
                <p style="font-size: 14px; color: #666; margin: 0;">Discounts & offers</p>
            </a>
            
            <a href="admin-event-spaces.php" style="background-color: white; padding: 25px; border-radius: 10px; text-decoration: none; color: var(--dark-color); box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 5px 20px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 10px rgba(0,0,0,0.05)';">
                <i class="fas fa-champagne-glasses" style="font-size: 30px; color: var(--primary-color); margin-bottom: 15px;"></i>
                <h4 style="font-size: 18px; margin-bottom: 5px;">Event Spaces</h4>
                <p style="font-size: 14px; color: #666; margin: 0;">Ballroom & meeting rooms</p>
            </a>
            
            <a href="admin-inventory-items.php" style="background-color: white; padding: 25px; border-radius: 10px; text-decoration: none; color: var(--dark-color); box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 5px 20px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 10px rgba(0,0,0,0.05)';">
                <i class="fas fa-boxes" style="font-size: 30px; color: var(--primary-color); margin-bottom: 15px;"></i>
                <h4 style="font-size: 18px; margin-bottom: 5px;">Inventory</h4>
                <p style="font-size: 14px; color: #666; margin: 0;">Stock & supplies</p>
            </a>
            
            <a href="admin-menu-items.php" style="background-color: white; padding: 25px; border-radius: 10px; text-decoration: none; color: var(--dark-color); box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 5px 20px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 10px rgba(0,0,0,0.05)';">
                <i class="fas fa-utensils" style="font-size: 30px; color: var(--primary-color); margin-bottom: 15px;"></i>
                <h4 style="font-size: 18px; margin-bottom: 5px;">Food Menu</h4>
                <p style="font-size: 14px; color: #666; margin: 0;">Menu items & pricing</p>
            </a>
            
            <a href="admin-faqs.php" style="background-color: white; padding: 25px; border-radius: 10px; text-decoration: none; color: var(--dark-color); box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 5px 20px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 10px rgba(0,0,0,0.05)';">
                <i class="fas fa-circle-question" style="font-size: 30px; color: var(--primary-color); margin-bottom: 15px;"></i>
                <h4 style="font-size: 18px; margin-bottom: 5px;">FAQs</h4>
                <p style="font-size: 14px; color: #666; margin: 0;">Manage questions</p>
            </a>
            
            <a href="admin-gallery.php" style="background-color: white; padding: 25px; border-radius: 10px; text-decoration: none; color: var(--dark-color); box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 5px 20px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 10px rgba(0,0,0,0.05)';">
                <i class="fas fa-images" style="font-size: 30px; color: var(--primary-color); margin-bottom: 15px;"></i>
                <h4 style="font-size: 18px; margin-bottom: 5px;">Gallery</h4>
                <p style="font-size: 14px; color: #666; margin: 0;">Hotel photos</p>
            </a>
            
            <a href="admin-reviews.php" style="background-color: white; padding: 25px; border-radius: 10px; text-decoration: none; color: var(--dark-color); box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 5px 20px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 10px rgba(0,0,0,0.05)';">
                <i class="fas fa-star" style="font-size: 30px; color: var(--primary-color); margin-bottom: 15px;"></i>
                <h4 style="font-size: 18px; margin-bottom: 5px;">Reviews</h4>
                <p style="font-size: 14px; color: #666; margin: 0;">Guest feedback</p>
            </a>
        </div>
    </div>
</section>

<?php require_once '../includes/admin-footer.php'; ?>
