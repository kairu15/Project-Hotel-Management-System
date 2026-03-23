<?php
/**
 * Comprehensive Reports Dashboard - Bayawan Bai Hotel
 * Hotel Management Reports with 7 categories:
 * 1. Occupancy Reports
 * 2. Revenue & Financial Reports
 * 3. Booking Reports
 * 4. Event & Banquet Reports
 * 5. Inventory & Housekeeping Reports
 * 6. Staff & Payroll Reports
 * 7. Customer Feedback & Reviews Reports
 * 
 * Features: Filters (date, room type, service, staff, event), Export (PDF, Excel, CSV), Charts
 */

$pageTitle = 'Reports Dashboard';
require_once __DIR__ . '/../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    showAlert('Access denied. Admin privileges required.', 'danger');
    redirect('../index.php');
}

$db = getDB();

// ==================== GET FILTER PARAMETERS ====================
$reportType = $_GET['report_type'] ?? 'overview';
$period = $_GET['period'] ?? 'month';
$customStart = $_GET['start_date'] ?? date('Y-m-01');
$customEnd = $_GET['end_date'] ?? date('Y-m-d');
$roomTypeFilter = $_GET['room_type'] ?? 'all';
$bookingSourceFilter = $_GET['booking_source'] ?? 'all';
$staffFilter = $_GET['staff_id'] ?? 'all';
$eventSpaceFilter = $_GET['event_space'] ?? 'all';
$exportFormat = $_GET['export'] ?? null;

// Calculate date ranges
$today = date('Y-m-d');
switch ($period) {
    case 'today':
        $startDate = $today;
        $endDate = $today;
        break;
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
        $startDate = $customStart;
        $endDate = $customEnd;
        break;
    default:
        $startDate = date('Y-m-01');
        $endDate = $today;
}

$startDateTime = $startDate . ' 00:00:00';
$endDateTime = $endDate . ' 23:59:59';
$daysInPeriod = max(1, (strtotime($endDate) - strtotime($startDate)) / 86400 + 1);

// Helper functions
function addRoomTypeFilter($sql, $roomTypeFilter) {
    if ($roomTypeFilter !== 'all' && is_numeric($roomTypeFilter)) {
        return $sql . " AND b.category_id = " . intval($roomTypeFilter);
    }
    return $sql;
}

function addBookingSourceFilter($sql, $bookingSourceFilter) {
    if ($bookingSourceFilter !== 'all') {
        return $sql . " AND b.booking_source = '" . $bookingSourceFilter . "'";
    }
    return $sql;
}

