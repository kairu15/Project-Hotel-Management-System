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
// MAXIMUM TARGETS FOR DASHBOARD METRICS (100% = TARGET ACHIEVED)
// ============================================
$MAX_TARGETS = [
    'users' => 100,           // Total Users max = 100
    'bookings' => 100,        // Bookings (30 Days) max = 100
    'revenue' => 300000,      // Revenue (This Month) max = ₱300,000
    'events' => 50            // Total Events (This Month) max = 50
];

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
// HELPER FUNCTION: CALCULATE ACHIEVEMENT PERCENTAGE (0-100%)
// Based on absolute value against maximum target
// 0% = 0 value, 100% = max target reached
// ============================================
function calculateAchievementPercentage($current, $maxTarget) {
    if ($maxTarget <= 0) {
        return ['value' => 0, 'label' => '0%'];
    }
    $percentage = ($current / $maxTarget) * 100;
    // Cap at 100% (can't exceed target)
    $cappedPercentage = min(100, max(0, $percentage));
    return ['value' => $cappedPercentage, 'label' => round($cappedPercentage) . '%'];
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
$usersMoMChange = calculatePercentageChange($currentUsers, $previousUsers);

// Current Month Bookings (last 30 days equivalent)
$currentBookings = $db->query("SELECT COUNT(*) FROM bookings WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();
$previousBookings = $db->query("SELECT COUNT(*) FROM bookings WHERE created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY) AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();
$bookingsMoMChange = calculatePercentageChange($currentBookings, $previousBookings);

// Current Month Revenue
$currentRevenue = $db->query("SELECT COALESCE(SUM(total_amount), 0) FROM bookings WHERE status = 'checked_out' AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())")->fetchColumn();
$previousRevenue = $db->query("SELECT COALESCE(SUM(total_amount), 0) FROM bookings WHERE status = 'checked_out' AND MONTH(created_at) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) AND YEAR(created_at) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))")->fetchColumn();
$revenueMoMChange = calculatePercentageChange($currentRevenue, $previousRevenue);

// Current Month Events
$currentEvents = $db->query("SELECT COUNT(*) FROM event_bookings WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())")->fetchColumn();
$previousEvents = $db->query("SELECT COUNT(*) FROM event_bookings WHERE MONTH(created_at) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) AND YEAR(created_at) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))")->fetchColumn();
$eventsMoMChange = calculatePercentageChange($currentEvents, $previousEvents);

// ============================================
// ACHIEVEMENT PERCENTAGES FOR DASHBOARD CARDS (0-100% based on targets)
// ============================================

// Total Users Achievement (100 users = 100%)
$totalUsers = $db->query("SELECT COUNT(*) FROM users WHERE role = 'guest'")->fetchColumn();
$usersChange = calculateAchievementPercentage($totalUsers, $MAX_TARGETS['users']);

// Bookings (30 Days) Achievement (100 bookings = 100%)
$bookingsChange = calculateAchievementPercentage($currentBookings, $MAX_TARGETS['bookings']);

// Revenue (This Month) Achievement (₱300,000 = 100%)
$revenueChange = calculateAchievementPercentage($currentRevenue, $MAX_TARGETS['revenue']);

// Total Events (This Month) Achievement (50 events = 100%)
$eventsChange = calculateAchievementPercentage($currentEvents, $MAX_TARGETS['events']);

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

// ============================================
// CHART DATA QUERIES
// ============================================

// Bookings by Status (for pie chart)
$bookingsByStatus = $db->query("
    SELECT status, COUNT(*) as count 
    FROM bookings 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY status
")->fetchAll();

// Daily bookings for the last 14 days (for line chart)
$dailyBookings = $db->query("
    SELECT DATE(created_at) as date, COUNT(*) as count 
    FROM bookings 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date
")->fetchAll();

// Fill in missing dates with zero values
$dailyBookingsData = [];
for ($i = 13; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $found = false;
    foreach ($dailyBookings as $booking) {
        if ($booking['date'] === $date) {
            $dailyBookingsData[] = ['date' => $date, 'count' => $booking['count']];
            $found = true;
            break;
        }
    }
    if (!$found) {
        $dailyBookingsData[] = ['date' => $date, 'count' => 0];
    }
}

// Event bookings vs Room bookings comparison (last 30 days)
$eventBookingsMonthly = $db->query("
    SELECT COUNT(*) as count 
    FROM event_bookings 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
")->fetchColumn();

$roomBookingsMonthly = $db->query("
    SELECT COUNT(*) as count 
    FROM bookings 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
")->fetchColumn();

// Room category occupancy distribution
$roomCategoryData = $db->query("
    SELECT rc.category_name, 
           COUNT(r.room_id) as total_rooms,
           SUM(CASE WHEN r.status = 'occupied' THEN 1 ELSE 0 END) as occupied
    FROM room_categories rc
    LEFT JOIN rooms r ON rc.category_id = r.category_id
    GROUP BY rc.category_id
    ORDER BY total_rooms DESC
")->fetchAll();

// Food orders by status (for doughnut chart)
$foodOrdersByStatus = $db->query("
    SELECT status, COUNT(*) as count 
    FROM food_orders 
    WHERE DATE(created_at) = CURDATE()
    GROUP BY status
")->fetchAll();

// Revenue comparison (Room bookings vs Event bookings vs Food orders) - last 30 days
$revenueBreakdown = [
    'rooms' => $db->query("
        SELECT COALESCE(SUM(total_amount), 0) 
        FROM bookings 
        WHERE status IN ('confirmed', 'checked_out') 
        AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ")->fetchColumn(),
    'events' => $db->query("
        SELECT COALESCE(SUM(quoted_price), 0) 
        FROM event_bookings 
        WHERE status IN ('confirmed', 'completed') 
        AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ")->fetchColumn(),
    'food' => $db->query("
        SELECT COALESCE(SUM(total_price), 0) 
        FROM food_orders 
        WHERE status IN ('ready', 'delivered') 
        AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ")->fetchColumn()
];

// Weekly guest check-ins (last 4 weeks)
$weeklyCheckins = $db->query("
    SELECT 
        CONCAT('Week ', FLOOR(DATEDIFF(NOW(), created_at) / 7) + 1) as week_label,
        COUNT(*) as checkins
    FROM bookings 
    WHERE status = 'checked_in' 
    AND created_at >= DATE_SUB(NOW(), INTERVAL 28 DAY)
    GROUP BY FLOOR(DATEDIFF(NOW(), created_at) / 7)
    ORDER BY FLOOR(DATEDIFF(NOW(), created_at) / 7) DESC
    LIMIT 4
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

        <!-- CHARTS & ANALYTICS SECTION -->
        <h2 style="font-size: 18px; color: var(--dark-color); margin: 30px 0 20px; font-weight: 600;">
            <i class="fas fa-chart-pie" style="margin-right: 8px; color: var(--primary-color);"></i>Charts & Analytics
        </h2>

        <!-- Charts Grid -->
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 25px; margin-bottom: 30px;">
            <!-- Revenue Trend Chart -->
            <div style="background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="font-size: 16px; margin: 0; font-weight: 600; color: var(--dark-color);">
                        <i class="fas fa-chart-line" style="margin-right: 8px; color: var(--success-color);"></i>Revenue Trend (6 Months)
                    </h3>
                </div>
                <div style="height: 250px; position: relative;">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <!-- Daily Bookings Trend -->
            <div style="background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="font-size: 16px; margin: 0; font-weight: 600; color: var(--dark-color);">
                        <i class="fas fa-chart-area" style="margin-right: 8px; color: var(--info-color);"></i>Daily Bookings (Last 14 Days)
                    </h3>
                </div>
                <div style="height: 250px; position: relative;">
                    <canvas id="dailyBookingsChart"></canvas>
                </div>
            </div>

            <!-- Bookings by Status -->
            <div style="background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="font-size: 16px; margin: 0; font-weight: 600; color: var(--dark-color);">
                        <i class="fas fa-chart-pie" style="margin-right: 8px; color: var(--warning-color);"></i>Bookings by Status
                    </h3>
                </div>
                <div style="height: 250px; position: relative; display: flex; justify-content: center;">
                    <canvas id="bookingsStatusChart"></canvas>
                </div>
            </div>

            <!-- Revenue Breakdown -->
            <div style="background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="font-size: 16px; margin: 0; font-weight: 600; color: var(--dark-color);">
                        <i class="fas fa-coins" style="margin-right: 8px; color: #9c27b0;"></i>Revenue Breakdown (30 Days)
                    </h3>
                </div>
                <div style="height: 250px; position: relative; display: flex; justify-content: center;">
                    <canvas id="revenueBreakdownChart"></canvas>
                </div>
            </div>
        </div>

        <!-- COMPREHENSIVE CHARTS DASHBOARD -->
        <h2 style="font-size: 18px; color: var(--dark-color); margin: 30px 0 20px; font-weight: 600;">
            <i class="fas fa-chart-simple" style="margin-right: 8px; color: var(--primary-color);"></i>System Overview Charts
        </h2>

        <!-- Charts Grid - First Row -->
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; margin-bottom: 30px;">
            <!-- Room Status Distribution -->
            <div style="background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <h3 style="font-size: 16px; margin: 0 0 15px 0; font-weight: 600; color: var(--dark-color);">
                    <i class="fas fa-bed" style="margin-right: 8px; color: var(--warning-color);"></i>Room Status
                </h3>
                <div style="height: 200px; position: relative;">
                    <canvas id="roomStatusChart"></canvas>
                </div>
            </div>

            <!-- Event Spaces Status -->
            <div style="background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <h3 style="font-size: 16px; margin: 0 0 15px 0; font-weight: 600; color: var(--dark-color);">
                    <i class="fas fa-champagne-glasses" style="margin-right: 8px; color: #e91e63;"></i>Event Spaces
                </h3>
                <div style="height: 200px; position: relative;">
                    <canvas id="eventSpacesChart"></canvas>
                </div>
            </div>

            <!-- Food Orders Status -->
            <div style="background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <h3 style="font-size: 16px; margin: 0 0 15px 0; font-weight: 600; color: var(--dark-color);">
                    <i class="fas fa-utensils" style="margin-right: 8px; color: #ff9800;"></i>Food Orders (Today)
                </h3>
                <div style="height: 200px; position: relative;">
                    <canvas id="foodOrdersChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Charts Grid - Second Row -->
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 25px; margin-bottom: 30px;">
            <!-- Staff & Operations -->
            <div style="background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <h3 style="font-size: 16px; margin: 0 0 15px 0; font-weight: 600; color: var(--dark-color);">
                    <i class="fas fa-users-gear" style="margin-right: 8px; color: #17a2b8;"></i>Staff & Operations
                </h3>
                <div style="height: 200px; position: relative;">
                    <canvas id="staffOperationsChart"></canvas>
                </div>
            </div>

            <!-- Guest Services Metrics -->
            <div style="background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <h3 style="font-size: 16px; margin: 0 0 15px 0; font-weight: 600; color: var(--dark-color);">
                    <i class="fas fa-star" style="margin-right: 8px; color: #ffc107;"></i>Guest Services
                </h3>
                <div style="height: 200px; position: relative;">
                    <canvas id="guestServicesChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Charts Grid - Third Row -->
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 25px; margin-bottom: 30px;">
            <!-- Content Management -->
            <div style="background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <h3 style="font-size: 16px; margin: 0 0 15px 0; font-weight: 600; color: var(--dark-color);">
                    <i class="fas fa-images" style="margin-right: 8px; color: #6610f2;"></i>Content Management
                </h3>
                <div style="height: 200px; position: relative;">
                    <canvas id="contentChart"></canvas>
                </div>
            </div>

            <!-- Key Performance Indicators -->
            <div style="background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <h3 style="font-size: 16px; margin: 0 0 15px 0; font-weight: 600; color: var(--dark-color);">
                    <i class="fas fa-chart-column" style="margin-right: 8px; color: var(--success-color);"></i>Key Metrics Comparison
                </h3>
                <div style="height: 200px; position: relative;">
                    <canvas id="kpiChart"></canvas>
                </div>
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

<!-- Chart.js Initialization -->
<script>
// Common chart options
const commonOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom',
            labels: {
                padding: 15,
                font: { size: 12 }
            }
        }
    }
};

// Revenue Trend Chart (Line Chart)
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
new Chart(revenueCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_map(function($item) { return date('M Y', strtotime($item['month'] . '-01')); }, $revenueData)); ?>,
        datasets: [{
            label: 'Revenue (₱)',
            data: <?php echo json_encode(array_map(function($item) { return $item['revenue']; }, $revenueData)); ?>,
            borderColor: '#367D8A',
            backgroundColor: 'rgba(54, 125, 138, 0.1)',
            borderWidth: 3,
            tension: 0.4,
            fill: true,
            pointBackgroundColor: '#367D8A',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 5,
            pointHoverRadius: 7
        }]
    },
    options: {
        ...commonOptions,
        plugins: {
            ...commonOptions.plugins,
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'Revenue: ₱' + context.parsed.y.toLocaleString();
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '₱' + (value / 1000).toFixed(0) + 'k';
                    }
                }
            }
        }
    }
});

// Daily Bookings Chart (Area Chart)
const dailyCtx = document.getElementById('dailyBookingsChart').getContext('2d');
new Chart(dailyCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_map(function($item) { return date('M d', strtotime($item['date'])); }, $dailyBookingsData)); ?>,
        datasets: [{
            label: 'Bookings',
            data: <?php echo json_encode(array_map(function($item) { return $item['count']; }, $dailyBookingsData)); ?>,
            borderColor: '#17a2b8',
            backgroundColor: 'rgba(23, 162, 184, 0.15)',
            borderWidth: 2,
            tension: 0.3,
            fill: true,
            pointBackgroundColor: '#17a2b8',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 4
        }]
    },
    options: {
        ...commonOptions,
        plugins: {
            ...commonOptions.plugins,
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { stepSize: 1 }
            }
        }
    }
});

