<?php
/**
 * Admin Ratings Management Page - Bayawan Bai Hotel
 * View and manage all user ratings
 */

require_once __DIR__ . '/../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    showAlert('Access denied. Admin privileges required.', 'danger');
    redirect('../index.php');
}

$pageTitle = 'Ratings Management';

$db = getDB();

// Handle delete action
if (isset($_POST['delete_rating']) && isset($_POST['rating_id'])) {
    $ratingId = intval($_POST['rating_id']);
    $stmt = $db->prepare("DELETE FROM ratings WHERE rating_id = ?");
    $stmt->execute([$ratingId]);
    $_SESSION['success'] = 'Rating deleted successfully';
    redirect('admin-ratings.php');
}

// Get filter parameters
$filterService = $_GET['service'] ?? '';
$filterRating = $_GET['rating'] ?? '';
$filterDateFrom = $_GET['date_from'] ?? '';
$filterDateTo = $_GET['date_to'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;

// Build query
$sql = "
    SELECT 
        r.rating_id,
        r.service_type,
        r.rating_value,
        r.comment,
        r.created_at,
        u.first_name,
        u.last_name,
        u.email,
        -- Room booking details
        rc.category_name as room_name,
        b.booking_id,
        -- Event booking details
        es.space_name as event_space,
        eb.event_booking_id,
        eb.event_type,
        -- Food order details
        mi.item_name as food_name,
        fo.order_id as food_order_id
    FROM ratings r
    JOIN users u ON r.user_id = u.user_id
    LEFT JOIN bookings b ON r.booking_id = b.booking_id
    LEFT JOIN room_categories rc ON b.category_id = rc.category_id
    LEFT JOIN event_bookings eb ON r.event_booking_id = eb.event_booking_id
    LEFT JOIN event_spaces es ON eb.space_id = es.space_id
    LEFT JOIN food_orders fo ON r.food_order_id = fo.order_id
    LEFT JOIN menu_items mi ON fo.food_id = mi.item_id
    WHERE 1=1
";

$params = [];

if ($filterService) {
    $sql .= " AND r.service_type = ?";
    $params[] = $filterService;
}

if ($filterRating) {
    $sql .= " AND r.rating_value = ?";
    $params[] = $filterRating;
}

if ($filterDateFrom) {
    $sql .= " AND r.created_at >= ?";
    $params[] = $filterDateFrom . ' 00:00:00';
}

if ($filterDateTo) {
    $sql .= " AND r.created_at <= ?";
    $params[] = $filterDateTo . ' 23:59:59';
}

if ($search) {
    $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR r.comment LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$sql .= " ORDER BY r.created_at DESC";

// Get total count for pagination
$countSql = str_replace("SELECT \n        r.rating_id,", "SELECT COUNT(*) as total FROM ratings r", $sql);
$countSql = preg_replace('/JOIN users u.*?WHERE/s', 'WHERE', $countSql, 1);
$countSql = preg_replace('/LEFT JOIN bookings b.*?LEFT JOIN food_orders fo ON/s', 'LEFT JOIN users u ON r.user_id = u.user_id WHERE', $countSql, 1);
$countSql = preg_replace('/LEFT JOIN menu_items mi.*?\)/s', ')', $countSql, 1);

// Simpler count query
$countSql = "
    SELECT COUNT(*) as total 
    FROM ratings r 
    JOIN users u ON r.user_id = u.user_id 
    WHERE 1=1
";

if ($filterService) {
    $countSql .= " AND r.service_type = ?";
}
if ($filterRating) {
    $countSql .= " AND r.rating_value = ?";
}
if ($filterDateFrom) {
    $countSql .= " AND r.created_at >= ?";
}
if ($filterDateTo) {
    $countSql .= " AND r.created_at <= ?";
}
if ($search) {
    $countSql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR r.comment LIKE ?)";
}

$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalRatings = $countStmt->fetchColumn();
$totalPages = ceil($totalRatings / $perPage);

// Add pagination
$offset = ($page - 1) * $perPage;
$sql .= " LIMIT $perPage OFFSET $offset";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$ratings = $stmt->fetchAll();

// Get statistics
$statsSql = "
    SELECT 
        COUNT(*) as total_count,
        AVG(rating_value) as avg_rating,
        SUM(CASE WHEN service_type = 'room' THEN 1 ELSE 0 END) as room_count,
        SUM(CASE WHEN service_type = 'event' THEN 1 ELSE 0 END) as event_count,
        SUM(CASE WHEN service_type = 'food' THEN 1 ELSE 0 END) as food_count,
        AVG(CASE WHEN service_type = 'room' THEN rating_value END) as room_avg,
        AVG(CASE WHEN service_type = 'event' THEN rating_value END) as event_avg,
        AVG(CASE WHEN service_type = 'food' THEN rating_value END) as food_avg
    FROM ratings
";
$stats = $db->query($statsSql)->fetch();

// Rating distribution
$distributionSql = "
    SELECT rating_value, COUNT(*) as count
    FROM ratings
    GROUP BY rating_value
    ORDER BY rating_value DESC
";
$distribution = $db->query($distributionSql)->fetchAll(PDO::FETCH_KEY_PAIR);

// Calculate percentages
$ratingPercentages = [];
for ($i = 5; $i >= 1; $i--) {
    $count = $distribution[$i] ?? 0;
    $percentage = $stats['total_count'] > 0 ? round(($count / $stats['total_count']) * 100, 1) : 0;
    $ratingPercentages[$i] = [
        'count' => $count,
        'percentage' => $percentage
    ];
}

// Find highest and lowest rated services
$highestRated = null;
$lowestRated = null;

foreach (['room', 'event', 'food'] as $service) {
    $avg = $stats["{$service}_avg"] ?? 0;
    if ($avg > 0) {
        if (!$highestRated || $avg > $highestRated['avg']) {
            $highestRated = ['service' => $service, 'avg' => $avg];
        }
        if (!$lowestRated || $avg < $lowestRated['avg']) {
            $lowestRated = ['service' => $service, 'avg' => $avg];
        }
    }
}

require_once __DIR__ . '/../includes/admin-header.php';

$serviceLabels = [
    'room' => 'Room Booking',
    'event' => 'Event Booking',
    'food' => 'Food Order'
];

$serviceIcons = [
    'room' => 'fa-bed',
    'event' => 'fa-calendar-alt',
    'food' => 'fa-utensils'
];

function renderStars($rating) {
    $output = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $output .= '<i class="fas fa-star"></i>';
        } elseif ($i - 0.5 <= $rating) {
            $output .= '<i class="fas fa-star-half-alt"></i>';
        } else {
            $output .= '<i class="far fa-star"></i>';
        }
    }
    return $output;
}
?>

