<?php
$pageTitle = 'My Service Requests';
require_once '../includes/config.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

require_once '../includes/user-header.php';

$db = getDB();
$userId = getUserId();

// Handle cancel request
if (isset($_GET['cancel']) && is_numeric($_GET['cancel'])) {
    $requestId = intval($_GET['cancel']);

    // Verify the request belongs to the current user and is pending
    $stmt = $db->prepare("SELECT status FROM guest_service_requests WHERE request_id = ? AND user_id = ? AND is_deleted = 0");
    $stmt->execute([$requestId, $userId]);
    $request = $stmt->fetch();

    if ($request && $request['status'] === 'pending') {
        $stmt = $db->prepare("UPDATE guest_service_requests SET status = 'cancelled', payment_status = 'cancelled' WHERE request_id = ?");
        $stmt->execute([$requestId]);
        showAlert('Request #' . str_pad($requestId, 6, '0', STR_PAD_LEFT) . ' cancelled successfully', 'success');
    } else {
        showAlert('Unable to cancel this request', 'danger');
    }
    redirect('my-service-requests.php');
}

// Handle move to trash
if (isset($_GET['trash']) && is_numeric($_GET['trash'])) {
    $requestId = intval($_GET['trash']);

    // Verify the request belongs to the current user and is cancelled
    $stmt = $db->prepare("SELECT status FROM guest_service_requests WHERE request_id = ? AND user_id = ? AND is_deleted = 0");
    $stmt->execute([$requestId, $userId]);
    $request = $stmt->fetch();

    if ($request && in_array($request['status'], ['cancelled', 'declined'])) {
        $stmt = $db->prepare("UPDATE guest_service_requests SET is_deleted = 1, deleted_at = NOW() WHERE request_id = ?");
        $stmt->execute([$requestId]);
        showAlert('Request moved to trash', 'success');
    } else {
        showAlert('Unable to move this request to trash', 'danger');
    }
    redirect('my-service-requests.php');
}

// Get filter parameters
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';
$view = $_GET['view'] ?? 'active'; // 'active' or 'trash'

// Build query for user's service requests
$sql = "
    SELECT gsr.*, s.service_name, s.category, s.subcategory, s.duration_minutes,
           b.booking_ref, b.check_in, b.check_out, r.room_number as booking_room
    FROM guest_service_requests gsr
    JOIN additional_services s ON gsr.service_id = s.service_id
    LEFT JOIN bookings b ON gsr.booking_id = b.booking_id
    LEFT JOIN rooms r ON b.room_id = r.room_id
    WHERE gsr.user_id = ?
";
$params = [$userId];

// Apply view filter (active vs trash)
if ($view === 'trash') {
    $sql .= " AND gsr.is_deleted = 1";
} else {
    $sql .= " AND gsr.is_deleted = 0";
    // Apply status filter only for active view
    if ($filter === 'pending') {
        $sql .= " AND gsr.status IN ('pending', 'confirmed')";
    } elseif ($filter === 'in_progress') {
        $sql .= " AND gsr.status = 'in_progress'";
    } elseif ($filter === 'completed') {
        $sql .= " AND gsr.status = 'completed'";
    } elseif ($filter === 'cancelled') {
        $sql .= " AND gsr.status IN ('cancelled', 'declined')";
    }
}