// Bookings by Status Chart (Pie Chart)
const statusCtx = document.getElementById('bookingsStatusChart').getContext('2d');
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode(array_map(function($item) { return ucfirst($item['status']); }, $bookingsByStatus)); ?>,
        datasets: [{
            data: <?php echo json_encode(array_map(function($item) { return $item['count']; }, $bookingsByStatus)); ?>,
            backgroundColor: [
                '#ffc107', // Pending - Yellow
                '#28a745', // Confirmed - Green
                '#007bff', // Checked In - Blue
                '#6c757d', // Checked Out - Gray
                '#dc3545', // Cancelled - Red
                '#17a2b8'  // Other - Cyan
            ],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        ...commonOptions,
        cutout: '60%',
        plugins: {
            ...commonOptions.plugins,
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((context.parsed / total) * 100).toFixed(1);
                        return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                    }
                }
            }
        }
    }
});

// Revenue Breakdown Chart (Doughnut Chart)
const breakdownCtx = document.getElementById('revenueBreakdownChart').getContext('2d');
new Chart(breakdownCtx, {
    type: 'doughnut',
    data: {
        labels: ['Room Bookings', 'Event Bookings', 'Food Orders'],
        datasets: [{
            data: [
                <?php echo $revenueBreakdown['rooms']; ?>,
                <?php echo $revenueBreakdown['events']; ?>,
                <?php echo $revenueBreakdown['food']; ?>
            ],
            backgroundColor: [
                '#367D8A', // Room Bookings - Primary
                '#9c27b0', // Event Bookings - Purple
                '#ff9800'  // Food Orders - Orange
            ],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        ...commonOptions,
        cutout: '55%',
        plugins: {
            ...commonOptions.plugins,
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const value = context.parsed;
                        const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                        return context.label + ': ₱' + value.toLocaleString() + ' (' + percentage + '%)';
                    }
                }
            }
        }
    }
});