// ==================== 1. OCCUPANCY REPORTS ====================
function getOccupancyData($db, $startDate, $endDate, $daysInPeriod) {
    $data = [];
    $data['total_rooms'] = $db->query("SELECT COUNT(*) FROM rooms WHERE status != 'deleted'")->fetchColumn();
    $data['occupied'] = $db->query("SELECT COUNT(*) FROM rooms WHERE status = 'occupied'")->fetchColumn();
    $data['available'] = $db->query("SELECT COUNT(*) FROM rooms WHERE status = 'available'")->fetchColumn();
    $data['maintenance'] = $db->query("SELECT COUNT(*) FROM rooms WHERE status = 'maintenance'")->fetchColumn();
    $data['current_occupancy_rate'] = $data['total_rooms'] > 0 ? round(($data['occupied'] / $data['total_rooms']) * 100, 1) : 0;
    
    $stmt = $db->prepare("
        SELECT rc.category_name, COUNT(DISTINCT r.room_id) as total_rooms,
               SUM(CASE WHEN r.status = 'occupied' THEN 1 ELSE 0 END) as occupied,
               SUM(CASE WHEN r.status = 'available' THEN 1 ELSE 0 END) as available,
               SUM(CASE WHEN r.status = 'maintenance' THEN 1 ELSE 0 END) as maintenance
        FROM room_categories rc
        LEFT JOIN rooms r ON rc.category_id = r.category_id AND r.status != 'deleted'
        GROUP BY rc.category_id
        ORDER BY rc.category_name
    ");
    $stmt->execute();
    $data['room_type_occupancy'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $db->prepare("
        SELECT DATE(b.check_in) as date, COUNT(*) as check_ins
        FROM bookings b
        WHERE b.check_in BETWEEN ? AND ? AND b.status != 'cancelled'
        GROUP BY DATE(b.check_in) ORDER BY date
    ");
    $stmt->execute([$startDate, $endDate]);
    $data['daily_checkins'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $db->prepare("
        SELECT DATE(b.check_out) as date, COUNT(*) as check_outs
        FROM bookings b
        WHERE b.check_out BETWEEN ? AND ? AND b.status IN ('checked_out', 'confirmed')
        GROUP BY DATE(b.check_out) ORDER BY date
    ");
    $stmt->execute([$startDate, $endDate]);
    $data['daily_checkouts'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $maxPossibleRoomDays = $data['total_rooms'] * $daysInPeriod;
    $stmt = $db->prepare("
        SELECT SUM(DATEDIFF(LEAST(check_out, DATE_ADD(?, INTERVAL 1 DAY)), GREATEST(check_in, ?))) as occupied_days
        FROM bookings WHERE status IN ('confirmed', 'checked_in', 'checked_out')
        AND check_in <= ? AND check_out >= ?
    ");
    $stmt->execute([$endDate, $startDate, $endDate, $startDate]);
    $occupiedDays = $stmt->fetchColumn() ?: 0;
    $data['period_occupancy_rate'] = $maxPossibleRoomDays > 0 ? round(($occupiedDays / $maxPossibleRoomDays) * 100, 1) : 0;
    
    return $data;
}

// ==================== 2. REVENUE & FINANCIAL REPORTS ====================
function getRevenueData($db, $startDateTime, $endDateTime, $roomTypeFilter, $bookingSourceFilter) {
    $data = [];
    // Extract date-only portion from datetime for date comparisons
    $startDate = substr($startDateTime, 0, 10);
    $endDate = substr($endDateTime, 0, 10);
    
    $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_date BETWEEN ? AND ? AND status = 'completed'");
    $stmt->execute([$startDateTime, $endDateTime]);
    $data['total_revenue'] = $stmt->fetchColumn();
    
    $sql = "SELECT rc.category_name, SUM(p.amount) as revenue, COUNT(p.payment_id) as transactions
        FROM payments p JOIN bookings b ON p.booking_id = b.booking_id
        JOIN room_categories rc ON b.category_id = rc.category_id
        WHERE p.payment_date BETWEEN ? AND ? AND p.status = 'completed'";
    $sql = addRoomTypeFilter($sql, $roomTypeFilter);
    $sql .= " GROUP BY rc.category_id ORDER BY revenue DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute([$startDateTime, $endDateTime]);
    $data['revenue_by_room_type'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $sql = "SELECT b.booking_source, SUM(p.amount) as revenue, COUNT(*) as bookings
        FROM payments p JOIN bookings b ON p.booking_id = b.booking_id
        WHERE p.payment_date BETWEEN ? AND ? AND p.status = 'completed'";
    $sql = addBookingSourceFilter($sql, $bookingSourceFilter);
    $sql .= " GROUP BY b.booking_source ORDER BY revenue DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute([$startDateTime, $endDateTime]);
    $data['revenue_by_source'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $db->prepare("
        SELECT DATE(payment_date) as date, SUM(amount) as revenue, COUNT(*) as transactions
        FROM payments WHERE payment_date BETWEEN ? AND ? AND status = 'completed'
        GROUP BY DATE(payment_date) ORDER BY date
    ");
    $stmt->execute([$startDateTime, $endDateTime]);
    $data['daily_revenue'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $db->prepare("
        SELECT b.booking_id, u.first_name, u.last_name, u.email, b.total_amount,
               COALESCE(SUM(p.amount), 0) as paid_amount, (b.total_amount - COALESCE(SUM(p.amount), 0)) as balance,
               b.check_in, b.check_out, rc.category_name
        FROM bookings b JOIN users u ON b.user_id = u.user_id
        JOIN room_categories rc ON b.category_id = rc.category_id
        LEFT JOIN payments p ON b.booking_id = p.booking_id AND p.status = 'completed'
        WHERE b.status IN ('confirmed', 'checked_in', 'checked_out') AND b.created_at <= ?
        GROUP BY b.booking_id HAVING balance > 0 ORDER BY balance DESC LIMIT 50
    ");
    $stmt->execute([$endDateTime]);
    $data['outstanding_payments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $data['total_outstanding'] = array_sum(array_column($data['outstanding_payments'], 'balance'));
    $data['taxes_collected'] = $data['total_revenue'] * 0.12;
    
    $stmt = $db->prepare("
        SELECT AVG(b.total_amount / DATEDIFF(b.check_out, b.check_in)) as adr
        FROM bookings b WHERE b.status IN ('confirmed', 'checked_in', 'checked_out')
        AND b.check_in BETWEEN ? AND ? AND DATEDIFF(b.check_out, b.check_in) > 0
    ");
    $stmt->execute([$startDate, $endDate]);
    $data['adr'] = round($stmt->fetchColumn() ?: 0, 2);
    
    return $data;
}

// ==================== 3. BOOKING REPORTS ====================
function getBookingData($db, $startDateTime, $endDateTime, $roomTypeFilter, $bookingSourceFilter) {
    $data = [];
    $sql = "SELECT COUNT(*) FROM bookings WHERE created_at BETWEEN ? AND ?";
    $sql = addRoomTypeFilter($sql, $roomTypeFilter);
    $sql = addBookingSourceFilter($sql, $bookingSourceFilter);
    $stmt = $db->prepare($sql);
    $stmt->execute([$startDateTime, $endDateTime]);
    $data['total_bookings'] = $stmt->fetchColumn();
    
    $sql = "SELECT status, COUNT(*) as count FROM bookings WHERE created_at BETWEEN ? AND ?";
    $sql = addRoomTypeFilter($sql, $roomTypeFilter);
    $sql = addBookingSourceFilter($sql, $bookingSourceFilter);
    $sql .= " GROUP BY status";
    $stmt = $db->prepare($sql);
    $stmt->execute([$startDateTime, $endDateTime]);
    $statusCounts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $data['bookings_by_status'] = [];
    foreach ($statusCounts as $status => $count) {
        $pct = $data['total_bookings'] > 0 ? round(($count / $data['total_bookings']) * 100, 1) : 0;
        $data['bookings_by_status'][] = ['status' => $status, 'count' => $count, 'percentage' => $pct];
    }
    
    $sql = "SELECT booking_source, COUNT(*) as count FROM bookings WHERE created_at BETWEEN ? AND ?";
    $sql = addRoomTypeFilter($sql, $roomTypeFilter);
    $sql .= " GROUP BY booking_source ORDER BY count DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute([$startDateTime, $endDateTime]);
    $sourceCounts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $data['bookings_by_source'] = [];
    foreach ($sourceCounts as $source => $count) {
        $pct = $data['total_bookings'] > 0 ? round(($count / $data['total_bookings']) * 100, 1) : 0;
        $data['bookings_by_source'][] = ['booking_source' => $source, 'count' => $count, 'percentage' => $pct];
    }
    
    $sql = "SELECT DATE(created_at) as date, COUNT(*) as bookings,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancellations
        FROM bookings WHERE created_at BETWEEN ? AND ?";
    $sql = addRoomTypeFilter($sql, $roomTypeFilter);
    $sql = addBookingSourceFilter($sql, $bookingSourceFilter);
    $sql .= " GROUP BY DATE(created_at) ORDER BY date";
    $stmt = $db->prepare($sql);
    $stmt->execute([$startDateTime, $endDateTime]);
    $data['daily_bookings'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $db->prepare("
        SELECT SUM(CASE WHEN booking_count = 1 THEN 1 ELSE 0 END) as new_customers,
               SUM(CASE WHEN booking_count > 1 THEN 1 ELSE 0 END) as returning_customers
        FROM (SELECT user_id, COUNT(*) as booking_count FROM bookings
              WHERE created_at BETWEEN ? AND ? GROUP BY user_id) as user_bookings
    ");
    $stmt->execute([$startDateTime, $endDateTime]);
    $customerTypes = $stmt->fetch(PDO::FETCH_ASSOC);
    $data['new_customers'] = $customerTypes['new_customers'] ?? 0;
    $data['returning_customers'] = $customerTypes['returning_customers'] ?? 0;
    
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM bookings b JOIN users u ON b.user_id = u.user_id
        WHERE b.created_at BETWEEN ? AND ? AND u.loyalty_points >= 100
    ");
    $stmt->execute([$startDateTime, $endDateTime]);
    $data['vip_bookings'] = $stmt->fetchColumn();
    
    $cancelled = $statusCounts['cancelled'] ?? 0;
    $data['cancellation_rate'] = $data['total_bookings'] > 0 ? round(($cancelled / $data['total_bookings']) * 100, 1) : 0;
    
    return $data;
}

// ==================== 4. EVENT & BANQUET REPORTS ====================
function getEventData($db, $startDateTime, $endDateTime, $eventSpaceFilter) {
    $data = [];
    $sql = "SELECT COUNT(*) FROM event_bookings WHERE created_at BETWEEN ? AND ?";
    if ($eventSpaceFilter !== 'all' && is_numeric($eventSpaceFilter)) {
        $sql .= " AND space_id = " . intval($eventSpaceFilter);
    }
    $stmt = $db->prepare($sql);
    $stmt->execute([$startDateTime, $endDateTime]);
    $data['total_events'] = $stmt->fetchColumn();
    
    $sql = "SELECT status, COUNT(*) as count FROM event_bookings WHERE created_at BETWEEN ? AND ?";
    if ($eventSpaceFilter !== 'all' && is_numeric($eventSpaceFilter)) {
        $sql .= " AND space_id = " . intval($eventSpaceFilter);
    }
    $sql .= " GROUP BY status";
    $stmt = $db->prepare($sql);
    $stmt->execute([$startDateTime, $endDateTime]);
    $statusCounts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $data['events_by_status'] = [];
    foreach ($statusCounts as $status => $count) {
        $pct = $data['total_events'] > 0 ? round(($count / $data['total_events']) * 100, 1) : 0;
        $data['events_by_status'][] = ['status' => $status, 'count' => $count, 'percentage' => $pct];
    }
    
    $sql = "SELECT COALESCE(SUM(quoted_price), 0) FROM event_bookings WHERE created_at BETWEEN ? AND ? AND status IN ('confirmed', 'completed')";
    if ($eventSpaceFilter !== 'all' && is_numeric($eventSpaceFilter)) {
        $sql .= " AND space_id = " . intval($eventSpaceFilter);
    }
    $stmt = $db->prepare($sql);
    $stmt->execute([$startDateTime, $endDateTime]);
    $data['event_revenue'] = $stmt->fetchColumn();
    
    $stmt = $db->prepare("
        SELECT es.space_name, COUNT(eb.event_booking_id) as bookings,
               COALESCE(SUM(eb.quoted_price), 0) as revenue,
               COALESCE(SUM(eb.guests_count), 0) as total_guests
        FROM event_spaces es
        LEFT JOIN event_bookings eb ON es.space_id = eb.space_id AND eb.created_at BETWEEN ? AND ?
        GROUP BY es.space_id ORDER BY bookings DESC
    ");
    $stmt->execute([$startDateTime, $endDateTime]);
    $data['events_by_space'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $db->prepare("
        SELECT DATE_FORMAT(event_date, '%Y-%m') as month, COUNT(*) as events, COALESCE(SUM(quoted_price), 0) as revenue
        FROM event_bookings WHERE event_date BETWEEN ? AND ? GROUP BY month ORDER BY month
    ");
    $startDate = substr($startDateTime, 0, 10);
    $endDate = substr($endDateTime, 0, 10);
    $stmt->execute([$startDate, $endDate]);
    $data['monthly_events'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $db->prepare("
        SELECT AVG(guests_count) FROM event_bookings WHERE created_at BETWEEN ? AND ? AND guests_count > 0
    ");
    $stmt->execute([$startDateTime, $endDateTime]);
    $data['avg_guests_per_event'] = round($stmt->fetchColumn() ?: 0, 1);
    
    return $data;
}

// ==================== 5. INVENTORY & HOUSEKEEPING REPORTS ====================
function getInventoryData($db, $startDateTime, $endDateTime) {
    $data = [];
    $stmt = $db->query("
        SELECT COUNT(*) as total_items, SUM(CASE WHEN quantity <= reorder_level THEN 1 ELSE 0 END) as low_stock,
               SUM(quantity * unit_cost) as total_value, AVG(quantity) as avg_quantity
        FROM inventory_items
    ");
    $data['overview'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $db->query("
        SELECT ic.category_name, COUNT(ii.item_id) as item_count, SUM(ii.quantity) as total_quantity,
               SUM(ii.quantity * ii.unit_cost) as category_value,
               SUM(CASE WHEN ii.quantity <= ii.reorder_level THEN 1 ELSE 0 END) as low_stock_items
        FROM inventory_categories ic
        LEFT JOIN inventory_items ii ON ic.inv_cat_id = ii.inv_cat_id
        GROUP BY ic.inv_cat_id ORDER BY category_value DESC
    ");
    $data['by_category'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $db->query("
        SELECT ii.*, ic.category_name FROM inventory_items ii
        JOIN inventory_categories ic ON ii.inv_cat_id = ic.inv_cat_id
        WHERE ii.quantity <= ii.reorder_level ORDER BY (ii.quantity / ii.reorder_level) ASC LIMIT 20
    ");
    $data['low_stock_items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $db->query("
        SELECT COUNT(*) as total_requests,
               SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
               SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
               SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
        FROM maintenance_requests
    ");
    $data['maintenance'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $db->query("
        SELECT issue_type, COUNT(*) as count FROM maintenance_requests GROUP BY issue_type ORDER BY count DESC
    ");
    $data['maintenance_by_type'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $data;
}

// ==================== 6. STAFF & PAYROLL REPORTS ====================
function getStaffData($db, $startDate, $endDate, $staffFilter) {
    $data = [];
    $stmt = $db->query("
        SELECT role, COUNT(*) as count FROM users
        WHERE role IN ('admin', 'manager', 'receptionist', 'housekeeping', 'maintenance')
        GROUP BY role ORDER BY count DESC
    ");
    $data['staff_by_role'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $data['total_staff'] = array_sum(array_column($data['staff_by_role'], 'count'));
    
    $sql = "
        SELECT u.user_id, u.first_name, u.last_name, u.role,
               COUNT(s.schedule_id) as shifts_worked,
               SUM(CASE WHEN s.status = 'completed' THEN 1 ELSE 0 END) as completed_shifts,
               SUM(CASE WHEN s.status = 'absent' THEN 1 ELSE 0 END) as absences
        FROM users u
        LEFT JOIN staff_schedules s ON u.user_id = s.user_id AND s.work_date BETWEEN ? AND ?
        WHERE u.role IN ('admin', 'manager', 'receptionist', 'housekeeping', 'maintenance')
    ";
    if ($staffFilter !== 'all' && is_numeric($staffFilter)) {
        $sql .= " AND u.user_id = " . intval($staffFilter);
    }
    $sql .= " GROUP BY u.user_id ORDER BY shifts_worked DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute([$startDate, $endDate]);
    $data['attendance'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $data['monthly_payroll'] = 0; // No salary data available
    
    return $data;
}

// ==================== 7. CUSTOMER FEEDBACK REPORTS ====================
function getFeedbackData($db, $startDateTime, $endDateTime, $roomTypeFilter) {
    $data = [];
    $stmt = $db->prepare("
        SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews
        FROM reviews WHERE created_at BETWEEN ? AND ? AND is_approved = 1
    ");
    $stmt->execute([$startDateTime, $endDateTime]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $data['overall_rating'] = round($result['avg_rating'] ?? 0, 1);
    $data['total_reviews'] = $result['total_reviews'] ?? 0;
    
    $stmt = $db->prepare("
        SELECT rating, COUNT(*) as count FROM reviews
        WHERE created_at BETWEEN ? AND ? AND is_approved = 1 GROUP BY rating ORDER BY rating DESC
    ");
    $stmt->execute([$startDateTime, $endDateTime]);
    $data['rating_distribution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $db->prepare("
        SELECT category, AVG(rating) as avg_rating, COUNT(*) as review_count FROM reviews
        WHERE created_at BETWEEN ? AND ? AND is_approved = 1 GROUP BY category ORDER BY avg_rating DESC
    ");
    $stmt->execute([$startDateTime, $endDateTime]);
    $data['reviews_by_category'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $sql = "
        SELECT rc.category_name, AVG(r.rating) as avg_rating, COUNT(*) as review_count
        FROM reviews r JOIN bookings b ON r.booking_id = b.booking_id
        JOIN room_categories rc ON b.category_id = rc.category_id
        WHERE r.created_at BETWEEN ? AND ? AND r.is_approved = 1
    ";
    if ($roomTypeFilter !== 'all' && is_numeric($roomTypeFilter)) {
        $sql .= " AND b.category_id = " . intval($roomTypeFilter);
    }
    $sql .= " GROUP BY rc.category_id ORDER BY avg_rating DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute([$startDateTime, $endDateTime]);
    $data['reviews_by_room_type'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $db->prepare("
        SELECT SUM(CASE WHEN rating >= 4 THEN 1 ELSE 0 END) as promoters,
               SUM(CASE WHEN rating <= 2 THEN 1 ELSE 0 END) as detractors, COUNT(*) as total
        FROM reviews WHERE created_at BETWEEN ? AND ? AND is_approved = 1
    ");
    $stmt->execute([$startDateTime, $endDateTime]);
    $npsData = $stmt->fetch(PDO::FETCH_ASSOC);
    $promoters = $npsData['promoters'] ?? 0;
    $detractors = $npsData['detractors'] ?? 0;
    $total = max(1, $npsData['total'] ?? 1);
    $data['nps_score'] = round((($promoters - $detractors) / $total) * 100, 1);
    
    return $data;
}

// ==================== FETCH FILTER OPTIONS ====================
$roomTypes = $db->query("SELECT category_id, category_name FROM room_categories ORDER BY category_name")->fetchAll(PDO::FETCH_ASSOC);
$eventSpaces = $db->query("SELECT space_id, space_name FROM event_spaces ORDER BY space_name")->fetchAll(PDO::FETCH_ASSOC);
$staffMembers = $db->query("
    SELECT user_id, first_name, last_name, role FROM users
    WHERE role IN ('admin', 'manager', 'receptionist', 'housekeeping', 'maintenance')
    ORDER BY first_name
")->fetchAll(PDO::FETCH_ASSOC);
$bookingSources = ['online', 'phone', 'walk-in', 'email', 'referral', 'OTA'];

// ==================== FETCH REPORT DATA ====================
$reportData = [];
switch ($reportType) {
    case 'occupancy':
        $reportData = getOccupancyData($db, $startDate, $endDate, $daysInPeriod);
        break;
    case 'revenue':
        $reportData = getRevenueData($db, $startDateTime, $endDateTime, $roomTypeFilter, $bookingSourceFilter);
        break;
    case 'bookings':
        $reportData = getBookingData($db, $startDateTime, $endDateTime, $roomTypeFilter, $bookingSourceFilter);
        break;
    case 'events':
        $reportData = getEventData($db, $startDateTime, $endDateTime, $eventSpaceFilter);
        break;
    case 'inventory':
        $reportData = getInventoryData($db, $startDateTime, $endDateTime);
        break;
    case 'staff':
        $reportData = getStaffData($db, $startDate, $endDate, $staffFilter);
        break;
    case 'feedback':
        $reportData = getFeedbackData($db, $startDateTime, $endDateTime, $roomTypeFilter);
        break;
    case 'overview':
    default:
        $reportData = [
            'occupancy' => getOccupancyData($db, $startDate, $endDate, $daysInPeriod),
            'revenue' => getRevenueData($db, $startDateTime, $endDateTime, $roomTypeFilter, $bookingSourceFilter),
            'bookings' => getBookingData($db, $startDateTime, $endDateTime, $roomTypeFilter, $bookingSourceFilter),
            'events' => getEventData($db, $startDateTime, $endDateTime, $eventSpaceFilter),
            'inventory' => getInventoryData($db, $startDateTime, $endDateTime),
            'staff' => getStaffData($db, $startDate, $endDate, $staffFilter),
            'feedback' => getFeedbackData($db, $startDateTime, $endDateTime, $roomTypeFilter)
        ];
        break;
}

// ==================== EXPORT FUNCTIONALITY ====================
if ($exportFormat && in_array($exportFormat, ['csv', 'excel', 'pdf'])) {
    $filename = "bayawan_report_{$reportType}_{$startDate}_to_{$endDate}";
    
    switch ($exportFormat) {
        case 'csv':
            header('Content-Type: text/csv; charset=utf-8');
            header("Content-Disposition: attachment; filename={$filename}.csv");
            exportToCSV($reportData, $reportType);
            exit;
        case 'excel':
            header('Content-Type: application/vnd.ms-excel');
            header("Content-Disposition: attachment; filename={$filename}.xls");
            exportToExcel($reportData, $reportType);
            exit;
        case 'pdf':
            header('Content-Type: text/html');
            header("Content-Disposition: attachment; filename={$filename}.html");
            exportToPrintableHTML($reportData, $reportType, $startDate, $endDate);
            exit;
    }
}

function exportToCSV($data, $type) {
    $output = fopen('php://output', 'w');
    switch ($type) {
        case 'occupancy':
            fputcsv($output, ['Room Type', 'Total', 'Occupied', 'Available', 'Maintenance', 'Rate %']);
            foreach ($data['room_type_occupancy'] ?? [] as $row) {
                $rate = $row['total_rooms'] > 0 ? round(($row['occupied'] / $row['total_rooms']) * 100, 1) : 0;
                fputcsv($output, [$row['category_name'], $row['total_rooms'], $row['occupied'], $row['available'], $row['maintenance'], $rate]);
            }
            break;
        case 'revenue':
            fputcsv($output, ['Category', 'Revenue', 'Transactions']);
            foreach ($data['revenue_by_room_type'] ?? [] as $row) {
                fputcsv($output, [$row['category_name'], $row['revenue'], $row['transactions']]);
            }
            break;
        case 'bookings':
            fputcsv($output, ['Status', 'Count']);
            foreach ($data['bookings_by_status'] ?? [] as $row) {
                fputcsv($output, [$row['status'], $row['count']]);
            }
            break;
        case 'feedback':
            fputcsv($output, ['Category', 'Avg Rating', 'Reviews']);
            foreach ($data['reviews_by_category'] ?? [] as $row) {
                fputcsv($output, [$row['category'], round($row['avg_rating'], 1), $row['review_count']]);
            }
            break;
        default:
            fputcsv($output, ['Report Type: ' . $type]);
    }
    fclose($output);
}

function exportToExcel($data, $type) {
    echo "<html><head><style>table{border-collapse:collapse;}th,td{border:1px solid #000;padding:5px;}</style></head><body>";
    echo "<h2>" . ucfirst($type) . " Report</h2><table>";
    exportTableData($data, $type);
    echo "</table></body></html>";
}

function exportToPrintableHTML($data, $type, $startDate, $endDate) {
    echo "<!DOCTYPE html><html><head><title>Bayawan Bai Hotel Report</title>";
    echo "<style>body{font-family:Arial;margin:40px;}h1{color:#367D8A;}table{width:100%;border-collapse:collapse;}th,td{border:1px solid #ddd;padding:12px;}th{background:#367D8A;color:white;}</style></head><body>";
    echo "<h1>Bayawan Bai Hotel</h1><h2>" . ucfirst($type) . " Report</h2>";
    echo "<p><strong>Period:</strong> $startDate to $endDate</p><hr>";
    exportTableData($data, $type);
    echo "</body></html>";
}

function exportTableData($data, $type) {
    echo "<table>";
    switch ($type) {
        case 'occupancy':
            echo "<tr><th>Room Type</th><th>Total</th><th>Occupied</th><th>Available</th><th>Rate %</th></tr>";
            foreach ($data['room_type_occupancy'] ?? [] as $row) {
                $rate = $row['total_rooms'] > 0 ? round(($row['occupied'] / $row['total_rooms']) * 100, 1) : 0;
                echo "<tr><td>" . htmlspecialchars($row['category_name']) . "</td><td>" . $row['total_rooms'] . "</td><td>" . $row['occupied'] . "</td><td>" . $row['available'] . "</td><td>" . $rate . "%</td></tr>";
            }
            break;
        case 'revenue':
            echo "<tr><th>Category</th><th>Revenue</th></tr>";
            foreach ($data['revenue_by_room_type'] ?? [] as $row) {
                echo "<tr><td>" . htmlspecialchars($row['category_name']) . "</td><td>₱" . number_format($row['revenue'], 2) . "</td></tr>";
            }
            break;
        default:
            echo "<tr><td>Report: " . htmlspecialchars($type) . "</td></tr>";
    }
    echo "</table>";
}

require_once __DIR__ . '/../includes/admin-header.php';
?>

<style>
.reports-container { max-width: 1600px; margin: 0 auto; padding: 0 20px; }
.reports-header { margin-bottom: 30px; }
.reports-tabs { display: flex; gap: 5px; flex-wrap: wrap; margin-bottom: 25px; border-bottom: 2px solid var(--gray-medium); padding-bottom: 10px; }
.report-tab { 
    padding: 12px 24px; 
    border: none; 
    background: transparent; 
    color: #666; 
    font-weight: 500; 
    cursor: pointer; 
    border-radius: 8px 8px 0 0;
    transition: all 0.2s;
    display: flex; align-items: center; gap: 8px;
}
.report-tab:hover { background: #f5f5f5; color: var(--primary-color); }
.report-tab.active { 
    background: var(--primary-color); 
    color: white; 
    box-shadow: 0 2px 8px rgba(54, 125, 138, 0.3);
}
.filter-panel { 
    background: white; 
    padding: 25px; 
    border-radius: 12px; 
    box-shadow: 0 2px 10px rgba(0,0,0,0.05); 
    margin-bottom: 25px;
}
.filter-grid { 
    display: grid; 
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
    gap: 20px; 
    align-items: end;
}
.filter-group label { 
    display: block; 
    font-size: 13px; 
    color: #666; 
    margin-bottom: 8px; 
    font-weight: 500;
}
.filter-group select, .filter-group input {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid var(--gray-medium);
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.2s;
}
.filter-group select:focus, .filter-group input:focus {
    outline: none;
    border-color: var(--primary-color);
}
.export-buttons { display: flex; gap: 10px; flex-wrap: wrap; }
.export-btn {
    padding: 10px 20px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    font-size: 14px;
    transition: all 0.2s;
    display: flex; align-items: center; gap: 8px;
}
.export-btn.csv { background: #28a745; color: white; }
.export-btn.excel { background: #217346; color: white; }
.export-btn.pdf { background: #dc3545; color: white; }
.export-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
.kpi-grid { 
    display: grid; 
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); 
    gap: 20px; 
    margin-bottom: 30px;
}
.kpi-card {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    transition: transform 0.2s, box-shadow 0.2s;
}
.kpi-card:hover { transform: translateY(-3px); box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
.kpi-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; margin-bottom: 15px; }
.kpi-icon.blue { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
.kpi-icon.green { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; }
.kpi-icon.orange { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; }
.kpi-icon.purple { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; }
.kpi-icon.yellow { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; }
.kpi-value { font-size: 32px; font-weight: 700; color: var(--dark-color); margin-bottom: 5px; }
.kpi-label { font-size: 14px; color: #666; }
.report-section { background: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 25px; overflow: hidden; }
.report-section-header { 
    padding: 20px 25px; 
    border-bottom: 1px solid var(--gray-medium);
    display: flex; justify-content: space-between; align-items: center;
}
.report-section-title { font-size: 18px; font-weight: 600; color: var(--dark-color); display: flex; align-items: center; gap: 10px; }
.report-section-content { padding: 25px; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th, .data-table td { padding: 14px 16px; text-align: left; border-bottom: 1px solid #eee; }
.data-table th { background: #f8f9fa; font-weight: 600; color: var(--dark-color); font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
.data-table tr:hover { background: #f8f9fa; }
.badge { padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.badge-success { background: #d4edda; color: #155724; }
.badge-warning { background: #fff3cd; color: #856404; }
.badge-danger { background: #f8d7da; color: #721c24; }
.badge-info { background: #d1ecf1; color: #0c5460; }
.progress-bar { height: 10px; background: #e9ecef; border-radius: 5px; overflow: hidden; }
.progress-fill { height: 100%; border-radius: 5px; background: linear-gradient(90deg, var(--primary-color), var(--secondary-color)); transition: width 0.3s; }
.chart-placeholder { 
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); 
    border-radius: 12px; 
    height: 250px; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    color: #666; 
    font-size: 16px;
    margin-bottom: 20px;
}
.two-column { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
@media (max-width: 992px) { .two-column { grid-template-columns: 1fr; } .kpi-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 768px) { .kpi-grid { grid-template-columns: 1fr; } .filter-grid { grid-template-columns: 1fr; } .reports-tabs { overflow-x: auto; flex-wrap: nowrap; } }
</style>

<div class="reports-container">
    <div class="reports-header">
        <h1 style="margin-bottom: 8px;"><i class="fas fa-chart-bar" style="color: var(--primary-color);"></i> Reports Dashboard</h1>
        <p style="color: #666; margin: 0;">Comprehensive hotel management reports with 7 categories</p>
    </div>

    <!-- Report Type Tabs -->
    <div class="reports-tabs">
        <a href="?report_type=overview&period=<?php echo $period; ?>&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" 
           class="report-tab <?php echo $reportType === 'overview' ? 'active' : ''; ?>">
            <i class="fas fa-th-large"></i> Overview
        </a>
        <a href="?report_type=occupancy&period=<?php echo $period; ?>&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" 
           class="report-tab <?php echo $reportType === 'occupancy' ? 'active' : ''; ?>">
            <i class="fas fa-bed"></i> Occupancy
        </a>
        <a href="?report_type=revenue&period=<?php echo $period; ?>&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" 
           class="report-tab <?php echo $reportType === 'revenue' ? 'active' : ''; ?>">
            <i class="fas fa-money-bill-wave"></i> Revenue
        </a>
        <a href="?report_type=bookings&period=<?php echo $period; ?>&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" 
           class="report-tab <?php echo $reportType === 'bookings' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-check"></i> Bookings
        </a>
        <a href="?report_type=events&period=<?php echo $period; ?>&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" 
           class="report-tab <?php echo $reportType === 'events' ? 'active' : ''; ?>">
            <i class="fas fa-building"></i> Events
        </a>
        <a href="?report_type=inventory&period=<?php echo $period; ?>&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" 
           class="report-tab <?php echo $reportType === 'inventory' ? 'active' : ''; ?>">
            <i class="fas fa-boxes"></i> Inventory
        </a>
        <a href="?report_type=staff&period=<?php echo $period; ?>&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" 
           class="report-tab <?php echo $reportType === 'staff' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i> Staff
        </a>
        <a href="?report_type=feedback&period=<?php echo $period; ?>&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" 
           class="report-tab <?php echo $reportType === 'feedback' ? 'active' : ''; ?>">
            <i class="fas fa-star"></i> Feedback
        </a>
    </div>

    <!-- Filters Panel -->
    <div class="filter-panel">
        <form method="GET" action="" style="display: contents;">
            <input type="hidden" name="report_type" value="<?php echo htmlspecialchars($reportType); ?>">
            <div class="filter-grid">
                <div class="filter-group">
                    <label>Period</label>
                    <select name="period" onchange="toggleCustomDates(this.value)">
                        <option value="today" <?php echo $period === 'today' ? 'selected' : ''; ?>>Today</option>
                        <option value="week" <?php echo $period === 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
                        <option value="month" <?php echo $period === 'month' ? 'selected' : ''; ?>>This Month</option>
                        <option value="quarter" <?php echo $period === 'quarter' ? 'selected' : ''; ?>>Last 3 Months</option>
                        <option value="year" <?php echo $period === 'year' ? 'selected' : ''; ?>>This Year</option>
                        <option value="custom" <?php echo $period === 'custom' ? 'selected' : ''; ?>>Custom Range</option>
                    </select>
                </div>
                
                <div class="filter-group custom-date" style="display: <?php echo $period === 'custom' ? 'block' : 'none'; ?>;">
                    <label>Start Date</label>
                    <input type="date" name="start_date" value="<?php echo $startDate; ?>">
                </div>
                <div class="filter-group custom-date" style="display: <?php echo $period === 'custom' ? 'block' : 'none'; ?>;">
                    <label>End Date</label>
                    <input type="date" name="end_date" value="<?php echo $endDate; ?>">
                </div>
                
                <?php if (in_array($reportType, ['occupancy', 'revenue', 'bookings', 'feedback', 'overview'])): ?>
                <div class="filter-group">
                    <label>Room Type</label>
                    <select name="room_type">
                        <option value="all">All Room Types</option>
                        <?php foreach ($roomTypes as $rt): ?>
                        <option value="<?php echo $rt['category_id']; ?>" <?php echo $roomTypeFilter == $rt['category_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($rt['category_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <?php if (in_array($reportType, ['revenue', 'bookings', 'overview'])): ?>
                <div class="filter-group">
                    <label>Booking Source</label>
                    <select name="booking_source">
                        <option value="all">All Sources</option>
                        <?php foreach ($bookingSources as $source): ?>
                        <option value="<?php echo $source; ?>" <?php echo $bookingSourceFilter === $source ? 'selected' : ''; ?>>
                            <?php echo ucfirst($source); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <?php if (in_array($reportType, ['events', 'overview'])): ?>
                <div class="filter-group">
                    <label>Event Space</label>
                    <select name="event_space">
                        <option value="all">All Spaces</option>
                        <?php foreach ($eventSpaces as $es): ?>
                        <option value="<?php echo $es['space_id']; ?>" <?php echo $eventSpaceFilter == $es['space_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($es['space_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <?php if (in_array($reportType, ['staff', 'overview'])): ?>
                <div class="filter-group">
                    <label>Staff Member</label>
                    <select name="staff_id">
                        <option value="all">All Staff</option>
                        <?php foreach ($staffMembers as $staff): ?>
                        <option value="<?php echo $staff['user_id']; ?>" <?php echo $staffFilter == $staff['user_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?> (<?php echo ucfirst($staff['role']); ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <div class="filter-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                </div>
            </div>
        </form>
        
        <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--gray-medium);">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <span style="color: #666; font-size: 14px;">
                    <i class="fas fa-calendar-alt"></i> 
                    <strong>Period:</strong> <?php echo date('M d, Y', strtotime($startDate)); ?> - <?php echo date('M d, Y', strtotime($endDate)); ?>
                    (<?php echo $daysInPeriod; ?> days)
                </span>
                <div class="export-buttons">
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" class="export-btn csv">
                        <i class="fas fa-file-csv"></i> CSV
                    </a>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'excel'])); ?>" class="export-btn excel">
                        <i class="fas fa-file-excel"></i> Excel
                    </a>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'pdf'])); ?>" class="export-btn pdf">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
    function toggleCustomDates(value) {
        const customDates = document.querySelectorAll('.custom-date');
        customDates.forEach(el => el.style.display = value === 'custom' ? 'block' : 'none');
    }
    </script>

    <!-- ==================== REPORT CONTENT SECTIONS ==================== -->
    <?php if ($reportType === 'overview'): ?>
    <!-- OVERVIEW DASHBOARD -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-icon blue"><i class="fas fa-bed"></i></div>
            <div class="kpi-value"><?php echo $reportData['occupancy']['current_occupancy_rate']; ?>%</div>
            <div class="kpi-label">Current Occupancy</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon green"><i class="fas fa-peso-sign"></i></div>
            <div class="kpi-value">₱<?php echo number_format($reportData['revenue']['total_revenue'] ?? 0, 0); ?></div>
            <div class="kpi-label">Total Revenue</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon orange"><i class="fas fa-calendar-check"></i></div>
            <div class="kpi-value"><?php echo $reportData['bookings']['total_bookings']; ?></div>
            <div class="kpi-label">Total Bookings</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon purple"><i class="fas fa-star"></i></div>
            <div class="kpi-value"><?php echo $reportData['feedback']['overall_rating']; ?>/5</div>
            <div class="kpi-label">Avg Rating</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon yellow"><i class="fas fa-users"></i></div>
            <div class="kpi-value"><?php echo $reportData['events']['total_events']; ?></div>
            <div class="kpi-label">Events</div>
        </div>
    </div>
    <div class="two-column">
        <div class="report-section">
            <div class="report-section-header">
                <div class="report-section-title"><i class="fas fa-money-bill-wave"></i> Revenue by Room Type</div>
            </div>
            <div class="report-section-content">
                <table class="data-table">
                    <thead><tr><th>Room Type</th><th>Revenue</th><th>Bookings</th></tr></thead>
                    <tbody>
                    <?php foreach ($reportData['revenue']['revenue_by_room_type'] ?? [] as $row): ?>
                    <tr><td><?php echo htmlspecialchars($row['category_name']); ?></td><td>₱<?php echo number_format($row['revenue'] ?? 0, 2); ?></td><td><?php echo $row['transactions']; ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="report-section">
            <div class="report-section-header">
                <div class="report-section-title"><i class="fas fa-calendar-check"></i> Booking Sources</div>
            </div>
            <div class="report-section-content">
                <table class="data-table">
                    <thead><tr><th>Source</th><th>Count</th><th>%</th></tr></thead>
                    <tbody>
                    <?php foreach ($reportData['bookings']['bookings_by_source'] ?? [] as $row): ?>
                    <tr><td><?php echo ucfirst($row['booking_source']); ?></td><td><?php echo $row['count']; ?></td><td><?php echo $row['percentage']; ?>%</td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php elseif ($reportType === 'occupancy'): ?>
    <!-- OCCUPANCY REPORT -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-icon blue"><i class="fas fa-door-open"></i></div>
            <div class="kpi-value"><?php echo $reportData['total_rooms']; ?></div>
            <div class="kpi-label">Total Rooms</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon green"><i class="fas fa-bed"></i></div>
            <div class="kpi-value"><?php echo $reportData['occupied']; ?></div>
            <div class="kpi-label">Occupied</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon orange"><i class="fas fa-check-circle"></i></div>
            <div class="kpi-value"><?php echo $reportData['available']; ?></div>
            <div class="kpi-label">Available</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon purple"><i class="fas fa-percentage"></i></div>
            <div class="kpi-value"><?php echo $reportData['current_occupancy_rate']; ?>%</div>
            <div class="kpi-label">Current Occupancy</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon yellow"><i class="fas fa-chart-line"></i></div>
            <div class="kpi-value"><?php echo $reportData['period_occupancy_rate']; ?>%</div>
            <div class="kpi-label">Period Occupancy</div>
        </div>
    </div>
    <div class="report-section">
        <div class="report-section-header">
            <div class="report-section-title"><i class="fas fa-layer-group"></i> Room Type Occupancy</div>
        </div>
        <div class="report-section-content">
            <table class="data-table">
                <thead><tr><th>Room Type</th><th>Total</th><th>Occupied</th><th>Available</th><th>Maintenance</th><th>Occupancy %</th></tr></thead>
                <tbody>
                <?php foreach ($reportData['room_type_occupancy'] as $row): 
                    $rate = $row['total_rooms'] > 0 ? round(($row['occupied'] / $row['total_rooms']) * 100, 1) : 0;
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                    <td><?php echo $row['total_rooms']; ?></td>
                    <td><?php echo $row['occupied']; ?></td>
                    <td><?php echo $row['available']; ?></td>
                    <td><?php echo $row['maintenance']; ?></td>
                    <td><div class="progress-bar"><div class="progress-fill" style="width: <?php echo $rate; ?>%"></div></div> <?php echo $rate; ?>%</td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="two-column">
        <div class="report-section">
            <div class="report-section-header"><div class="report-section-title"><i class="fas fa-sign-in-alt"></i> Daily Check-ins</div></div>
            <div class="report-section-content">
                <table class="data-table">
                    <thead><tr><th>Date</th><th>Check-ins</th></tr></thead>
                    <tbody>
                    <?php foreach ($reportData['daily_checkins'] as $row): ?>
                    <tr><td><?php echo date('M d, Y', strtotime($row['date'])); ?></td><td><?php echo $row['check_ins']; ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="report-section">
            <div class="report-section-header"><div class="report-section-title"><i class="fas fa-sign-out-alt"></i> Daily Check-outs</div></div>
            <div class="report-section-content">
                <table class="data-table">
                    <thead><tr><th>Date</th><th>Check-outs</th></tr></thead>
                    <tbody>
                    <?php foreach ($reportData['daily_checkouts'] as $row): ?>
                    <tr><td><?php echo date('M d, Y', strtotime($row['date'])); ?></td><td><?php echo $row['check_outs']; ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php elseif ($reportType === 'revenue'): ?>
    <!-- REVENUE REPORT -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-icon green"><i class="fas fa-money-bill-wave"></i></div>
            <div class="kpi-value">₱<?php echo number_format($reportData['total_revenue'] ?? 0, 0); ?></div>
            <div class="kpi-label">Total Revenue</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon orange"><i class="fas fa-receipt"></i></div>
            <div class="kpi-value">₱<?php echo number_format($reportData['taxes_collected'] ?? 0, 0); ?></div>
            <div class="kpi-label">Taxes (12%)</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon purple"><i class="fas fa-exclamation-circle"></i></div>
            <div class="kpi-value">₱<?php echo number_format($reportData['total_outstanding'] ?? 0, 0); ?></div>
            <div class="kpi-label">Outstanding</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon blue"><i class="fas fa-calculator"></i></div>
            <div class="kpi-value">₱<?php echo number_format($reportData['adr'] ?? 0, 0); ?></div>
            <div class="kpi-label">ADR</div>
        </div>
    </div>
    <div class="two-column">
        <div class="report-section">
            <div class="report-section-header"><div class="report-section-title"><i class="fas fa-bed"></i> Revenue by Room Type</div></div>
            <div class="report-section-content">
                <table class="data-table">
                    <thead><tr><th>Room Type</th><th>Revenue</th><th>Transactions</th></tr></thead>
                    <tbody>
                    <?php foreach ($reportData['revenue_by_room_type'] as $row): ?>
                    <tr><td><?php echo htmlspecialchars($row['category_name']); ?></td><td>₱<?php echo number_format($row['revenue'] ?? 0, 2); ?></td><td><?php echo $row['transactions']; ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="report-section">
            <div class="report-section-header"><div class="report-section-title"><i class="fas fa-globe"></i> Revenue by Source</div></div>
            <div class="report-section-content">
                <table class="data-table">
                    <thead><tr><th>Source</th><th>Revenue</th><th>Bookings</th></tr></thead>
                    <tbody>
                    <?php foreach ($reportData['revenue_by_source'] as $row): ?>
                    <tr><td><?php echo ucfirst($row['booking_source']); ?></td><td>₱<?php echo number_format($row['revenue'] ?? 0, 2); ?></td><td><?php echo $row['bookings']; ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="report-section">
        <div class="report-section-header"><div class="report-section-title"><i class="fas fa-exclamation-triangle"></i> Outstanding Payments</div></div>
        <div class="report-section-content">
            <table class="data-table">
                <thead><tr><th>Guest</th><th>Room</th><th>Total</th><th>Paid</th><th>Balance</th></tr></thead>
                <tbody>
                <?php foreach ($reportData['outstanding_payments'] as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                    <td>₱<?php echo number_format($row['total_amount'] ?? 0, 2); ?></td>
                    <td>₱<?php echo number_format($row['paid_amount'] ?? 0, 2); ?></td>
                    <td><span class="badge badge-warning">₱<?php echo number_format($row['balance'] ?? 0, 2); ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php elseif ($reportType === 'bookings'): ?>
    <!-- BOOKINGS REPORT -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-icon blue"><i class="fas fa-calendar-check"></i></div>
            <div class="kpi-value"><?php echo $reportData['total_bookings']; ?></div>
            <div class="kpi-label">Total Bookings</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon green"><i class="fas fa-user-plus"></i></div>
            <div class="kpi-value"><?php echo $reportData['new_customers']; ?></div>
            <div class="kpi-label">New Customers</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon purple"><i class="fas fa-redo"></i></div>
            <div class="kpi-value"><?php echo $reportData['returning_customers']; ?></div>
            <div class="kpi-label">Returning</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon yellow"><i class="fas fa-crown"></i></div>
            <div class="kpi-value"><?php echo $reportData['vip_bookings']; ?></div>
            <div class="kpi-label">VIP Bookings</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon orange"><i class="fas fa-times-circle"></i></div>
            <div class="kpi-value"><?php echo $reportData['cancellation_rate']; ?>%</div>
            <div class="kpi-label">Cancel Rate</div>
        </div>
    </div>
    <div class="two-column">
        <div class="report-section">
            <div class="report-section-header"><div class="report-section-title"><i class="fas fa-chart-pie"></i> Bookings by Status</div></div>
            <div class="report-section-content">
                <table class="data-table">
                    <thead><tr><th>Status</th><th>Count</th><th>Percentage</th></tr></thead>
                    <tbody>
                    <?php foreach ($reportData['bookings_by_status'] as $row): ?>
                    <tr>
                        <td><?php echo ucfirst($row['status']); ?></td>
                        <td><?php echo $row['count']; ?></td>
                        <td><div class="progress-bar"><div class="progress-fill" style="width: <?php echo $row['percentage']; ?>%"></div></div> <?php echo $row['percentage']; ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="report-section">
            <div class="report-section-header"><div class="report-section-title"><i class="fas fa-globe"></i> Bookings by Source</div></div>
            <div class="report-section-content">
                <table class="data-table">
                    <thead><tr><th>Source</th><th>Count</th><th>Percentage</th></tr></thead>
                    <tbody>
                    <?php foreach ($reportData['bookings_by_source'] as $row): ?>
                    <tr>
                        <td><?php echo ucfirst($row['booking_source']); ?></td>
                        <td><?php echo $row['count']; ?></td>
                        <td><?php echo $row['percentage']; ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="report-section">
        <div class="report-section-header"><div class="report-section-title"><i class="fas fa-chart-line"></i> Daily Booking Trend</div></div>
        <div class="report-section-content">
            <table class="data-table">
                <thead><tr><th>Date</th><th>Bookings</th><th>Cancellations</th></tr></thead>
                <tbody>
                <?php foreach ($reportData['daily_bookings'] as $row): ?>
                <tr>
                    <td><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                    <td><?php echo $row['bookings']; ?></td>
                    <td><?php echo $row['cancellations'] ? '<span class="badge badge-danger">' . $row['cancellations'] . '</span>' : '-'; ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php elseif ($reportType === 'events'): ?>
    <!-- EVENTS REPORT -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-icon blue"><i class="fas fa-building"></i></div>
            <div class="kpi-value"><?php echo $reportData['total_events']; ?></div>
            <div class="kpi-label">Total Events</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon green"><i class="fas fa-money-bill-wave"></i></div>
            <div class="kpi-value">₱<?php echo number_format($reportData['event_revenue'] ?? 0, 0); ?></div>
            <div class="kpi-label">Event Revenue</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon purple"><i class="fas fa-users"></i></div>
            <div class="kpi-value"><?php echo $reportData['avg_guests_per_event']; ?></div>
            <div class="kpi-label">Avg Guests/Event</div>
        </div>
    </div>
    <div class="two-column">
        <div class="report-section">
            <div class="report-section-header"><div class="report-section-title"><i class="fas fa-chart-pie"></i> Events by Status</div></div>
            <div class="report-section-content">
                <table class="data-table">
                    <thead><tr><th>Status</th><th>Count</th><th>Percentage</th></tr></thead>
                    <tbody>
                    <?php foreach ($reportData['events_by_status'] as $row): ?>
                    <tr><td><?php echo ucfirst($row['status']); ?></td><td><?php echo $row['count']; ?></td><td><?php echo $row['percentage']; ?>%</td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="report-section">
            <div class="report-section-header"><div class="report-section-title"><i class="fas fa-door-open"></i> Events by Space</div></div>
            <div class="report-section-content">
                <table class="data-table">
                    <thead><tr><th>Space</th><th>Bookings</th><th>Revenue</th></tr></thead>
                    <tbody>
                    <?php foreach ($reportData['events_by_space'] as $row): ?>
                    <tr><td><?php echo htmlspecialchars($row['space_name']); ?></td><td><?php echo $row['bookings']; ?></td><td>₱<?php echo number_format($row['revenue'] ?? 0, 2); ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php elseif ($reportType === 'inventory'): ?>
    <!-- INVENTORY REPORT -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-icon blue"><i class="fas fa-boxes"></i></div>
            <div class="kpi-value"><?php echo $reportData['overview']['total_items']; ?></div>
            <div class="kpi-label">Total Items</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon green"><i class="fas fa-peso-sign"></i></div>
            <div class="kpi-value">₱<?php echo number_format($reportData['overview']['total_value'] ?? 0, 0); ?></div>
            <div class="kpi-label">Inventory Value</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon orange"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="kpi-value"><?php echo $reportData['overview']['low_stock']; ?></div>
            <div class="kpi-label">Low Stock Items</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon purple"><i class="fas fa-tools"></i></div>
            <div class="kpi-value"><?php echo $reportData['maintenance']['total_requests']; ?></div>
            <div class="kpi-label">Maintenance</div>
        </div>
    </div>
    <div class="two-column">
        <div class="report-section">
            <div class="report-section-header"><div class="report-section-title"><i class="fas fa-folder"></i> Inventory by Category</div></div>
            <div class="report-section-content">
                <table class="data-table">
                    <thead><tr><th>Category</th><th>Items</th><th>Value</th><th>Low Stock</th></tr></thead>
                    <tbody>
                    <?php foreach ($reportData['by_category'] as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                        <td><?php echo $row['item_count']; ?></td>
                        <td>₱<?php echo number_format($row['category_value'] ?? 0, 2); ?></td>
                        <td><?php echo $row['low_stock_items'] ? '<span class="badge badge-warning">' . $row['low_stock_items'] . '</span>' : '-'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="report-section">
            <div class="report-section-header"><div class="report-section-title"><i class="fas fa-exclamation-circle"></i> Low Stock Alert</div></div>
            <div class="report-section-content">
                <table class="data-table">
                    <thead><tr><th>Item</th><th>Category</th><th>Qty</th><th>Reorder</th></tr></thead>
                    <tbody>
                    <?php foreach ($reportData['low_stock_items'] as $row): ?>
                    <tr><td><?php echo htmlspecialchars($row['item_name']); ?></td><td><?php echo htmlspecialchars($row['category_name']); ?></td><td><span class="badge badge-danger"><?php echo $row['quantity']; ?></span></td><td><?php echo $row['reorder_level']; ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="report-section">
        <div class="report-section-header"><div class="report-section-title"><i class="fas fa-wrench"></i> Maintenance by Type</div></div>
        <div class="report-section-content">
            <table class="data-table">
                <thead><tr><th>Issue Type</th><th>Count</th></tr></thead>
                <tbody>
                <?php foreach ($reportData['maintenance_by_type'] as $row): ?>
                <tr><td><?php echo ucfirst($row['issue_type']); ?></td><td><?php echo $row['count']; ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php elseif ($reportType === 'staff'): ?>
    <!-- STAFF REPORT -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-icon blue"><i class="fas fa-users"></i></div>
            <div class="kpi-value"><?php echo $reportData['total_staff']; ?></div>
            <div class="kpi-label">Total Staff</div>
        </div>
    </div>
    <div class="two-column">
        <div class="report-section">
            <div class="report-section-header"><div class="report-section-title"><i class="fas fa-user-tie"></i> Staff by Role</div></div>
            <div class="report-section-content">
                <table class="data-table">
                    <thead><tr><th>Role</th><th>Count</th></tr></thead>
                    <tbody>
                    <?php foreach ($reportData['staff_by_role'] as $row): ?>
                    <tr><td><?php echo ucfirst($row['role']); ?></td><td><?php echo $row['count']; ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="report-section">
            <div class="report-section-header"><div class="report-section-title"><i class="fas fa-calendar-alt"></i> Attendance</div></div>
            <div class="report-section-content">
                <table class="data-table">
                    <thead><tr><th>Staff</th><th>Shifts</th><th>Completed</th><th>Absences</th></tr></thead>
                    <tbody>
                    <?php foreach ($reportData['attendance'] as $row): ?>
                    <tr><td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td><td><?php echo $row['shifts_worked']; ?></td><td><?php echo $row['completed_shifts']; ?></td><td><?php echo $row['absences'] ? '<span class="badge badge-danger">' . $row['absences'] . '</span>' : '-'; ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php elseif ($reportType === 'feedback'): ?>
    <!-- FEEDBACK REPORT -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-icon yellow"><i class="fas fa-star"></i></div>
            <div class="kpi-value"><?php echo $reportData['overall_rating']; ?>/5</div>
            <div class="kpi-label">Avg Rating</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon blue"><i class="fas fa-comments"></i></div>
            <div class="kpi-value"><?php echo $reportData['total_reviews']; ?></div>
            <div class="kpi-label">Total Reviews</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon green"><i class="fas fa-thumbs-up"></i></div>
            <div class="kpi-value"><?php echo $reportData['nps_score']; ?></div>
            <div class="kpi-label">NPS Score</div>
        </div>
    </div>
    <div class="two-column">
        <div class="report-section">
            <div class="report-section-header"><div class="report-section-title"><i class="fas fa-chart-bar"></i> Rating Distribution</div></div>
            <div class="report-section-content">
                <table class="data-table">
                    <thead><tr><th>Rating</th><th>Count</th></tr></thead>
                    <tbody>
                    <?php foreach ($reportData['rating_distribution'] as $row): ?>
                    <tr><td><?php echo $row['rating']; ?> Star<?php echo $row['rating'] > 1 ? 's' : ''; ?></td><td><div class="progress-bar"><div class="progress-fill" style="width: <?php echo ($row['count'] / max(1, $reportData['total_reviews'])) * 100; ?>%"></div></div> <?php echo $row['count']; ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="report-section">
            <div class="report-section-header"><div class="report-section-title"><i class="fas fa-list"></i> Reviews by Category</div></div>
            <div class="report-section-content">
                <table class="data-table">
                    <thead><tr><th>Category</th><th>Avg Rating</th><th>Reviews</th></tr></thead>
                    <tbody>
                    <?php foreach ($reportData['reviews_by_category'] as $row): ?>
                    <tr><td><?php echo ucfirst($row['category']); ?></td><td><?php echo round($row['avg_rating'], 1); ?></td><td><?php echo $row['review_count']; ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="report-section">
        <div class="report-section-header"><div class="report-section-title"><i class="fas fa-bed"></i> Reviews by Room Type</div></div>
        <div class="report-section-content">
            <table class="data-table">
                <thead><tr><th>Room Type</th><th>Avg Rating</th><th>Review Count</th></tr></thead>
                <tbody>
                <?php foreach ($reportData['reviews_by_room_type'] as $row): ?>
                <tr><td><?php echo htmlspecialchars($row['category_name']); ?></td><td><?php echo round($row['avg_rating'], 1); ?></td><td><?php echo $row['review_count']; ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php
require_once __DIR__ . '/../includes/admin-footer.php';
?>