// Apply search filter
if ($search) {
    $sql .= " AND (s.service_name LIKE ? OR gsr.request_ref LIKE ? OR gsr.room_number LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$sql .= " ORDER BY gsr.requested_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

// Status color mapping
$statusColors = [
    'pending' => ['#fff3cd', '#856404', 'clock', 'Pending'],
    'confirmed' => ['#cce5ff', '#004085', 'check', 'Confirmed'],
    'in_progress' => ['#d1ecf1', '#0c5460', 'spinner', 'In Progress'],
    'completed' => ['#d4edda', '#155724', 'check-circle', 'Completed'],
    'cancelled' => ['#f8d7da', '#721c24', 'times-circle', 'Cancelled'],
    'declined' => ['#f8d7da', '#721c24', 'times-circle', 'Declined']
];

$paymentStatusColors = [
    'pending' => ['#fff3cd', '#856404'],
    'added_to_bill' => ['#cce5ff', '#004085'],
    'paid' => ['#d4edda', '#155724'],
    'waived' => ['#f8d7da', '#721c24'],
    'cancelled' => ['#e9ecef', '#6c757d']
];

// Calculate stats (only for non-deleted items)
$activeRequests = array_filter($requests, function($r) { return $r['is_deleted'] == 0; });
$pendingCount = count(array_filter($activeRequests, function($r) { return in_array($r['status'], ['pending', 'confirmed']); }));
$inProgressCount = count(array_filter($activeRequests, function($r) { return $r['status'] === 'in_progress'; }));
$completedCount = count(array_filter($activeRequests, function($r) { return $r['status'] === 'completed'; }));
$cancelledCount = count(array_filter($activeRequests, function($r) { return in_array($r['status'], ['cancelled', 'declined']); }));
$totalSpent = array_sum(array_map(function($r) { return in_array($r['status'], ['completed', 'in_progress', 'confirmed']) ? $r['total_price'] : 0; }, $activeRequests));
?>

<style>
.request-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    transition: transform 0.3s;
}
.request-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 15px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    text-transform: capitalize;
}
.request-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 15px;
}
.request-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}
.detail-item {
    display: flex;
    flex-direction: column;
}
.detail-label {
    font-size: 12px;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 5px;
}
.detail-value {
    font-weight: 600;
    color: #333;
}
.price-tag {
    font-size: 20px;
    font-weight: 700;
    color: var(--primary-color);
}
.stats-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
.stats-value {
    font-size: 32px;
    font-weight: 700;
    color: var(--primary-color);
    margin-bottom: 5px;
}
.stats-label {
    color: #666;
    font-size: 14px;
}
.filter-tabs {
    display: inline-flex;
    background: white;
    padding: 5px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
.filter-tab {
    padding: 12px 25px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s;
    color: #666;
}
.filter-tab.active {
    background-color: var(--primary-color);
    color: white;
}
.empty-state {
    text-align: center;
    padding: 60px 20px;
}
.empty-state i {
    font-size: 80px;
    color: #ddd;
    margin-bottom: 25px;
}
</style>

<section>
    <div class="container">
        <!-- View Toggle -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div class="filter-tabs">
                <a href="?view=active" class="filter-tab <?php echo $view === 'active' ? 'active' : ''; ?>">
                    <i class="fas fa-inbox"></i> Active
                </a>
                <a href="?view=trash" class="filter-tab <?php echo $view === 'trash' ? 'active' : ''; ?>">
                    <i class="fas fa-trash"></i> Trash
                </a>
            </div>
        </div>

        <!-- Stats Row -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px;">
            <div class="stats-card">
                <div class="stats-value"><?php echo $view === 'trash' ? count($requests) : count($activeRequests); ?></div>
                <div class="stats-label"><?php echo $view === 'trash' ? 'In Trash' : 'Total Requests'; ?></div>
            </div>
            <?php if ($view === 'active'): ?>
            <div class="stats-card">
                <div class="stats-value" style="color: var(--warning-color);"><?php echo $pendingCount; ?></div>
                <div class="stats-label">Pending/Confirmed</div>
            </div>
            <div class="stats-card">
                <div class="stats-value" style="color: var(--info-color);"><?php echo $inProgressCount; ?></div>
                <div class="stats-label">In Progress</div>
            </div>
            <div class="stats-card">
                <div class="stats-value" style="color: var(--success-color);"><?php echo formatPrice($totalSpent); ?></div>
                <div class="stats-label">Total Spent</div>
            </div>
            <?php else: ?>
            <div class="stats-card">
                <div class="stats-value" style="color: var(--warning-color);">-</div>
                <div class="stats-label">Pending/Confirmed</div>
            </div>
            <div class="stats-card">
                <div class="stats-value" style="color: var(--info-color);">-</div>
                <div class="stats-label">In Progress</div>
            </div>
            <div class="stats-card">
                <div class="stats-value" style="color: var(--success-color);">-</div>
                <div class="stats-label">Total Spent</div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Filters & Search -->
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; margin-bottom: 30px;">
            <div class="filter-tabs">
                <a href="?view=<?php echo $view; ?>&filter=all" class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">All</a>
                <a href="?view=<?php echo $view; ?>&filter=pending" class="filter-tab <?php echo $filter === 'pending' ? 'active' : ''; ?>">Pending</a>
                <a href="?view=<?php echo $view; ?>&filter=in_progress" class="filter-tab <?php echo $filter === 'in_progress' ? 'active' : ''; ?>">In Progress</a>
                <a href="?view=<?php echo $view; ?>&filter=completed" class="filter-tab <?php echo $filter === 'completed' ? 'active' : ''; ?>">Completed</a>
                <?php if ($view === 'active'): ?>
                <a href="?view=<?php echo $view; ?>&filter=cancelled" class="filter-tab <?php echo $filter === 'cancelled' ? 'active' : ''; ?>">Cancelled</a>
                <?php endif; ?>
            </div>
            
            <form method="GET" action="" style="display: flex; gap: 10px;">
                <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
                <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search services..."
                       style="padding: 12px 20px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px; width: 250px;">
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if ($search): ?>
                <a href="?view=<?php echo $view; ?>&filter=<?php echo $filter; ?>" class="btn btn-secondary">Clear</a>
                <?php endif; ?>
            </form>

            <a href="request-service.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Request New Service
            </a>
        </div>

        <!-- Requests List -->
        <?php if (empty($requests)): ?>
        <div class="empty-state">
            <i class="fas fa-concierge-bell"></i>
            <h3>No service requests found</h3>
            <p style="color: #666; margin-bottom: 25px;">
                <?php
                if ($search) {
                    echo 'Try a different search term.';
                } elseif ($view === 'trash') {
                    echo 'Your trash is empty. Cancelled requests will appear here when you move them to trash.';
                } else {
                    echo 'You haven\'t requested any services yet.';
                }
                ?>
            </p>
            <?php if ($view === 'active'): ?>
            <a href="request-service.php" class="btn btn-primary">Request a Service</a>
            <?php endif; ?>
        </div>
        <?php else: ?>
            <?php foreach ($requests as $request): 
                $statusInfo = $statusColors[$request['status']] ?? ['#e2e3e5', '#383d41', 'question', $request['status']];
                $paymentInfo = $paymentStatusColors[$request['payment_status']] ?? ['#e2e3e5', '#383d41'];
            ?>
            <div class="request-card">
                <div class="request-header">
                    <div>
                        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 10px;">
                            <h3 style="margin: 0;"><?php echo htmlspecialchars($request['service_name']); ?></h3>
                            <span class="status-badge" style="background-color: <?php echo $statusInfo[0]; ?>; color: <?php echo $statusInfo[1]; ?>">
                                <i class="fas fa-<?php echo $statusInfo[2]; ?>"></i>
                                <?php echo $statusInfo[3]; ?>
                            </span>
                        </div>
                        <p style="margin: 0; color: #666; font-size: 14px;">
                            <i class="fas fa-hashtag"></i> Ref: <?php echo htmlspecialchars($request['request_ref']); ?>
                            <span style="margin: 0 10px;">|</span>
                            <i class="fas fa-calendar"></i> <?php echo formatDate($request['requested_at'], 'M d, Y h:i A'); ?>
                        </p>
                    </div>
                    <div style="text-align: right;">
                        <div class="price-tag"><?php echo formatPrice($request['total_price']); ?></div>
                        <span style="font-size: 12px; color: #666;">
                            <?php echo $request['quantity']; ?> x <?php echo formatPrice($request['unit_price']); ?>
                        </span>
                    </div>
                </div>

                <?php if ($request['special_instructions']): ?>
                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                    <div style="font-size: 12px; color: #666; text-transform: uppercase; margin-bottom: 5px;">Special Instructions</div>
                    <div style="color: #333;"><?php echo htmlspecialchars($request['special_instructions']); ?></div>
                </div>
                <?php endif; ?>

                <div class="request-details">
                    <div class="detail-item">
                        <span class="detail-label">Category</span>
                        <span class="detail-value">
                            <i class="fas fa-<?php echo $request['category'] === 'laundry' ? 'tshirt' : ($request['category'] === 'spa' ? 'spa' : 'om'); ?>"></i>
                            <?php echo htmlspecialchars($request['subcategory'] ?? ucfirst($request['category'])); ?>
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Room Number</span>
                        <span class="detail-value">
                            <i class="fas fa-door-open"></i> Room <?php echo htmlspecialchars($request['room_number']); ?>
                        </span>
                    </div>
                    <?php if ($request['duration_minutes']): ?>
                    <div class="detail-item">
                        <span class="detail-label">Duration</span>
                        <span class="detail-value">
                            <i class="fas fa-clock"></i> <?php echo $request['duration_minutes']; ?> minutes
                        </span>
                    </div>
                    <?php endif; ?>
                    <?php if ($request['preferred_date']): ?>
                    <div class="detail-item">
                        <span class="detail-label">Scheduled For</span>
                        <span class="detail-value">
                            <i class="fas fa-calendar-check"></i> 
                            <?php echo formatDate($request['preferred_date']); ?>
                            <?php if ($request['preferred_time']): ?>
                                at <?php echo date('h:i A', strtotime($request['preferred_time'])); ?>
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    <div class="detail-item">
                        <span class="detail-label">Payment Status</span>
                        <span class="detail-value">
                            <span style="padding: 4px 12px; border-radius: 15px; font-size: 12px; background-color: <?php echo $paymentInfo[0]; ?>; color: <?php echo $paymentInfo[1]; ?>">
                                <?php echo ucwords(str_replace('_', ' ', $request['payment_status'])); ?>
                            </span>
                        </span>
                    </div>
                    <?php if ($request['booking_ref']): ?>
                    <div class="detail-item">
                        <span class="detail-label">Associated Booking</span>
                        <span class="detail-value">
                            <i class="fas fa-book"></i> <?php echo htmlspecialchars($request['booking_ref']); ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if ($view === 'active' && $request['status'] === 'pending'): ?>
                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; text-align: right;">
                    <button type="button"
                       onclick="openDeleteModal(null, 'Cancel Request', 'Are you sure you want to cancel this request?', 'my-service-requests.php?cancel=<?php echo $request['request_id']; ?>', 'cancel')"
                       class="btn btn-danger" style="font-size: 13px;">
                        <i class="fas fa-times"></i> Cancel Request
                    </button>
                </div>
                <?php endif; ?>

                <?php if ($view === 'active' && in_array($request['status'], ['cancelled', 'declined'])): ?>
                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; text-align: right;">
                    <button type="button"
                       onclick="openDeleteModal(null, 'Move to Trash', 'Are you sure you want to move this request to trash? It will no longer count toward your total requests.', 'my-service-requests.php?trash=<?php echo $request['request_id']; ?>', 'trash')"
                       class="btn btn-secondary" style="font-size: 13px; background: #6c757d; color: white;">
                        <i class="fas fa-trash"></i> Move to Trash
                    </button>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

