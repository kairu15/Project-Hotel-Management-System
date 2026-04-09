<?php
/**
 * Pending Requests - Bayawan Bai Hotel
 * Shows reservations awaiting approval or action
 */

$pageTitle = 'Pending Requests';
require_once '../includes/config.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

require_once '../includes/user-header.php';

$db = getDB();
$userId = getUserId();

// Handle cancellation action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $type = $_POST['type'] ?? 'room';
    
    if ($id > 0 && $action === 'cancel') {
        $table = ($type === 'event') ? 'event_bookings' : 'bookings';
        $idColumn = ($type === 'event') ? 'event_booking_id' : 'booking_id';
        
        $verifyStmt = $db->prepare("SELECT * FROM {$table} WHERE {$idColumn} = ? AND user_id = ? AND status = 'pending'");
        $verifyStmt->execute([$id, $userId]);
        $item = $verifyStmt->fetch();
        
        if ($item) {
            try {
                $db->beginTransaction();
                
                // Update status to cancelled
                $cancelStmt = $db->prepare("UPDATE {$table} SET status = 'cancelled', payment_status = 'refunded', updated_at = NOW() WHERE {$idColumn} = ?");
                $cancelStmt->execute([$id]);
                
                // If room booking, free up the room
                if ($type === 'room' && $item['room_id']) {
                    $roomStmt = $db->prepare("UPDATE rooms SET status = 'available' WHERE room_id = ?");
                    $roomStmt->execute([$item['room_id']]);
                }
                
                // Log the cancellation
                $logTable = ($type === 'event') ? 'event_booking_logs' : 'booking_logs';
                $logIdColumn = ($type === 'event') ? 'event_booking_id' : 'booking_id';
                $logStmt = $db->prepare("INSERT INTO {$logTable} ({$logIdColumn}, action, details, created_by) VALUES (?, 'cancelled', 'Cancelled by user from pending list', ?)");
                $logStmt->execute([$id, $userId]);
                
                $db->commit();
                showAlert('Reservation cancelled successfully. Refund will be processed within 5-7 business days.', 'success');
            } catch (Exception $e) {
                $db->rollBack();
                showAlert('Failed to cancel reservation. Please try again.', 'danger');
            }
        }
        redirect('pending.php');
    }
}

// Get pending reservations only
$allReservations = [];