// Room Status Distribution Chart
const roomStatusCtx = document.getElementById('roomStatusChart').getContext('2d');
new Chart(roomStatusCtx, {
    type: 'doughnut',
    data: {
        labels: ['Occupied', 'Available', 'Maintenance'],
        datasets: [{
            data: [
                <?php echo $stats['occupied_rooms']; ?>,
                <?php echo $stats['available_rooms']; ?>,
                <?php echo $stats['maintenance_rooms']; ?>
            ],
            backgroundColor: ['#ffc107', '#28a745', '#dc3545'],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        ...commonOptions,
        cutout: '60%',
        plugins: {
            ...commonOptions.plugins,
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const total = <?php echo $stats['occupied_rooms'] + $stats['available_rooms'] + $stats['maintenance_rooms']; ?>;
                        const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                        return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                    }
                }
            }
        }
    }
});

// Event Spaces Chart
const eventSpacesCtx = document.getElementById('eventSpacesChart').getContext('2d');
new Chart(eventSpacesCtx, {
    type: 'doughnut',
    data: {
        labels: ['Occupied', 'Available'],
        datasets: [{
            data: [
                <?php echo $stats['occupied_event_spaces']; ?>,
                <?php echo $stats['available_event_spaces']; ?>
            ],
            backgroundColor: ['#e91e63', '#4caf50'],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        ...commonOptions,
        cutout: '60%',
        plugins: {
            ...commonOptions.plugins,
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const total = <?php echo $stats['occupied_event_spaces'] + $stats['available_event_spaces']; ?>;
                        const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                        return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                    }
                }
            }
        }
    }
});

