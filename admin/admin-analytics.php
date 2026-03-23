<?php
/**
 * Admin Analytics Dashboard - Bayawan Bai Hotel
 * Comprehensive analytics with charts and KPIs
 */

require_once __DIR__ . '/../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    showAlert('Access denied. Admin privileges required.', 'danger');
    redirect('../index.php');
}

$pageTitle = 'Analytics Dashboard';

// Get database connection
$db = getDB();

// Get date range from request or default to current month
$period = isset($_GET['period']) ? $_GET['period'] : 'month';
$customStart = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$customEnd = isset($_GET['end_date']) ? $_GET['end_date'] : null;

// Calculate date ranges
$today = date('Y-m-d');
$thisMonth = date('Y-m');
$thisYear = date('Y');

switch ($period) {
    case 'week':
        $startDate = date('Y-m-d', strtotime('-7 days'));
        $endDate = $today;
        break;
    case 'month':
        $startDate = date('Y-m-01');
        $endDate = $today;
        break;
    case 'quarter':
        $startDate = date('Y-m-d', strtotime('-3 months'));
        $endDate = $today;
        break;
    case 'year':
        $startDate = date('Y-01-01');
        $endDate = $today;
        break;
    case 'custom':
        $startDate = $customStart ?: date('Y-m-01');
        $endDate = $customEnd ?: $today;
        break;
    default:
        $startDate = date('Y-m-01');
        $endDate = $today;
}

// ==================== ROOM & OCCUPANCY ANALYTICS ====================

// Total rooms count
$totalRooms = $db->query("SELECT COUNT(*) FROM rooms")->fetchColumn();

// Occupied rooms (currently checked in)
$occupiedRooms = $db->query("SELECT COUNT(*) FROM rooms WHERE status = 'occupied'")->fetchColumn();

// Available rooms
$availableRooms = $db->query("SELECT COUNT(*) FROM rooms WHERE status = 'available'")->fetchColumn();

// Maintenance rooms
$maintenanceRooms = $db->query("SELECT COUNT(*) FROM rooms WHERE status = 'maintenance'")->fetchColumn();

// Occupancy rate calculation
$occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 1) : 0;