$roomStmt = $db->prepare("
    SELECT 
        b.booking_id as id,
        'room' as type,
        b.status,
        b.check_in,
        b.check_out,
        b.total_amount,
        b.created_at,
        b.updated_at,
        rc.category_name as item_name,
        r.room_number,
        b.adults,
        b.children,
        b.nights,
        b.payment_status,
        b.special_requests
    FROM bookings b
    JOIN room_categories rc ON b.category_id = rc.category_id
    LEFT JOIN rooms r ON b.room_id = r.room_id
    WHERE b.user_id = ? 
    AND b.status = 'pending'
    AND b.is_archived = 0 
    AND b.is_deleted = 0
    ORDER BY b.created_at DESC
");
$roomStmt->execute([$userId]);
$roomBookings = $roomStmt->fetchAll();

$eventStmt = $db->prepare("
    SELECT 
        eb.event_booking_id as id,
        'event' as type,
        eb.status,
        eb.event_date as check_in,
        eb.event_date as check_out,
        eb.created_at,
        eb.updated_at,
        eb.event_type as item_name,
        eb.event_type,
        eb.guests_count,
        eb.start_time,
        eb.end_time,
        eb.payment_status,
        eb.special_requests,
        eb.quoted_price as total_amount
    FROM event_bookings eb
    WHERE eb.user_id = ?
    AND eb.status = 'pending'
    AND eb.is_archived = 0 
    AND eb.is_deleted = 0
    ORDER BY eb.created_at DESC
");
$eventStmt->execute([$userId]);
$eventBookings = $eventStmt->fetchAll();

$allReservations = array_merge($roomBookings, $eventBookings);

// Get status counts for all categories
$statusStmt = $db->prepare("
    SELECT
        (SELECT COUNT(*) FROM bookings WHERE user_id = ? AND is_archived = 0 AND is_deleted = 0) +
        (SELECT COUNT(*) FROM event_bookings WHERE user_id = ? AND is_archived = 0 AND is_deleted = 0) as all_count,
        (SELECT COUNT(*) FROM bookings WHERE user_id = ? AND status = 'confirmed' AND is_archived = 0 AND is_deleted = 0) +
        (SELECT COUNT(*) FROM event_bookings WHERE user_id = ? AND status = 'confirmed' AND is_archived = 0 AND is_deleted = 0) as confirmed,
        (SELECT COUNT(*) FROM bookings WHERE user_id = ? AND status = 'checked_in' AND is_archived = 0 AND is_deleted = 0) +
        (SELECT COUNT(*) FROM event_bookings WHERE user_id = ? AND status = 'checked_in' AND is_archived = 0 AND is_deleted = 0) as checked_in,
        (SELECT COUNT(*) FROM bookings WHERE user_id = ? AND status = 'checked_out' AND is_archived = 0 AND is_deleted = 0) +
        (SELECT COUNT(*) FROM event_bookings WHERE user_id = ? AND status = 'checked_out' AND is_archived = 0 AND is_deleted = 0) as checked_out,
        (SELECT COUNT(*) FROM bookings WHERE user_id = ? AND status = 'cancelled' AND is_archived = 0 AND is_deleted = 0) +
        (SELECT COUNT(*) FROM event_bookings WHERE user_id = ? AND status = 'cancelled' AND is_archived = 0 AND is_deleted = 0) as cancelled,
        (SELECT COUNT(*) FROM event_bookings WHERE user_id = ? AND status = 'completed' AND is_archived = 0 AND is_deleted = 0) as completed,
        (SELECT COUNT(*) FROM bookings WHERE user_id = ? AND status = 'pending' AND is_archived = 0 AND is_deleted = 0) +
        (SELECT COUNT(*) FROM event_bookings WHERE user_id = ? AND status = 'pending' AND is_archived = 0 AND is_deleted = 0) as pending
");
$statusStmt->execute([$userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId]);
$statusCounts = $statusStmt->fetch();

function getTypeIcon($type) {
    return $type === 'room' 
        ? '<i class="fas fa-bed" title="Room Booking"></i>' 
        : '<i class="fas fa-calendar-week" title="Event Booking"></i>';
}

function getPaymentStatusBadge($status) {
    $badges = [
        'pending' => '<span style="color: #ffc107; font-size: 12px;"><i class="fas fa-hourglass-half"></i> Payment Pending</span>',
        'completed' => '<span style="color: #28a745; font-size: 12px;"><i class="fas fa-check"></i> Paid</span>',
        'partial' => '<span style="color: #17a2b8; font-size: 12px;"><i class="fas fa-adjust"></i> Partially Paid</span>'
    ];
    return $badges[$status] ?? '';
}
?>

<style>
    .inbox-container {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        overflow: hidden;
    }
    
    .inbox-header {
        padding: 25px 30px;
        border-bottom: 1px solid #e9ecef;
        background: linear-gradient(135deg, #fff3cd 0%, #ffeeba 100%);
    }
    
    .inbox-header h2 {
        margin: 0;
        font-size: 24px;
        color: #856404;
    }
    
    .inbox-header h2 i {
        margin-right: 10px;
    }
    
    .inbox-filters {
        padding: 15px 30px;
        background: #f8f9fa;
        border-bottom: 1px solid #e9ecef;
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    
    .filter-btn {
        padding: 8px 16px;
        border-radius: 20px;
        border: 1px solid #dee2e6;
        background: white;
        color: #495057;
        font-size: 13px;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: all 0.3s;
    }
    
    .filter-btn:hover {
        background: #f8f9fa;
        border-color: #adb5bd;
    }
    
    .filter-btn.active {
        background: #ffc107;
        color: #333;
        border-color: #ffc107;
    }
    
    .inbox-list {
        padding: 0;
    }
    
    .inbox-item {
        display: flex;
        align-items: center;
        padding: 20px 30px;
        border-bottom: 1px solid #e9ecef;
        transition: background-color 0.2s;
        gap: 20px;
    }
    
    .inbox-item:hover {
        background-color: #f8f9fa;
    }
    
    .item-icon {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }
    
    .item-icon.room {
        background: linear-gradient(135deg, #fff3cd, #ffeeba);
        color: #856404;
    }
    
    .item-icon.event {
        background: linear-gradient(135deg, #f3e5f5, #e1bee7);
        color: #7b1fa2;
    }
    
    .item-content {
        flex: 1;
        min-width: 0;
    }
    
    .item-title-row {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 6px;
        flex-wrap: wrap;
    }
    
    .item-title {
        font-weight: 600;
        font-size: 16px;
        color: var(--dark-color);
        margin: 0;
    }
    
    .status-badge {
        padding: 4px 12px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: #fff3cd;
        color: #856404;
    }
    
    .item-meta {
        display: flex;
        align-items: center;
        gap: 15px;
        font-size: 13px;
        color: #6c757d;
        flex-wrap: wrap;
    }
    
    .item-meta span {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .item-amount {
        font-size: 18px;
        font-weight: 700;
        color: #856404;
        text-align: right;
        min-width: 120px;
    }
    
    .item-actions {
        display: flex;
        gap: 8px;
    }
    
    .action-btn {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        border: 1px solid #dee2e6;
        background: white;
        color: #6c757d;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
        text-decoration: none;
    }
    
    .action-btn:hover {
        background: #f8f9fa;
        border-color: #adb5bd;
    }
    
    .action-btn.cancel-btn:hover {
        background: #f8d7da;
        color: #dc3545;
        border-color: #dc3545;
    }
    
    .empty-state {
        text-align: center;
        padding: 80px 30px;
        color: #6c757d;
    }
    
    .empty-state i {
        font-size: 64px;
        margin-bottom: 20px;
        color: #fff3cd;
    }
    
    .submitted-date {
        font-size: 12px;
        color: #856404;
    }
    
    .notice-box {
        background: #fff3cd;
        border: 1px solid #ffeeba;
        border-radius: 8px;
        padding: 15px 20px;
        margin: 20px 30px;
        display: flex;
        align-items: center;
        gap: 10px;
        color: #856404;
    }
    
    @media (max-width: 768px) {
        .inbox-item {
            flex-wrap: wrap;
            padding: 15px 20px;
        }
        
        .item-amount {
            width: 100%;
            text-align: left;
            margin-top: 10px;
        }
        
        .item-actions {
            width: 100%;
            justify-content: flex-end;
            margin-top: 10px;
        }
        
        .notice-box {
            margin: 15px 20px;
        }
    }
</style>

<div class="inbox-container">
    <div class="inbox-header">
        <h2><i class="fas fa-clock"></i> Pending Requests</h2>
    </div>
    
    <div class="inbox-filters">
        <a href="inbox.php" class="filter-btn"><i class="fas fa-inbox"></i> All <span class="count"><?php echo $statusCounts['all_count']; ?></span></a>
        <a href="confirmed.php" class="filter-btn"><i class="fas fa-check-circle"></i> Confirmed <span class="count"><?php echo $statusCounts['confirmed']; ?></span></a>
        <a href="checkin.php" class="filter-btn"><i class="fas fa-door-open"></i> Check-In <span class="count"><?php echo $statusCounts['checked_in']; ?></span></a>
        <a href="checkout.php" class="filter-btn"><i class="fas fa-door-closed"></i> Check-Out <span class="count"><?php echo $statusCounts['checked_out']; ?></span></a>
        <a href="cancelled.php" class="filter-btn"><i class="fas fa-times-circle"></i> Cancelled <span class="count"><?php echo $statusCounts['cancelled']; ?></span></a>
        <a href="completed.php" class="filter-btn"><i class="fas fa-check-double"></i> Completed <span class="count"><?php echo $statusCounts['completed']; ?></span></a>
        <a href="pending.php" class="filter-btn active"><i class="fas fa-clock"></i> Pending <span class="count"><?php echo $statusCounts['pending']; ?></span></a>
    </div>
    
    <?php if (count($allReservations) > 0): ?>
    <div class="notice-box">
        <i class="fas fa-info-circle"></i>
        <span>These reservations are awaiting confirmation. You can cancel them if needed.</span>
    </div>
    
    <div class="inbox-list">
        <?php foreach ($allReservations as $reservation): 
            $daysSinceCreated = floor((time() - strtotime($reservation['created_at'])) / 86400);
        ?>
        <div class="inbox-item">
            <div class="item-icon <?php echo $reservation['type']; ?>">
                <?php echo getTypeIcon($reservation['type']); ?>
            </div>
            
            <div class="item-content">
                <div class="item-title-row">
                    <h4 class="item-title"><?php echo htmlspecialchars($reservation['item_name']); ?></h4>
                    <span class="status-badge">
                        <i class="fas fa-clock"></i> Pending
                    </span>
                </div>
                <div class="item-meta">
                    <span><i class="fas fa-calendar"></i> 
                        <?php echo formatDate($reservation['check_in']); ?> - 
                        <?php echo formatDate($reservation['check_out']); ?>
                    </span>
                    <span class="submitted-date">
                        <i class="fas fa-paper-plane"></i> Submitted <?php echo $daysSinceCreated == 0 ? 'today' : $daysSinceCreated . ' day(s) ago'; ?>
                    </span>
                    <?php echo getPaymentStatusBadge($reservation['payment_status']); ?>
                </div>
            </div>
            
            <div class="item-amount">
                <?php echo formatPrice($reservation['total_amount']); ?>
            </div>
            
            <div class="item-actions">
                <?php if ($reservation['type'] === 'room'): ?>
                    <a href="booking-details.php?id=<?php echo $reservation['id']; ?>" class="action-btn" title="View Details">
                        <i class="fas fa-eye"></i>
                    </a>
                <?php else: ?>
                    <a href="event-booking-details.php?id=<?php echo $reservation['id']; ?>" class="action-btn" title="View Details">
                        <i class="fas fa-eye"></i>
                    </a>
                <?php endif; ?>
                
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <i class="fas fa-clock"></i>
        <h3>No pending requests</h3>
        <p>You don't have any reservations awaiting confirmation.</p>
        <a href="inbox.php" class="btn btn-primary">
            <i class="fas fa-inbox"></i> View All Reservations
        </a>
    </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/user-footer.php'; ?>