// Food Orders Status Chart
const foodOrdersCtx = document.getElementById('foodOrdersChart').getContext('2d');
new Chart(foodOrdersCtx, {
    type: 'doughnut',
    data: {
        labels: ['Pending', 'Preparing', 'Completed'],
        datasets: [{
            data: [
                <?php echo $stats['pending_food_orders']; ?>,
                <?php echo $stats['preparing_food_orders']; ?>,
                <?php echo $stats['completed_food_orders']; ?>
            ],
            backgroundColor: ['#ff5722', '#ff9800', '#28a745'],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        ...commonOptions,
        cutout: '55%',
        plugins: {
            ...commonOptions.plugins,
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const total = <?php echo $stats['pending_food_orders'] + $stats['preparing_food_orders'] + $stats['completed_food_orders']; ?>;
                        const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                        return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                    }
                }
            }
        }
    }
});

// Staff & Operations Chart
const staffOperationsCtx = document.getElementById('staffOperationsChart').getContext('2d');
new Chart(staffOperationsCtx, {
    type: 'bar',
    data: {
        labels: ['Staff on Duty', 'Pending Maintenance', 'Inventory Alerts'],
        datasets: [{
            label: 'Count',
            data: [
                <?php echo $stats['staff_on_duty']; ?>,
                <?php echo $stats['pending_maintenance']; ?>,
                <?php echo $stats['inventory_alerts']; ?>
            ],
            backgroundColor: ['#17a2b8', '#fd7e14', '#6f42c1'],
            borderRadius: 5
        }]
    },
    options: {
        ...commonOptions,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 } }
        }
    }
});