// Room type popularity
$roomTypePopularity = $db->prepare("
    SELECT rc.category_name, COUNT(b.booking_id) as booking_count, 
           SUM(b.total_amount) as total_revenue
    FROM room_categories rc
    LEFT JOIN bookings b ON rc.category_id = b.category_id
    WHERE b.created_at >= ? AND b.created_at <= ? AND b.status != 'cancelled'
    GROUP BY rc.category_id
    ORDER BY booking_count DESC
");
$roomTypePopularity->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
$roomTypeData = $roomTypePopularity->fetchAll(PDO::FETCH_ASSOC);

// Room status distribution for pie chart
$roomStatusData = $db->query("
    SELECT status, COUNT(*) as count 
    FROM rooms 
    GROUP BY status
")->fetchAll(PDO::FETCH_KEY_PAIR);

// ==================== BOOKING & REVENUE ANALYTICS ====================

// Total bookings in period
$totalBookings = $db->prepare("
    SELECT COUNT(*) FROM bookings 
    WHERE created_at >= ? AND created_at <= ?
");
$totalBookings->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
$totalBookingsCount = $totalBookings->fetchColumn();

// Confirmed bookings
$confirmedBookings = $db->prepare("
    SELECT COUNT(*) FROM bookings 
    WHERE created_at >= ? AND created_at <= ? AND status IN ('confirmed', 'checked_in', 'checked_out')
");
$confirmedBookings->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
$confirmedBookingsCount = $confirmedBookings->fetchColumn();

// Total revenue in period
$totalRevenue = $db->prepare("
    SELECT COALESCE(SUM(amount), 0) FROM payments 
    WHERE payment_date >= ? AND payment_date <= ? AND status = 'completed'
");
$totalRevenue->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
$totalRevenueAmount = $totalRevenue->fetchColumn();

// Revenue by room type
$revenueByRoomType = $db->prepare("
    SELECT rc.category_name, SUM(p.amount) as revenue
    FROM payments p
    JOIN bookings b ON p.booking_id = b.booking_id
    JOIN room_categories rc ON b.category_id = rc.category_id
    WHERE p.payment_date >= ? AND p.payment_date <= ? AND p.status = 'completed'
    GROUP BY rc.category_id
    ORDER BY revenue DESC
");
$revenueByRoomType->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
$revenueByRoom = $revenueByRoomType->fetchAll(PDO::FETCH_ASSOC);

// Average Daily Rate (ADR) - Average revenue per occupied room
$daysInPeriod = max(1, (strtotime($endDate) - strtotime($startDate)) / 86400 + 1);
$adr = $occupiedRooms > 0 ? round($totalRevenueAmount / $occupiedRooms / $daysInPeriod, 2) : 0;

// RevPAR (Revenue per Available Room) - Total revenue / total rooms available
$revpar = round($totalRevenueAmount / ($totalRooms * $daysInPeriod), 2);

// Monthly booking trends for chart
$bookingTrends = $db->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as bookings
    FROM bookings
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY month
    ORDER BY month
")->fetchAll(PDO::FETCH_ASSOC);

// Monthly revenue trends
$revenueTrends = $db->query("
    SELECT DATE_FORMAT(payment_date, '%Y-%m') as month, SUM(amount) as revenue
    FROM payments
    WHERE payment_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH) AND status = 'completed'
    GROUP BY month
    ORDER BY month
")->fetchAll(PDO::FETCH_ASSOC);

// ==================== CUSTOMER ANALYTICS ====================

// Total customers
$totalCustomers = $db->query("SELECT COUNT(*) FROM users WHERE role = 'guest'")->fetchColumn();

// New customers in period
$newCustomers = $db->prepare("
    SELECT COUNT(*) FROM users 
    WHERE role = 'guest' AND created_at >= ? AND created_at <= ?
");
$newCustomers->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
$newCustomersCount = $newCustomers->fetchColumn();

// Repeat guests (customers with 2+ bookings)
$repeatGuests = $db->query("
    SELECT u.first_name, u.last_name, u.email, COUNT(b.booking_id) as booking_count
    FROM users u
    JOIN bookings b ON u.user_id = b.user_id
    WHERE u.role = 'guest' AND b.status != 'cancelled'
    GROUP BY u.user_id
    HAVING booking_count >= 2
    ORDER BY booking_count DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// VIP customers (loyalty points > 100)
$vipCustomers = $db->query("
    SELECT first_name, last_name, email, loyalty_points
    FROM users
    WHERE role = 'guest' AND loyalty_points >= 100
    ORDER BY loyalty_points DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// Customer feedback summary
$reviewStats = $db->query("
    SELECT category, AVG(rating) as avg_rating, COUNT(*) as review_count
    FROM reviews
    WHERE is_approved = 1
    GROUP BY category
")->fetchAll(PDO::FETCH_ASSOC);

$overallRating = $db->query("
    SELECT AVG(rating) FROM reviews WHERE is_approved = 1
")->fetchColumn() ?: 0;

// ==================== STAFF & SERVICE ANALYTICS ====================

// Staff count by role
$staffByRole = $db->query("
    SELECT role, COUNT(*) as count FROM users
    WHERE role IN ('receptionist', 'manager', 'admin')
    GROUP BY role
")->fetchAll(PDO::FETCH_KEY_PAIR);

// Staff schedules this week
$staffSchedules = $db->prepare("
    SELECT u.first_name, u.last_name, COUNT(s.schedule_id) as shifts
    FROM users u
    LEFT JOIN staff_schedules s ON u.user_id = s.user_id
    WHERE u.role IN ('receptionist', 'manager') 
    AND s.work_date >= ? AND s.work_date <= ?
    GROUP BY u.user_id
    ORDER BY shifts DESC
");
$staffSchedules->execute([date('Y-m-d', strtotime('-7 days')), $today]);
$staffPerformance = $staffSchedules->fetchAll(PDO::FETCH_ASSOC);

// Maintenance requests
$maintenanceStats = $db->query("
    SELECT 
        COUNT(*) as total_requests,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
    FROM maintenance_requests
")->fetch(PDO::FETCH_ASSOC);

// Maintenance by type
$maintenanceByType = $db->query("
    SELECT issue_type, COUNT(*) as count
    FROM maintenance_requests
    GROUP BY issue_type
    ORDER BY count DESC
")->fetchAll(PDO::FETCH_ASSOC);

// ==================== EVENT & INVENTORY ANALYTICS ====================

// Event bookings stats
$eventStats = $db->query("
    SELECT 
        COUNT(*) as total_events,
        SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
    FROM event_bookings
")->fetch(PDO::FETCH_ASSOC);

// Events by space
$eventsBySpace = $db->query("
    SELECT es.space_name, COUNT(eb.event_booking_id) as booking_count
    FROM event_spaces es
    LEFT JOIN event_bookings eb ON es.space_id = eb.space_id AND eb.status IN ('confirmed', 'completed')
    GROUP BY es.space_id
    ORDER BY booking_count DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Inventory stats
$inventoryStats = $db->query("
    SELECT 
        COUNT(*) as total_items,
        SUM(CASE WHEN quantity <= reorder_level THEN 1 ELSE 0 END) as low_stock,
        SUM(quantity * unit_cost) as total_value
    FROM inventory_items
")->fetch(PDO::FETCH_ASSOC);

// Low stock items
$lowStockItems = $db->query("
    SELECT item_name, quantity, reorder_level, unit_cost
    FROM inventory_items
    WHERE quantity <= reorder_level
    ORDER BY (quantity / reorder_level) ASC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// Inventory by category
$inventoryByCategory = $db->query("
    SELECT ic.category_name, SUM(ii.quantity) as total_quantity, SUM(ii.quantity * ii.unit_cost) as value
    FROM inventory_categories ic
    JOIN inventory_items ii ON ic.inv_cat_id = ii.inv_cat_id
    GROUP BY ic.inv_cat_id
    ORDER BY value DESC
")->fetchAll(PDO::FETCH_ASSOC);

// ==================== PROMOTIONS IMPACT ====================

// Promotions performance
$promotionsImpact = $db->query("
    SELECT 
        p.title,
        p.promo_code,
        COUNT(b.booking_id) as bookings_generated,
        SUM(b.total_amount) as revenue_generated
    FROM promotions p
    LEFT JOIN bookings b ON b.special_requests LIKE CONCAT('%', p.promo_code, '%')
        OR b.created_at BETWEEN p.start_date AND p.end_date
    WHERE p.is_active = 1
    GROUP BY p.promo_id
    ORDER BY bookings_generated DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Booking sources distribution
$bookingSources = $db->prepare("
    SELECT booking_source, COUNT(*) as count
    FROM bookings
    WHERE created_at >= ? AND created_at <= ?
    GROUP BY booking_source
    ORDER BY count DESC
");
$bookingSources->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
$bookingSourceData = $bookingSources->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/admin-header.php';
?>

<style>
    .analytics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .kpi-card {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        display: flex;
        align-items: center;
        gap: 16px;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }
    
    .kpi-icon {
        width: 56px;
        height: 56px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }
    
    .kpi-icon.blue { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
    .kpi-icon.green { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; }
    .kpi-icon.orange { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; }
    .kpi-icon.purple { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; }
    .kpi-icon.yellow { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; }
    .kpi-icon.teal { background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); color: white; }
    
    .kpi-content h3 {
        font-size: 28px;
        margin: 0;
        color: var(--dark-color);
    }
    
    .kpi-content p {
        margin: 4px 0 0 0;
        color: #666;
        font-size: 14px;
    }
    
    .chart-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .chart-card {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    
    .chart-card h3 {
        margin: 0 0 20px 0;
        font-size: 18px;
        color: var(--dark-color);
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .chart-container {
        position: relative;
        height: 300px;
    }
    
    .chart-container.pie {
        height: 250px;
    }
    
    .analytics-section {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 20px;
    }
    
    .analytics-section h3 {
        margin: 0 0 20px 0;
        font-size: 18px;
        color: var(--dark-color);
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .analytics-section h3 i {
        color: var(--primary-color);
    }
    
    .data-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .data-table th,
    .data-table td {
        padding: 12px 16px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }
    
    .data-table th {
        background: #f8f9fa;
        font-weight: 600;
        color: var(--dark-color);
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .data-table tr:hover {
        background: #f8f9fa;
    }
    
    .period-selector {
        display: flex;
        gap: 10px;
        margin-bottom: 30px;
        flex-wrap: wrap;
        align-items: center;
    }
    
    .period-btn {
        padding: 10px 20px;
        border: 2px solid var(--primary-color);
        background: white;
        color: var(--primary-color);
        border-radius: 8px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.2s;
        text-decoration: none;
    }
    
    .period-btn:hover,
    .period-btn.active {
        background: var(--primary-color);
        color: white;
    }
    
    .date-inputs {
        display: flex;
        gap: 10px;
        align-items: center;
    }
    
    .date-inputs input {
        padding: 10px 14px;
        border: 2px solid #ddd;
        border-radius: 8px;
        font-size: 14px;
    }
    
    .date-inputs button {
        padding: 10px 20px;
        background: var(--primary-color);
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
    }
    
    .progress-bar {
        height: 8px;
        background: #e9ecef;
        border-radius: 4px;
        overflow: hidden;
        margin-top: 8px;
    }
    
    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        border-radius: 4px;
        transition: width 0.3s;
    }
    
    .stat-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid #eee;
    }
    
    .stat-row:last-child {
        border-bottom: none;
    }
    
    .stat-label {
        font-weight: 500;
        color: var(--text-color);
    }
    
    .stat-value {
        font-weight: 700;
        color: var(--primary-color);
    }
    
    .rating-stars {
        color: #ffc107;
    }
    
    .badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .badge-success { background: #d4edda; color: #155724; }
    .badge-warning { background: #fff3cd; color: #856404; }
    .badge-danger { background: #f8d7da; color: #721c24; }
    .badge-info { background: #d1ecf1; color: #0c5460; }
    
    @media (max-width: 768px) {
        .chart-grid {
            grid-template-columns: 1fr;
        }
        .analytics-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<!-- Period Selector -->
<div class="period-selector">
    <a href="?period=week" class="period-btn <?php echo $period == 'week' ? 'active' : ''; ?>">Last 7 Days</a>
    <a href="?period=month" class="period-btn <?php echo $period == 'month' ? 'active' : ''; ?>">This Month</a>
    <a href="?period=quarter" class="period-btn <?php echo $period == 'quarter' ? 'active' : ''; ?>">Last 3 Months</a>
    <a href="?period=year" class="period-btn <?php echo $period == 'year' ? 'active' : ''; ?>">This Year</a>
    
    <form class="date-inputs" method="get">
        <input type="hidden" name="period" value="custom">
        <input type="date" name="start_date" value="<?php echo $startDate; ?>" required>
        <span>to</span>
        <input type="date" name="end_date" value="<?php echo $endDate; ?>" required>
        <button type="submit">Apply</button>
    </form>
</div>

<!-- KPI Dashboard -->
<div class="analytics-grid">
    <div class="kpi-card">
        <div class="kpi-icon blue">
            <i class="fas fa-bed"></i>
        </div>
        <div class="kpi-content">
            <h3><?php echo $occupancyRate; ?>%</h3>
            <p>Occupancy Rate</p>
        </div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-icon green">
            <i class="fas fa-calendar-check"></i>
        </div>
        <div class="kpi-content">
            <h3><?php echo $totalBookingsCount; ?></h3>
            <p>Total Bookings</p>
        </div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-icon orange">
            <i class="fas fa-peso-sign"></i>
        </div>
        <div class="kpi-content">
            <h3>₱<?php echo number_format($totalRevenueAmount, 0); ?></h3>
            <p>Total Revenue</p>
        </div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-icon purple">
            <i class="fas fa-users"></i>
        </div>
        <div class="kpi-content">
            <h3><?php echo $newCustomersCount; ?></h3>
            <p>New Customers</p>
        </div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-icon yellow">
            <i class="fas fa-chart-line"></i>
        </div>
        <div class="kpi-content">
            <h3>₱<?php echo number_format($revpar, 0); ?></h3>
            <p>RevPAR</p>
        </div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-icon teal">
            <i class="fas fa-star"></i>
        </div>
        <div class="kpi-content">
            <h3><?php echo number_format($overallRating, 1); ?>/5</h3>
            <p>Avg Rating</p>
        </div>
    </div>
</div>

<!-- Charts Row 1 -->
<div class="chart-grid">
    <div class="chart-card">
        <h3><i class="fas fa-chart-bar"></i> Revenue Trend (Last 12 Months)</h3>
        <div class="chart-container">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>
    
    <div class="chart-card">
        <h3><i class="fas fa-chart-line"></i> Bookings Trend (Last 12 Months)</h3>
        <div class="chart-container">
            <canvas id="bookingsChart"></canvas>
        </div>
    </div>
</div>

<!-- Charts Row 2 -->
<div class="chart-grid">
    <div class="chart-card">
        <h3><i class="fas fa-chart-pie"></i> Room Status Distribution</h3>
        <div class="chart-container pie">
            <canvas id="roomStatusChart"></canvas>
        </div>
    </div>
    
    <div class="chart-card">
        <h3><i class="fas fa-chart-pie"></i> Revenue by Room Type</h3>
        <div class="chart-container pie">
            <canvas id="revenueByRoomChart"></canvas>
        </div>
    </div>
</div>

<!-- Room & Occupancy Analytics -->
<div class="analytics-section">
    <h3><i class="fas fa-door-open"></i> Room & Occupancy Analytics</h3>
    <div class="stat-row">
        <span class="stat-label">Total Rooms</span>
        <span class="stat-value"><?php echo $totalRooms; ?></span>
    </div>
    <div class="stat-row">
        <span class="stat-label">Occupied Rooms</span>
        <span class="stat-value"><?php echo $occupiedRooms; ?></span>
    </div>
    <div class="stat-row">
        <span class="stat-label">Available Rooms</span>
        <span class="stat-value"><?php echo $availableRooms; ?></span>
    </div>
    <div class="stat-row">
        <span class="stat-label">Under Maintenance</span>
        <span class="stat-value"><?php echo $maintenanceRooms; ?></span>
    </div>
    <div class="stat-row">
        <span class="stat-label">Average Daily Rate (ADR)</span>
        <span class="stat-value">₱<?php echo number_format($adr, 2); ?></span>
    </div>
    <div class="stat-row">
        <span class="stat-label">Revenue per Available Room (RevPAR)</span>
        <span class="stat-value">₱<?php echo number_format($revpar, 2); ?></span>
    </div>
</div>

<!-- Room Type Popularity -->
<div class="analytics-section">
    <h3><i class="fas fa-layer-group"></i> Room Type Popularity</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>Room Type</th>
                <th>Bookings</th>
                <th>Revenue</th>
                <th>Performance</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($roomTypeData as $room): ?>
            <tr>
                <td><?php echo htmlspecialchars($room['category_name']); ?></td>
                <td><?php echo $room['booking_count']; ?></td>
                <td>₱<?php echo number_format($room['total_revenue'], 2); ?></td>
                <td>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo min(100, ($room['booking_count'] / max(1, $totalBookingsCount)) * 100 * 4); ?>%"></div>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Customer Analytics -->
<div class="analytics-section">
    <h3><i class="fas fa-users"></i> Customer Analytics</h3>
    <div class="stat-row">
        <span class="stat-label">Total Customers</span>
        <span class="stat-value"><?php echo $totalCustomers; ?></span>
    </div>
    <div class="stat-row">
        <span class="stat-label">New Customers (Period)</span>
        <span class="stat-value"><?php echo $newCustomersCount; ?></span>
    </div>
    <div class="stat-row">
        <span class="stat-label">Repeat Guests</span>
        <span class="stat-value"><?php echo count($repeatGuests); ?></span>
    </div>
    <div class="stat-row">
        <span class="stat-label">VIP Customers</span>
        <span class="stat-value"><?php echo count($vipCustomers); ?></span>
    </div>
</div>

<!-- Most Frequent Guests -->
<div class="analytics-section">
    <h3><i class="fas fa-crown"></i> Most Frequent Guests</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>Guest Name</th>
                <th>Email</th>
                <th>Total Bookings</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $topGuests = array_slice($repeatGuests, 0, 5);
            foreach ($topGuests as $guest): 
            ?>
            <tr>
                <td><?php echo htmlspecialchars($guest['first_name'] . ' ' . $guest['last_name']); ?></td>
                <td><?php echo htmlspecialchars($guest['email']); ?></td>
                <td><?php echo $guest['booking_count']; ?></td>
                <td><span class="badge badge-success">Repeat Guest</span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Customer Feedback -->
<div class="analytics-section">
    <h3><i class="fas fa-star"></i> Customer Feedback Summary</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>Category</th>
                <th>Average Rating</th>
                <th>Review Count</th>
                <th>Rating</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reviewStats as $stat): ?>
            <tr>
                <td><?php echo ucfirst($stat['category']); ?></td>
                <td class="rating-stars">
                    <?php 
                    $rating = round($stat['avg_rating'], 1);
                    for ($i = 1; $i <= 5; $i++) {
                        if ($i <= $rating) {
                            echo '<i class="fas fa-star"></i>';
                        } elseif ($i - 0.5 <= $rating) {
                            echo '<i class="fas fa-star-half-alt"></i>';
                        } else {
                            echo '<i class="far fa-star"></i>';
                        }
                    }
                    ?>
                    <span style="color: #666; margin-left: 8px;"><?php echo $rating; ?></span>
                </td>
                <td><?php echo $stat['review_count']; ?></td>
                <td>
                    <?php if ($rating >= 4): ?>
                        <span class="badge badge-success">Excellent</span>
                    <?php elseif ($rating >= 3): ?>
                        <span class="badge badge-info">Good</span>
                    <?php else: ?>
                        <span class="badge badge-warning">Needs Improvement</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Staff & Service Analytics -->
<div class="analytics-section">
    <h3><i class="fas fa-user-tie"></i> Staff & Service Analytics</h3>
    <div class="stat-row">
        <span class="stat-label">Total Staff</span>
        <span class="stat-value"><?php echo array_sum($staffByRole); ?></span>
    </div>
    <?php foreach ($staffByRole as $role => $count): ?>
    <div class="stat-row">
        <span class="stat-label"><?php echo ucfirst($role); ?>s</span>
        <span class="stat-value"><?php echo $count; ?></span>
    </div>
    <?php endforeach; ?>
</div>

<!-- Staff Performance -->
<div class="analytics-section">
    <h3><i class="fas fa-calendar-alt"></i> Staff Performance (Last 7 Days)</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>Staff Name</th>
                <th>Shifts Completed</th>
                <th>Performance</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($staffPerformance as $staff): ?>
            <tr>
                <td><?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?></td>
                <td><?php echo $staff['shifts']; ?></td>
                <td>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo min(100, ($staff['shifts'] / 7) * 100); ?>%"></div>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Maintenance Requests -->
<div class="analytics-section">
    <h3><i class="fas fa-tools"></i> Maintenance Requests</h3>
    <div class="stat-row">
        <span class="stat-label">Total Requests</span>
        <span class="stat-value"><?php echo $maintenanceStats['total_requests']; ?></span>
    </div>
    <div class="stat-row">
        <span class="stat-label">Pending</span>
        <span class="stat-value"><span class="badge badge-warning"><?php echo $maintenanceStats['pending']; ?></span></span>
    </div>
    <div class="stat-row">
        <span class="stat-label">In Progress</span>
        <span class="stat-value"><span class="badge badge-info"><?php echo $maintenanceStats['in_progress']; ?></span></span>
    </div>
    <div class="stat-row">
        <span class="stat-label">Completed</span>
        <span class="stat-value"><span class="badge badge-success"><?php echo $maintenanceStats['completed']; ?></span></span>
    </div>
</div>

<!-- Event Analytics -->
<div class="analytics-section">
    <h3><i class="fas fa-calendar-alt"></i> Event Analytics</h3>
    <div class="stat-row">
        <span class="stat-label">Total Event Bookings</span>
        <span class="stat-value"><?php echo $eventStats['total_events']; ?></span>
    </div>
    <div class="stat-row">
        <span class="stat-label">Confirmed Events</span>
        <span class="stat-value"><span class="badge badge-success"><?php echo $eventStats['confirmed']; ?></span></span>
    </div>
    <div class="stat-row">
        <span class="stat-label">Pending Events</span>
        <span class="stat-value"><span class="badge badge-warning"><?php echo $eventStats['pending']; ?></span></span>
    </div>
    <div class="stat-row">
        <span class="stat-label">Completed Events</span>
        <span class="stat-value"><span class="badge badge-info"><?php echo $eventStats['completed']; ?></span></span>
    </div>
</div>

<!-- Event Space Bookings -->
<div class="analytics-section">
    <h3><i class="fas fa-building"></i> Event Space Utilization</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>Space Name</th>
                <th>Bookings</th>
                <th>Popularity</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($eventsBySpace as $space): ?>
            <tr>
                <td><?php echo htmlspecialchars($space['space_name']); ?></td>
                <td><?php echo $space['booking_count']; ?></td>
                <td>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo min(100, ($space['booking_count'] / max(1, $eventStats['total_events'])) * 100); ?>%"></div>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Inventory Analytics -->
<div class="analytics-section">
    <h3><i class="fas fa-boxes"></i> Inventory Analytics</h3>
    <div class="stat-row">
        <span class="stat-label">Total Inventory Items</span>
        <span class="stat-value"><?php echo $inventoryStats['total_items']; ?></span>
    </div>
    <div class="stat-row">
        <span class="stat-label">Low Stock Items</span>
        <span class="stat-value"><span class="badge badge-danger"><?php echo $inventoryStats['low_stock']; ?></span></span>
    </div>
    <div class="stat-row">
        <span class="stat-label">Total Inventory Value</span>
        <span class="stat-value">₱<?php echo number_format($inventoryStats['total_value'], 2); ?></span>
    </div>
</div>

<!-- Low Stock Alert -->
<?php if (!empty($lowStockItems)): ?>
<div class="analytics-section">
    <h3><i class="fas fa-exclamation-triangle"></i> Low Stock Items</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>Item Name</th>
                <th>Current Qty</th>
                <th>Reorder Level</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($lowStockItems as $item): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                <td><?php echo $item['quantity']; ?></td>
                <td><?php echo $item['reorder_level']; ?></td>
                <td>
                    <?php if ($item['quantity'] == 0): ?>
                        <span class="badge badge-danger">Out of Stock</span>
                    <?php else: ?>
                        <span class="badge badge-warning">Low Stock</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Booking Sources -->
<div class="analytics-section">
    <h3><i class="fas fa-globe"></i> Booking Sources</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>Source</th>
                <th>Bookings</th>
                <th>Percentage</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $totalSourceBookings = array_sum(array_column($bookingSourceData, 'count'));
            foreach ($bookingSourceData as $source): 
                $percentage = $totalSourceBookings > 0 ? round(($source['count'] / $totalSourceBookings) * 100, 1) : 0;
            ?>
            <tr>
                <td><?php echo ucfirst(str_replace('_', ' ', $source['booking_source'])); ?></td>
                <td><?php echo $source['count']; ?></td>
                <td>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                    </div>
                    <small><?php echo $percentage; ?>%</small>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Revenue Chart
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
const revenueChart = new Chart(revenueCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($revenueTrends, 'month')); ?>,
        datasets: [{
            label: 'Revenue (₱)',
            data: <?php echo json_encode(array_column($revenueTrends, 'revenue')); ?>,
            backgroundColor: 'rgba(54, 125, 138, 0.8)',
            borderColor: 'rgba(54, 125, 138, 1)',
            borderWidth: 2,
            borderRadius: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '₱' + value.toLocaleString();
                    }
                }
            }
        }
    }
});

// Bookings Chart
const bookingsCtx = document.getElementById('bookingsChart').getContext('2d');
const bookingsChart = new Chart(bookingsCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_column($bookingTrends, 'month')); ?>,
        datasets: [{
            label: 'Bookings',
            data: <?php echo json_encode(array_column($bookingTrends, 'bookings')); ?>,
            borderColor: 'rgba(40, 95, 107, 1)',
            backgroundColor: 'rgba(40, 95, 107, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// Room Status Pie Chart
const roomStatusCtx = document.getElementById('roomStatusChart').getContext('2d');
const roomStatusChart = new Chart(roomStatusCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode(array_map('ucfirst', array_keys($roomStatusData))); ?>,
        datasets: [{
            data: <?php echo json_encode(array_values($roomStatusData)); ?>,
            backgroundColor: [
                '#28a745',  // available - green
                '#dc3545',  // occupied - red
                '#ffc107',  // maintenance - yellow
                '#17a2b8',  // cleaning - cyan
                '#6f42c1'   // reserved - purple
            ],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'right'
            }
        }
    }
});

// Revenue by Room Type Pie Chart
const revenueRoomCtx = document.getElementById('revenueByRoomChart').getContext('2d');
const revenueRoomChart = new Chart(revenueRoomCtx, {
    type: 'pie',
    data: {
        labels: <?php echo json_encode(array_column($revenueByRoom, 'category_name')); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($revenueByRoom, 'revenue')); ?>,
            backgroundColor: [
                '#367D8A',
                '#285F6B',
                '#133336',
                '#5CA8B6',
                '#8BC4CE',
                '#B8DDE3'
            ],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'right'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.label + ': ₱' + context.raw.toLocaleString();
                    }
                }
            }
        }
    }
});
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