<style>
.ratings-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.stats-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    text-align: center;
    transition: transform 0.2s;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px;
    font-size: 24px;
}

.stat-icon.blue { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
.stat-icon.green { background: linear-gradient(135deg, #11998e, #38ef7d); color: white; }
.stat-icon.orange { background: linear-gradient(135deg, #f093fb, #f5576c); color: white; }
.stat-icon.purple { background: linear-gradient(135deg, #4facfe, #00f2fe); color: white; }
.stat-icon.yellow { background: linear-gradient(135deg, #fa709a, #fee140); color: white; }

.stat-value {
    font-size: 32px;
    font-weight: 700;
    color: var(--dark-color);
    margin-bottom: 5px;
}

.stat-label {
    font-size: 14px;
    color: #666;
}

.rating-breakdown {
    background: white;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.rating-breakdown h3 {
    margin: 0 0 20px 0;
    font-size: 18px;
    color: var(--dark-color);
}

.rating-bar {
    display: flex;
    align-items: center;
    margin-bottom: 12px;
    gap: 10px;
}

.rating-label {
    width: 60px;
    font-size: 14px;
    font-weight: 600;
    color: #666;
}

.rating-progress {
    flex: 1;
    height: 8px;
    background: #e9ecef;
    border-radius: 4px;
    overflow: hidden;
}

.rating-fill {
    height: 100%;
    border-radius: 4px;
    transition: width 0.3s;
}

.rating-fill.star-5 { background: #28a745; }
.rating-fill.star-4 { background: #6f42c1; }
.rating-fill.star-3 { background: #ffc107; }
.rating-fill.star-2 { background: #fd7e14; }
.rating-fill.star-1 { background: #dc3545; }

.rating-count {
    width: 50px;
    text-align: right;
    font-size: 14px;
    color: #666;
}

.rating-percentage {
    width: 50px;
    text-align: right;
    font-size: 14px;
    font-weight: 600;
    color: var(--dark-color);
}

.filters-section {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.filters-form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    align-items: end;
}

.filters-form label {
    display: block;
    font-size: 13px;
    color: #666;
    margin-bottom: 5px;
}

.filters-form input,
.filters-form select {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 14px;
}

.filters-form button {
    padding: 10px 20px;
    background: var(--primary-color);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
}

.filters-form a {
    padding: 10px 20px;
    background: #f5f5f5;
    color: #666;
    border-radius: 8px;
    text-decoration: none;
    font-size: 14px;
    text-align: center;
}

.ratings-table-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    overflow: hidden;
}

.ratings-table {
    width: 100%;
    border-collapse: collapse;
}

.ratings-table th {
    background: #f8f9fa;
    padding: 15px;
    text-align: left;
    font-size: 13px;
    font-weight: 600;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid #e9ecef;
}

.ratings-table td {
    padding: 15px;
    border-bottom: 1px solid #e9ecef;
    font-size: 14px;
}

.ratings-table tr:hover {
    background: #f8f9fa;
}

.ratings-table tr.low-rating {
    background: #fff5f5;
}

.ratings-table tr.low-rating:hover {
    background: #ffe0e0;
}

.user-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 16px;
}

.user-info h4 {
    margin: 0;
    font-size: 14px;
    color: var(--dark-color);
}

.user-info p {
    margin: 2px 0 0 0;
    font-size: 12px;
    color: #666;
}

.service-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
}

.service-badge.room { background: #e3f2fd; color: #1976d2; }
.service-badge.event { background: #f3e5f5; color: #7b1fa2; }
.service-badge.food { background: #e8f5e9; color: #388e3c; }

.rating-stars {
    color: #ffc107;
    font-size: 14px;
    letter-spacing: 2px;
}

.rating-value {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 15px;
    font-size: 12px;
    font-weight: 600;
    margin-left: 8px;
}

.rating-value.excellent { background: #d4edda; color: #155724; }
.rating-value.good { background: #cce5ff; color: #004085; }
.rating-value.average { background: #fff3cd; color: #856404; }
.rating-value.poor { background: #f8d7da; color: #721c24; }

.comment-cell {
    max-width: 250px;
}

.comment-cell .comment-text {
    color: #555;
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.comment-cell .no-comment {
    color: #999;
    font-style: italic;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 8px;
    padding: 20px;
    border-top: 1px solid #e9ecef;
}

.pagination a,
.pagination span {
    padding: 8px 14px;
    border-radius: 6px;
    font-size: 14px;
    text-decoration: none;
}

.pagination a {
    background: #f5f5f5;
    color: #666;
}

.pagination a:hover {
    background: var(--primary-color);
    color: white;
}

.pagination span {
    background: var(--primary-color);
    color: white;
}

.pagination .disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.delete-btn {
    background: #dc3545;
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 5px;
    font-size: 12px;
    cursor: pointer;
    transition: background 0.2s;
}

.delete-btn:hover {
    background: #c82333;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.empty-state i {
    font-size: 64px;
    color: #ddd;
    margin-bottom: 20px;
}

.empty-state h3 {
    margin: 0 0 10px 0;
    color: var(--dark-color);
}

.highlight-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 15px;
    font-size: 11px;
    font-weight: 600;
    margin-left: 10px;
}

.highlight-badge.highest { background: #d4edda; color: #155724; }
.highlight-badge.lowest { background: #f8d7da; color: #721c24; }

@media (max-width: 768px) {
    .stats-cards {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .filters-form {
        grid-template-columns: 1fr;
    }
    
    .ratings-table-container {
        overflow-x: auto;
    }
    
    .ratings-table {
        min-width: 900px;
    }
}
</style>

<div class="container">
    <div class="ratings-header">
        <h1><i class="fas fa-star" style="color: #ffc107; margin-right: 10px;"></i>Ratings & Feedback Management</h1>
        <a href="admin-analytics.php" class="btn btn-outline">View Analytics</a>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-cards">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="fas fa-star"></i>
            </div>
            <div class="stat-value"><?php echo number_format($stats['avg_rating'] ?? 0, 1); ?></div>
            <div class="stat-label">Average Rating</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="fas fa-comment"></i>
            </div>
            <div class="stat-value"><?php echo number_format($stats['total_count'] ?? 0); ?></div>
            <div class="stat-label">Total Ratings</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon room-bg" style="background: linear-gradient(135deg, #e3f2fd, #bbdefb); color: #1976d2;">
                <i class="fas fa-bed"></i>
            </div>
            <div class="stat-value"><?php echo number_format($stats['room_count'] ?? 0); ?></div>
            <div class="stat-label">Room Ratings</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon event-bg" style="background: linear-gradient(135deg, #f3e5f5, #e1bee7); color: #7b1fa2;">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="stat-value"><?php echo number_format($stats['event_count'] ?? 0); ?></div>
            <div class="stat-label">Event Ratings</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon food-bg" style="background: linear-gradient(135deg, #e8f5e9, #c8e6c9); color: #388e3c;">
                <i class="fas fa-utensils"></i>
            </div>
            <div class="stat-value"><?php echo number_format($stats['food_count'] ?? 0); ?></div>
            <div class="stat-label">Food Ratings</div>
        </div>
    </div>

    <!-- Rating Breakdown -->
    <div class="rating-breakdown">
        <h3><i class="fas fa-chart-bar" style="color: var(--primary-color); margin-right: 8px;"></i>Rating Distribution</h3>
        <?php for ($i = 5; $i >= 1; $i--): 
            $data = $ratingPercentages[$i];
        ?>
        <div class="rating-bar">
            <div class="rating-label"><?php echo $i; ?> star</div>
            <div class="rating-progress">
                <div class="rating-fill star-<?php echo $i; ?>" style="width: <?php echo $data['percentage']; ?>%"></div>
            </div>
            <div class="rating-count"><?php echo $data['count']; ?></div>
            <div class="rating-percentage"><?php echo $data['percentage']; ?>%</div>
        </div>
        <?php endfor; ?>
    </div>

    <!-- Filters -->
    <div class="filters-section">
        <form method="GET" action="" class="filters-form">
            <div>
                <label>Service Type</label>
                <select name="service">
                    <option value="">All Services</option>
                    <option value="room" <?php echo $filterService === 'room' ? 'selected' : ''; ?>>Room Booking</option>
                    <option value="event" <?php echo $filterService === 'event' ? 'selected' : ''; ?>>Event Booking</option>
                    <option value="food" <?php echo $filterService === 'food' ? 'selected' : ''; ?>>Food Order</option>
                </select>
            </div>
            
            <div>
                <label>Rating</label>
                <select name="rating">
                    <option value="">All Ratings</option>
                    <option value="5" <?php echo $filterRating === '5' ? 'selected' : ''; ?>>5 Stars</option>
                    <option value="4" <?php echo $filterRating === '4' ? 'selected' : ''; ?>>4 Stars</option>
                    <option value="3" <?php echo $filterRating === '3' ? 'selected' : ''; ?>>3 Stars</option>
                    <option value="2" <?php echo $filterRating === '2' ? 'selected' : ''; ?>>2 Stars</option>
                    <option value="1" <?php echo $filterRating === '1' ? 'selected' : ''; ?>>1 Star</option>
                </select>
            </div>
            
            <div>
                <label>From Date</label>
                <input type="date" name="date_from" value="<?php echo $filterDateFrom; ?>">
            </div>
            
            <div>
                <label>To Date</label>
                <input type="date" name="date_to" value="<?php echo $filterDateTo; ?>">
            </div>
            
            <div>
                <label>Search</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="User name or comment...">
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="submit"><i class="fas fa-filter"></i> Filter</button>
                <a href="admin-ratings.php"><i class="fas fa-times"></i> Reset</a>
            </div>
        </form>
    </div>

    <!-- Ratings Table -->
    <div class="ratings-table-container">
        <?php if (count($ratings) > 0): ?>
        <table class="ratings-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Service</th>
                    <th>Item</th>
                    <th>Rating</th>
                    <th>Comment</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ratings as $rating): 
                    $isLowRating = $rating['rating_value'] <= 2;
                    $ratingClass = '';
                    if ($rating['rating_value'] == 5) $ratingClass = 'excellent';
                    elseif ($rating['rating_value'] >= 4) $ratingClass = 'good';
                    elseif ($rating['rating_value'] == 3) $ratingClass = 'average';
                    else $ratingClass = 'poor';
                ?>
                <tr class="<?php echo $isLowRating ? 'low-rating' : ''; ?>">
                    <td>
                        <div class="user-cell">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($rating['first_name'], 0, 1)); ?>
                            </div>
                            <div class="user-info">
                                <h4><?php echo htmlspecialchars($rating['first_name'] . ' ' . $rating['last_name']); ?></h4>
                                <p><?php echo htmlspecialchars($rating['email']); ?></p>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="service-badge <?php echo $rating['service_type']; ?>">
                            <i class="fas <?php echo $serviceIcons[$rating['service_type']]; ?>"></i>
                            <?php echo $serviceLabels[$rating['service_type']]; ?>
                        </span>
                    </td>
                    <td>
                        <?php
                        $itemName = '';
                        switch ($rating['service_type']) {
                            case 'room':
                                $itemName = $rating['room_name'] ?: 'N/A';
                                break;
                            case 'event':
                                $itemName = ($rating['event_space'] ?: 'N/A') . ($rating['event_type'] ? ' - ' . $rating['event_type'] : '');
                                break;
                            case 'food':
                                $itemName = $rating['food_name'] ?: 'N/A';
                                break;
                        }
                        echo htmlspecialchars($itemName);
                        ?>
                    </td>
                    <td>
                        <span class="rating-stars"><?php echo renderStars($rating['rating_value']); ?></span>
                        <span class="rating-value <?php echo $ratingClass; ?>">
                            <?php echo $rating['rating_value']; ?>/5
                        </span>
                    </td>
                    <td class="comment-cell">
                        <?php if ($rating['comment']): ?>
                            <div class="comment-text"><?php echo htmlspecialchars($rating['comment']); ?></div>
                        <?php else: ?>
                            <span class="no-comment">No comment</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo date('M d, Y', strtotime($rating['created_at'])); ?>
                        <br>
                        <small style="color: #999;"><?php echo date('h:i A', strtotime($rating['created_at'])); ?></small>
                    </td>
                    <td>
                        <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this rating?');">
                            <input type="hidden" name="rating_id" value="<?php echo $rating['rating_id']; ?>">
                            <button type="submit" name="delete_rating" class="delete-btn">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">&laquo; Prev</a>
            <?php else: ?>
                <span class="disabled">&laquo; Prev</span>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <?php if ($i == $page): ?>
                    <span><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Next &raquo;</a>
            <?php else: ?>
                <span class="disabled">Next &raquo;</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-star"></i>
            <h3>No ratings found</h3>
            <p>There are no ratings matching your filters. Try adjusting your search criteria.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