// Guest Services Chart
const guestServicesCtx = document.getElementById('guestServicesChart').getContext('2d');
new Chart(guestServicesCtx, {
    type: 'bar',
    data: {
        labels: ['Pending Reviews', 'Avg Rating (×10)', 'Active Promos', "Today's Revenue (÷1000)"],
        datasets: [{
            label: 'Value',
            data: [
                <?php echo $stats['pending_reviews']; ?>,
                <?php echo $stats['average_rating'] * 10; ?>,
                <?php echo $stats['active_promotions']; ?>,
                <?php echo $stats['today_revenue'] / 1000; ?>
            ],
            backgroundColor: ['#20c997', '#ffc107', '#e83e8c', '#28a745'],
            borderRadius: 5
        }]
    },
    options: {
        ...commonOptions,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const labels = [
                            'Pending Reviews: <?php echo $stats['pending_reviews']; ?>',
                            'Average Rating: <?php echo number_format($stats['average_rating'], 1); ?>/5',
                            'Active Promotions: <?php echo $stats['active_promotions']; ?>',
                            "Today's Revenue: ₱<?php echo number_format($stats['today_revenue']); ?>"
                        ];
                        return labels[context.dataIndex];
                    }
                }
            }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});

// Content Management Chart
const contentCtx = document.getElementById('contentChart').getContext('2d');
new Chart(contentCtx, {
    type: 'bar',
    data: {
        labels: ['Gallery Images', 'Active FAQs', 'Virtual Tours', 'Pending Payments'],
        datasets: [{
            label: 'Count',
            data: [
                <?php echo $stats['gallery_images']; ?>,
                <?php echo $stats['active_faqs']; ?>,
                <?php echo $stats['virtual_tours']; ?>,
                <?php echo $stats['pending_payments']; ?>
            ],
            backgroundColor: ['#6610f2', '#0dcaf0', '#d63384', '#dc3545'],
            borderRadius: 5
        }]
    },
    options: {
        ...commonOptions,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 } }
        }
    }
});

// Key Performance Indicators Chart
const kpiCtx = document.getElementById('kpiChart').getContext('2d');
new Chart(kpiCtx, {
    type: 'bar',
    data: {
        labels: ['Total Users', 'Bookings (30d)', 'Events (Mo)', 'Food Orders (Today)'],
        datasets: [{
            label: 'Current Period',
            data: [
                <?php echo $stats['total_users']; ?>,
                <?php echo $stats['total_bookings']; ?>,
                <?php echo $stats['total_events']; ?>,
                <?php echo $stats['total_food_orders']; ?>
            ],
            backgroundColor: '#367D8A',
            borderRadius: 5
        }]
    },
    options: {
        ...commonOptions,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});
</script>

<?php require_once '../includes/admin-footer.php'; ?>
